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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(AuthorizationService $authorization)
    {
        $list_staffs = User::with('referrer')
            ->withSum(['deposits_made as total_deposit' => function ($q) {
                $q->where('type', 'deposit')
                    ->where('status', 'completed');
                // ->where('by_user_id', Auth::user()->id);
            }], 'value')
            ->whereIn('role', $authorization->manageableOperatorRoles(Auth::user()))
            ->get();

        $onlineStaffCount = $list_staffs->filter(fn($u) => $u->isOnline())->count();
        $offlineStaffCount = $list_staffs->count() - $onlineStaffCount;

        return view('admin.staff.index', compact('list_staffs', 'onlineStaffCount', 'offlineStaffCount'));
    }

    /**
     * API trả về trạng thái trực tuyến của toàn bộ nhân viên (phục vụ polling nhẹ)
     */
    public function getOnlineStatuses(AuthorizationService $authorization)
    {
        $staffs = User::whereIn('role', $authorization->manageableOperatorRoles(Auth::user()))
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
    public function edit_permissions($staff_id, AuthorizationService $authorization)
    {
        $get_user = User::find($staff_id);
        if (!$get_user) {
            return back()->with('error', 'Người dùng không xác định!');
        }
        abort_unless($authorization->canManageOperatorPermissions(Auth::user(), $get_user), 403);
        $list_manager_settings = Manager_setting::get();
        $get_user_manager_setting = User_manager_setting::where('user_id', $staff_id)->get();
        $existing_permission_ids = $get_user_manager_setting->pluck('manager_setting_id')->toArray();

        foreach ($list_manager_settings as $item_manager_setting) {
            if (!in_array($item_manager_setting->id, $existing_permission_ids)) {
                User_manager_setting::create([
                    'user_id' => $staff_id,
                    'manager_setting_id' => $item_manager_setting->id,
                    'is_active' => false,
                ]);
            }
        }

        $get_user_manager_setting = User_manager_setting::where('user_id', $staff_id)->get();

        return view('admin.staff.edit_permission', compact('list_manager_settings', 'get_user_manager_setting', 'get_user'));
    }
    public function change_status_permission(AuthorizationService $authorization)
    {
        $id = request()->input('id');
        $get_user_manager_setting = User_manager_setting::find($id);
        if (!$get_user_manager_setting) {
            return response()->json([
                'status' => 400,
                'message' => 'Không tìm thấy quyền hạn này!'
            ]);
        }
        $target = User::find($get_user_manager_setting->user_id);
        abort_unless($target && $authorization->canManageOperatorPermissions(Auth::user(), $target), 403);

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
    public function change_status_permissions(Request $request, AuthorizationService $authorization)
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
            ->get();

        if ($assignments->count() !== $assignmentIds->count()) {
            return back()->with('error', 'Danh sách quyền không hợp lệ hoặc không thuộc nhân viên này!');
        }

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
    public function create(AuthorizationService $authorization)
    {
        return view('admin.staff.create', [
            'allowedRoles' => $authorization->manageableOperatorRoles(Auth::user()),
        ]);
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
    public function store(Request $request, AuthorizationService $authorization)
    {
        $actor = Auth::user();
        $allowedRoles = $authorization->manageableOperatorRoles($actor);
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:6', 'max:255', 'unique:users,username'],
            'phone' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', Rule::in($allowedRoles)],
        ]);

        $requestedRole = $data['role'] ?? User::ROLE_STAFF;
        abort_unless(in_array($requestedRole, $allowedRoles, true), 403);
        $data['password'] = $data['password'] ?: '123456';
        $data['password'] = Hash::make($data['password']);
        $data['referrer_id'] = $actor->id;
        $data['status'] = "activated";
        $data['role'] = $requestedRole;
        $data['referral_code'] = $this->return_random_referral_code();
        $new_user = User::create($data);
        $list_manager_settings = Manager_setting::get();
        if ($list_manager_settings) {
            foreach ($list_manager_settings as $item_manager_setting) {
                User_manager_setting::create([
                    'user_id' => $new_user->id,
                    'manager_setting_id' => $item_manager_setting->id,
                    'is_active' => 0,
                ]);
            }
        }
        return redirect()->route('staff.index')->with('success', 'Tạo tài khoản nhân viên thành công!');
    }
    public function show(string $id, AuthorizationService $authorization)
    {
        $staff = User::with([
            'referrer',
            'user_manager_settings.manager_setting',
        ])
        ->withSum(['deposits_made as total_deposit' => function ($q) {
            $q->where('type', 'deposit')->where('status', 'completed');
        }], 'value')
        ->findOrFail($id);

        abort_unless($authorization->canManageOperator(Auth::user(), $staff), 403);

        $referrals = User::where('referrer_id', $staff->id)->latest()->paginate(10);

        return view('admin.staff.show', compact('staff', 'referrals'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id, AuthorizationService $authorization)
    {
        $get_staff_old = User::find($id);
        if (!$get_staff_old) {
            return back()->with('error', 'Người dùng không xác định!');
        }
        abort_unless($authorization->canManageOperator(Auth::user(), $get_staff_old), 403);

        return view('admin.staff.edit', compact('get_staff_old'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id, AuthorizationService $authorization)
    {
        $get_user = User::find($id);
        abort_unless($get_user && $authorization->canManageOperator(Auth::user(), $get_user), 403);
        $data = $request->only(['full_name', 'username', 'phone']);
        if (User::where('username', $data['username'])->where('username', '!=', $get_user->username)->exists()) {
            return back()->withErrors(['username' => 'Tên đăng nhập đã tồn tại!'])->withInput();
        }
        if (User::where('phone', $data['phone'])->where('phone', '!=', $get_user->phone)->exists()) {
            return back()->withErrors(['phone' => 'Số điện thoại đã tồn tại!'])->withInput();
        }
        $get_user->update($data);
        $list_manager_settings = Manager_setting::get();
        if ($list_manager_settings) {
            foreach ($list_manager_settings as $item_manager_setting) {
                $get_user_manager_setting = User_manager_setting::where('user_id', $get_user->id)
                    ->where('manager_setting_id', $item_manager_setting->id)
                    ->first();
                if (!$get_user_manager_setting) {
                    User_manager_setting::create([
                        'user_id' => $get_user->id,
                        'manager_setting_id' => $item_manager_setting->id,
                        'is_active' => 0,
                    ]);
                }
            }
        }
        return redirect()->route('staff.index')->with('success', 'Cập nhật tài khoản nhân viên thành công!');
    }
}
