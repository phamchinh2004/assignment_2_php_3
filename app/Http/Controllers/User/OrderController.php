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
        $rose = $total_price * $order->commission_percentage;
        $user->balance += $rose;
        $user->todays_discount += $rose;
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

        return response()->json([
            'status' => 200,
            'message' => __('order.PhanPhoiThanhCong'),
            'balance' => $user->balance,
            'profit' => $rose
        ]);
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
