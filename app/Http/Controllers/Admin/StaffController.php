<?php

namespace App\Http\Controllers\Admin;

use App\Events\PermissionRevoked;
use App\Events\StaffLocked;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Manager_setting;
use App\Models\User;
use App\Models\User_manager_setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_staffs = User::with('referrer')
            ->withSum(['deposits_made as total_deposit' => function ($q) {
                $q->where('type', 'deposit')
                    ->where('status', 'completed');
                // ->where('by_user_id', Auth::user()->id);
            }], 'value')
            ->where('role', 'staff')
            ->get();

        return view('admin.staff.index', compact('list_staffs'));
    }
    public function change_status_staff($staff_id)
    {
        $message = "";
        $user = User::find($staff_id);
        if ($user) {
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
    public function edit_permissions($staff_id)
    {
        $get_user = User::find($staff_id);
        if (!$get_user) {
            return back()->with('error', 'Người dùng không xác định!');
        }
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
        return view('admin.staff.edit_permission', compact('list_manager_settings', 'get_user_manager_setting', 'get_user'));
    }
    public function change_status_permission()
    {
        $id = request()->input('id');
        $get_user_manager_setting = User_manager_setting::find($id);
        if (!$get_user_manager_setting) {
            return response()->json([
                'status' => 400,
                'message' => 'Không tìm thấy quyền hạn này!'
            ]);
        }
        $get_user_manager_setting->is_active = !$get_user_manager_setting->is_active;
        $get_user_manager_setting->save();
        if (!$get_user_manager_setting->is_active) {
            $managerSetting = $get_user_manager_setting->manager_setting;
            event(new PermissionRevoked($get_user_manager_setting->user_id, $managerSetting->manager_code));
        }
        return response()->json([
            'status' => 200,
            'message' => 'Chỉnh sửa quyền hạn thành công!'
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.staff.create');
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
    public function store(Request $request)
    {
        $data = $request->only(['full_name', 'username', 'phone']);
        if (User::where('username', $data['username'])->exists()) {
            return back()->withErrors(['username' => 'Tên đăng nhập đã tồn tại!'])->withInput();
        }
        if (User::where('phone', $data['phone'])->exists()) {
            return back()->withErrors(['username' => 'Số điện thoại đã tồn tại!'])->withInput();
        }
        if ($request->password != "") {
            if ($request->password >= 6) {
                $data['password'] = $request->password;
            } else {
                return back()->withErrors(['password' => 'Mật khẩu phải lớn hơn hoặc bằng 6 ký tự!'])->withInput();
            }
        } else {
            $data['password'] = '123456';
        }
        $data['password'] = Hash::make($data['password']);
        $data['referrer_id'] = Auth::user()->id;
        $data['status'] = "activated";
        $data['role'] = "staff";
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
        $get_staff_old = User::find($id);
        if (!$get_staff_old) {
            return back()->with('error', 'Người dùng không xác định!');
        }

        return view('admin.staff.edit', compact('get_staff_old'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $get_user = User::find($id);
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
