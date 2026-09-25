<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManager_settingRequest;
use App\Http\Requests\UpdateManager_settingRequest;
use App\Models\Manager_setting;
use App\Services\PermissionRegistry;
use Illuminate\Support\Facades\DB;
use Str;

class ManagerSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissionGroups = app(PermissionRegistry::class)->settingGroups();
        $totalSettings = Manager_setting::count();

        return view('admin.manager_settings.index', compact('permissionGroups', 'totalSettings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parents = Manager_setting::whereNull('parent_manager_setting_id')->orderBy('id')->get();

        return view('admin.manager_settings.create', compact('parents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreManager_settingRequest $request)
    {
        $manager_name = $request->manager_name;
        $manager_code = Str::slug($manager_name, '_');
        $check_existed = Manager_setting::where('manager_code', $manager_code)->first();
        if ($check_existed) {
            return back()->with('error', 'Mã chức năng này đã tồn tại!');
        }
        DB::transaction(function () use ($request, $manager_name, $manager_code) {
            Manager_setting::orderBy('id')->lockForUpdate()->get();
            $request->validate($request->rules());
            Manager_setting::create([
                'manager_name' => $manager_name,
                'manager_code' => $manager_code,
                'parent_manager_setting_id' => $request->input('parent_manager_setting_id'),
            ]);
        });

        return redirect()->route('manager_setting.index')->with('success', 'Tạo mới chức năng thành công!');
    }

    public function show(Manager_setting $manager_setting)
    {
        $users_with_permission = \App\Models\User_manager_setting::with('user')
            ->where('manager_setting_id', $manager_setting->id)
            ->where('is_active', true)
            ->get();

        return view('admin.manager_settings.show', compact('manager_setting', 'users_with_permission'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Manager_setting $manager_setting)
    {
        $parents = Manager_setting::whereNull('parent_manager_setting_id')->where('id', '!=', $manager_setting->id)->orderBy('id')->get();
        if ($manager_setting->children()->exists()) {
            $parents = collect();
        }

        return view('admin.manager_settings.edit', compact('manager_setting', 'parents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateManager_settingRequest $request, Manager_setting $manager_setting)
    {
        if (! $manager_setting) {
            return back()->with('error', 'Chức năng này không tồn tại!');
        }
        DB::transaction(function () use ($request, $manager_setting) {
            Manager_setting::orderBy('id')->lockForUpdate()->get();
            $request->validate($request->rules());
            $manager_setting->parent_manager_setting_id = $request->input('parent_manager_setting_id', $manager_setting->parent_manager_setting_id);
            $new_manager_name = $request->manager_name;
            $manager_setting->manager_name = $new_manager_name;
            $manager_setting->save();
        });

        return redirect()->route('manager_setting.index')->with('success', 'Cập nhật chức năng thành công!');
    }

    public function destroy(Manager_setting $manager_setting)
    {
        return DB::transaction(function () use ($manager_setting) {
            Manager_setting::orderBy('id')->lockForUpdate()->get();
            if ($manager_setting->children()->exists()) {
                return back()->with('error', 'Không thể xóa chức năng này vì vẫn còn chức năng con.');
            }
            $manager_setting->user_manager_settings()->delete();
            $manager_setting->delete();

            return redirect()->route('manager_setting.index')->with('success', 'Đã xóa chức năng.');
        });
    }
}
