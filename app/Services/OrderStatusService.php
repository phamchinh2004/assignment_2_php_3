<?php

namespace App\Services;

use App\Models\Frozen_order;
use App\Models\Status;
use App\Models\StatusOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderStatusService
{
    /**
     * Thay đổi trạng thái đơn hàng và lưu vào status_orders
     * 
     * @param Frozen_order $frozenOrder
     * @param string $statusName Tên trạng thái (pending, confirmed, preparing, etc.)
     * @param string|null $notes Ghi chú thay đổi trạng thái
     * @param int|null $changedBy ID người thay đổi (mặc định là user hiện tại)
     * @return bool
     */
    public static function changeStatus(
        Frozen_order $frozenOrder, 
        string $statusName, 
        ?string $notes = null,
        ?int $changedBy = null
    ): bool {
        try {
            // Lấy Status từ database
            $status = Status::where('name', $statusName)->first();
            
            if (!$status) {
                Log::error('Không tìm thấy trạng thái', [
                    'status_name' => $statusName,
                    'frozen_order_id' => $frozenOrder->id
                ]);
                return false;
            }

            // Kiểm tra trạng thái có đang hoạt động không
            if (!$status->is_active) {
                Log::warning('Trạng thái không hoạt động', [
                    'status_id' => $status->id,
                    'status_name' => $statusName,
                    'frozen_order_id' => $frozenOrder->id
                ]);
                return false;
            }

            // Lấy user_id thay đổi (mặc định là user hiện tại hoặc system)
            $changedByUserId = $changedBy ?? Auth::id();

            // Tạo record trong status_orders
            try {
                StatusOrder::create([
                    'frozen_order_id' => $frozenOrder->id,
                    'status_id' => $status->id,
                    'notes' => $notes,
                    'changed_by' => $changedByUserId,
                ]);
            } catch (\Exception $e) {
                Log::error('Lỗi khi tạo StatusOrder', [
                    'frozen_order_id' => $frozenOrder->id,
                    'status_id' => $status->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            // Cập nhật trạng thái trong frozen_order
            $frozenOrder->status = $statusName;
            
            // Cập nhật timestamp tương ứng
            $timestampField = self::getTimestampField($statusName);
            if ($timestampField) {
                $frozenOrder->$timestampField = now();
            }
            
            $frozenOrder->save();

            Log::info('Đã thay đổi trạng thái đơn hàng', [
                'frozen_order_id' => $frozenOrder->id,
                'order_id' => $frozenOrder->order_id,
                'status_id' => $status->id,
                'status_name' => $statusName,
                'changed_by' => $changedByUserId,
                'notes' => $notes
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Lỗi khi thay đổi trạng thái đơn hàng', [
                'frozen_order_id' => $frozenOrder->id,
                'status_name' => $statusName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Lấy tên field timestamp tương ứng với trạng thái
     */
    private static function getTimestampField(string $statusName): ?string
    {
        $timestampMap = [
            'confirmed' => 'confirmed_at',
            'preparing' => 'preparing_at',
            'transit' => 'transit_at',
            'shipping' => 'shipping_at',
            'delivered' => 'delivered_at',
            'completed' => 'completed_at',
            'cancelled' => 'cancelled_at',
        ];

        return $timestampMap[$statusName] ?? null;
    }

    /**
     * Lấy trạng thái hiện tại của frozen order từ status_orders
     */
    public static function getCurrentStatus(int $frozenOrderId): ?StatusOrder
    {
        return StatusOrder::where('frozen_order_id', $frozenOrderId)
            ->with('status')
            ->latest('created_at')
            ->first();
    }

    /**
     * Lấy lịch sử thay đổi trạng thái của frozen order
     * Sắp xếp từ cũ đến mới (từ trên xuống dưới)
     */
    public static function getStatusHistory(int $frozenOrderId)
    {
        return StatusOrder::where('frozen_order_id', $frozenOrderId)
            ->with(['status', 'changedBy'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}

