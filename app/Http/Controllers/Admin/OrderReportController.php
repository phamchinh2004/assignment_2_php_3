<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PrepareOrder;
use App\Models\OrderReport;
use App\Models\OrderStatusTiming;
use App\Models\Status;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $reports = OrderReport::query()
            ->when($status, fn($q) => $q->where('status', $status))
            ->with([
                'frozenOrder.order.partner',
                'frozenOrder.user',
                'reporter',
                'resolver',
            ])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.order_reports.index', compact('reports', 'status'));
    }

    public function show(OrderReport $orderReport)
    {
        $orderReport->load([
            'frozenOrder.order.partner',
            'frozenOrder.user',
            'reporter',
            'resolver',
        ]);

        $frozenOrder = $orderReport->frozenOrder;
        
        // Load lịch sử thay đổi trạng thái
        $statusHistory = [];
        $allStatusesWithHistory = [];
        $currentStatus = null;
        
        if ($frozenOrder) {
            // Load status orders với relationships
            $frozenOrder->load([
                'statusOrders.status',
                'statusOrders.changedBy'
            ]);
            
            // Lấy lịch sử thay đổi trạng thái
            $statusHistory = OrderStatusService::getStatusHistory($frozenOrder->id);
            
            // Lấy tất cả các trạng thái theo thứ tự
            $allStatuses = Status::active()
                ->ordered()
                ->get();
            
            // Tạo map để dễ dàng tìm statusOrder theo status_id
            $statusHistoryMap = [];
            foreach ($statusHistory as $statusOrder) {
                $statusHistoryMap[$statusOrder->status_id] = $statusOrder;
            }
            
            // Tạo danh sách trạng thái đầy đủ với thông tin đã đạt đến hay chưa
            foreach ($allStatuses as $status) {
                $allStatusesWithHistory[] = [
                    'status' => $status,
                    'statusOrder' => $statusHistoryMap[$status->id] ?? null,
                    'isReached' => isset($statusHistoryMap[$status->id]),
                    'isSpecial' => false,
                ];
                
                // Thêm mục "Đã cộng tiền" ngay sau trạng thái "completed"
                if ($status->name === 'completed') {
                    $isCompleted = $frozenOrder->status === 'completed';
                    $isCommissionPaid = ($frozenOrder->commission_paid ?? false) && $isCompleted;
                    
                    $allStatusesWithHistory[] = [
                        'status' => null,
                        'statusOrder' => null,
                        'isReached' => $isCommissionPaid,
                        'isSpecial' => true,
                        'specialType' => 'commission_paid',
                        'commissionPaid' => $frozenOrder->commission_paid ?? false,
                        'isOrderCompleted' => $isCompleted,
                    ];
                }
            }
            
            // Lấy trạng thái hiện tại với màu sắc
            if ($frozenOrder->status) {
                $currentStatus = Status::where('name', $frozenOrder->status)->first();
            }
        }

        return view('admin.order_reports.show', compact(
            'orderReport', 
            'frozenOrder', 
            'statusHistory', 
            'currentStatus', 
            'allStatusesWithHistory'
        ));
    }

    /**
     * Admin xác nhận đơn hàng (bác báo cáo - đơn thật)
     * => report: rejected, frozen_order: confirmed
     */
    public function confirm(Request $request, OrderReport $orderReport)
    {
        if ($orderReport->status !== 'pending') {
            return redirect()->back()->with('error', 'Báo cáo này đã được xử lý trước đó.');
        }

        $frozenOrder = $orderReport->frozenOrder;
        if (!$frozenOrder) {
            return redirect()->back()->with('error', 'Không tìm thấy frozen order của báo cáo.');
        }

        $currentStatus = $frozenOrder->status;
        if ($currentStatus && $currentStatus !== 'pending') {
            return redirect()->back()->with('error', 'Đơn hàng không còn ở trạng thái chờ xử lý.');
        }

        // Chuyển trạng thái confirmed
        $success = OrderStatusService::changeStatus(
            $frozenOrder,
            'confirmed',
            'Admin xác nhận đơn hàng sau khi nhân viên báo cáo đơn hàng',
            Auth::id()
        );

        if (!$success) {
            return redirect()->back()->with('error', 'Không thể xác nhận đơn hàng. Vui lòng thử lại!');
        }

        // Đổi is_frozen = 0 như flow confirm của user
        $frozenOrder->is_frozen = 0;
        $frozenOrder->save();

        // Dispatch job chuyển trạng thái tiếp theo (giống confirm_order)
        $timing = OrderStatusTiming::getTiming('confirmed', 'preparing');
        if ($timing && $timing->is_active) {
            $minMinutes = $timing->getMinTimeInMinutes();
            $maxMinutes = $timing->getMaxTimeInMinutes();
            $delayMinutes = rand($minMinutes, $maxMinutes);
        } else {
            $delayMinutes = rand(5, 10);
        }

        try {
            PrepareOrder::dispatch($frozenOrder->id)->delay(now()->addMinutes($delayMinutes));
        } catch (\Exception $e) {
            \Log::warning('Không thể dispatch job PrepareOrder (admin confirm)', [
                'frozen_order_id' => $frozenOrder->id,
                'error' => $e->getMessage(),
            ]);
        }

        $orderReport->status = 'rejected';
        $orderReport->resolved_by = Auth::id();
        $orderReport->resolved_note = $request->input('resolved_note');
        $orderReport->resolved_at = now();
        $orderReport->save();

        return redirect()->route('order_reports.show', $orderReport)->with('success', 'Đã xác nhận đơn hàng (bác báo cáo).');
    }

    /**
     * Admin hủy đơn hàng (xác nhận báo cáo đúng - đơn ảo)
     * => report: approved, frozen_order: cancelled
     */
    public function cancel(Request $request, OrderReport $orderReport)
    {
        if ($orderReport->status !== 'pending') {
            return redirect()->back()->with('error', 'Báo cáo này đã được xử lý trước đó.');
        }

        $frozenOrder = $orderReport->frozenOrder;
        if (!$frozenOrder) {
            return redirect()->back()->with('error', 'Không tìm thấy frozen order của báo cáo.');
        }

        if ($frozenOrder->status !== 'pending') {
            return redirect()->back()->with('error', 'Chỉ có thể hủy đơn hàng khi đang ở trạng thái chờ xử lý.');
        }

        $success = OrderStatusService::changeStatus(
            $frozenOrder,
            'cancelled',
            'Admin hủy đơn hàng sau khi nhân viên báo cáo đơn hàng',
            Auth::id()
        );

        if (!$success) {
            return redirect()->back()->with('error', 'Không thể hủy đơn hàng. Vui lòng thử lại!');
        }

        $frozenOrder->is_frozen = 0;
        $frozenOrder->save();

        $orderReport->status = 'approved';
        $orderReport->resolved_by = Auth::id();
        $orderReport->resolved_note = $request->input('resolved_note');
        $orderReport->resolved_at = now();
        $orderReport->save();

        return redirect()->route('order_reports.show', $orderReport)->with('success', 'Đã hủy đơn hàng (xác nhận báo cáo đúng).');
    }
}


