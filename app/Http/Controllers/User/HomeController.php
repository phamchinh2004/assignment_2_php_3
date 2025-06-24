<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Frozen_order;
use App\Models\Order;
use App\Models\Rank;
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
        return view('user.home', compact('list_ranks'));
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
                            'message' => 'Không tìm thấy đơn hàng!'
                        ]);
                    }
                    if (!$query_current_spin) {
                        return response()->json([
                            'status' => 500,
                            'message' => 'Không tìm thấy tiến trình quay!'
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
                            'message' => 'Chúc mừng! Bạn nhận được đơn hàng đặc biệt!'
                        ]);
                    } else if ($query_current_spin->current_spin <= $get_order_special->index && $check_frozen->spun == true) {
                        return response()->json([
                            'status' => 200,
                            'is_frozen' => true,
                            'is_order_special' => true,
                            'is_new_order' => false,
                            'message' => 'Có đơn hàng đang bị đóng băng, vui lòng truy cập trang lịch sử đơn hàng để xử lý!'
                        ]);
                    } else {
                        $order = Order::where('index', $query_current_spin->current_spin + 1)->where('rank_id', $query_current_spin->rank_id)->first();
                        if (!$order) {
                            return response()->json([
                                'status' => 500,
                                'message' => 'Không tìm thấy đơn hàng!'
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
                        'message' => 'Có đơn hàng chưa xử lý, vui lòng truy cập trang lịch sử đơn hàng để xử lý đơn hàng!'
                    ]);
                }
            } else {
                $query_current_spin = User_spin_progress::where('user_id', Auth::user()->id)->first();
                if (!$query_current_spin) {
                    return response()->json([
                        'status' => 500,
                        'message' => 'Không tìm thấy tiến trình quay!'
                    ]);
                }
                $rank = Rank::find($query_current_spin->rank_id);
                if ($rank->spin_count == $query_current_spin->current_spin) {
                    return response()->json([
                        'status' => 400,
                        'is_frozen' => false,
                        'is_order_special' => false,
                        'is_new_order' => false,
                        'message' => 'Lượt quay đã đạt đến giới hạn tối đa!'
                    ]);
                }
                $order = Order::where('index', $query_current_spin->current_spin + 1)->where('rank_id', $query_current_spin->rank_id)->first();
                if (!$order) {
                    return response()->json([
                        'status' => 500,
                        'message' => 'Không tìm thấy đơn hàng!'
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
                'message' => 'Đã xảy ra lỗi khi kiểm tra đơn hàng!',
                'error' => $e->getMessage()
            ]);
        }
    }
    public function distribution()
    {
        $user = Auth::user();
        $frozen_price = null;
        $frozen_order = Frozen_order::where('user_id', $user->id)->where('custom_price', '!=', null)->where('is_frozen', true)->where('spun', true)->first();
        if ($frozen_order) {
            $frozen_price = $frozen_order->custom_price;
        }
        return view('user.distribution', compact('user', 'frozen_price'));
    }
    public function withdraw_money()
    {
        $user = Auth::user();
        $rank = Rank::find($user->rank_id);
        $has_password = $user->transaction_password ? true : false;

        $maximum_number_of_withdrawals = $rank->maximum_number_of_withdrawals - $user->count_withdrawals;
        $maximum_withdrawal_amount = $rank->maximum_withdrawal_amount;
        return view('user.withdraw_money', compact('user', 'maximum_number_of_withdrawals', 'maximum_withdrawal_amount', 'has_password', 'rank'));
    }
    public function handle_withdraw()
    {
        $user = User::find(Auth::user()->id);
        $rank = Rank::find($user->rank_id);
        $check = Wallet_balance_history::where('user_id', $user->id)->where('status', 'processing')->where('type', 'withdraw')->first();
        if ($check) {
            return response()->json([
                'status' => 400,
                'message' => 'Đang có một đơn rút tiền chưa hoàn thành!'
            ]);
        }
        if ($user->count_withdrawals >= $rank->maximum_number_of_withdrawals) {
            return response()->json([
                'status' => 400,
                'message' => 'Số lần rút đã đạt tối đa trong ngày!'
            ]);
        }
        $amount = floatval(request()->input('amount'));
        if ($user->balance < $amount) {
            return response()->json([
                'status' => 400,
                'message' => 'Số dư không đủ!'
            ]);
        }
        if ($amount > $rank->maximum_withdrawal_amount) {
            return response()->json([
                'status' => 400,
                'message' => 'Số tiền rút vượt quá giới hạn quy định!'
            ]);
        }
        $username_bank = request()->input('username_bank');
        $bank_name = request()->input('bank_name');
        $account_number = request()->input('account_number');
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
                    'message' => 'Vui lòng nhập đầy đủ thông tin!',
                ]);
            }

            // Kiểm tra hai mật khẩu có khớp không
            if ($transaction_password !== $confirm_transaction_password) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Mật khẩu xác nhận không khớp!',
                ]);
            }

            // Lưu mật khẩu giao dịch
            $user->transaction_password = password_hash($transaction_password, PASSWORD_DEFAULT);
            $user->save();
        } else {
            if (!password_verify($transaction_password, $user->transaction_password)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Mật khẩu giao dịch không chính xác!',
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
            'message' => 'Tạo đơn rút tiền thành công, vui lòng chờ xử lý!',
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
