<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrozenOrderSetting;
use App\Services\AuthorizationService;
use App\Services\ReactPageService;
use Illuminate\Http\Request;

class FrozenOrderSettingController extends Controller
{
    public function __construct(private readonly ReactPageService $reactPage, private readonly AuthorizationService $authorization)
    {
    }

    public function index()
    {
        $settings = FrozenOrderSetting::query()->first() ?? FrozenOrderSetting::defaults();

        return $this->reactPage->admin('admin.frozen-order-settings.index', [
            'settings' => $settings,
            'routes' => ['store' => route('frozen_order_settings.store')],
            'permissions' => [
                'update' => $this->authorization->can(auth()->user(), config('authorization.capabilities.frozen_order_settings_update')),
            ],
        ], 'Cấu hình Frozen Order');
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
