<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Frozen_order;
use App\Models\LuckyWheelSetting;
use App\Models\LuckyWheelSpin;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Rank;
use App\Models\Section;
use App\Models\Transaction_history;
use App\Models\User;
use App\Models\User_spin_progress;
use App\Models\Wallet_balance_history;
use App\Services\LuckyWheelRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_sections = Section::get();
        $list_partners = Partner::get();
        $get_banner = Banner::with('banner_images')->where('status', true)->first();
        $user_spin_progress = User_spin_progress::where('user_id', Auth::id())->first();
        $rank = null;
        if (Auth::user()->rank_id) {
            $rank = Rank::find(Auth::user()->rank_id);
        }

        // Kiểm tra xem user đã quay vòng quay may mắn hôm nay chưa
        $has_spun_today = LuckyWheelSpin::hasSpunToday(Auth::id());
        $bonus_spins_remaining = (int) (Auth::user()->lucky_wheel_bonus_spins ?? 0);
        $reward_history = LuckyWheelSpin::query()
            ->where('user_id', Auth::id())
            ->whereIn('reward_status', [
                LuckyWheelSpin::STATUS_PENDING,
                LuckyWheelSpin::STATUS_APPROVED,
                LuckyWheelSpin::STATUS_REJECTED,
                LuckyWheelSpin::STATUS_NO_REWARD,
            ])
            ->latest('id')
            ->limit(6)
            ->get();

        return view('user.home', compact(
            'list_sections',
            'list_partners',
            'get_banner',
            'user_spin_progress',
            'rank',
            'has_spun_today',
            'bonus_spins_remaining',
            'reward_history'
        ));
        // return view('info');
    }

    public function get_10_orders_next()
    {
        $user = Auth::user();
        $current_spin = User_spin_progress::where('user_id', $user->id)->first();
        $get_rank = Rank::where('id', $user->rank_id)->first();
        if (!$current_spin || !$get_rank) {
            $response = [
                'order_next' => "",
                'orders' => "",
                'status' => 404
            ];
            return response()->json($response);
        }
        $total_orders_of_rank = $get_rank->spin_count;
        $remaining_orders = $total_orders_of_rank - $current_spin->current_spin;
        $list_10_orders = "";
        $order_next = $total_orders_of_rank <= $current_spin->current_spin ? $total_orders_of_rank : $current_spin->current_spin;
        if ($remaining_orders <= 10) {
            // Nếu còn ít hơn hoặc bằng 10 đơn hàng, lấy tất cả từ current_spin đến cuối cấp
            $list_10_orders = Order::where('rank_id', $user->rank_id)
                ->orderBy('index', 'desc') // Sắp xếp giảm dần theo index
                ->limit(10) // Lấy 10 đơn hàng cuối cùng
                ->get();
        } else {
            // Nếu còn nhiều hơn 10 đơn hàng, lấy đúng 10 đơn hàng tiếp theo
            $list_10_orders = Order::where('rank_id', $user->rank_id)
                ->where('index', '>', $current_spin->current_spin)
                ->limit(10)
                ->get();
        }
        if (!$list_10_orders) {
            $response = [
                'order_next' => "",
                'orders' => "",
                'status' => 404
            ];
            return response()->json($response);
        }
        $response = [
            'order_next' => $order_next,
            'orders' => $list_10_orders,
            'status' => 200
        ];
        return response()->json($response);
    }
    public function check_frozen_order()
    {
        try {
            $result = DB::transaction(function () {
                $user = User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();
                if (!$user->rank_id) {
                    return [
                        'status' => 500,
                        'message' => __('home.BanChuaCoGianHang')
                    ];
                }

                $check_frozen = Frozen_order::where('user_id', $user->id)
                    ->where('is_frozen', 1)
                    ->with('order')
                    ->join('orders', 'frozen_orders.order_id', '=', 'orders.id')
                    ->orderBy('orders.index', 'asc')
                    ->select('frozen_orders.*')
                    ->lockForUpdate()
                    ->first();

                return $this->processFrozenOrderCheck($user, $check_frozen);
            });

            return $result instanceof \Illuminate\Http\JsonResponse
                ? $result
                : response()->json($result);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => __('home.DaXayRaLoiKhiKiemTraDonHang'),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function processFrozenOrderCheck(User $user, ?Frozen_order $check_frozen)
    {
            if ($check_frozen) {
                if ($check_frozen->custom_price !== null) {
                    $hvo_order_id = $check_frozen->order_id;
                    $high_value_order = Order::find($hvo_order_id);
                    $query_current_spin = User_spin_progress::where('user_id', $user->id)->lockForUpdate()->first();
                    if (!$high_value_order) {
                        return response()->json([
                            'status' => 500,
                            'message' => __('home.KhongTimThayDonHang')
                        ]);
                    }
                    if (!$query_current_spin) {
                        User_spin_progress::create([
                            'user_id' => $user->id,
                            'rank_id' => $user->rank_id
                        ]);
                        return response()->json([
                            'status' => 500,
                            'message' => __('home.KhongTimThayTienTrinhQuay')
                        ]);
                    }
                    if ($query_current_spin->current_spin + 1 == $high_value_order->index) {
                        $query_current_spin->current_spin = $query_current_spin->current_spin + 1;
                        $query_current_spin->save();
                        $check_frozen->spun = true;
                        $check_frozen->processing_started_at ??= now();
                        if (!$check_frozen->status) {
                            $check_frozen->status = 'pending'; // Đảm bảo có status
                            // Tạo record status đầu tiên trong status_orders
                            \App\Services\OrderStatusService::changeStatus(
                                $check_frozen,
                                'pending',
                                'Nhân viên nhận đơn hàng',
                                null // System change
                            );
                        }
                        $check_frozen->save();

                        // Chuyển số dư hiện tại vào số dư đóng băng khi nhận đơn hàng giá trị cao
                        $user->frozen_balance += $user->balance;
                        $user->balance = 0;
                        $user->distribution_today += 1;
                        $user->save();
                        return response()->json([
                            'status' => 200,
                            'is_frozen' => true,
                            'is_high_value_order' => true,
                            // Backward compatibility for older API clients.
                            'is_order_special' => true,
                            'is_new_order' => true,
                            'custom_price' => $check_frozen->custom_price,
                            'order_amount' => $check_frozen->snapshot_order_value,
                            'commission_percentage' => $check_frozen->commission_percentage,
                            'commission_amount' => $check_frozen->snapshot_commission_value,
                            'order_id' => $high_value_order->id,
                            'frozen_id' => $check_frozen->id,
                            'frozen_updated_at' => $check_frozen->updated_at,
                            'message' => __('home.ChucMungBanNhanDuocDonHangGiaTriCao')
                        ]);
                    } else if ($query_current_spin->current_spin <= $high_value_order->index && $check_frozen->spun == true) {
                        return response()->json([
                            'status' => 200,
                            'is_frozen' => true,
                            'is_high_value_order' => true,
                            'is_order_special' => true,
                            'is_new_order' => false,
                            'redirect' => url('/order/' . $check_frozen->getRouteKey()),
                            'message' => __('home.CoDonHangChuaXuLy')
                        ]);
                    } else {
                        $rank = Rank::find($query_current_spin->rank_id);
                        if ($rank->spin_count == $query_current_spin->current_spin) {
                            return response()->json([
                                'status' => 400,
                                'is_frozen' => false,
                                'is_high_value_order' => false,
                                'is_order_special' => false,
                                'is_new_order' => false,
                                'message' => __('home.LuotQuayDaDatDenGioiHanToiDa')
                            ]);
                        }
                        $order = Order::where('index', $query_current_spin->current_spin + 1)->where('rank_id', $query_current_spin->rank_id)->first();
                        if (!$order) {
                            return response()->json([
                                'status' => 500,
                                'message' => __('home.KhongTimThayDonHang')
                            ]);
                        }
                        $query_current_spin->current_spin = $query_current_spin->current_spin + 1;
                        $query_current_spin->save();
                        $new_frozen = Frozen_order::snapshotFromOrder($order, [
                            'user_id' => $user->id,
                            'order_id' => $order->id,
                            'spun' => true,
                            'status' => 'pending' // Trạng thái chờ nhận đơn
                        ]);

                        // Tạo record status đầu tiên trong status_orders
                        \App\Services\OrderStatusService::changeStatus(
                            $new_frozen,
                            'pending',
                            'Nhân viên nhận đơn hàng',
                            null // System change
                        );
                        return response()->json([
                            'status' => 200,
                            'is_frozen' => false,
                            'is_high_value_order' => false,
                            'is_order_special' => false,
                            'is_new_order' => true,
                            'order_amount' => $new_frozen->snapshot_order_value,
                            'commission_percentage' => $new_frozen->commission_percentage,
                            'commission_amount' => $new_frozen->snapshot_commission_value,
                            'order_id' => $order->id,
                            'frozen_id' => $new_frozen->id,
                            'frozen_updated_at' => $new_frozen->updated_at,
                            'message' => 'Đây là đơn hàng bình thường'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 200,
                        'is_frozen' => true,
                        'is_high_value_order' => false,
                        'is_order_special' => false,
                        'is_new_order' => false,
                        'redirect' => url('/order/' . $check_frozen->getRouteKey()),
                        'message' => __('home.CoDonHangChuaXuLy')
                    ]);
                }
            } else {
                $query_current_spin = User_spin_progress::where('user_id', $user->id)->lockForUpdate()->first();
                if (!$query_current_spin) {
                    User_spin_progress::create([
                        'user_id' => $user->id,
                        'rank_id' => $user->rank_id
                    ]);
                    return response()->json([
                        'status' => 500,
                        'message' => __('home.KhongTimThayTienTrinhQuay')
                    ]);
                }
                $rank = Rank::find($query_current_spin->rank_id);
                if ($rank->spin_count == $query_current_spin->current_spin) {
                    return response()->json([
                        'status' => 400,
                        'is_frozen' => false,
                        'is_high_value_order' => false,
                        'is_order_special' => false,
                        'is_new_order' => false,
                        'message' => __('home.LuotQuayDaDatDenGioiHanToiDa')
                    ]);
                }
                $order = Order::where('index', $query_current_spin->current_spin + 1)->where('rank_id', $query_current_spin->rank_id)->first();
                if (!$order) {
                    return response()->json([
                        'status' => 500,
                        'message' => __('home.KhongTimThayDonHang')
                    ]);
                }
                $query_current_spin->current_spin = $query_current_spin->current_spin + 1;
                $query_current_spin->save();
                $new_frozen = Frozen_order::snapshotFromOrder($order, [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'spun' => true,
                    'status' => 'pending' // Trạng thái chờ nhận đơn
                ]);

                // Tạo record status đầu tiên trong status_orders
                \App\Services\OrderStatusService::changeStatus(
                    $new_frozen,
                    'pending',
                    'Nhân viên nhận đơn hàng',
                    null // System change
                );

                $user->distribution_today += 1;
                $user->save();
                return response()->json([
                    'status' => 200,
                    'is_frozen' => false,
                    'is_high_value_order' => false,
                    'is_order_special' => false,
                    'is_new_order' => true,
                    'order_amount' => $new_frozen->snapshot_order_value,
                    'commission_percentage' => $new_frozen->commission_percentage,
                    'commission_amount' => $new_frozen->snapshot_commission_value,
                    'order_id' => $order->id,
                    'frozen_id' => $new_frozen->id,
                    'frozen_updated_at' => $new_frozen->updated_at,
                    'message' => 'Đây là đơn hàng bình thường'
                ]);
            }
    }
    public function distribution()
    {
        $user = Auth::user();
        $section_mo_ta = Section::where('code', 'mo_ta')->first();
        // Hiển thị số dư đóng băng (frozen_balance)
        $frozen_price = $user->frozen_balance ?? 0;

        // Lấy rank của user hiện tại
        $user_rank = null;
        $total_orders = 0;
        $current_order = 0;

        if ($user->rank_id) {
            $user_rank = Rank::find($user->rank_id);
            $total_orders = $user_rank->spin_count ?? 0;

            // Lấy tiến trình quay hiện tại
            $spin_progress = User_spin_progress::where('user_id', $user->id)
                ->where('rank_id', $user->rank_id)
                ->first();

            if ($spin_progress) {
                $current_order = $spin_progress->current_spin ?? 0;
            }
        }

        // Tính hoa hồng dự tính hôm nay từ các đơn hàng đã xác nhận trong ngày
        $today_start = \Carbon\Carbon::today();
        $today_end = \Carbon\Carbon::tomorrow();

        $today_confirmed_orders = Frozen_order::where('user_id', $user->id)
            ->whereIn('status', ['confirmed', 'preparing', 'transit', 'shipping', 'delivered'])
            ->where('updated_at', '>=', $today_start)
            ->where('updated_at', '<=', $today_end)
            ->with('order')
            ->get();

        $todays_discount = 0;
        $todays_expected_refund = 0;
        foreach ($today_confirmed_orders as $frozen_order) {
            // Tính tổng giá trị đơn hàng
            $total_price = $frozen_order->snapshot_order_value;

            // Tính hoa hồng dự tính = tổng giá * phần trăm hoa hồng
            $commission = $frozen_order->snapshot_commission_value;
            if ($total_price === null || $commission === null) {
                continue;
            }
            $todays_discount += $commission;
            $todays_expected_refund += bcadd($total_price, $commission, 6);
        }

        // Tính hoa hồng đã được cộng hôm nay từ các đơn hàng đã hoàn thành
        $today_completed_orders = Frozen_order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', \Carbon\Carbon::today())
            ->with('order')
            ->get();

        // Lấy các order_code từ các đơn hàng đã hoàn thành hôm nay
        $completed_order_codes = $today_completed_orders->pluck('display_order_code')->filter()->toArray();

        // Tính tổng hoa hồng đã được cộng từ Transaction_history
        // Lấy hoa hồng từ các đơn hàng đã completed hôm nay (không cần kiểm tra thời gian tạo Transaction_history)
        $today_commission_added = 0;
        if (!empty($completed_order_codes)) {
            $today_commission_added = Transaction_history::where('user_id', $user->id)
                ->where('type', 'profit')
                ->whereIn('note', $completed_order_codes)
                ->sum('value');
        }

        return view('user.distribution', compact('user', 'frozen_price', 'section_mo_ta', 'user_rank', 'total_orders', 'current_order', 'todays_discount', 'todays_expected_refund', 'today_commission_added'));
    }
    public function withdraw_money()
    {
        $user = Auth::user();
        $rank = Rank::find($user->rank_id);
        if (!$rank) {
            return redirect()->back()->with('error', 'Bạn chưa có gian hàng. Vui lòng liên hệ quản trị viên.');
        }
        $has_password = $user->transaction_password ? true : false;

        $maximum_number_of_withdrawals = max(0, $rank->maximum_number_of_withdrawals - $user->count_withdrawals);
        $maximum_withdrawal_amount = $rank->maximum_withdrawal_amount;

        // Lấy thông tin tiến độ hoàn thành đơn hàng
        $user_spin_progress = User_spin_progress::where('user_id', $user->id)
            ->where('rank_id', $rank->id)
            ->first();
        $current_orders = $user_spin_progress ? $user_spin_progress->current_spin : 0;
        $total_orders = $rank->spin_count;

        // Frozen balance là tiền đang bị khóa cho flow đơn hàng, không phải nguồn rút tiền.
        $frozen_balance = (float) ($user->frozen_balance ?? 0);
        $has_frozen_balance = $this->withdrawalLockedByFrozenBalance($user);
        $withdrawable_balance = (float) ($user->balance ?? 0);
        $effective_withdrawal_limit = $has_frozen_balance
            ? 0
            : max(0, min($withdrawable_balance, (float) $maximum_withdrawal_amount));
        $has_processing_withdrawal = Wallet_balance_history::where('user_id', $user->id)
            ->where('status', 'processing')
            ->where('type', 'withdraw')
            ->exists();
        $has_bank_account = filled($user->username_bank) && filled($user->bank_name) && filled($user->account_number);
        $order_progress_ready = $user_spin_progress && $current_orders >= $total_orders;
        $banks = config('banks', []);
        return view('user.withdraw_money', compact(
            'user',
            'maximum_number_of_withdrawals',
            'maximum_withdrawal_amount',
            'has_password',
            'rank',
            'banks',
            'current_orders',
            'total_orders',
            'frozen_balance',
            'has_frozen_balance',
            'withdrawable_balance',
            'effective_withdrawal_limit',
            'has_processing_withdrawal',
            'has_bank_account',
            'order_progress_ready'
        ));
    }

    private function withdrawalLockedByFrozenBalance(User $user): bool
    {
        return (float) ($user->frozen_balance ?? 0) > 0;
    }

    public function handle_withdraw()
    {
        $user = User::find(Auth::user()->id);
        $rank = Rank::find($user->rank_id);
        $spin_progress = User_spin_progress::where('user_id', $user->id)->first();

        if ($user && $this->withdrawalLockedByFrozenBalance($user)) {
            return response()->json([
                'status' => 400,
                'message' => 'Bạn đang có số dư bị đóng băng do còn đơn hàng chưa hoàn tất. Vui lòng hoàn tất các đơn hàng đang xử lý trước khi rút tiền.'
            ]);
        }

        if ($user && $rank && $spin_progress) {
            if ($spin_progress->current_spin < $rank->spin_count) {
                return response()->json([
                    'status' => 400,
                    'message' => "Bạn chưa hoàn thành tất cả các đơn hàng trong gian hàng!"
                ]);
            } else if ($spin_progress->current_spin == $rank->spin_count) {
                $order = Order::where('index', $rank->spin_count)->where('rank_id', $rank->id)->first();
                if ($order) {
                    $frozen_order = Frozen_order::where('order_id', $order->id)->where('user_id', $user->id)->first();
                    if ($frozen_order) {
                        if ($frozen_order->spun) {
                            if ($frozen_order->is_frozen) {
                                return response()->json([
                                    'status' => 400,
                                    'message' => "Bạn chưa hoàn thành tất cả các đơn hàng trong gian hàng!"
                                ]);
                            }
                        } else {
                            return response()->json([
                                'status' => 400,
                                'message' => "Có lỗi xảy ra, vui lòng báo với nhân viên chăm sóc khách hàng, xin cảm ơn!"
                            ]);
                        }
                    } else {
                        return response()->json([
                            'status' => 400,
                            'message' => "Có lỗi xảy ra, vui lòng báo với nhân viên chăm sóc khách hàng, xin cảm ơn!"
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 400,
                        'message' => "Kiểm tra đơn hàng đã quay tới không xác định, vui lòng báo với nhân viên chăm sóc khách hàng, xin cảm ơn!"
                    ]);
                }
            }
            $check = Wallet_balance_history::where('user_id', $user->id)->where('status', 'processing')->where('type', 'withdraw')->first();
            if ($check) {
                return response()->json([
                    'status' => 400,
                    'message' => __('home.DangCoMotDonRutTienChuaHoanThanh')
                ]);
            }
            if ($user->count_withdrawals >= $rank->maximum_number_of_withdrawals) {
                return response()->json([
                    'status' => 400,
                    'message' => __('home.SoLanRutDaDatToiDaTrongNgay')
                ]);
            }
            $amount = floatval(request()->input('amount'));

            if ($amount <= 0) {
                return response()->json([
                    'status' => 400,
                    'message' => __('withdraw_money.VuiLongNhapSoTienRut')
                ]);
            }

            if ($user->balance < $amount) {
                return response()->json([
                    'status' => 400,
                    'message' => __('home.SoDuKhongDu')
                ]);
            }
            $user->balance -= $amount;
            if ($amount > $rank->maximum_withdrawal_amount) {
                return response()->json([
                    'status' => 400,
                    'message' => __('home.SoTienRutVuotQuaGioiHanQuyDinh')
                ]);
            }
            $username_bank = request()->input('username_bank');
            $bank_name = request()->input('bank_name');
            $account_number = request()->input('account_number');
            if ($user->account_number !== $account_number || $user->bank_name !== $bank_name) {
                return back()->with('warning', 'Mỗi tài khoản chỉ được liên kết với một ngân hàng! Liên hệ CSKH nếu cần thay đổi!');
            }
            $transaction_password = request()->input('transaction_password');
            $confirm_transaction_password = request()->input('confirm_transaction_password');
            if (!$user->transaction_password) {
                // Kiểm tra đã nhập đầy đủ chưa
                if (!$transaction_password || !$confirm_transaction_password) {
                    return response()->json([
                        'status' => 400,
                        'message' => __('home.VuiLongNhapDayDuThongTin')
                    ]);
                }

                // Kiểm tra hai mật khẩu có khớp không
                if ($transaction_password !== $confirm_transaction_password) {
                    return response()->json([
                        'status' => 400,
                        'message' => __('home.MatKhauXacNhanKhongKhop')
                    ]);
                }

                // Lưu mật khẩu giao dịch
                $user->transaction_password = password_hash($transaction_password, PASSWORD_DEFAULT);
                $user->save();
            } else {
                if (!password_verify($transaction_password, $user->transaction_password)) {
                    return response()->json([
                        'status' => 400,
                        'message' => __('home.MatKhauGiaoDichKhongChinhXac')
                    ]);
                }
            }
            $initial_balance = $user->balance;
            $user->username_bank = $username_bank;
            $user->bank_name = $bank_name;
            $user->account_number = $account_number;
            $user->count_withdrawals += 1;
            $user->save();
            Wallet_balance_history::create([
                'user_id' => $user->id,
                'value' => $amount,
                'initial_balance' => $initial_balance,
                'type' => "withdraw",
                'username_bank' => $username_bank,
                'bank_name' => $bank_name,
                'account_number' => $account_number,
            ]);
            return response()->json([
                'status' => 200,
                'message' => __('home.TaoDonRutTienThanhCong')
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => "Có lỗi xảy ra, vui lòng báo với nhân viên chăm sóc khách hàng!"
            ]);
        }
    }
    /**
     * Endpoint cũ: frozen balance là tiền đang bị khóa và không được phép rút/chuyển về balance thủ công.
     */
    public function handle_withdraw_frozen()
    {
        return response()->json([
            'status' => 400,
            'message' => 'Số dư đóng băng là tiền đang bị khóa cho quá trình xử lý đơn hàng và không thể rút. Vui lòng hoàn tất các đơn hàng đang xử lý trước.'
        ], 400);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function bank_link()
    {
        $username_bank = request()->input('username_bank');
        $bank_name = request()->input('bank_name');
        $account_number = request()->input('account_number');
        $transaction_password = request()->input('transaction_password');

        if (!$username_bank || !$bank_name || !$account_number || !$transaction_password) {
            return response()->json([
                'status' => 400,
                'message' => "Dữ liệu không hợp lệ, vui lòng thử lại!"
            ]);
        }

        $result = DB::transaction(function () use ($username_bank, $bank_name, $account_number, $transaction_password) {
            $user = User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();

            if (filled($user->username_bank) && filled($user->bank_name) && filled($user->account_number)) {
                return [
                    'status' => 409,
                    'message' => 'Tài khoản ngân hàng đã được liên kết và không thể chỉnh sửa.'
                ];
            }

            $user->username_bank = $username_bank;
            $user->bank_name = $bank_name;
            $user->account_number = $account_number;
            $user->transaction_password = password_hash($transaction_password, PASSWORD_DEFAULT);
            $user->save();

            return [
                'status' => 200,
                'message' => "Liên kết ngân hàng thành công!"
            ];
        });

        return response()->json($result, $result['status'] === 409 ? 409 : 200);
    }

    /**
     * Xử lý quay vòng quay may mắn
     */
    public function spinLuckyWheel(LuckyWheelRewardService $rewardService)
    {
        try {
            $result = DB::transaction(function () use ($rewardService) {
                $user = User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();
                $userId = $user->id;
                $spinType = LuckyWheelSpin::TYPE_DAILY_COMPLETION;
                $bonusSpinsRemaining = (int) $user->lucky_wheel_bonus_spins;

                // Lượt admin cấp luôn được ưu tiên và không phụ thuộc tiến trình đơn hàng / giới hạn 1 lần mỗi ngày.
                if ($bonusSpinsRemaining > 0) {
                    $user->lucky_wheel_bonus_spins = (int) $user->lucky_wheel_bonus_spins - 1;
                    $user->save();
                    $spinType = LuckyWheelSpin::TYPE_ADMIN_BONUS;
                    $bonusSpinsRemaining = (int) $user->lucky_wheel_bonus_spins;
                } else {
                    // Hết lượt admin cấp thì quay lại đúng logic hằng ngày hiện có.
                    if (LuckyWheelSpin::hasSpunToday($userId)) {
                        return [
                            'success' => false,
                            'message' => 'Bạn đã quay vòng quay hôm nay rồi. Hãy quay lại vào ngày mai!',
                        ];
                    }

                    if (!$user->rank_id) {
                        return [
                            'success' => false,
                            'message' => 'Bạn cần có cấp độ để tham gia quay thưởng!',
                        ];
                    }

                    $rank = Rank::find($user->rank_id);
                    $userSpinProgress = User_spin_progress::where('user_id', $userId)->first();

                    if (!$rank || !$userSpinProgress) {
                        return [
                            'success' => false,
                            'message' => 'Bạn chưa có tiến trình phân phối!',
                        ];
                    }

                    $current = $userSpinProgress->current_spin ?? 0;
                    $total = $rank->spin_count ?? 0;

                    if ($current < $total) {
                        return [
                            'success' => false,
                            'message' => 'Bạn cần hoàn thành ' . ($total - $current) . ' đơn hàng nữa để quay!',
                        ];
                    }
                }

                // Kết quả quay phải được quyết định ở server. Frontend chỉ dùng prize_index để chạy animation.
                $prize = LuckyWheelSpin::drawPrize();
                $spin = LuckyWheelSpin::recordSpin($userId, $prize, $spinType);

                if (
                    $spin->reward_type === LuckyWheelSpin::REWARD_CASH
                    && LuckyWheelSetting::current()->auto_approve_rewards
                ) {
                    $spin = $rewardService->approve(
                        $spin,
                        null,
                        LuckyWheelSpin::APPROVAL_AUTOMATIC
                    );
                }

                $isReward = $spin->reward_type !== LuckyWheelSpin::REWARD_NONE;
                $rewardMessage = match ($spin->reward_status) {
                    LuckyWheelSpin::STATUS_APPROVED => 'Tiền thưởng đã được cộng vào số dư của bạn.',
                    LuckyWheelSpin::STATUS_PENDING => 'Phần thưởng đã được ghi nhận và đang chờ quản trị viên duyệt.',
                    default => 'Chưa trúng thưởng ở lượt này. Chúc bạn may mắn ở lượt tiếp theo!',
                };

                return [
                    'success' => true,
                    'message' => $isReward
                        ? 'Chúc mừng bạn đã trúng ' . $spin->prize . '!'
                        : $rewardMessage,
                    'prize' => $spin->prize,
                    'prize_index' => (int) $spin->prize_index,
                    'reward_id' => (int) $spin->id,
                    'reward_type' => $spin->reward_type,
                    'reward_amount' => $spin->reward_amount !== null ? (float) $spin->reward_amount : null,
                    'reward_status' => $spin->reward_status,
                    'reward_status_label' => $spin->rewardStatusLabel(),
                    'reward_message' => $rewardMessage,
                    'approval_method' => $spin->approval_method,
                    'bonus_spins_remaining' => $bonusSpinsRemaining,
                ];
            });

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Lucky wheel spin error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại!'
            ], 500);
        }
    }

}
