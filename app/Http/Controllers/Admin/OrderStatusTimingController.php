<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatusTiming;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderStatusTimingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $timings = OrderStatusTiming::orderBy('id')->get();
        } catch (\Exception $e) {
            // Nếu bảng chưa tồn tại, trả về collection rỗng
            Log::warning('Bảng order_status_timings chưa tồn tại. Vui lòng chạy migration.', [
                'error' => $e->getMessage()
            ]);
            $timings = collect([]);
        }
        
        return view('admin.order_status_timing.index', compact('timings'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderStatusTiming $orderStatusTiming)
    {
        return view('admin.order_status_timing.edit', compact('orderStatusTiming'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderStatusTiming $orderStatusTiming)
    {
        $request->validate([
            'min_time' => 'required|integer|min:0',
            'max_time' => 'required|integer|min:0|gte:min_time',
            'time_unit' => 'required|in:minutes,hours,days',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        try {
            $orderStatusTiming->min_time = $request->min_time;
            $orderStatusTiming->max_time = $request->max_time;
            $orderStatusTiming->time_unit = $request->time_unit;
            $orderStatusTiming->description = $request->description;
            $orderStatusTiming->is_active = $request->has('is_active') ? 1 : 0;
            $orderStatusTiming->save();

            Log::info('Admin đã cập nhật cấu hình thời gian chuyển trạng thái', [
                'timing_id' => $orderStatusTiming->id,
                'from_status' => $orderStatusTiming->from_status,
                'to_status' => $orderStatusTiming->to_status,
                'min_time' => $orderStatusTiming->min_time,
                'max_time' => $orderStatusTiming->max_time,
                'time_unit' => $orderStatusTiming->time_unit,
            ]);

            return redirect()->route('admin.order_status_timing.index')
                ->with('success', 'Cập nhật cấu hình thời gian thành công!');
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật cấu hình thời gian', [
                'timing_id' => $orderStatusTiming->id,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Update multiple timings at once
     */
    public function updateMultiple(Request $request)
    {
        $request->validate([
            'timings' => 'required|array',
            'timings.*.id' => 'required|exists:order_status_timings,id',
            'timings.*.min_time' => 'required|integer|min:0',
            'timings.*.max_time' => 'required|integer|min:0',
            'timings.*.time_unit' => 'required|in:minutes,hours,days',
            'timings.*.is_active' => 'boolean',
        ]);

        try {
            foreach ($request->timings as $timingData) {
                $timing = OrderStatusTiming::find($timingData['id']);
                if ($timing) {
                    $timing->min_time = $timingData['min_time'];
                    $timing->max_time = $timingData['max_time'];
                    $timing->time_unit = $timingData['time_unit'];
                    $timing->is_active = isset($timingData['is_active']) ? 1 : 0;
                    if (isset($timingData['description'])) {
                        $timing->description = $timingData['description'];
                    }
                    $timing->save();
                }
            }

            Log::info('Admin đã cập nhật nhiều cấu hình thời gian chuyển trạng thái', [
                'count' => count($request->timings)
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Cập nhật thành công!'
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi cập nhật nhiều cấu hình thời gian', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 500,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}
