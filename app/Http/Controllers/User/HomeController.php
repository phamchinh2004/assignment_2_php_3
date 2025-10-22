<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Frozen_order;
use App\Models\LuckyWheelSpin;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Rank;
use App\Models\Section;
use App\Models\User;
use App\Models\User_spin_progress;
use App\Models\Wallet_balance_history;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_ranks = Rank::get();
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

        // Tính toán số liệu thành viên hợp lý cho từng gian hàng
        $list_ranks_with_member_count = $this->calculateMemberCounts($list_ranks);

        return view('user.home', compact('list_ranks_with_member_count', 'list_sections', 'list_partners', 'get_banner', 'user_spin_progress', 'rank', 'has_spun_today'));
    }

    /**
     * Tính toán số lượng thành viên hợp lý cho từng gian hàng
     */
    private function calculateMemberCounts($ranks)
    {
        $ranks_with_count = collect();
        
        foreach ($ranks as $index => $rank) {
            // Số liệu thực từ database
            $real_member_count = User::where('rank_id', $rank->id)->count();
            
            // Số liệu ảo dựa trên logic phân cấp
            // Gian hàng cấp thấp (dễ nâng cấp) có nhiều thành viên hơn
            // Gian hàng cấp cao (khó nâng cấp) có ít thành viên hơn
            $virtual_member_count = $this->getVirtualMemberCount($index, count($ranks));
            
            // Tổng số thành viên = số thực + số ảo
            $total_member_count = $real_member_count + $virtual_member_count;
            
            // Làm tròn số liệu và thêm dấu "+" nếu cần
            $formatted_count = $this->formatMemberCount($total_member_count, $real_member_count);
            
            // Thêm thuộc tính user_count vào rank
            $rank->user_count = $formatted_count;
            $ranks_with_count->push($rank);
        }
        
        return $ranks_with_count;
    }

    /**
     * Format số liệu thành viên với dấu "+" và làm tròn
     */
    private function formatMemberCount($total_count, $real_count)
    {
        // Nếu có thành viên thực tế, hiển thị số chính xác (không làm tròn)
        if ($real_count > 0) {
            return number_format($total_count);
        }
        
        // Nếu chưa có thành viên thực tế, làm tròn và thêm dấu "+"
        $rounded_count = $this->roundToNearest($total_count);
        return number_format($rounded_count) . '+';
    }

    /**
     * Làm tròn số về các mốc đẹp nhưng giữ nguyên số liệu thực tế
     */
    private function roundToNearest($number)
    {
        if ($number >= 100000) {
            return round($number / 1000) * 1000;
        } elseif ($number >= 10000) {
            return round($number / 100) * 100;
        } elseif ($number >= 1000) {
            return round($number / 50) * 50;
        } elseif ($number >= 100) {
            return round($number / 10) * 10;
        } else {
            return round($number);
        }
    }

    /**
     * Tính số liệu ảo dựa trên vị trí gian hàng
     */
    private function getVirtualMemberCount($index, $total_ranks)
    {
        // Logic phân phối số liệu ảo với số tròn:
        // - Gian hàng đầu tiên (index 0): nhiều thành viên nhất
        // - Gian hàng cuối cùng: ít thành viên nhất
        // - Giảm dần theo cấp độ
        
        $base_members = [
            0 => 12000,  // Gian hàng cấp 1: 12,000 thành viên
            1 => 21000,   // Gian hàng cấp 2: 6,000 thành viên  
            2 => 14000,   // Gian hàng cấp 3: 3,000 thành viên
            3 => 5500,   // Gian hàng cấp 4: 1,500 thành viên
        ];
        
        // Nếu có nhiều hơn 4 gian hàng, tính toán động
        if ($index >= 4) {
            // Công thức giảm dần: 1500 * (0.5 ^ (index - 3))
            $virtual_count = 1500 * pow(0.5, $index - 3);
            return max(100, round($virtual_count)); // Tối thiểu 100 thành viên
        }
        
        return $base_members[$index] ?? 100;
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
            $user = Auth::user();
            if (!$user->rank_id) {
                return response()->json([
                    'status' => 500,
                    'message' => __('home.BanChuaCoGianHang')
                ]);
            }
            $check_frozen = Frozen_order::join('orders', 'frozen_orders.order_id', '=', 'orders.id')
                ->where('frozen_orders.user_id', Auth::id())
                ->where('frozen_orders.is_frozen', 1)
                ->orderBy('orders.index', 'asc')
                ->select('frozen_orders.*')
                ->first();
            // dd($check_frozen);
            if ($check_frozen) {
                if ($check_frozen->custom_price) {
                    $order_special_id = $check_frozen->order_id;
                    $get_order_special = Order::find($order_special_id);
                    $query_current_spin = User_spin_progress::where('user_id', Auth::user()->id)->first();
                    if (!$get_order_special) {
                        return response()->json([
                            'status' => 500,
                            'message' => __('home.KhongTimThayDonHang')
                        ]);
                    }
                    if (!$query_current_spin) {
                        User_spin_progress::create([
                            'user_id' => Auth::user()->id,
                            'rank_id' => Auth::user()->rank_id
                        ]);
                        return response()->json([
                            'status' => 500,
                            'message' => __('home.KhongTimThayTienTrinhQuay')
                        ]);
                    }
                    if ($query_current_spin->current_spin + 1 == $get_order_special->index) {
                        $query_current_spin->current_spin = $query_current_spin->current_spin + 1;
                        $query_current_spin->save();
                        $check_frozen->spun = true;
                        $check_frozen->save();
                        return response()->json([
                            'status' => 200,
                            'is_frozen' => true,
                            'is_order_special' => true,
                            'is_new_order' => true,
                            'custom_price' => $check_frozen->custom_price,
                            'order_id' => $get_order_special->id,
                            'frozen_id' => $check_frozen->id,
                            'message' => __('home.ChucMungBanNhanDuocDonHangDacBiet')
                        ]);
                    } else if ($query_current_spin->current_spin <= $get_order_special->index && $check_frozen->spun == true) {
                        return response()->json([
                            'status' => 200,
                            'is_frozen' => true,
                            'is_order_special' => true,
                            'is_new_order' => false,
                            'message' => __('home.CoDonHangDangBiDongBang')
                        ]);
                    } else {
                        $rank = Rank::find($query_current_spin->rank_id);
                        if ($rank->spin_count == $query_current_spin->current_spin) {
                            return response()->json([
                                'status' => 400,
                                'is_frozen' => false,
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
                        $new_frozen = Frozen_order::create([
                            'user_id' => Auth::user()->id,
                            'order_id' => $order->id,
                            'spun' => true
                        ]);
                        return response()->json([
                            'status' => 200,
                            'is_frozen' => false,
                            'is_order_special' => false,
                            'is_new_order' => true,
                            'order_id' => $order->id,
                            'frozen_id' => $new_frozen->id,
                            'message' => 'Đây là đơn hàng bình thường'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 200,
                        'is_frozen' => true,
                        'is_order_special' => false,
                        'is_new_order' => false,
                        'message' => __('home.CoDonHangChuaXuLy')
                    ]);
                }
            } else {
                $query_current_spin = User_spin_progress::where('user_id', Auth::user()->id)->first();
                if (!$query_current_spin) {
                    User_spin_progress::create([
                        'user_id' => Auth::user()->id,
                        'rank_id' => Auth::user()->rank_id
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
                $new_frozen = Frozen_order::create([
                    'user_id' => Auth::user()->id,
                    'order_id' => $order->id,
                    'spun' => true
                ]);
                $user = User::find(Auth::user()->id);
                $user->distribution_today += 1;
                $user->save();
                return response()->json([
                    'status' => 200,
                    'is_frozen' => false,
                    'is_order_special' => false,
                    'is_new_order' => true,
                    'order_id' => $order->id,
                    'frozen_id' => $new_frozen->id,
                    'message' => 'Đây là đơn hàng bình thường'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => __('home.DaXayRaLoiKhiKiemTraDonHang'),
                'error' => $e->getMessage()
            ]);
        }
    }
    public function distribution()
    {
        $user = Auth::user();
        $section_mo_ta = Section::where('code', 'mo_ta')->first();
        $frozen_price = null;
        $frozen_order = Frozen_order::where('user_id', $user->id)->where('custom_price', '!=', null)->where('is_frozen', true)->where('spun', true)->first();
        if ($frozen_order) {
            $frozen_price = $frozen_order->custom_price;
        }
        
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
        
        return view('user.distribution', compact('user', 'frozen_price', 'section_mo_ta', 'user_rank', 'total_orders', 'current_order'));
    }
    public function withdraw_money()
    {
        $user = Auth::user();
        $rank = Rank::find($user->rank_id);
        if (!$rank) {
            return redirect()->back()->with('error', 'Bạn chưa được gán cấp bậc. Vui lòng liên hệ quản trị viên.');
        }
        $has_password = $user->transaction_password ? true : false;

        $maximum_number_of_withdrawals = $rank->maximum_number_of_withdrawals - $user->count_withdrawals;
        $maximum_withdrawal_amount = $rank->maximum_withdrawal_amount;
        
        // Lấy thông tin tiến độ hoàn thành đơn hàng
        $user_spin_progress = User_spin_progress::where('user_id', $user->id)
            ->where('rank_id', $rank->id)
            ->first();
        $current_orders = $user_spin_progress ? $user_spin_progress->current_spin : 0;
        $total_orders = $rank->spin_count;
        $banks = [
            'Ngân hàng Việt Nam' => [
                'VPBank',
                'BIDV',
                'Vietcombank',
                'VietinBank',
                'MBBANK',
                'ACB',
                'SHB',
                'Techcombank',
                'Agribank',
                'Sacombank',
                'HDBank',
                'LienVietPostBank',
                'VIB',
                'SeABank',
                'VBSP',
                'TPBank',
                'OCB',
                'MSB',
                'Eximbank',
                'SCB',
                'VDB',
                'Nam A Bank',
                'ABBANK',
                'PVcomBank',
                'Bac A Bank',
                'UOB',
                'Woori',
                'HSBC',
                'SCBVL',
                'PBVN',
                'SHBVN',
                'NCB',
                'VietABank',
                'BVBank',
                'Vikki Bank',
                'Vietbank',
                'ANZVL',
                'MBV',
                'CIMB',
                'Kienlongbank',
                'IVB',
                'BAOVIET Bank',
                'SAIGONBANK',
                'Co-opBank',
                'GPBank',
                'VRB',
                'VCBNeo',
                'HLBVN',
                'PGBank'
            ],
            'Ngân hàng Nhật Bản' => [
                'MUFG Bank (三菱UFJ銀行)',
                'SMBC (Sumitomo Mitsui Banking Corporation, 三井住友銀行)',
                'Mizuho Bank (みずほ銀行)',
                'Resona Bank (りそな銀行)',
                'Shinsei Bank (新生銀行)',
                'Japan Post Bank (ゆうちょ銀行)',
                'Rakuten Bank (楽天銀行)',
                'PayPay Bank (旧ジャパンネット銀行)',
                'Sony Bank (ソニー銀行)'
            ],
            'Ngân hàng Đài Loan' => [
                'Bank of Taiwan (臺灣銀行)',
                'Taipei Fubon Bank (台北富邦銀行)',
                'CTBC Bank/ChinaTrust (中國信託商業銀行)',
                'Mega International Commercial Bank (兆豐國際商業銀行)',
                'First Commercial Bank (第一商業銀行)',
                'Cathay United Bank (國泰世華銀行)',
                'Taishin International Bank (台新銀行)',
                'Richart Digital Bank (by Taishin Bank)',
                'LINE Bank (by LINE & Union Bank of Taiwan)',
            ],
            'Ngân hàng Hàn Quốc' => [
                'Kookmin Bank (KB국민은행)',
                'Shinhan Bank (신한은행)',
                'Woori Bank (우리은행)',
                'Hana Bank (하나은행)',
                'IBK Industrial Bank (IBK기업은행)',
                'NongHyup Bank (NH농협은행)',
                'KakaoBank (카카오뱅크)',
                'Toss Bank (토스뱅크)',
                'K Bank (케이뱅크)',
            ],
            'Ngân hàng Trung Quốc' => [
                'ICBC (中国工商银行)',
                'Bank of China (中国银行)',
                'China Construction Bank (中国建设银行)',
                'Agricultural Bank of China (中国农业银行)',
                'China Merchants Bank (招商银行)',
            ],
            'Ngân hàng Mỹ' => [
                'JPMorgan Chase Bank',
                'Bank of America',
                'Wells Fargo Bank',
                'Citibank',
                'US Bank',
                'PNC Bank',
                'Capital One Bank',
                'TD Bank',
                'BB&T (Truist Bank)',
                'SunTrust (Truist Bank)',
            ],
            'Ngân hàng Tây Ban Nha' => [
                'Banco Santander',
                'BBVA (Banco Bilbao Vizcaya Argentaria)',
                'CaixaBank',
                'Bankia',
                'Banco Sabadell',
                'Banco Popular Español',
            ],
        ];
        return view('user.withdraw_money', compact('user', 'maximum_number_of_withdrawals', 'maximum_withdrawal_amount', 'has_password', 'rank', 'banks', 'current_orders', 'total_orders'));
    }
    public function handle_withdraw()
    {
        $user = User::find(Auth::user()->id);
        $rank = Rank::find($user->rank_id);
        $spin_progress = User_spin_progress::where('user_id', $user->id)->first();
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
            if ($user->balance < $amount) {
                return response()->json([
                    'status' => 400,
                    'message' => __('home.SoDuKhongDu')
                ]);
            }
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
            // return response()->json([
            //     'status' => 400,
            //     'message' => 'Vui lòng nhập đầy đủ thông tin!',
            //     'data' => [
            //         'username_bank' => $username_bank,
            //         'bank_name' => $bank_name,
            //         'account_number' => $account_number,
            //         'transaction_password' => $transaction_password,
            //         'confirm_transaction_password' => $confirm_transaction_password,
            //     ]
            // ]);
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
            $user->balance -= $amount;
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
     * Show the form for creating a new resource.
     */
    public function bank_link()
    {
        $user = User::find(Auth::user()->id);
        $username_bank = request()->input('username_bank');
        $bank_name = request()->input('bank_name');
        $account_number = request()->input('account_number');
        $transaction_password = request()->input('transaction_password');
        if (!$username_bank || !$bank_name || !$account_number || !$transaction_password) {
            return response()->json([
                'status' => 400,
                'message' => "Dữ liệu không hợp lệ, vui lòng thử lại!"
            ]);
        } else {
            $user->username_bank = $username_bank;
            $user->bank_name = $bank_name;
            $user->account_number = $account_number;
            $user->transaction_password = password_hash($transaction_password, PASSWORD_DEFAULT);
            $user->save();
            return response()->json([
                'status' => 200,
                'message' => "Liên kết ngân hàng thành công!"
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Xử lý quay vòng quay may mắn
     */
    public function spinLuckyWheel(Request $request)
    {
        try {
            $userId = Auth::id();
            
            // Kiểm tra đã quay hôm nay chưa
            if (LuckyWheelSpin::hasSpunToday($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã quay vòng quay hôm nay rồi. Hãy quay lại vào ngày mai!'
                ], 400);
            }
            
            // Kiểm tra điều kiện: phải hoàn thành đủ đơn hàng trong cấp độ
            $user = Auth::user();
            if (!$user->rank_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần có cấp độ để tham gia quay thưởng!'
                ], 400);
            }
            
            $rank = Rank::find($user->rank_id);
            $user_spin_progress = User_spin_progress::where('user_id', $userId)->first();
            
            if (!$user_spin_progress) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn chưa có tiến trình phân phối!'
                ], 400);
            }
            
            $current = $user_spin_progress->current_spin ?? 0;
            $total = $rank->spin_count ?? 0;
            
            if ($current < $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn cần hoàn thành ' . ($total - $current) . ' đơn hàng nữa để được quay!'
                ], 400);
            }
            
            // Lấy phần thưởng từ request
            $prize = $request->input('prize');
            
            // Lưu lịch sử quay
            LuckyWheelSpin::recordSpin($userId, $prize);
            
            return response()->json([
                'success' => true,
                'message' => 'Chúc mừng bạn đã trúng ' . $prize . '!',
                'prize' => $prize
            ]);
            
        } catch (\Exception $e) {
            Log::error('Lucky wheel spin error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra. Vui lòng thử lại!'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
