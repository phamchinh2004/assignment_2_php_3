<?php

namespace App\Http\Controllers\Admin;

use App\Events\ApproximateLocationRefreshRequested;
use App\Events\UserLocked;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFrozenOrderRequest;
use App\Models\FrozenOrderSetting;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateFrozenOrderRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Conversation;
use App\Models\Frozen_order;
use App\Models\Order;
use App\Models\Rank;
use App\Models\User_spin_progress;
use App\Services\AuthorizationService;
use App\Services\UserDepositService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AuthorizationService $authorization)
    {
        $query = User::with(['frozen_orders', 'referrer', 'rank'])->where('role', 'member');
        $actor = Auth::user();

        if (!$authorization->can($actor, config('authorization.capabilities.manage_all_users'))) {
            $query->where('referrer_id', $actor->id);
        }

        $users = $query->latest('id')->get();
        return view('admin.user.index', compact('users'));
    }

    /**
     * Return member presence data for the user management page polling.
     */
    public function getOnlineStatuses(AuthorizationService $authorization)
    {
        $query = User::where('role', User::ROLE_MEMBER)
            ->select('id', 'last_seen');
        $actor = Auth::user();

        if (!$authorization->can($actor, config('authorization.capabilities.manage_all_users'))) {
            $query->where('referrer_id', $actor->id);
        }

        $users = $query->get()->map(function (User $user) {
            return [
                'id' => $user->id,
                'is_online' => $user->isOnline(),
                'last_seen_formatted' => $user->last_seen_formatted,
                'last_seen_diff' => $user->last_seen
                    ? $user->last_seen->diffForHumans()
                    : 'Chưa từng online',
            ];
        });

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    /**
     * Display the details of a member.
     */
    public function show(User $user, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);

        $user->load([
            'rank',
            'referrer',
            'user_spin_progress',
            'frozen_orders.order',
            'transaction_histories' => fn ($query) => $query->latest()->limit(10),
            'wallet_balance_histories' => fn ($query) => $query->latest()->limit(10),
        ]);

        return view('admin.user.show', compact('user'));
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
     * Show the form for editing the specified resource.
     */
    public function edit(User $user, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);
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
    public function update(UpdateUserRequest $request, User $user, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);
        $oldRankId = $user->rank_id;
        $data = $request->only([
            'full_name',
            'username',
            'email',
            'phone',
            'username_bank',
            'bank_name',
            'account_number',
            'balance',
            'frozen_balance',
            'status',
            'warehouse_area',
            'warehouse_address',
        ]);

        // Only the owner may change account roles.
        if ($authorization->isSuperuser(Auth::user()) && $request->filled('role')) {
            $data['role'] = $request->role;
        }
        if ($authorization->can(Auth::user(), config('authorization.capabilities.manage_all_users')) && $request->filled('lucky_wheel_bonus_spins')) {
            $data['lucky_wheel_bonus_spins'] = (int) $request->lucky_wheel_bonus_spins;
        }
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

    public function refreshApproximateLocation(User $user, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);

        if (!$user->isOnline()) {
            return redirect()
                ->route('user.edit', ['user' => $user->id])
                ->with('warning', 'Người dùng đang ngoại tuyến, chưa thể lấy vị trí tương đối mới.');
        }

        Cache::put('approx-location-force-refresh:' . $user->id, true, now()->addMinutes(5));
        try {
            event(new ApproximateLocationRefreshRequested($user->id));
        } catch (\Throwable $exception) {
            Log::warning('Không thể broadcast yêu cầu cập nhật vị trí tương đối.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('user.edit', ['user' => $user->id])
            ->with('success', 'Đã gửi yêu cầu cập nhật vị trí tương đối tới người dùng đang online.');
    }

    public function destroyLocation(User $user, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);

        $user->update([
            'location_latitude' => null,
            'location_longitude' => null,
            'location_accuracy' => null,
            'location_country_code' => null,
            'location_country' => null,
            'location_city' => null,
            'location_updated_at' => null,
            'approx_location_country_code' => null,
            'approx_location_country' => null,
            'approx_location_updated_at' => null,
        ]);

        return redirect()
            ->route('user.edit', ['user' => $user->id])
            ->with('success', 'Đã xoá dữ liệu vị trí người dùng!');
    }

    public function changeStatusUser(User $user, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);
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
    public function editFrozenOrderInterface(User $user, $id, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);
        $list_orders = Order::where('rank_id', $user->rank_id)->get();
        $progress = User_spin_progress::where('user_id', $user->id)->where('rank_id', $user->rank_id)->first();
        $frozen_order_old = Frozen_order::where('id', $id)->first();
        return view('admin.user.edit_frozen_order', compact('list_orders', 'progress', 'user', 'frozen_order_old'));
    }
    public function frozenOrderInterface(?User $user, AuthorizationService $authorization)
    {
        if (!$user) {
            abort(404, 'Không tìm thấy người dùng này');
        }
        $this->authorizeMemberAccess($user, $authorization);

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
        $frozen_orders = $frozen_orders_detail->pluck('order_id')->toArray();
        $defaultFrozenOrderSettings = FrozenOrderSetting::query()->first() ?? FrozenOrderSetting::defaults();

        return view('admin.user.frozen_order', compact('list_orders', 'progress', 'user', 'frozen_orders', 'frozen_orders_detail', 'defaultFrozenOrderSettings'));
    }


    public function frozenOrder(StoreFrozenOrderRequest $request, User $user, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);
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
        $defaultSettings = FrozenOrderSetting::query()->first() ?? FrozenOrderSetting::defaults();

        foreach ($order_data as $data) {
            $order_id = $data['order_id'] ?? null;
            $custom_price = $data['custom_price'] ?? null;
            $commission_percentage = $data['commission_percentage'] ?? null;
            $processing_time_limit = isset($data['processing_time_limit']) && $data['processing_time_limit'] !== '' ? (int) $data['processing_time_limit'] : (int) $defaultSettings->processing_time_limit;
            $notification_1_remaining_time = isset($data['notification_1_remaining_time']) && $data['notification_1_remaining_time'] !== '' ? (int) $data['notification_1_remaining_time'] : (int) $defaultSettings->notification_1_remaining_time;
            $notification_2_remaining_time = isset($data['notification_2_remaining_time']) && $data['notification_2_remaining_time'] !== '' ? (int) $data['notification_2_remaining_time'] : (int) $defaultSettings->notification_2_remaining_time;

            if ($processing_time_limit <= 0 || $notification_1_remaining_time <= 0 || $notification_2_remaining_time <= 0) {
                $error_messages[] = "Dữ liệu thời gian không hợp lệ cho đơn hàng ID: {$order_id}";
                continue;
            }

            if ($notification_1_remaining_time <= $notification_2_remaining_time || $processing_time_limit <= $notification_1_remaining_time) {
                $error_messages[] = "Dữ liệu thời gian không hợp lệ cho đơn hàng ID: {$order_id}. Cần: processing > notification_1 > notification_2";
                continue;
            }

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

            $frozen_order = Frozen_order::snapshotFromOrder($get_order_by_id, [
                'custom_price' => $custom_price,
                'commission_percentage' => $commission_percentage,
                'user_id' => $user->id,
                'assigned_by' => Auth::id(),
                'assignment_source' => 'admin',
                'is_frozen' => true,
                'processing_time_limit' => $processing_time_limit,
                'notification_1_remaining_time' => $notification_1_remaining_time,
                'notification_2_remaining_time' => $notification_2_remaining_time,
                'status' => 'pending',
            ]);
            
            // Tạo record status đầu tiên trong status_orders
            \App\Services\OrderStatusService::changeStatus(
                $frozen_order,
                'pending',
                'Quản trị viên phân phối đơn hàng',
                Auth::id()
            );

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
    public function unfrozenOrder(User $user, Frozen_order $frozenOrder, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);
        if ($frozenOrder->user_id !== $user->id) {
            return back()->with('error', 'Không có quyền thực hiện thao tác này!');
        }

        $order_name = $frozenOrder->snapshot_name ?? 'Đơn hàng';

        $frozenOrder->delete();

        return back()->with('success', "Đã hủy đóng băng đơn hàng '{$order_name}'!");
    }

    // Cập nhật giá giả
    public function updateFrozenOrder(Request $request, User $user, Frozen_order $frozenOrder, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);
        $request->validate([
            'custom_price' => 'required|numeric|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'processing_time_limit' => 'nullable|integer|min:1',
            'notification_1_remaining_time' => 'nullable|integer|min:1',
            'notification_2_remaining_time' => 'nullable|integer|min:1',
        ], [
            'custom_price.required' => 'Vui lòng nhập giá giả',
            'custom_price.numeric' => 'Giá phải là số',
            'custom_price.min' => 'Giá phải lớn hơn hoặc bằng 0',
            'commission_percentage.numeric' => 'Phần trăm hoa hồng phải là số',
            'commission_percentage.min' => 'Phần trăm hoa hồng phải lớn hơn hoặc bằng 0',
            'commission_percentage.max' => 'Phần trăm hoa hồng không được vượt quá 100',
            'processing_time_limit.integer' => 'Thời hạn xử lý phải là số nguyên',
            'processing_time_limit.min' => 'Thời hạn xử lý phải lớn hơn 0',
            'notification_1_remaining_time.integer' => 'Thời gian cảnh báo lần 1 phải là số nguyên',
            'notification_1_remaining_time.min' => 'Thời gian cảnh báo lần 1 phải lớn hơn 0',
            'notification_2_remaining_time.integer' => 'Thời gian cảnh báo lần 2 phải là số nguyên',
            'notification_2_remaining_time.min' => 'Thời gian cảnh báo lần 2 phải lớn hơn 0',
        ]);

        if ($frozenOrder->user_id !== $user->id) {
            return back()->with('error', 'Không có quyền thực hiện thao tác này!');
        }

        $old_price = $frozenOrder->custom_price;
        $old_commission = $frozenOrder->commission_percentage;
        $processing_time_limit = $request->filled('processing_time_limit') ? (int) $request->processing_time_limit : ($frozenOrder->processing_time_limit ?? 24);
        $notification_1_remaining_time = $request->filled('notification_1_remaining_time') ? (int) $request->notification_1_remaining_time : ($frozenOrder->notification_1_remaining_time ?? 12);
        $notification_2_remaining_time = $request->filled('notification_2_remaining_time') ? (int) $request->notification_2_remaining_time : ($frozenOrder->notification_2_remaining_time ?? 1);

        if ($notification_1_remaining_time <= $notification_2_remaining_time || $processing_time_limit <= $notification_1_remaining_time) {
            return back()->with('error', 'Dữ liệu thời gian không hợp lệ. Cần: processing_time_limit > notification_1_remaining_time > notification_2_remaining_time');
        }
        
        $frozenOrder->custom_price = $request->custom_price;
        if ($request->has('commission_percentage') && $request->commission_percentage !== null && $request->commission_percentage !== '') {
            $frozenOrder->commission_percentage = $request->commission_percentage;
        }
        $frozenOrder->processing_time_limit = $processing_time_limit;
        $frozenOrder->notification_1_remaining_time = $notification_1_remaining_time;
        $frozenOrder->notification_2_remaining_time = $notification_2_remaining_time;
        $frozenOrder->save();

        $order_name = $frozenOrder->snapshot_name ?? 'Đơn hàng';
        
        $message = "Đã cập nhật giá giả của đơn hàng '{$order_name}' từ {$old_price}$ thành {$request->custom_price}$";
        if ($request->has('commission_percentage') && $request->commission_percentage !== null && $request->commission_percentage !== '') {
            $old_commission_display = $old_commission ?? 0;
            $message .= " và phần trăm hoa hồng từ {$old_commission_display}% thành {$request->commission_percentage}%";
        }
        $message .= "!";

        return back()->with('success', $message);
    }

    // Thay ảnh đơn hàng đã đóng băng
    public function updateOrderImage(Request $request, User $user, Frozen_order $frozenOrder, AuthorizationService $authorization)
    {
        $this->authorizeMemberAccess($user, $authorization);
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ], [
            'image.required' => 'Vui lòng chọn ảnh',
            'image.image' => 'File phải là hình ảnh',
            'image.mimes' => 'Ảnh phải có định dạng: jpeg, png, jpg, gif, svg, webp',
            'image.max' => 'Kích thước ảnh không được vượt quá 2MB',
        ]);

        if ($frozenOrder->user_id !== $user->id) {
            return back()->with('error', 'Không có quyền thực hiện thao tác này!');
        }

        // Lưu ảnh mới
        $file = $request->file('image');
        $file_name = $file->store('uploads/images/frozen-orders', 'public');
        $oldSnapshotImage = $frozenOrder->snapshot_image;
        $frozenOrder->snapshot_image = $file_name;
        $frozenOrder->save();
        Frozen_order::deleteOwnedSnapshotImage($oldSnapshotImage);

        $order_name = $frozenOrder->snapshot_name ?? 'Đơn hàng';
        return back()->with('success', "Đã thay ảnh cho đơn hàng '{$order_name}' thành công!");
    }

    public function plus_money(AuthorizationService $authorization, UserDepositService $depositService)
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
        $this->authorizeMemberAccess($get_user, $authorization);
        $transactionType = $isRealDeposit ? 'normal' : 'bonus';
        $adminName = Auth::user()->full_name ?? Auth::user()->username;

        $deposit = $depositService->deposit(
            $get_user,
            (float) $value,
            $transactionType,
            Auth::user(),
            $adminName
        );
        
        $message = 'Đã nạp thêm ' . $value . '$ vào tài khoản của người dùng ' . $get_user->full_name . '!';
        if ($deposit['balance_type'] === 'frozen_balance') {
            $moved_balance = $deposit['initial_balance'] > 0
                ? ' và số dư hiện tại ($' . number_format($deposit['initial_balance'], 2) . ')'
                : '';
            $message .= ' (Toàn bộ số tiền nạp' . $moved_balance . ' đã được chuyển vào số dư đóng băng vì có đơn hàng giá trị cao chưa xác nhận)';
        }
        
        return response()->json([
            'status' => 200,
            'message' => $message
        ]);
    }

    private function authorizeMemberAccess(User $member, AuthorizationService $authorization): void
    {
        abort_unless($member->role === User::ROLE_MEMBER, 404);

        $actor = Auth::user();
        $canManageAll = $authorization->can(
            $actor,
            config('authorization.capabilities.manage_all_users')
        );

        abort_unless(
            $canManageAll || (int) $member->referrer_id === (int) $actor->id,
            403
        );
    }
}
