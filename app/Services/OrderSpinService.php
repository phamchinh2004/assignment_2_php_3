<?php

namespace App\Services;

use App\Models\Frozen_order;
use App\Models\Order;
use App\Models\Rank;
use App\Models\User;
use App\Models\User_spin_progress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderSpinService
{
    public function receive(int $userId, ?int $changedBy = null): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($userId, $changedBy) {
                $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
                if (!$user->rank_id) {
                    return [
                        'status' => 500,
                        'message' => __('home.BanChuaCoGianHang')
                    ];
                }

                $check_frozen = Frozen_order::where('user_id', $user->id)
                    ->where('is_frozen', 1)
                    ->with('order')
                    ->join('orders', 'frozen_orders.order_id', '=', 'orders.id')
                    ->orderBy('orders.index', 'asc')
                    ->select('frozen_orders.*')
                    ->lockForUpdate()
                    ->first();

                return $this->processFrozenOrderCheck($user, $check_frozen, $changedBy);
            });

            return $result instanceof \Illuminate\Http\JsonResponse
                ? $result
                : response()->json($result);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json([
                'status' => 500,
                'message' => __('home.DaXayRaLoiKhiKiemTraDonHang'),
                'error' => $e->getMessage()
            ]);
        }
    }

    private function processFrozenOrderCheck(User $user, ?Frozen_order $check_frozen, ?int $changedBy): JsonResponse
    {
            if ($check_frozen) {
                if ($check_frozen->custom_price !== null) {
                    $hvo_order_id = $check_frozen->order_id;
                    $high_value_order = Order::find($hvo_order_id);
                    $query_current_spin = User_spin_progress::where('user_id', $user->id)->lockForUpdate()->first();
                    if (!$high_value_order) {
                        return response()->json([
                            'status' => 500,
                            'message' => __('home.KhongTimThayDonHang')
                        ]);
                    }
                    if (!$query_current_spin) {
                        User_spin_progress::create([
                            'user_id' => $user->id,
                            'rank_id' => $user->rank_id
                        ]);
                        return response()->json([
                            'status' => 500,
                            'message' => __('home.KhongTimThayTienTrinhQuay')
                        ]);
                    }
                    if ($query_current_spin->current_spin + 1 == $high_value_order->index) {
                        // The customer receives the current quantity; preserve it for history
                        // before marking the assigned high-value order as received.
                        if (!$check_frozen->spun) {
                            $check_frozen->snapshot_quantity = $high_value_order->quantity;
                            $check_frozen->snapshot_order_amount = $check_frozen->custom_price;
                            $quantity = (int) $check_frozen->snapshot_quantity;
                            $check_frozen->snapshot_unit_price = $quantity > 0
                                ? round((float) $check_frozen->custom_price / $quantity, 6)
                                : null;
                        }
                        $query_current_spin->current_spin = $query_current_spin->current_spin + 1;
                        $query_current_spin->save();
                        $check_frozen->spun = true;
                        $check_frozen->processing_started_at ??= now();
                        // Assignment may already have set pending; receipt is still
                        // a separate customer event and must be recorded once.
                        if (!OrderStatusService::changeStatus(
                            $check_frozen,
                            'pending',
                            'Người dùng nhận đơn hàng',
                            $changedBy ?? $user->id
                        )) {
                            throw new \RuntimeException('Không thể lưu lịch sử nhận đơn hàng.');
                        }
                        $check_frozen->save();

                        // Chuyển số dư hiện tại vào số dư đóng băng khi nhận đơn hàng giá trị cao
                        $user->frozen_balance += $user->balance;
                        $user->balance = 0;
                        $user->distribution_today += 1;
                        $user->save();
                        return response()->json([
                            'status' => 200,
                            'is_frozen' => true,
                            'is_high_value_order' => true,
                            // Backward compatibility for older API clients.
                            'is_order_special' => true,
                            'is_new_order' => true,
                            'custom_price' => $check_frozen->custom_price,
                            'order_amount' => $check_frozen->snapshot_order_value,
                            'order_quantity' => (int) $check_frozen->snapshot_quantity,
                            'unit_price' => $check_frozen->display_unit_price,
                            'commission_percentage' => $check_frozen->commission_percentage,
                            'commission_amount' => $check_frozen->snapshot_commission_value,
                            'order_id' => $high_value_order->id,
                            'frozen_id' => $check_frozen->id,
                            'frozen_updated_at' => $check_frozen->updated_at,
                            'message' => __('home.ChucMungBanNhanDuocDonHangGiaTriCao')
                        ]);
                    } else if ($query_current_spin->current_spin <= $high_value_order->index && $check_frozen->spun == true) {
                        return response()->json([
                            'status' => 200,
                            'is_frozen' => true,
                            'is_high_value_order' => true,
                            'is_order_special' => true,
                            'is_new_order' => false,
                            'redirect' => url('/order/' . $check_frozen->getRouteKey()),
                            'message' => __('home.CoDonHangChuaXuLy')
                        ]);
                    } else {
                        $rank = Rank::find($query_current_spin->rank_id);
                        if ($rank->spin_count == $query_current_spin->current_spin) {
                            return response()->json([
                                'status' => 400,
                                'is_frozen' => false,
                                'is_high_value_order' => false,
                                'is_order_special' => false,
                                'is_new_order' => false,
                                'message' => __('home.LuotQuayDaDatDenGioiHanToiDa')
                            ]);
                        }
                        $order = Order::where('index', $query_current_spin->current_spin + 1)->where('rank_id', $query_current_spin->rank_id)->first();
                        if (!$order) {
                            return response()->json([
                                'status' => 500,
                                'message' => __('home.KhongTimThayDonHang')
                            ]);
                        }
                        $query_current_spin->current_spin = $query_current_spin->current_spin + 1;
                        $query_current_spin->save();
                        $new_frozen = Frozen_order::snapshotFromOrder($order, [
                            'user_id' => $user->id,
                            'order_id' => $order->id,
                            'assignment_source' => 'spin',
                            'spun' => true,
                            'status' => 'pending' // Trạng thái chờ nhận đơn
                        ]);

                        // Tạo record status đầu tiên trong status_orders
                        \App\Services\OrderStatusService::changeStatus(
                            $new_frozen,
                            'pending',
                            'Người dùng nhận đơn hàng',
                            $changedBy
                        );
                        return response()->json([
                            'status' => 200,
                            'is_frozen' => false,
                            'is_high_value_order' => false,
                            'is_order_special' => false,
                            'is_new_order' => true,
                            'order_amount' => $new_frozen->snapshot_order_value,
                            'commission_percentage' => $new_frozen->commission_percentage,
                            'commission_amount' => $new_frozen->snapshot_commission_value,
                            'order_id' => $order->id,
                            'frozen_id' => $new_frozen->id,
                            'frozen_updated_at' => $new_frozen->updated_at,
                            'message' => 'Đây là đơn hàng bình thường'
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 200,
                        'is_frozen' => true,
                        'is_high_value_order' => false,
                        'is_order_special' => false,
                        'is_new_order' => false,
                        'redirect' => url('/order/' . $check_frozen->getRouteKey()),
                        'message' => __('home.CoDonHangChuaXuLy')
                    ]);
                }
            } else {
                $query_current_spin = User_spin_progress::where('user_id', $user->id)->lockForUpdate()->first();
                if (!$query_current_spin) {
                    User_spin_progress::create([
                        'user_id' => $user->id,
                        'rank_id' => $user->rank_id
                    ]);
                    return response()->json([
                        'status' => 500,
                        'message' => __('home.KhongTimThayTienTrinhQuay')
                    ]);
                }
                $rank = Rank::find($query_current_spin->rank_id);
                if ($rank->spin_count == $query_current_spin->current_spin) {
                    return response()->json([
                        'status' => 400,
                        'is_frozen' => false,
                        'is_high_value_order' => false,
                        'is_order_special' => false,
                        'is_new_order' => false,
                        'message' => __('home.LuotQuayDaDatDenGioiHanToiDa')
                    ]);
                }
                $order = Order::where('index', $query_current_spin->current_spin + 1)->where('rank_id', $query_current_spin->rank_id)->first();
                if (!$order) {
                    return response()->json([
                        'status' => 500,
                        'message' => __('home.KhongTimThayDonHang')
                    ]);
                }
                $query_current_spin->current_spin = $query_current_spin->current_spin + 1;
                $query_current_spin->save();
                $new_frozen = Frozen_order::snapshotFromOrder($order, [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'assignment_source' => 'spin',
                    'spun' => true,
                    'status' => 'pending' // Trạng thái chờ nhận đơn
                ]);

                // Tạo record status đầu tiên trong status_orders
                \App\Services\OrderStatusService::changeStatus(
                    $new_frozen,
                    'pending',
                    'Người dùng nhận đơn hàng',
                    $changedBy
                );

                $user->distribution_today += 1;
                $user->save();
                return response()->json([
                    'status' => 200,
                    'is_frozen' => false,
                    'is_high_value_order' => false,
                    'is_order_special' => false,
                    'is_new_order' => true,
                    'order_amount' => $new_frozen->snapshot_order_value,
                    'commission_percentage' => $new_frozen->commission_percentage,
                    'commission_amount' => $new_frozen->snapshot_commission_value,
                    'order_id' => $order->id,
                    'frozen_id' => $new_frozen->id,
                    'frozen_updated_at' => $new_frozen->updated_at,
                    'message' => 'Đây là đơn hàng bình thường'
                ]);
            }
    }
}
