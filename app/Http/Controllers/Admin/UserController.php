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
use App\Services\ReactPageService;
use App\Services\UserDepositService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly ReactPageService $reactPage)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(AuthorizationService $authorization)
    {
        $query = User::with([
            'frozen_orders',
            'referrer',
            'rank',
            'memberConversations' => fn ($conversationQuery) => $conversationQuery
                ->select('id', 'user_id', 'staff_id', 'public_id', 'updated_at')
                ->with('staff:id,role')
                ->latest('updated_at'),
        ])->where('role', 'member');
        $actor = Auth::user();
        $capabilities = config('authorization.capabilities');
        $canViewAllCustomers = $authorization->can($actor, $capabilities['customers_view_all']);

        $query->visibleCustomersTo($actor, $canViewAllCustomers);

        $users = $query->latest('id')->get();
        $canViewFinancials = $authorization->can($actor, $capabilities['customers_view_financials']);

        $items = $users->values()->map(function (User $user, int $index) use ($actor, $authorization, $canViewFinancials) {
            $hasFrozenOrder = $user->frozen_orders->contains(
                fn ($frozenOrder) => $frozenOrder->custom_price !== null && (bool) $frozenOrder->is_frozen
            );

            $item = [
                'id' => $user->id,
                'position' => $index + 1,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'phone' => $user->phone,
                'status' => $user->status,
                'clone_account' => (bool) $user->clone_account,
                'rank' => $user->rank ? [
                    'id' => $user->rank->id,
                    'name' => $user->rank->name,
                ] : null,
                'referrer' => $user->referrer ? [
                    'id' => $user->referrer->id,
                    'full_name' => $user->referrer->full_name,
                    'username' => $user->referrer->username,
                ] : null,
                'avatar_url' => get_user_avatar($user),
                'is_online' => $user->isOnline(),
                'last_seen' => $user->last_seen?->toISOString(),
                'last_seen_formatted' => $user->last_seen_formatted,
                'last_seen_diff' => $user->last_seen ? $user->last_seen->diffForHumans() : 'Chưa từng online',
                'location_country_code' => $user->location_country_code ?: $user->approx_location_country_code,
                'location_country' => $user->location_country ?: $user->approx_location_country,
                'location_city' => $user->location_city,
                'warehouse_area' => $user->warehouse_area,
                'created_at' => $user->created_at?->toISOString(),
                'updated_at' => $user->updated_at?->toISOString(),
                'has_frozen_order' => $hasFrozenOrder,
                'chat_url' => $this->chatUrlForUser($user, $authorization, $actor),
            ];

            if ($canViewFinancials) {
                $item['balance'] = (float) ($user->balance ?? 0);
                $item['frozen_balance'] = (float) ($user->frozen_balance ?? 0);
            }

            return $item;
        });

        return $this->reactPage->admin('admin.users.index', [
            'users' => $items,
            'stats' => [
                'total' => $users->count(),
                'active' => $users->where('status', 'activated')->count(),
                'inactive' => $users->where('status', 'inactivated')->count(),
                'locked' => $users->whereNotIn('status', ['activated', 'inactivated'])->count(),
                'frozen' => $items->where('has_frozen_order', true)->count(),
                'clones' => $items->where('clone_account', true)->count(),
                'total_balance' => $canViewFinancials ? (float) $users->sum('balance') : null,
                'total_frozen_balance' => $canViewFinancials ? (float) $users->sum('frozen_balance') : null,
            ],
            'permissions' => [
                'create' => $authorization->can($actor, $capabilities['customers_create']),
                'viewDetail' => $authorization->can($actor, $capabilities['customers_view_detail']),
                'viewFinancials' => $canViewFinancials,
                'adjustBalance' => $authorization->can($actor, $capabilities['customers_adjust_balance']),
                'changeStatus' => $authorization->can($actor, $capabilities['customers_change_status']),
                'manageFrozenOrders' => $authorization->can($actor, $capabilities['customers_manage_frozen_orders']),
                'update' => $authorization->can($actor, $capabilities['customers_update']),
            ],
            'routes' => [
                'create' => route('user.create'),
                'show' => route('user.show', ['user' => '__USER_ID__']),
                'edit' => route('user.edit', ['user' => '__USER_ID__']),
                'chat' => route('chat-panel'),
                'changeStatus' => route('user.change.status', ['user' => '__USER_ID__']),
                'frozenOrders' => route('user.frozen.order.interface', ['user' => '__USER_ID__']),
                'onlineStatuses' => route('user.online.statuses'),
                'plusMoney' => route('plus_money'),
            ],
        ], 'Danh sách người dùng');
    }

    /**
     * Return member presence data for the user management page polling.
     */
    public function getOnlineStatuses(AuthorizationService $authorization)
    {
        $actor = Auth::user();
        $canViewAllCustomers = $authorization->can(
            $actor,
            config('authorization.capabilities.customers_view_all')
        );
        $query = User::query()
            ->visibleCustomersTo($actor, $canViewAllCustomers)
            ->select('id', 'last_seen');

        $users = $query->get()->map(function (User $user) {
            return [
                'id' => $user->id,
                'is_online' => $user->isOnline(),
                'last_seen' => $user->last_seen?->toISOString(),
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
        $this->authorizeMemberView($user, $authorization);
        $actor = Auth::user();

        $relations = [
            'rank',
            'referrer',
            'user_spin_progress',
            'frozen_orders.order',
        ];
        $canViewFinancials = $authorization->can(
            Auth::user(),
            config('authorization.capabilities.customers_view_financials')
        );
        if ($canViewFinancials) {
            $relations['transaction_histories'] = fn ($query) => $query->latest()->limit(10);
            $relations['wallet_balance_histories'] = fn ($query) => $query->latest()->limit(10);
        }
        $user->load($relations);

        $payload = $user->toArray();
        $payload['avatar_url'] = get_user_avatar($user);
        $payload['is_online'] = $user->isOnline();
        $payload['last_seen_formatted'] = $user->last_seen_formatted;

        if (!$canViewFinancials) {
            unset(
                $payload['balance'],
                $payload['frozen_balance'],
                $payload['transaction_histories'],
                $payload['wallet_balance_histories']
            );
        }

        return $this->reactPage->admin('admin.users.show', [
            'user' => $payload,
            'permissions' => [
                'viewFinancials' => $canViewFinancials,
                'update' => $authorization->can($actor, config('authorization.capabilities.customers_update')),
                'manageFrozenOrders' => $authorization->can($actor, config('authorization.capabilities.customers_manage_frozen_orders')),
            ],
            'routes' => [
                'index' => route('user.index'),
                'edit' => route('user.edit', ['user' => $user->id]),
                'frozenOrders' => route('user.frozen.order.interface', ['user' => $user->id]),
                'chat' => $this->chatUrlForUser($user, $authorization, $actor),
            ],
        ], 'Chi tiết người dùng');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $list_ranks = Rank::get();
        return $this->reactPage->admin('admin.users.create', [
            'ranks' => $list_ranks,
            'routes' => [
                'index' => route('user.index'),
                'store' => route('user.store'),
                'checkUsername' => route('check_username'),
                'checkEmail' => route('check_email'),
            ],
        ], 'Thêm người dùng');
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
        $data = $request->only(['full_name', 'username', 'email']);
        if (User::where('username', $data['username'])->exists()) {
            return back()->withErrors(['username' => 'Tên đăng nhập đã tồn tại!'])->withInput();
        }
        if (User::where('email', $data['email'])->exists()) {
            return back()->withErrors(['email' => 'Email đã tồn tại!'])->withInput();
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
        $user->loadMissing('referrer:id,full_name,username,role,status');
        $list_ranks = Rank::get();
        $canChangeReferrer = $authorization->can(
            Auth::user(),
            config('authorization.capabilities.customers_change_referrer')
        );
        $actor = Auth::user();
        $referrerCandidates = $canChangeReferrer
            ? User::query()
                ->whereIn('role', User::MANAGEMENT_ROLES)
                ->orderBy('full_name')
                ->orderBy('id')
                ->get(['id', 'full_name', 'username', 'role', 'status'])
            : collect();
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
        $capabilities = config('authorization.capabilities');
        $canAdjustBalance = $authorization->can($actor, $capabilities['customers_adjust_balance']);
        $canChangeStatus = $authorization->can($actor, $capabilities['customers_change_status']);
        $canChangeReferrer = $authorization->can($actor, $capabilities['customers_change_referrer']);
        $canManageSpin = $authorization->can($actor, $capabilities['customers_manage_spin']);
        $canManageLocation = $authorization->can($actor, $capabilities['customers_manage_location']);

        $userPayload = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'username_bank' => $user->username_bank,
            'bank_name' => $user->bank_name,
            'account_number' => $user->account_number,
            'warehouse_area' => $user->warehouse_area,
            'warehouse_address' => $user->warehouse_address,
            'rank_id' => $user->rank_id,
            'status' => $user->status,
            'role' => $user->role,
            'clone_account' => (bool) $user->clone_account,
            'lucky_wheel_bonus_spins' => (int) ($user->lucky_wheel_bonus_spins ?? 0),
            'referral_code' => $user->referral_code,
            'referrer_id' => $user->referrer_id,
            'referrer' => $user->referrer,
            'register_ip' => $user->register_ip,
            'created_at' => $user->created_at?->toISOString(),
            'last_seen' => $user->last_seen?->toISOString(),
            'is_online' => $user->isOnline(),
            'location_latitude' => $user->location_latitude,
            'location_longitude' => $user->location_longitude,
            'location_accuracy' => $user->location_accuracy,
            'location_country_code' => $user->location_country_code,
            'location_country' => $user->location_country,
            'location_city' => $user->location_city,
            'location_updated_at' => $user->location_updated_at?->toISOString(),
            'approx_location_country_code' => $user->approx_location_country_code,
            'approx_location_country' => $user->approx_location_country,
            'approx_location_updated_at' => $user->approx_location_updated_at?->toISOString(),
        ];
        if ($canAdjustBalance) {
            $userPayload['balance'] = (float) ($user->balance ?? 0);
            $userPayload['frozen_balance'] = (float) ($user->frozen_balance ?? 0);
        }

        return $this->reactPage->admin('admin.users.edit', [
            'user' => $userPayload,
            'ranks' => $list_ranks,
            'banks' => $banks,
            'referrerCandidates' => $referrerCandidates,
            'permissions' => [
                'adjustBalance' => $canAdjustBalance,
                'changeStatus' => $canChangeStatus,
                'changeReferrer' => $canChangeReferrer,
                'manageSpin' => $canManageSpin,
                'manageLocation' => $canManageLocation,
                'chooseRole' => $authorization->isSuperuser($actor),
            ],
            'routes' => [
                'index' => route('user.index'),
                'update' => route('user.update', ['user' => $user->id]),
                'locationRefresh' => route('user.location.refresh', ['user' => $user->id]),
                'locationDestroy' => route('user.location.destroy', ['user' => $user->id]),
            ],
        ], 'Chỉnh sửa người dùng');
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
            'warehouse_area',
            'warehouse_address',
        ]);
        $actor = Auth::user();
        $capabilities = config('authorization.capabilities');

        if ($authorization->can($actor, $capabilities['customers_adjust_balance'])) {
            foreach (['balance', 'frozen_balance'] as $financialField) {
                if ($request->has($financialField)) {
                    $data[$financialField] = $request->input($financialField);
                }
            }
        }
        if ($authorization->can($actor, $capabilities['customers_change_status']) && $request->has('status')) {
            $data['status'] = $request->input('status');
        }
        if ($authorization->can($actor, $capabilities['customers_change_referrer']) && $request->has('referrer_id')) {
            $data['referrer_id'] = $request->filled('referrer_id')
                ? (int) $request->input('referrer_id')
                : null;
        }

        // Only the owner may change account roles.
        if ($authorization->isSuperuser($actor) && $request->filled('role')) {
            $data['role'] = $request->role;
        }
        $canManageSpin = $authorization->can($actor, $capabilities['customers_manage_spin']);
        if ($canManageSpin && $request->filled('lucky_wheel_bonus_spins')) {
            $data['lucky_wheel_bonus_spins'] = (int) $request->lucky_wheel_bonus_spins;
        }
        if ($canManageSpin && $request->filled('rank')) {
            $data['rank_id'] = $request->rank;
        }
        $reset_progress = $canManageSpin && $request->boolean('reset_progress');
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
        if (array_key_exists('rank_id', $data)) {
            if ($progress) {
                $progress->rank_id = $data['rank_id'];
                $progress->save();
            } else {
                User_spin_progress::create([
                    'user_id' => $user->id,
                    'rank_id' => $data['rank_id']
                ]);
            }
        } else if ($user->rank_id && !$progress) {
            User_spin_progress::create([
                'user_id' => $user->id,
                'rank_id' => $user->rank_id
            ]);
        }
        $user->update($data);
        if ($user->rank_id != $oldRankId) {
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

        return $this->reactPage->admin('admin.users.frozen-orders', [
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'rank_id' => $user->rank_id,
            ],
            'orders' => $list_orders->map(fn (Order $order) => [
                ...$order->toArray(),
                'image_url' => $order->image ? Storage::url($order->image) : null,
            ]),
            'progress' => $progress,
            'frozenOrderIds' => $frozen_orders,
            'frozenOrders' => $frozen_orders_detail->map(fn (Frozen_order $frozenOrder) => [
                ...$frozenOrder->toArray(),
                'image_url' => $frozenOrder->snapshot_image ? Storage::url($frozenOrder->snapshot_image) : null,
            ]),
            'defaultSettings' => $defaultFrozenOrderSettings,
            'routes' => [
                'index' => route('user.index'),
                'store' => route('user.frozen.order', ['user' => $user->id]),
                'update' => route('user.update.frozen.order', [
                    'user' => $user->id,
                    'frozenOrder' => '__FROZEN_ID__',
                ]),
                'destroy' => route('user.unfrozen.order', [
                    'user' => $user->id,
                    'frozenOrder' => '__FROZEN_ID__',
                ]),
                'updateImage' => route('user.update.frozen.order.image', [
                    'user' => $user->id,
                    'frozenOrder' => '__FROZEN_ID__',
                ]),
            ],
        ], 'Đóng băng đơn hàng');
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

    private function chatUrlForUser(User $member, AuthorizationService $authorization, User $actor): ?string
    {
        $conversations = $member->relationLoaded('memberConversations')
            ? $member->memberConversations
            : $member->memberConversations()
                ->select('id', 'user_id', 'staff_id', 'public_id', 'updated_at')
                ->with('staff:id,role')
                ->latest('updated_at')
                ->get();

        $conversation = $conversations->first(
            fn (Conversation $conversation) => $conversation->public_id
                && $authorization->canViewConversation($actor, $conversation)
        );

        return $conversation
            ? route('chat-panel', ['conversation' => $conversation->public_id])
            : null;
    }

    private function authorizeMemberAccess(User $member, AuthorizationService $authorization): void
    {
        abort_unless($member->role === User::ROLE_MEMBER, 404);

        $actor = Auth::user();
        abort_unless($actor->canAccessCustomer($member), 403);
    }

    private function authorizeMemberView(User $member, AuthorizationService $authorization): void
    {
        abort_unless($member->role === User::ROLE_MEMBER, 404);

        $actor = Auth::user();
        $canViewAllCustomers = $authorization->can(
            $actor,
            config('authorization.capabilities.customers_view_all')
        );

        abort_unless($canViewAllCustomers || $actor->canAccessCustomer($member), 403);
    }
}
