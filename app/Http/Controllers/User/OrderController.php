<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Frozen_order;
use App\Models\Order;
use App\Models\OrderReport;
use App\Models\Status;
use App\Models\Transaction_history;
use App\Models\User;
use App\Jobs\PrepareOrder;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use function PHPSTORM_META\type;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::find(Auth::user()->id);
        return view('user.order', compact('user'));
    }
    public function get_list_orders_by_tab()
    {
        $tab = request()->input('tabId');

        // Bắt đầu từ bảng frozen_orders
        $query = Frozen_order::query()
            ->join('orders', 'frozen_orders.order_id', '=', 'orders.id')
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
            // Đóng băng: đơn đặc biệt (custom_price) chưa hoàn thành
            $query->where('frozen_orders.is_frozen', 1)
                ->whereNotNull('frozen_orders.custom_price')
                ->whereNotIn('frozen_orders.status', ['completed', 'cancelled']);
        }

        // Sắp xếp theo index trong bảng orders
        $list_orders = $query
            ->orderBy('frozen_orders.id', 'desc')
            ->select('frozen_orders.*') // chỉ lấy dữ liệu từ frozen_orders
            ->with('order') // eager load thông tin đơn hàng
            ->get();

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
    public function handle_so_du($order_id, $total_price, $frozen_id)
    {
        try {
            $user = User::find(Auth::user()->id);
            $user_id = $user->id;
            $frozen_order = Frozen_order::find($frozen_id);
            $order = Order::find($order_id);
            if (!$order) {
                return response()->json([
                    'status' => 404,
                    'message' => __('order.KhongTimThayDonHang'),
                ]);
            }
            // Kiểm tra nếu là đơn đặc biệt
            $is_special_order = $frozen_order->custom_price != null;
            $penalty_amount = $frozen_order->penalty_amount ?? 0;
            $total_required = $is_special_order ? ($frozen_order->custom_price + $penalty_amount) : 0;

            // Nếu là đơn đặc biệt, kiểm tra số dư đủ để xử lý
            if ($is_special_order) {
                if ($user->balance < $total_required) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Số dư không đủ để xử lý đơn hàng đặc biệt. Vui lòng nạp thêm tiền!',
                    ]);
                }

                // Trừ tiền từ balance để xử lý đơn hàng
                $user->balance -= $total_required;

                // Cho phép rút từ frozen_balance (chuyển frozen_balance về balance)
                $user->balance += $user->frozen_balance;
                $user->frozen_balance = 0;
            }

            $frozen_order->is_frozen = 0;
            $frozen_order->save();

            // Tính chiết khấu
            $rose = $total_price * $order->commission_percentage;

            // Trừ tiền phạt nếu có (đã trừ ở trên nếu là đơn đặc biệt)
            if (!$is_special_order) {
                $actual_profit = $rose - $penalty_amount;
            } else {
                // Đối với đơn đặc biệt, profit = rose (đã trừ penalty khi trừ total_required)
                $actual_profit = $rose;
            }

            Log::info('Phân phối đơn hàng', [
                'order_id' => $order_id,
                'total_price' => $total_price,
                'rose' => $rose,
                'penalty_amount' => $penalty_amount,
                'actual_profit' => $actual_profit,
                'is_special_order' => $is_special_order
            ]);

            // Cập nhật balance (cộng hoa hồng)
            $user->balance += $actual_profit;
            $user->todays_discount += $actual_profit;
            $user->save();
            
            // Đánh dấu đã cộng tiền hoa hồng
            $frozen_order->commission_paid = true;
            $frozen_order->save();

            Transaction_history::create([
                'user_id' => $user_id,
                'value' => $total_price,
                'type' => "order",
                'note' => $order->order_code
            ]);
            Transaction_history::create([
                'user_id' => $user_id,
                'value' => $rose,
                'type' => "profit",
                'note' => $order->order_code
            ]);

            // Lưu lịch sử phạt nếu có
            if ($penalty_amount > 0) {
                Transaction_history::create([
                    'user_id' => $user_id,
                    'value' => $penalty_amount, // Lưu số dương
                    'type' => "penalty",
                    'note' => $order->order_code // Giống format với order và profit
                ]);
            }

            // Reload user để lấy thông tin mới nhất
            $user->refresh();

            // Tính chiết khấu hôm nay
            $today_start = \Carbon\Carbon::today();
            $today_end = \Carbon\Carbon::tomorrow();

            $today_profit = Transaction_history::where('user_id', $user_id)
                ->where('type', 'profit')
                ->whereBetween('created_at', [$today_start, $today_end])
                ->sum('value');

            $today_penalty = Transaction_history::where('user_id', $user_id)
                ->where('type', 'penalty')
                ->whereBetween('created_at', [$today_start, $today_end])
                ->sum('value');

            $todays_discount = $today_profit - $today_penalty;

            // Tính số dư đóng băng (nếu có đơn đặc biệt chưa phân phối)
            $frozen_price = 0;
            $frozen_order = Frozen_order::where('user_id', $user_id)
                ->where('custom_price', '!=', null)
                ->where('is_frozen', true)
                ->where('spun', true)
                ->first();

            if ($frozen_order) {
                $penalty_amount_frozen = $frozen_order->penalty_amount ?? 0;
                $total_required = $frozen_order->custom_price + $penalty_amount_frozen;
                $frozen_price = max(0, $total_required - $user->balance);
            }

            return response()->json([
                'status' => 200,
                'message' => __('order.PhanPhoiThanhCong'),
                'balance' => $user->balance,
                'profit' => $actual_profit,
                'total_amount' => $total_price,
                'commission' => $rose,
                'penalty_amount' => $penalty_amount,
                'distribution_today' => $user->distribution_today,
                'todays_discount' => $todays_discount,
                'frozen_price' => $frozen_price
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi phân phối đơn hàng', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Nhận đơn hàng (thay thế handle_distribution)
     * Chỉ redirect đến trang order, không thay đổi status
     */
    public function handle_accept_order()
    {
        $frozen_id = request()->input('frozen_id');
        $user = Auth::user();
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
    public function show(Frozen_order $frozen_order)
    {
        // Kiểm tra quyền truy cập
        if ($frozen_order->user_id !== Auth::id()) {
            abort(403, 'Bạn không có quyền truy cập đơn hàng này');
        }

        // Load đầy đủ thông tin
        $frozen_order->load([
            'order.partner',
            'statusOrders.status',
            'statusOrders.changedBy',
            'orderReport'
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
                'isSpecial' => false, // Đánh dấu đây là trạng thái bình thường
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
                    'isSpecial' => true, // Đánh dấu đây là mục đặc biệt
                    'specialType' => 'commission_paid', // Loại mục đặc biệt
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

        return view('user.order_detail', compact('frozen_order', 'statusHistory', 'currentStatus', 'allStatusesWithHistory'));
    }

    /**
     * Xác nhận đơn hàng
     */
    public function confirm_order(Frozen_order $frozen_order)
    {
        // Kiểm tra quyền truy cập
        if ($frozen_order->user_id !== Auth::id()) {
            return response()->json([
                'status' => 403,
                'message' => 'Bạn không có quyền thực hiện thao tác này'
            ]);
        }

        // Refresh model để lấy status mới nhất từ database
        $frozen_order->refresh();

        // Kiểm tra trạng thái - chỉ cho phép xác nhận khi status là 'pending' hoặc null
        $currentStatus = $frozen_order->status;
        if ($currentStatus && $currentStatus !== 'pending') {
            return response()->json([
                'status' => 400,
                'message' => 'Đơn hàng không thể xác nhận. Trạng thái hiện tại: ' . $currentStatus
            ]);
        }

        // Kiểm tra số dư đủ để xử lý đơn hàng (chỉ kiểm tra khi xác nhận)
        $user = Auth::user();
        $total_price = $frozen_order->custom_price
            ? $frozen_order->custom_price
            : ($frozen_order->order->price * $frozen_order->order->quantity);

        // Nếu là đơn đặc biệt, cần kiểm tra số dư + tiền phạt
        $penalty_amount = $frozen_order->penalty_amount ?? 0;
        $total_required = $total_price + $penalty_amount;

        // Kiểm tra số dư: đơn đặc biệt kiểm tra frozen_balance, đơn thường kiểm tra balance
        if ($frozen_order->custom_price != null) {
            // Đơn đặc biệt: kiểm tra frozen_balance
            $available_balance = $user->frozen_balance ?? 0;
            $balance_type = 'số dư đóng băng';
        } else {
            // Đơn thường: kiểm tra balance
            $available_balance = $user->balance;
            $balance_type = 'số dư';
        }

        if ($total_required > $available_balance) {
            return response()->json([
                'status' => 400,
                'message' => __('order.SoDuKhongDu') . ' Số tiền cần: $' . number_format($total_required, 2) . ', ' . $balance_type . ' hiện tại: $' . number_format($available_balance, 2),
            ]);
        }

        // Lấy thông tin từ order nếu có
        $order = $frozen_order->order;

        // Cập nhật platform từ partner nếu có
        if ($order && $order->partner) {
            $frozen_order->platform = $order->partner->name;
        } elseif (!$frozen_order->platform) {
            // Nếu không có partner, tạo platform giả lập
            $platforms = ['Shopee', 'Lazada', 'TikTok Shop', 'Sendo', 'Tiki', 'Amazon'];
            $frozen_order->platform = $platforms[array_rand($platforms)];
        }

        // Cập nhật order_date nếu chưa có
        if (!$frozen_order->order_date) {
            $frozen_order->order_date = now()->subDays(rand(1, 7));
        }

        // Cập nhật customer_info từ order nếu có
        if ($order && $order->customer_name) {
            $frozen_order->customer_info = [
                'name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'address' => $order->customer_address,
                'note' => $order->customer_note,
            ];
        } elseif (!$frozen_order->customer_info) {
            // Nếu không có thông tin từ order, tạo thông tin giả lập
            $customerNames = ['Nguyễn Văn A', 'Trần Thị B', 'Lê Văn C', 'Phạm Thị D', 'Hoàng Văn E'];
            $frozen_order->customer_info = [
                'name' => $customerNames[array_rand($customerNames)],
                'phone' => '0' . rand(100000000, 999999999),
                'address' => 'Số ' . rand(1, 999) . ', Đường ' . ['Nguyễn Trãi', 'Lê Lợi', 'Trần Hưng Đạo', 'Hoàng Diệu'][array_rand(['Nguyễn Trãi', 'Lê Lợi', 'Trần Hưng Đạo', 'Hoàng Diệu'])] . ', ' . ['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng'][array_rand(['Hà Nội', 'TP. Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng'])]
            ];
        }

        // Lưu các thay đổi
        $frozen_order->save();

        // Kiểm tra status 'confirmed' có tồn tại không
        $confirmedStatus = \App\Models\Status::where('name', 'confirmed')->first();
        if (!$confirmedStatus) {
            Log::error('Status confirmed không tồn tại trong database');
            return response()->json([
                'status' => 500,
                'message' => 'Hệ thống chưa được cấu hình đúng. Vui lòng liên hệ quản trị viên!'
            ]);
        }

        // Chuyển trạng thái sử dụng OrderStatusService
        try {
            $success = OrderStatusService::changeStatus(
                $frozen_order,
                'confirmed',
                'Nhân viên xác nhận đơn hàng',
                Auth::id()
            );

            if (!$success) {
                Log::error('Không thể thay đổi trạng thái đơn hàng', [
                    'frozen_order_id' => $frozen_order->id,
                    'status' => 'confirmed'
                ]);
                return response()->json([
                    'status' => 500,
                    'message' => 'Không thể thay đổi trạng thái đơn hàng. Vui lòng thử lại!'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception khi thay đổi trạng thái đơn hàng', [
                'frozen_order_id' => $frozen_order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 500,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }

        // Lấy cấu hình thời gian từ database
        $timing = \App\Models\OrderStatusTiming::getTiming('confirmed', 'preparing');
        if ($timing && $timing->is_active) {
            $minMinutes = $timing->getMinTimeInMinutes();
            $maxMinutes = $timing->getMaxTimeInMinutes();
            $delayMinutes = rand($minMinutes, $maxMinutes);
        } else {
            // Fallback: 5-10 phút nếu không có cấu hình
            $delayMinutes = rand(5, 10);
        }

        try {
            PrepareOrder::dispatch($frozen_order->id)
                ->delay(now()->addMinutes($delayMinutes));
        } catch (\Exception $e) {
            Log::warning('Không thể dispatch job PrepareOrder', [
                'frozen_order_id' => $frozen_order->id,
                'error' => $e->getMessage(),
                'queue_connection' => config('queue.default')
            ]);
            // Trong môi trường dev (sync), job sẽ chạy ngay lập tức
            // Trong môi trường production (redis), cần đảm bảo Redis đang chạy
        }

        // Đổi is_frozen = 0 khi đã xác nhận đơn hàng
        $frozen_order->is_frozen = 0;
        $frozen_order->save();

        // Refresh lại model để đảm bảo có dữ liệu mới nhất
        $frozen_order->refresh();

        Log::info('Đơn hàng đã được xác nhận', [
            'frozen_order_id' => $frozen_order->id,
            'order_id' => $frozen_order->order_id,
            'platform' => $frozen_order->platform,
            'status' => $frozen_order->status,
            'is_frozen' => 0
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Đã xác nhận đơn hàng thành công!',
            'frozen_order' => $frozen_order->fresh()
        ]);
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

        // Không cho báo cáo đơn đặc biệt (đang dùng flow liên hệ CSKH)
        if ($frozen_order->custom_price != null) {
            return response()->json([
                'status' => 400,
                'message' => 'Đơn hàng đặc biệt không thể báo cáo. Vui lòng liên hệ CSKH.'
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
