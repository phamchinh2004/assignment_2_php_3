<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrozenOrderSetting;
use Illuminate\Http\Request;

class FrozenOrderSettingController extends Controller
{
    public function index()
    {
        $settings = FrozenOrderSetting::query()->first() ?? FrozenOrderSetting::defaults();

        return view('admin.frozen_order_settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'processing_time_limit' => 'required|integer|min:1',
            'notification_1_remaining_time' => 'required|integer|min:1',
            'notification_2_remaining_time' => 'required|integer|min:1',
        ]);

        if ($request->notification_1_remaining_time <= $request->notification_2_remaining_time) {
            return back()->with('error', 'Thời gian cảnh báo lần 1 phải lớn hơn cảnh báo lần 2.');
        }

        if ($request->processing_time_limit <= $request->notification_1_remaining_time) {
            return back()->with('error', 'Thời hạn xử lý phải lớn hơn thời gian cảnh báo lần 1.');
        }

        $settings = FrozenOrderSetting::query()->first() ?? new FrozenOrderSetting();
        $settings->fill($request->only([
            'processing_time_limit',
            'notification_1_remaining_time',
            'notification_2_remaining_time',
        ]));
        $settings->save();

        return redirect()->route('frozen_order_settings.index')->with('success', 'Cập nhật cấu hình Frozen Order thành công!');
    }
}
