<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserLocked;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFrozenOrderRequest;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateFrozenOrderRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Conversation;
use App\Models\Frozen_order;
use App\Models\Manager_setting;
use App\Models\Order;
use App\Models\Rank;
use App\Models\User_manager_setting;
use App\Models\User_spin_progress;
use App\Models\Wallet_balance_history;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = User::with('frozen_orders')->with('referrer')->where('role', 'member');
        if (Auth::user()->role === "staff") {
            $get_quan_ly_tat_ca_nguoi_dung = Manager_setting::where('manager_code', 'quan_ly_tat_ca_nguoi_dung')->first();
            if ($get_quan_ly_tat_ca_nguoi_dung) {
                $get_user_manager_setting_by_user_id = User_manager_setting::where('manager_setting_id', $get_quan_ly_tat_ca_nguoi_dung->id)->where('user_id', Auth::user()->id)->first();
                if ($get_user_manager_setting_by_user_id) {
                    if (!$get_user_manager_setting_by_user_id->is_active) {
                        $query->where('referrer_id', Auth::user()->id);
                    }
                }
            }
        }
        $users = $query->latest('id')->get();
        return view('admin.user.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $list_ranks = Rank::get();
        return view('admin.user.create', compact('list_ranks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function return_random_referral_code()
    {
        do {
            $random_number = random_int(100000, 999999);
            $exists = User::where('referral_code', $random_number)->exists();
        } while ($exists);

        return $random_number;
    }
    public function store(StoreUserRequest $request)
    {
        $data = $request->only(['full_name', 'username', 'phone']);
        if (User::where('username', $data['username'])->exists()) {
            return back()->withErrors(['username' => 'Tên đăng nhập đã tồn tại!'])->withInput();
        }
        if (User::where('phone', $data['phone'])->exists()) {
            return back()->withErrors(['phone' => 'Số điện thoại đã tồn tại!'])->withInput();
        }
        if ($request->password != "") {
            if (strlen($request->password) >= 6) {
                $data['password'] = $request->password;
            } else {
                return back()->withErrors(['password' => 'Mật khẩu phải lớn hơn hoặc bằng 6 ký tự!'])->withInput();
            }
        } else {
            $data['password'] = '123456';
        }
        $data['password'] = Hash::make($data['password']);
        $data['rank_id'] = $request->filled('rank') ? $request->rank : null;
        $data['referrer_id'] = Auth::user()->id;
        $data['status'] = "activated";
        $data['referral_code'] = $this->return_random_referral_code();
        $new_user = User::create($data);
        if ($request->rank) {
            User_spin_progress::create([
                'user_id' => $new_user->id,
                'rank_id' => $data['rank_id']
            ]);
        }
        return redirect()->route('user.index')->with('success', 'Tạo tài khoản người dùng thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $list_ranks = Rank::get();
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
        return view('admin.user.edit', compact('user', 'list_ranks', 'banks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $oldRankId = $user->rank_id;
        $data = $request->only(['full_name', 'username', 'email', 'phone', 'username_bank', 'bank_name', 'account_number', 'balance']);
        $data['rank_id'] = $request->rank;
        $reset_progress = $request->has('reset_progress');
        $clone_account = $request->has('clone_account');
        $progress = User_spin_progress::where('user_id', $user->id)->first();
        if ($reset_progress && $progress) {
            $progress->current_spin = 0;
            $progress->save();
        }
        if ($clone_account) {
            $data['clone_account'] = true;
        } else {
            $data['clone_account'] = false;
        }
        if ($request->rank) {
            if ($progress) {
                $progress->rank_id = $request->rank;
                $progress->save();
            } else {
                User_spin_progress::create([
                    'user_id' => $user->id,
                    'rank_id' => $request->rank
                ]);
            }
        } else if ($user->rank_id && !$progress) {
            User_spin_progress::create([
                'user_id' => $user->id,
                'rank_id' => $user->rank_id
            ]);
        }
        $user->update($data);
        if ($request->rank != $oldRankId) {
            Frozen_order::where('user_id', $user->id)
                ->where('is_frozen', true)
                ->update(['is_frozen' => false]);
        }
        return redirect()->route('user.index')->with('success', 'Cập nhật tài khoản người dùng thành công!');
    }
    public function changeStatusUser(User $user)
    {
        $message = "";
        if ($user) {
            if ($user->status === "inactivated") {
                $message = "Kích hoạt tài khoản người dùng thành công!";
                Conversation::create([
                    'staff_id' => Auth::user()->id,
                    'user_id' => $user->id
                ]);
                $user->status = "activated";
                $user->referrer_id = $user->referrer_id ?? Auth::user()->id;
            } elseif ($user->status === "activated") {
                $message = "Khóa tài khoản người dùng thành công!";
                $user->status = "banned";
                event(new UserLocked($user->id));
            } else {
                $user->status = "activated";
                $message = "Mở khóa tài khoản người dùng thành công!";
            }
            $user->save();
            return redirect()->route('user.index')->with('success', $message);
        } else {
            return redirect()->route('user.index')->with('error', 'Không tìm thấy người dùng cần thay đổi trạng thái!');
        }
    }
    public function editFrozenOrderInterface(User $user, $id)
    {
        $list_orders = Order::where('rank_id', $user->rank_id)->get();
        $progress = User_spin_progress::where('user_id', $user->id)->where('rank_id', $user->rank_id)->first();
        $frozen_order_old = Frozen_order::where('id', $id)->first();
        return view('admin.user.edit_frozen_order', compact('list_orders', 'progress', 'user', 'frozen_order_old'));
    }
    public function frozenOrderInterface(?User $user)
    {
        if (!$user) {
            abort(404, 'Không tìm thấy người dùng này');
        }

        if (!$user->rank_id) {
            return back()->with('error', 'Thằng này chưa có gian hàng!');
        }

        // Lấy order theo rank của user
        $list_orders = Order::where('rank_id', $user->rank_id)->get();

        // Lấy hoặc tạo progress
        $progress = User_spin_progress::firstOrCreate(
            ['user_id' => $user->id, 'rank_id' => $user->rank_id]
        );

        // Lấy danh sách order đã đóng băng với thông tin chi tiết
        $frozen_orders_detail = Frozen_order::where('user_id', $user->id)
            ->where('is_frozen', true)
            ->where('custom_price', "!=", null)
            ->with('order')
            ->get();
        // dd($frozen_orders_detail);
        $frozen_orders = $frozen_orders_detail->pluck('order_id')->toArray();

        return view('admin.user.frozen_order', compact('list_orders', 'progress', 'user', 'frozen_orders', 'frozen_orders_detail'));
    }


    public function frozenOrder(StoreFrozenOrderRequest $request, User $user)
    {
        $order_data = $request->order_data;

        if (empty($order_data) || !is_array($order_data)) {
            return back()->with('error', 'Vui lòng chọn ít nhất một đơn hàng!');
        }

        $get_progress_user = User_spin_progress::where('user_id', $user->id)->first();
        if (!$get_progress_user) {
            return back()->with('error', 'Không tìm thấy tiến trình quay của người dùng!');
        }

        $success_count = 0;
        $error_messages = [];

        foreach ($order_data as $data) {
            $order_id = $data['order_id'] ?? null;
            $custom_price = $data['custom_price'] ?? null;

            if (!$order_id) {
                continue;
            }

            $get_order_by_id = Order::find($order_id);
            if (!$get_order_by_id) {
                $error_messages[] = "Không tìm thấy đơn hàng ID: {$order_id}";
                continue;
            }

            $check_frozen_order = Frozen_order::where('order_id', $order_id)
                ->where('user_id', $user->id)
                ->where('is_frozen', true)
                ->first();

            if ($check_frozen_order) {
                $error_messages[] = "Đơn hàng '{$get_order_by_id->name}' đã được đóng băng trước đó";
                continue;
            }

            Frozen_order::create([
                'custom_price' => $custom_price,
                'order_id' => $order_id,
                'user_id' => $user->id,
                'is_frozen' => true,
            ]);

            $success_count++;
        }

        if ($success_count > 0 && empty($error_messages)) {
            return back()->with('success', "Đóng băng thành công {$success_count} đơn hàng!");
        } elseif ($success_count > 0 && !empty($error_messages)) {
            return back()->with('warning', "Đóng băng thành công {$success_count} đơn hàng. Lỗi: " . implode(', ', $error_messages));
        } else {
            return back()->with('error', 'Không đóng băng được đơn hàng nào. ' . implode(', ', $error_messages));
        }
    }

    // Hủy đóng băng đơn hàng
    public function unfrozenOrder(User $user, Frozen_order $frozenOrder)
    {
        if ($frozenOrder->user_id !== $user->id) {
            return back()->with('error', 'Không có quyền thực hiện thao tác này!');
        }

        $order_name = $frozenOrder->order->name ?? 'Đơn hàng';

        $frozenOrder->delete();

        return back()->with('success', "Đã hủy đóng băng đơn hàng '{$order_name}'!");
    }

    // Cập nhật giá giả
    public function updateFrozenOrder(Request $request, User $user, Frozen_order $frozenOrder)
    {
        $request->validate([
            'custom_price' => 'required|numeric|min:0',
        ], [
            'custom_price.required' => 'Vui lòng nhập giá giả',
            'custom_price.numeric' => 'Giá phải là số',
            'custom_price.min' => 'Giá phải lớn hơn hoặc bằng 0',
        ]);

        if ($frozenOrder->user_id !== $user->id) {
            return back()->with('error', 'Không có quyền thực hiện thao tác này!');
        }

        $old_price = $frozenOrder->custom_price;
        $frozenOrder->custom_price = $request->custom_price;
        $frozenOrder->save();

        $order_name = $frozenOrder->order->name ?? 'Đơn hàng';

        return back()->with('success', "Đã cập nhật giá giả của đơn hàng '{$order_name}' từ {$old_price}$ thành {$request->custom_price}$!");
    }

    public function plus_money()
    {
        $value = request()->input('value');
        $user_id = request()->input('user_id');
        $isRealDeposit = request()->input(key: 'isRealDeposit');
        if (!is_numeric($value)) {
            return response()->json([
                'status' => 400,
                'message' => 'Giá trị không hợp lệ, vui lòng nhập số!'
            ]);
        }
        if ($value <= 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Số tiền phải lớn hơn 0!'
            ]);
        }
        $get_user = User::find($user_id);
        if (!$get_user) {
            return response()->json([
                'status' => 400,
                'message' => 'Người dùng không tồn tại!'
            ]);
        }
        $initial_balance = $get_user->balance;
        $get_user->balance = $get_user->balance + $value;
        $get_user->save();
        Wallet_balance_history::create([
            'user_id' => $user_id,
            'value' => $value,
            'initial_balance' => $initial_balance,
            'type' => 'deposit',
            'status' => 'completed',
            'by_user_id' => Auth::user()->id,
            'transaction_type' => $isRealDeposit ? "normal" : "bonus"
        ]);
        
        // Broadcast event thông báo nạp tiền realtime
        event(new \App\Events\MoneyDeposited(
            $user_id,
            $value,
            $get_user->balance,
            $isRealDeposit ? 'normal' : 'bonus',
            Auth::user()->full_name ?? Auth::user()->username
        ));
        
        return response()->json([
            'status' => 200,
            'message' => 'Đã nạp thêm ' . $value . '$ vào tài khoản của người dùng ' . $get_user->full_name . '!'
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
