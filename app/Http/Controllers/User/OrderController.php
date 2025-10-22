<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Frozen_order;
use App\Models\Order;
use App\Models\Transaction_history;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->where('frozen_orders.user_id', auth()->id())
            ->where('frozen_orders.spun', true);

        // Lọc theo từng tab
        if ($tab === "btn_cho_xu_ly") {
            $query->where('frozen_orders.is_frozen', 1)
                ->whereNull('frozen_orders.custom_price');
        } elseif ($tab === "btn_hoan_thanh") {
            $query->where('frozen_orders.is_frozen', 0);
        } elseif ($tab === "btn_dong_bang") {
            $query->where('frozen_orders.is_frozen', 1)
                ->whereNotNull('frozen_orders.custom_price');
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
            $frozen_order->is_frozen = 0;
            $frozen_order->save();
            
            // Tính chiết khấu
            $rose = $total_price * $order->commission_percentage;
            
            // Trừ tiền phạt nếu có
            $penalty_amount = $frozen_order->penalty_amount ?? 0;
            $actual_profit = $rose - $penalty_amount;
            
            \Log::info('Phân phối đơn hàng', [
                'order_id' => $order_id,
                'total_price' => $total_price,
                'rose' => $rose,
                'penalty_amount' => $penalty_amount,
                'actual_profit' => $actual_profit
            ]);
            
            // Cập nhật balance (trừ đi tiền phạt nếu có)
            $user->balance += $actual_profit;
            $user->todays_discount += $actual_profit;
            $user->save();
            
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
            \Log::error('Lỗi phân phối đơn hàng', [
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

    public function handle_distribution()
    {
        $frozen_id = request()->input('frozen_id');
        $user = Auth::user();
        $get_frozen_order = Frozen_order::with('order')->find($frozen_id);
        if ($get_frozen_order) {
            if ($get_frozen_order->is_frozen == 0) {
                return response()->json([
                    'status' => 409,
                    'message' => __('order.DonHangDaHoanThanh'),
                ]);
            }
            $total_price = $get_frozen_order->custom_price ? $get_frozen_order->custom_price : $get_frozen_order->order->price * $get_frozen_order->order->quantity;
            if ($total_price <= $user->balance) {
                return $this->handle_so_du($get_frozen_order->order_id, $total_price, $frozen_id);
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => __('order.SoDuKhongDu'),
                ]);
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => __('order.KhongTimThayLichSuDatHang'),
            ]);
        }
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
