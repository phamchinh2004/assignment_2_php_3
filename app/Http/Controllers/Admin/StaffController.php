<?php

namespace App\Http\Controllers\Admin;

use App\Events\AuthorizationUpdated;
use App\Events\StaffLocked;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Manager_setting;
use App\Models\User;
use App\Models\User_manager_setting;
use App\Services\AuthorizationService;
use App\Services\PermissionRegistry;
use App\Services\ReactPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(private readonly ReactPageService $reactPage)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(AuthorizationService $authorization): View|JsonResponse
    {
        $list_staffs = User::with('referrer')
            ->visibleOperatorsTo(Auth::user())
            ->get();

        $depositTotals = $this->depositTotalsByStaff($list_staffs->pluck('id'));

        $onlineStaffCount = $list_staffs->filter(fn($u) => $u->isOnline())->count();
        $offlineStaffCount = $list_staffs->count() - $onlineStaffCount;

        $actor = Auth::user();
        $list_staffs->each(function (User $staff) use ($authorization, $actor, $depositTotals) {
            $staff->setAttribute('total_deposit', (float) ($depositTotals->get($staff->id) ?? 0));
            $staff->setAttribute('is_online', $staff->isOnline());
            $staff->setAttribute('last_seen_text', $staff->last_seen_text);
            $staff->setAttribute('last_seen_formatted', $staff->last_seen_formatted);
            $staff->setAttribute('can_manage', $authorization->canManageOperator($actor, $staff));
            $staff->setAttribute('can_manage_permissions', $authorization->canManageOperatorPermissions($actor, $staff));
        });

        $capabilities = config('authorization.capabilities');

        return $this->reactPage->admin('admin.staff.index', [
            'staffs' => $list_staffs,
            'onlineStaffCount' => $onlineStaffCount,
            'offlineStaffCount' => $offlineStaffCount,
            'routes' => [
                'create' => route('staff.create'),
                'show' => route('staff.show', ['staff' => '__STAFF_ID__']),
                'edit' => route('staff.edit', ['staff' => '__STAFF_ID__']),
                'permissions' => route('staff.edit.permissions', ['id' => '__STAFF_ID__']),
                'changeStatus' => route('staff.change.status', ['id' => '__STAFF_ID__']),
                'onlineStatuses' => route('staff.online.statuses'),
            ],
            'permissions' => [
                'create' => $authorization->can($actor, $capabilities['staff_create']),
                'viewDetail' => $authorization->can($actor, $capabilities['staff_view_detail']),
                'update' => $authorization->can($actor, $capabilities['staff_update']),
                'changeStatus' => $authorization->can($actor, $capabilities['staff_change_status']),
                'viewPermissions' => $authorization->can($actor, $capabilities['staff_permissions_view']),
            ],
        ], 'Danh sách nhân sự quản trị');
    }

    /**
     * API trả về trạng thái trực tuyến của toàn bộ nhân viên (phục vụ polling nhẹ)
     */
    public function getOnlineStatuses(AuthorizationService $authorization)
    {
        $staffs = User::query()
            ->visibleOperatorsTo(Auth::user())
            ->select('id', 'full_name', 'username', 'role', 'last_seen')
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'is_online' => $staff->isOnline(),
                    'last_seen_text' => $staff->last_seen_text,
                    'last_seen_formatted' => $staff->last_seen_formatted,
                    'last_seen_diff' => $staff->last_seen ? $staff->last_seen->diffForHumans() : 'Chưa từng online'
                ];
            });

        $onlineCount = $staffs->where('is_online', true)->count();
        $totalCount = $staffs->count();

        return response()->json([
            'success' => true,
            'online_count' => $onlineCount,
            'offline_count' => $totalCount - $onlineCount,
            'total_count' => $totalCount,
            'staffs' => $staffs
        ]);
    }
    public function change_status_staff($staff_id, AuthorizationService $authorization)
    {
        $message = "";
        $user = User::find($staff_id);
        if ($user) {
            abort_unless($authorization->canManageOperator(Auth::user(), $user), 403);

            if ($user->status === "inactivated") {
                $message = "Kích hoạt tài khoản nhân viên thành công!";
                $user->status = "activated";
            } elseif ($user->status === "activated") {
                $message = "Khóa tài khoản nhân viên thành công!";
                $user->status = "banned";
                event(new StaffLocked($user->id));
            } else {
                $user->status = "activated";
                $message = "Mở khóa tài khoản nhân viên thành công!";
            }
            $user->save();
            return redirect()->route('staff.index')->with('success', $message);
        } else {
            return redirect()->route('staff.index')->with('error', 'Không tìm thấy nhân viên cần thay đổi trạng thái!');
        }
    }
    public function edit_permissions(
        $staff_id,
        AuthorizationService $authorization,
        PermissionRegistry $registry
    ): View|JsonResponse|RedirectResponse
    {
        $get_user = User::find($staff_id);
        if (!$get_user) {
            return back()->with('error', 'Người dùng không xác định!');
        }
        $actor = Auth::user();
        abort_unless($authorization->canManageOperatorPermissions($actor, $get_user), 403);
        $this->syncRegistryAssignments($get_user, $registry);
        $assignablePermissionCodes = $authorization->assignableOperatorPermissions(
            $actor,
            $get_user,
            $registry->codes()
        );
        $assignments = User_manager_setting::with('manager_setting')
            ->where('user_id', $get_user->id)
            ->whereHas(
                'manager_setting',
                fn ($query) => $query->whereIn('manager_code', $assignablePermissionCodes)
            )
            ->get();
        $permissionGroups = $registry->groups($assignments);

        return $this->reactPage->admin('admin.staff.permissions', [
            'permissionGroups' => $permissionGroups,
            'staff' => $get_user,
            'routes' => [
                'index' => route('staff.index'),
                'toggle' => route('staff.change.status.permission'),
                'bulk' => route('staff.change.status.permissions'),
            ],
        ], 'Phân quyền nhân viên — ' . ($get_user->full_name ?: $get_user->username));
    }
    public function change_status_permission(
        AuthorizationService $authorization,
        PermissionRegistry $registry
    )
    {
        $id = request()->input('id');
        $get_user_manager_setting = User_manager_setting::with('manager_setting')->find($id);
        if (!$get_user_manager_setting) {
            return response()->json([
                'status' => 400,
                'message' => 'Không tìm thấy quyền hạn này!'
            ]);
        }
        $target = User::find($get_user_manager_setting->user_id);
        $actor = Auth::user();
        abort_unless($target && $authorization->canManageOperatorPermissions($actor, $target), 403);
        abort_unless(
            $get_user_manager_setting->manager_setting
                && $registry->contains($get_user_manager_setting->manager_setting->manager_code)
                && $authorization->canAssignOperatorPermission(
                    $actor,
                    $target,
                    $get_user_manager_setting->manager_setting->manager_code
                ),
            403
        );

        $get_user_manager_setting->is_active = !$get_user_manager_setting->is_active;
        $get_user_manager_setting->save();

        $staff = $target;
        if ($staff) {
            $staff->unsetRelation('user_manager_settings');
            $state = $authorization->state($staff);

            try {
                event(new AuthorizationUpdated($staff->id, $state['version']));
            } catch (\Throwable $exception) {
                Log::error('Không thể broadcast thay đổi phân quyền realtime.', [
                    'staff_id' => $staff->id,
                    'authorization_version' => $state['version'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Chỉnh sửa quyền hạn thành công!',
            'is_active' => (bool) $get_user_manager_setting->is_active,
        ]);
    }
    public function change_status_permissions(
        Request $request,
        AuthorizationService $authorization,
        PermissionRegistry $registry
    )
    {
        $data = $request->validate([
            'staff_id' => ['required', 'integer'],
            'assignment_ids' => ['required', 'array', 'min:1'],
            'assignment_ids.*' => ['required', 'integer', 'distinct'],
            'is_active' => ['required', 'boolean'],
        ]);

        $staff = User::find($data['staff_id']);

        if (!$staff || !$authorization->canManageOperatorPermissions(Auth::user(), $staff)) {
            return back()->with('error', 'Không tìm thấy tài khoản cần phân quyền hoặc bạn không có quyền quản lý tài khoản này!');
        }

        $assignmentIds = collect($data['assignment_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $assignments = User_manager_setting::where('user_id', $staff->id)
            ->whereIn('id', $assignmentIds)
            ->with('manager_setting')
            ->get();

        if (
            $assignments->count() !== $assignmentIds->count()
            || $assignments->contains(
                fn ($assignment) => !$assignment->manager_setting
                    || !$registry->contains($assignment->manager_setting->manager_code)
            )
        ) {
            return back()->with('error', 'Danh sách quyền không hợp lệ hoặc không thuộc nhân viên này!');
        }

        $actor = Auth::user();
        abort_if(
            $assignments->contains(
                fn ($assignment) => ! $authorization->canAssignOperatorPermission(
                    $actor,
                    $staff,
                    $assignment->manager_setting->manager_code
                )
            ),
            403
        );

        User_manager_setting::where('user_id', $staff->id)
            ->whereIn('id', $assignmentIds)
            ->update(['is_active' => (bool) $data['is_active']]);

        $staff->unsetRelation('user_manager_settings');
        $state = $authorization->state($staff);

        try {
            event(new AuthorizationUpdated($staff->id, $state['version']));
        } catch (\Throwable $exception) {
            Log::error('Unable to broadcast bulk authorization update.', [
                'staff_id' => $staff->id,
                'authorization_version' => $state['version'],
                'error' => $exception->getMessage(),
            ]);
        }

        return back()->with(
            'success',
            (bool) $data['is_active']
                ? 'Cấp quyền hàng loạt thành công!'
                : 'Bỏ quyền hàng loạt thành công!'
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(AuthorizationService $authorization): View|JsonResponse
    {
        $actor = Auth::user();

        return $this->reactPage->admin('admin.staff.create', [
            'canChooseRole' => $authorization->isSuperuser($actor),
            'managerCandidates' => $this->managerCandidates($actor, $authorization),
            'routes' => [
                'index' => route('staff.index'),
                'store' => route('staff.store'),
            ],
        ], 'Thêm tài khoản nội bộ');
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

    private function return_random_phone(): string
    {
        do {
            $phone = '09' . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (User::where('phone', $phone)->exists());

        return $phone;
    }

    public function store(
        Request $request,
        AuthorizationService $authorization,
        PermissionRegistry $registry
    )
    {
        $actor = Auth::user();
        $canChooseRole = $authorization->isSuperuser($actor);
        $data = $request->validate([
            'username' => ['required', 'string', 'min:6', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => $canChooseRole
                ? ['required', Rule::in([User::ROLE_STAFF, User::ROLE_ADMIN])]
                : ['prohibited'],
            'manager_id' => $canChooseRole
                ? ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN]))]
                : ['prohibited'],
        ]);

        $requestedRole = $canChooseRole ? $data['role'] : User::ROLE_STAFF;
        $managerId = $canChooseRole && $requestedRole === User::ROLE_STAFF
            ? (int) ($data['manager_id'] ?? $actor->id)
            : (int) $actor->id;
        unset($data['manager_id']);
        $data['full_name'] = ($requestedRole === User::ROLE_ADMIN ? 'Admin ' : 'Nhân viên ')
            . Str::upper(Str::random(8));
        $data['phone'] = $this->return_random_phone();
        $data['password'] = empty($data['password']) ? '123456' : $data['password'];
        $data['password'] = Hash::make($data['password']);
        $data['referrer_id'] = $managerId;
        $data['status'] = "activated";
        $data['role'] = $requestedRole;
        $data['referral_code'] = $this->return_random_referral_code();
        $new_user = User::create($data);
        $this->syncRegistryAssignments(
            $new_user,
            $registry,
            $requestedRole === User::ROLE_ADMIN
        );
        return redirect()->route('staff.index')->with('success', 'Tạo tài khoản quản trị thành công!');
    }
    public function show(string $id, AuthorizationService $authorization): View|JsonResponse
    {
        $staff = User::with([
            'referrer',
            'user_manager_settings.manager_setting',
        ])
        ->findOrFail($id);

        abort_unless($authorization->canManageOperator(Auth::user(), $staff), 403);
        $staff->setAttribute('total_deposit', (float) ($this->depositTotalsByStaff([$staff->id])->get($staff->id) ?? 0));

        $referrals = $staff->managedMembers()
            ->with('rank')
            ->latest()
            ->paginate(10);
        $referrals->getCollection()->each(fn (User $customer) => $customer->setAttribute('avatar_url', get_user_avatar($customer)));

        $staff->setAttribute('is_online', $staff->isOnline());
        $staff->setAttribute('last_seen_text', $staff->last_seen_text);
        $staff->setAttribute('last_seen_formatted', $staff->last_seen_formatted);
        $activePermissions = $staff->user_manager_settings
            ->where('is_active', true)
            ->map(fn ($assignment) => [
                'id' => $assignment->id,
                'label' => $assignment->manager_setting?->manager_name ?? (string) $assignment->manager_setting_id,
                'code' => $assignment->manager_setting?->manager_code,
            ])->values();
        $actor = Auth::user();
        $capabilities = config('authorization.capabilities');

        return $this->reactPage->admin('admin.staff.show', [
            'staff' => $staff,
            'referrals' => $referrals,
            'activePermissions' => $activePermissions,
            'routes' => [
                'index' => route('staff.index'),
                'edit' => route('staff.edit', ['staff' => $staff]),
                'permissions' => route('staff.edit.permissions', ['id' => $staff->id]),
                'customerShow' => route('user.show', ['user' => '__USER_ID__']),
            ],
            'permissions' => [
                'managePermissions' => $authorization->can($actor, $capabilities['staff_permissions_view'])
                    && $authorization->canManageOperatorPermissions($actor, $staff),
                'update' => $authorization->can($actor, $capabilities['staff_update'])
                    && $authorization->canManageOperator($actor, $staff),
                'viewCustomerDetail' => $authorization->can($actor, $capabilities['customers_view_detail']),
            ],
        ], 'Chi tiết nhân viên — ' . ($staff->full_name ?: $staff->username));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id, AuthorizationService $authorization): View|JsonResponse|RedirectResponse
    {
        $get_staff_old = User::find($id);
        if (!$get_staff_old) {
            return back()->with('error', 'Người dùng không xác định!');
        }
        abort_unless($authorization->canManageOperator(Auth::user(), $get_staff_old), 403);

        return $this->reactPage->admin('admin.staff.edit', [
            'staff' => $get_staff_old,
            'canChooseRole' => $authorization->isSuperuser(Auth::user()),
            'managerCandidates' => $this->managerCandidates(Auth::user(), $authorization),
            'routes' => [
                'index' => route('staff.index'),
                'update' => route('staff.update', ['staff' => $get_staff_old]),
            ],
        ], 'Chỉnh sửa tài khoản nội bộ');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        string $id,
        AuthorizationService $authorization,
        PermissionRegistry $registry
    )
    {
        $get_user = User::find($id);
        $actor = Auth::user();
        abort_unless($get_user && $authorization->canManageOperator($actor, $get_user), 403);
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($get_user->id)],
            'phone' => ['prohibited'],
            'username' => ['required', 'string', 'min:6', 'max:255', Rule::unique('users', 'username')->ignore($get_user->id)],
            'role' => $authorization->isSuperuser($actor)
                ? ['required', Rule::in([User::ROLE_STAFF, User::ROLE_ADMIN])]
                : ['prohibited'],
            'manager_id' => $authorization->isSuperuser($actor)
                ? ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN]))]
                : ['prohibited'],
        ]);
        $oldRole = $get_user->role;
        $roleChanged = isset($data['role']) && $data['role'] !== $oldRole;
        if ($authorization->isSuperuser($actor)) {
            $targetRole = $data['role'] ?? $get_user->role;
            $data['referrer_id'] = $targetRole === User::ROLE_STAFF
                ? (int) ($data['manager_id'] ?? $actor->id)
                : (int) $actor->id;
        }
        unset($data['manager_id']);
        $get_user->update($data);
        $forcedPermissionState = $roleChanged
            ? $get_user->role === User::ROLE_ADMIN
            : null;
        $this->syncRegistryAssignments($get_user, $registry, $forcedPermissionState);
        if ($roleChanged) {
            $get_user->unsetRelation('user_manager_settings');
            $state = $authorization->state($get_user);

            try {
                event(new AuthorizationUpdated($get_user->id, $state['version']));
            } catch (\Throwable $exception) {
                Log::error('Unable to broadcast staff role update.', [
                    'staff_id' => $get_user->id,
                    'authorization_version' => $state['version'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }
        return redirect()->route('staff.index')->with('success', 'Cập nhật tài khoản nhân viên thành công!');
    }

    private function managerCandidates(User $actor, AuthorizationService $authorization): array
    {
        if (!$authorization->isSuperuser($actor)) {
            return [];
        }

        return User::query()
            ->where(function ($query) use ($actor) {
                $query->whereKey($actor->id)
                    ->orWhere('role', User::ROLE_ADMIN);
            })
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$actor->id])
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'username', 'role'])
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'role' => $user->role,
            ])
            ->all();
    }

    private function syncRegistryAssignments(
        User $user,
        PermissionRegistry $registry,
        ?bool $forceState = null
    ): void {
        foreach ($registry->permissions() as $permission) {
            $managerSetting = Manager_setting::firstOrCreate(
                ['manager_code' => $permission['code']],
                ['manager_name' => $permission['label']]
            );

            if ($managerSetting->manager_name !== $permission['label']) {
                $managerSetting->update(['manager_name' => $permission['label']]);
            }

            $assignment = User_manager_setting::firstOrNew([
                'user_id' => $user->id,
                'manager_setting_id' => $managerSetting->id,
            ]);

            if (!$assignment->exists) {
                $assignment->is_active = $forceState ?? false;
                $assignment->save();
                continue;
            }

            if ($forceState !== null && (bool) $assignment->is_active !== $forceState) {
                $assignment->is_active = $forceState;
                $assignment->save();
            }
        }
    }

    private function depositTotalsByStaff($staffIds)
    {
        $staffIds = collect($staffIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($staffIds->isEmpty()) {
            return collect();
        }

        $operators = User::query()
            ->whereIn('id', $staffIds)
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->get(['id', 'role']);

        $adminIds = $operators
            ->where('role', User::ROLE_ADMIN)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $staffByAdmin = collect();
        if ($adminIds->isNotEmpty()) {
            $staffByAdmin = User::query()
                ->where('role', User::ROLE_STAFF)
                ->whereIn('referrer_id', $adminIds)
                ->get(['id', 'referrer_id'])
                ->groupBy(fn (User $staff) => (int) $staff->referrer_id);
        }

        $attributionIds = $staffIds
            ->merge($staffByAdmin->flatten(1)->pluck('id')->map(fn ($id) => (int) $id))
            ->unique()
            ->values();

        $directTotals = DB::table('wallet_balance_histories as wbh')
            ->join('users as customers', 'wbh.user_id', '=', 'customers.id')
            ->where('customers.clone_account', 0)
            ->where('customers.role', User::ROLE_MEMBER)
            ->where('wbh.type', 'deposit')
            ->where('wbh.status', 'completed')
            ->where('wbh.transaction_type', 'normal')
            ->where(function ($query) use ($attributionIds) {
                $query->whereIn('wbh.assigned_staff_id', $attributionIds)
                    ->orWhere(function ($legacyQuery) use ($attributionIds) {
                        $legacyQuery->whereNull('wbh.assigned_staff_id')
                            ->whereIn('customers.referrer_id', $attributionIds);
                    });
            })
            ->selectRaw('COALESCE(wbh.assigned_staff_id, customers.referrer_id) as staff_id')
            ->selectRaw('SUM(wbh.value) as total_deposit')
            ->groupByRaw('COALESCE(wbh.assigned_staff_id, customers.referrer_id)')
            ->pluck('total_deposit', 'staff_id');

        return $operators->mapWithKeys(function (User $operator) use ($directTotals, $staffByAdmin) {
            $operatorId = (int) $operator->id;
            $total = (float) ($directTotals->get($operatorId) ?? 0);

            if ($operator->role === User::ROLE_ADMIN) {
                $childStaffIds = collect($staffByAdmin->get($operatorId, collect()))
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id);

                $total += $childStaffIds->sum(
                    fn ($staffId) => (float) ($directTotals->get($staffId) ?? 0)
                );
            }

            return [$operatorId => $total];
        });
    }
}
