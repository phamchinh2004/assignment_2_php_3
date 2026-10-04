<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Frozen_order;
use App\Models\OrderReport;
use App\Models\Status;
use App\Models\Transaction_history;
use App\Models\User;
use App\Services\OrderStatusService;
use App\Services\FrozenOrderSettlementService;
use App\Services\OverdueOrderPenaltyService;
use App\Services\ApproximateLocationService;
use App\Services\ReactPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        private readonly FrozenOrderSettlementService $settlementService,
        private readonly OverdueOrderPenaltyService $overduePenaltyService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ReactPageService $reactPageService)
    {
        $user = User::find(Auth::user()->id);
        return $reactPageService->user('user.order', [
            'userBalance' => (float) ($user->balance ?? 0),
            'userBalanceFormatted' => format_money($user->balance ?? 0, 7),
            'routes' => [
                'list' => route('get_list_orders_by_tab'),
                'order' => route('order'),
                'distribution' => route('distribution'),
            ],
            'labels' => [
                'empty' => __('order.KhongCoDuLieu'),
                'noData' => __('order.KhongTimThayDuLieuDonHang'),
                'time' => __('order.ThoiGianDatPhanPhoi'),
                'orderCode' => __('order.MaDonHang'),
                'orderTotal' => __('order.TongTienDonHang'),
                'commission' => __('order.ChietKhau'),
                'refund' => __('order.SoTienHoanNhap'),
                'historyTitle' => __('order.LichSuPhanPhoi'),
                'currentBalance' => __('order.SoDuHienTai'),
                'description' => __('order.DuLieuNayDuocCungCap'),
                'all' => __('order.TatCa'),
                'pending' => __('order.ChoXuLy'),
                'confirmed' => __('order.DaXacNhan'),
                'preparing' => __('order.DangChuanBi'),
                'transit' => __('order.DangTrungChuyen'),
                'shipping' => __('order.DangVanChuyen'),
                'delivered' => __('order.DaGiaoHang'),
                'completed' => __('order.HoanThanh'),
                'cancelled' => __('order.DaHuy'),
                'highValue' => __('order.GiaTriCao'),
            ],
        ]);
    }
    public function get_list_orders_by_tab()
    {
        $tab = request()->input('tabId');

        // Safety net: keep overdue state correct even when the scheduler is temporarily down.
        $this->overduePenaltyService->applyPendingForUser((int) Auth::id());

        // Bắt đầu từ bảng frozen_orders
        $query = Frozen_order::query()
            ->where('frozen_orders.user_id', Auth::id())
            ->where('frozen_orders.spun', true);

        // Lọc theo từng tab với logic mới
        if ($tab === "btn_cho_xu_ly") {
            // Chờ xử lý: pending
            $query->where(function ($q) {
                $q->where('frozen_orders.status', 'pending')
                    ->orWhere(function ($q2) {
                        // Giữ lại logic cũ cho đơn hàng chưa có status (backward compatibility)
                        $q2->whereNull('frozen_orders.status')
                            ->where('frozen_orders.is_frozen', 1)
                            ->whereNull('frozen_orders.custom_price');
                    });
            });
        } elseif ($tab === "btn_da_xac_nhan") {
            // Đã xác nhận: confirmed
            $query->where('frozen_orders.status', 'confirmed');
        } elseif ($tab === "btn_dang_chuan_bi") {
            // Đang chuẩn bị: preparing
            $query->where('frozen_orders.status', 'preparing');
        } elseif ($tab === "btn_dang_trung_chuyen") {
            // Đang trung chuyển: transit
            $query->where('frozen_orders.status', 'transit');
        } elseif ($tab === "btn_dang_van_chuyen") {
            // Đang vận chuyển: shipping
            $query->where('frozen_orders.status', 'shipping');
        } elseif ($tab === "btn_da_giao_hang") {
            // Đã giao hàng: delivered
            $query->where('frozen_orders.status', 'delivered');
        } elseif ($tab === "btn_hoan_thanh") {
            // Hoàn thành: completed
            $query->where('frozen_orders.status', 'completed');
        } elseif ($tab === "btn_da_huy") {
            // Đã hủy: cancelled
            $query->where('frozen_orders.status', 'cancelled');
        } elseif ($tab === "btn_dong_bang") {
            // Đóng băng: đơn hàng giá trị cao (custom_price) chưa hoàn thành
            $query->where('frozen_orders.is_frozen', 1)
                ->whereNotNull('frozen_orders.custom_price')
                ->whereNotIn('frozen_orders.status', ['completed', 'cancelled']);
        } elseif ($tab === "btn_bi_phat") {
            $query->whereNotNull('frozen_orders.penalty_amount')
                ->where('frozen_orders.penalty_amount', '>', 0);
        }

        // Sắp xếp theo index trong bảng orders
        $list_orders = $query
            ->orderBy('frozen_orders.id', 'desc')
            ->select('frozen_orders.*') // chỉ lấy dữ liệu từ frozen_orders
            ->with('order.partner')
            ->get();

        $penalizedOrders = $list_orders->filter(fn (Frozen_order $frozenOrder) => (float) $frozenOrder->penalty_amount > 0);
        if ($penalizedOrders->isNotEmpty()) {
            $notes = $penalizedOrders
                ->flatMap(fn (Frozen_order $frozenOrder) => $this->settlementService->possibleNotes($frozenOrder))
                ->unique()
                ->values();
            $transactions = Transaction_history::query()
                ->where('user_id', Auth::id())
                ->whereIn('note', $notes)
                ->whereIn('type', ['order', 'profit', 'penalty'])
                ->get();

            $penalizedOrders->each(function (Frozen_order $frozenOrder) use ($transactions) {
                $orderNotes = $this->settlementService->possibleNotes($frozenOrder);
                $frozenOrder->setAttribute(
                    'penalty_settlement',
                    $this->settlementService->resolve(
                        $frozenOrder,
                        $transactions->whereIn('note', $orderNotes)
                    )
                );
            });
        }

        if (!$list_orders) {
            return response()->json([
                'status' => 400,
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Lấy danh sách đơn hàng theo tab thành công!',
                'list_orders' => $list_orders
            ]);
        }
    }
    /**
     * Nhận đơn hàng (thay thế handle_distribution)
     * Chỉ redirect đến trang order, không thay đổi status
     */
    public function handle_accept_order(Request $request, ApproximateLocationService $approximateLocationService)
    {
        $frozen_id = $request->input('frozen_id');
        $user = Auth::user();

        $hasPreciseLocation = $user->location_permission === 'granted'
            && $user->location_latitude !== null
            && $user->location_longitude !== null;

        if (!$hasPreciseLocation) {
            $approximateLocationService->refresh($user, $request);
            $user->refresh();
        }

        $get_frozen_order = Frozen_order::with('order')->find($frozen_id);

        if (!$get_frozen_order) {
            return response()->json([
                'status' => 400,
                'message' => __('order.KhongTimThayLichSuDatHang'),
            ]);
        }

        // Kiểm tra quyền truy cập
        if ($get_frozen_order->user_id !== $user->id) {
            return response()->json([
                'status' => 403,
                'message' => 'Bạn không có quyền truy cập đơn hàng này',
            ]);
        }

        // Kiểm tra trạng thái
        if ($get_frozen_order->status && $get_frozen_order->status !== 'pending') {
            return response()->json([
                'status' => 409,
                'message' => 'Đơn hàng đã được xử lý. Vui lòng kiểm tra trong danh sách đơn hàng!',
            ]);
        }

        // Chỉ redirect đến trang chi tiết đơn hàng, không kiểm tra số dư ở đây
        // Kiểm tra số dư sẽ được thực hiện khi bấm "Xác nhận đơn hàng"
        // Sử dụng đường dẫn tuyệt đối để tránh xung đột với route admin
        return response()->json([
            'status' => 200,
            'message' => 'Đã nhận đơn hàng thành công!',
            'redirect' => url('/order/' . $frozen_id)
        ]);
    }

    /**
     * Hiển thị trang chi tiết đơn hàng
     */
    public function show(Frozen_order $frozen_order, ReactPageService $reactPageService)
    {
        // Kiểm tra quyền truy cập
        if ($frozen_order->user_id !== Auth::id()) {
            abort(403, 'Bạn không có quyền truy cập đơn hàng này');
        }

        $this->overduePenaltyService->applyIfOverdue($frozen_order);

        // Load đầy đủ thông tin
        $frozen_order->load([
            'order.partner',
            'statusOrders.status',
            'statusOrders.changedBy',
            'orderReport.reporter',
            'orderReport.resolver',
        ]);

        // Lấy lịch sử thay đổi trạng thái
        $statusHistory = \App\Services\OrderStatusService::getStatusHistory($frozen_order->id);

        // Lấy tất cả các trạng thái theo thứ tự (trừ cancelled nếu đơn chưa bị hủy)
        $allStatuses = Status::active()
            ->ordered()
            ->get();
        
        // Tạo map để dễ dàng tìm statusOrder theo status_id
        $statusHistoryMap = [];
        foreach ($statusHistory as $statusOrder) {
            $statusHistoryMap[$statusOrder->status_id] = $statusOrder;
        }
        
        // Tạo danh sách trạng thái đầy đủ với thông tin đã đạt đến hay chưa
        $allStatusesWithHistory = [];
        foreach ($allStatuses as $status) {
            $allStatusesWithHistory[] = [
                'status' => $status,
                'statusOrder' => $statusHistoryMap[$status->id] ?? null, // null nếu chưa đạt đến
                'isReached' => isset($statusHistoryMap[$status->id]), // true nếu đã đạt đến
                'isHighValueOrder' => false, // Đánh dấu đây là trạng thái bình thường
            ];
            
            // Thêm mục "Đã cộng tiền" ngay sau trạng thái "completed"
            if ($status->name === 'completed') {
                // Luôn hiển thị mục "Đã cộng tiền" sau trạng thái completed
                // Nếu đơn hàng chưa completed, hiển thị "Chưa cộng tiền"
                // Nếu đơn hàng đã completed và đã cộng tiền, hiển thị "Đã cộng tiền"
                $isCompleted = $frozen_order->status === 'completed';
                $isCommissionPaid = ($frozen_order->commission_paid ?? false) && $isCompleted;
                
                $allStatusesWithHistory[] = [
                    'status' => null, // Không phải trạng thái thực sự
                    'statusOrder' => null,
                    'isReached' => $isCommissionPaid, // true nếu đã completed và đã cộng tiền
                    'isHighValueOrder' => true, // Đánh dấu đây là mục HVO
                    'highValueOrderType' => 'commission_paid', // Loại mục HVO
                    'commissionPaid' => $frozen_order->commission_paid ?? false,
                    'isOrderCompleted' => $isCompleted, // Đánh dấu đơn hàng đã completed chưa
                ];
            }
        }

        // Lấy trạng thái hiện tại với màu sắc
        $currentStatus = null;
        if ($frozen_order->status) {
            $currentStatus = Status::where('name', $frozen_order->status)->first();
        }

        $currentBalance = (float) Auth::user()->balance;
        $apiUrl = $frozen_order->snapshot_api
            ? rtrim(config('app.url'), '/') . '/order?api_key=' . urlencode($frozen_order->snapshot_api)
            : null;
        $financial = $this->settlementService->detail($frozen_order);
        $cancellation = $statusHistory
            ->first(fn ($item) => in_array($item->status?->name, ['cancelled', 'canceled'], true));

        $viewData = compact(
            'frozen_order',
            'statusHistory',
            'currentStatus',
            'allStatusesWithHistory',
            'currentBalance',
            'apiUrl',
            'financial',
            'cancellation'
        );

        return $reactPageService->user('user.order_detail', [
            'legacyPage' => 'order_detail',
            'html' => view('user.partials.order_detail-content', $viewData)->render(),
            'globals' => [
                'orderDetailPageConfig' => [
                    'trans' => [
                        'XacNhanDonHang' => 'Xác nhận đơn hàng',
                        'HuyDonHang' => 'Hủy đơn hàng',
                        'ThanhCong' => 'Thành công',
                        'Loi' => 'Lỗi',
                        'CanhBao' => 'Cảnh báo',
                    ],
                    'routes' => [
                        'confirm' => route('order.confirm', $frozen_order->id),
                        'cancel' => route('order.cancel', $frozen_order->id),
                        'report' => route('order.report', $frozen_order->id),
                        'order' => route('order'),
                        'distribution' => route('distribution'),
                    ],
                    'csrf' => csrf_token(),
                    'frozen_order_id' => $frozen_order->id,
                ],
            ],
        ]);
    }

    /**
     * Xác nhận đơn hàng
     */
    public function confirm_order(Frozen_order $frozen_order, \App\Services\OrderConfirmationService $confirmation)
    {
        return $confirmation->confirm(Auth::user(), $frozen_order, (int) Auth::id());
    }

    /**
     * Hủy đơn hàng
     */
    public function cancel_order(Frozen_order $frozen_order)
    {
        // Kiểm tra quyền truy cập
        if ($frozen_order->user_id !== Auth::id()) {
            return response()->json([
                'status' => 403,
                'message' => 'Bạn không có quyền thực hiện thao tác này'
            ]);
        }

        // Chỉ cho phép hủy khi đang ở trạng thái pending
        if ($frozen_order->status !== 'pending') {
            return response()->json([
                'status' => 400,
                'message' => 'Chỉ có thể hủy đơn hàng khi đang ở trạng thái chờ xử lý'
            ]);
        }

        // Chuyển trạng thái sử dụng OrderStatusService
        $success = OrderStatusService::changeStatus(
            $frozen_order,
            'cancelled',
            'Nhân viên hủy đơn hàng',
            Auth::id()
        );

        if (!$success) {
            return response()->json([
                'status' => 500,
                'message' => 'Không thể hủy đơn hàng. Vui lòng thử lại!'
            ]);
        }

        // Cập nhật is_frozen
        $frozen_order->is_frozen = 0;
        $frozen_order->save();

        Log::info('Đơn hàng đã bị hủy', [
            'frozen_order_id' => $frozen_order->id,
            'order_id' => $frozen_order->order_id
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Đã hủy đơn hàng thành công'
        ]);
    }

    /**
     * Báo cáo đơn hàng (đẩy sang admin để duyệt)
     */
    public function report_order(Request $request, Frozen_order $frozen_order)
    {
        // Kiểm tra quyền truy cập
        if ($frozen_order->user_id !== Auth::id()) {
            return response()->json([
                'status' => 403,
                'message' => 'Bạn không có quyền thực hiện thao tác này'
            ]);
        }

        // Chỉ cho phép báo cáo khi đang ở trạng thái pending (hoặc null để tương thích)
        $currentStatus = $frozen_order->status;
        if ($currentStatus && $currentStatus !== 'pending') {
            return response()->json([
                'status' => 400,
                'message' => 'Chỉ có thể báo cáo khi đơn đang ở trạng thái chờ xử lý'
            ]);
        }

        // Không cho báo cáo đơn hàng giá trị cao (đang dùng flow liên hệ CSKH)
        if ($frozen_order->custom_price != null) {
            return response()->json([
                'status' => 400,
                'message' => 'Đơn hàng giá trị cao không thể báo cáo. Vui lòng liên hệ CSKH.'
            ]);
        }

        // Chặn báo cáo trùng
        $existing = OrderReport::where('frozen_order_id', $frozen_order->id)->first();
        if ($existing) {
            return response()->json([
                'status' => 400,
                'message' => 'Đơn hàng này đã được báo cáo và đang chờ admin xử lý.'
            ]);
        }

        $reason = $request->input('reason');

        OrderReport::create([
            'frozen_order_id' => $frozen_order->id,
            'order_id' => $frozen_order->order_id,
            'reported_by' => Auth::id(),
            'reason' => $reason,
            'status' => 'pending',
        ]);

        Log::info('Báo cáo đơn hàng', [
            'frozen_order_id' => $frozen_order->id,
            'order_id' => $frozen_order->order_id,
            'reported_by' => Auth::id(),
            'reason' => $reason,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Đã gửi báo cáo đơn hàng. Admin sẽ kiểm tra và xử lý.',
        ]);
    }
}
