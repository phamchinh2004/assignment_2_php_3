<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manager_setting;
use App\Http\Requests\StoreManager_settingRequest;
use App\Http\Requests\UpdateManager_settingRequest;
use Illuminate\Database\Capsule\Manager;
use Str;

class ManagerSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list_manager_settings = Manager_setting::where('parent_manager_setting_id', null)->get();
        return view('admin.manager_settings.index', compact('list_manager_settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.manager_settings.create');
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
        Manager_setting::create([
            'manager_name' => $manager_name,
            'manager_code' => $manager_code,
        ]);
        return redirect()->route('manager_setting.index')->with('success', 'Tạo mới chức năng thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Manager_setting $manager_setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Manager_setting $manager_setting)
    {
        return view('admin.manager_settings.edit', compact('manager_setting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateManager_settingRequest $request, Manager_setting $manager_setting)
    {
        if (!$manager_setting) {
            return back()->with('error', 'Chức năng này không tồn tại!');
        }
        $new_manager_name = $request->manager_name;
        $new_manager_code = Str::slug($request->manager_code, '_');
        $check_existed = Manager_setting::where('manager_code', $new_manager_code)->where('manager_code', '!=', $manager_setting->manager_code)->first();
        if ($check_existed) {
            return back()->with('error', 'Mã chức năng này đã tồn tại!');
        }
        $manager_setting->manager_name = $new_manager_name;
        $manager_setting->manager_code = $new_manager_code;
        $manager_setting->save();
        return redirect()->route('manager_setting.index')->with('success', 'Cập nhật chức năng thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Manager_setting $manager_setting)
    {
        //
    }
}
