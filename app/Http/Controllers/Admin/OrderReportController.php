<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PrepareOrder;
use App\Models\OrderReport;
use App\Models\OrderStatusTiming;
use App\Models\Status;
use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\OrderStatusService;
use App\Services\ReactPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrderReportController extends Controller
{
    public function __construct(
        private readonly ReactPageService $reactPage,
        private readonly AuthorizationService $authorization,
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        $status = $request->input('status', 'pending');

        $reportsQuery = OrderReport::query()
            ->when($status, fn($q) => $q->where('status', $status));
        $this->scopeToVisibleCustomers($reportsQuery, $request->user());

        $reports = $reportsQuery
            ->with([
                'frozenOrder.order.partner',
                'frozenOrder.user',
                'reporter',
                'resolver',
            ])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $reports->getCollection()->each(function (OrderReport $report) {
            $report->setAttribute('order_code', $report->frozenOrder?->display_order_code ?? 'N/A');
        });

        return $this->reactPage->admin('admin.order-reports.index', [
            'reports' => $reports,
            'status' => $status,
            'routes' => [
                'index' => route('order_reports.index'),
                'show' => route('order_reports.show', ['orderReport' => '__REPORT_ID__']),
            ],
            'permissions' => [
                'viewDetail' => $this->authorization->can($request->user(), config('authorization.capabilities.order_reports_view_detail')),
            ],
        ], 'Đơn hàng bị báo cáo');
    }

    public function show(OrderReport $orderReport): View|JsonResponse
    {
        $this->authorizeReportAccess($orderReport);
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
                    'isHighValueOrder' => false,
                ];
                
                // Thêm mục "Đã cộng tiền" ngay sau trạng thái "completed"
                if ($status->name === 'completed') {
                    $isCompleted = $frozenOrder->status === 'completed';
                    $isCommissionPaid = ($frozenOrder->commission_paid ?? false) && $isCompleted;
                    
                    $allStatusesWithHistory[] = [
                        'status' => null,
                        'statusOrder' => null,
                        'isReached' => $isCommissionPaid,
                        'isHighValueOrder' => true,
                        'highValueOrderType' => 'commission_paid',
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

        $frozenDisplay = null;
        if ($frozenOrder) {
            $frozenDisplay = [
                'order_code' => $frozenOrder->display_order_code,
                'uses_snapshot_fallback' => (bool) $frozenOrder->uses_snapshot_fallback,
                'name' => $frozenOrder->display_name,
                'image_url' => $frozenOrder->display_image ? Storage::url($frozenOrder->display_image) : null,
                'unit_price' => $frozenOrder->display_unit_price,
                'quantity' => $frozenOrder->display_quantity,
                'commission_percentage' => $frozenOrder->display_commission_percentage,
                'order_amount' => $frozenOrder->display_order_amount,
                'commission_amount' => $frozenOrder->display_commission_amount,
                'penalty_amount' => $frozenOrder->penalty_amount,
                'custom_price' => $frozenOrder->custom_price,
                'customer_name' => $frozenOrder->display_customer_name,
                'customer_phone' => $frozenOrder->display_customer_phone,
                'customer_address' => $frozenOrder->display_customer_address,
                'customer_note' => $frozenOrder->display_customer_note,
                'partner_name' => $frozenOrder->display_partner_name,
                'order_date' => $frozenOrder->order_date,
                'payment_method' => $frozenOrder->display_payment_method,
                'is_paid' => (bool) $frozenOrder->display_is_paid,
                'tracking_number' => $frozenOrder->tracking_number,
                'shipping_carrier' => $frozenOrder->shipping_carrier,
                'api' => $frozenOrder->display_api,
                'status' => $frozenOrder->status,
            ];
        }

        $user = auth()->user();

        return $this->reactPage->admin('admin.order-reports.show', [
            'orderReport' => $orderReport,
            'frozenOrder' => $frozenOrder,
            'frozenDisplay' => $frozenDisplay,
            'currentStatus' => $currentStatus,
            'timeline' => $allStatusesWithHistory,
            'routes' => [
                'index' => route('order_reports.index'),
                'confirm' => route('order_reports.confirm', $orderReport),
                'cancel' => route('order_reports.cancel', $orderReport),
            ],
            'permissions' => [
                'confirm' => $this->authorization->can($user, config('authorization.capabilities.order_reports_confirm')),
                'cancel' => $this->authorization->can($user, config('authorization.capabilities.order_reports_cancel')),
            ],
        ], 'Chi tiết báo cáo đơn hàng');
    }

    /**
     * Admin xác nhận đơn hàng (bác báo cáo - đơn thật)
     * => report: rejected, frozen_order: confirmed
     */
    public function confirm(Request $request, OrderReport $orderReport)
    {
        $this->authorizeReportAccess($orderReport);
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
        $this->authorizeReportAccess($orderReport);
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

    private function scopeToVisibleCustomers($query, User $actor)
    {
        if ($actor->role === User::ROLE_OWNER) {
            return $query;
        }

        return $query->whereHas(
            'frozenOrder.user',
            fn ($userQuery) => $userQuery->visibleCustomersTo($actor)
        );
    }

    private function authorizeReportAccess(OrderReport $orderReport): void
    {
        $actor = Auth::user();
        if ($actor->role === User::ROLE_OWNER) {
            return;
        }

        $orderReport->loadMissing('frozenOrder.user');
        $customer = $orderReport->frozenOrder?->user;
        abort_unless($customer && $actor->canAccessCustomer($customer), 403);
    }
}
