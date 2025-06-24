<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Rank;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $status = request()->input('status');
        $rank = request()->input('rank');
        $list_ranks = Rank::withCount('orders')->get();
        if ($status != "" || $rank != "") {
            $query = Order::query();
            if ($status == "0" || $status == "1") {
                $query->where('status', $status);
            }
            if ($rank !== "all" && $rank) {
                $query->where('rank_id', $rank);
            }
            $list_orders = $query->get();
            $response = [
                'status' => 200,
                'message' => 'Lấy dữ liệu thành công!',
                'data' => $list_orders
            ];
            return response()->json($response);
        } else {
            $list_orders = Order::get();
            return view('admin.order.index', compact('list_ranks', 'list_orders'));
        }
    }
    public function changeStatusOrder(Order $order)
    {
        if ($order) {
            $order->status = $order->status == "1" ? "0" : "1";
            $order->save();
            if ($order->status == "1") {
                return redirect()->route('order.index')->with('success', 'Mở khóa đơn hàng thành công!');
            } else {
                return redirect()->route('order.index')->with('success', 'Khóa đơn hàng thành công!');
            }
        } else {
            return redirect()->route('order.index')->with('error', 'Không tìm thấy đơn hàng cần thay đổi trạng thái!');
        }
    }
    public function orderUpdateCommissionPercentage()
    {
        $ranks = Rank::all();
        foreach ($ranks as $rank) {
            $orders = Order::where('rank_id', $rank->id)->get();
            foreach ($orders as $order) {
                if ($order->commission_percentage != $rank->commission_percentage) {
                    $order->commission_percentage = $rank->commission_percentage;
                    $order->save();
                }
            }
        }
        return redirect()->route('order.index')->with('success', 'Cập nhật hoa hồng đơn hàng thành công!');
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $get_all_ranks = Rank::get();
        $list_ranks = [];
        foreach ($get_all_ranks as $item) {
            $rank_item = [];
            $get_orders_by_vip = Order::where('rank_id', $item->id)->get();
            $sum_price = 0;
            if ($get_orders_by_vip) {
                foreach ($get_orders_by_vip as $order_item) {
                    $sum_price += $order_item->price;
                }
            }
            $rank_item['id'] = $item->id;
            $rank_item['name'] = $item->name;
            $rank_item['value'] = $item->value - $sum_price;
            $rank_item['commission_percentage'] = $item->commission_percentage;
            $rank_item['spin_count'] = $item->spin_count;
            $rank_item['quantity'] = $item->spin_count - count($get_orders_by_vip);
            $rank_item['start'] = count($get_orders_by_vip);
            $list_ranks[] = $rank_item;
        }
        return view('admin.order.create', compact('list_ranks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $list_orders = $request->input('orders');
        $rank_id = $request->input('rank_id');
        $commission_percentage = Rank::find($rank_id);
        if ($commission_percentage) {
            $commission_percentage = $commission_percentage->commission_percentage;
        } else {
            $response = [
                'status' => 400,
                'mess' => "Tạo đơn hàng ko thành công!",
                'data' => $commission_percentage
            ];
            return response()->json($response);
        }
        foreach ($list_orders as $index => $item) {
            $file = $request->file("orders.$index.image");
            $fileName = "";
            if ($file && $file->isValid()) {
                $fileName = $file->hashName();
                $file->move(public_path('uploads/orders/images/'), $fileName);
            }
            $new_order = Order::create([
                'order_code' => $this->generateUniqueOrderCode(),
                'index' => $item['index'],
                'name' => $item['name'] ?? "",
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'image' => $fileName,
                'rank_id' => $rank_id,
                'commission_percentage' => $commission_percentage
            ]);
            if (!$new_order) {
                $response = [
                    'status' => 400,
                    'mess' => "Tạo đơn hàng thứ $index không thành công!"
                ];
                return response()->json($response);
            }
        }
        $response = [
            'status' => 200,
            'mess' => "Tạo đơn hàng thành công!",
            'redirect_url' => route('order.index')
        ];
        return response()->json($response);
    }
    function generateUniqueOrderCode($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);

        do {
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[random_int(0, $charactersLength - 1)];
            }
            $exists = Order::where('order_code', $randomString)->exists();
        } while ($exists);

        return $randomString;
    }
    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        return view('admin.order.edit', compact('order'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $data = $request->only(['name', 'order_code', 'price', 'quantity']);
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($order->image && public_path('uploads/orders/images/' . $order->image)) {
                unlink(public_path('uploads/orders/images/' . $order->image));
            }
            $file = $request->file('image');
            $file_name = $file->hashName();
            $file->move(public_path('uploads/orders/images/'), $file_name);
            $data['image'] = $file_name;
        }
        $order->update($data);
        return redirect()->route('order.index')->with('success', 'Cập nhật đơn hàng thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
