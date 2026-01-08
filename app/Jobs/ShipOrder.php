<?php

namespace App\Jobs;

use App\Models\Frozen_order;
use App\Models\OrderStatusTiming;
use App\Jobs\DeliverOrder;
use App\Services\OrderStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ShipOrder implements ShouldQueue
{
    use Queueable;

    public $frozenOrderId;

    /**
     * Create a new job instance.
     */
    public function __construct($frozenOrderId)
    {
        $this->frozenOrderId = $frozenOrderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $frozenOrder = Frozen_order::find($this->frozenOrderId);
        
        if (!$frozenOrder || $frozenOrder->status !== 'transit') {
            return;
        }

        // Tạo mã vận đơn ngẫu nhiên
        $trackingNumber = 'TRK' . strtoupper(substr(md5($this->frozenOrderId . time()), 0, 10));
        
        // Danh sách đơn vị vận chuyển
        $carriers = ['Vietnam Post', 'Viettel Post', 'J&T Express', 'Giao Hàng Nhanh', 'Shopee Express'];
        
        // Chuyển sang trạng thái shipping sử dụng OrderStatusService
        $success = OrderStatusService::changeStatus(
            $frozenOrder,
            'shipping',
            'Đơn hàng đang vận chuyển đến khách hàng. Mã vận đơn: ' . $trackingNumber
        );
        
        if (!$success) {
            Log::error('Không thể chuyển trạng thái sang shipping', [
                'frozen_order_id' => $this->frozenOrderId
            ]);
            return;
        }
        
        // Cập nhật thông tin vận chuyển
        $frozenOrder->tracking_number = $trackingNumber;
        $frozenOrder->shipping_carrier = $carriers[array_rand($carriers)];
        $frozenOrder->save();

        Log::info('Đơn hàng đã được giao vận chuyển', [
            'frozen_order_id' => $this->frozenOrderId,
            'tracking_number' => $trackingNumber
        ]);

        // Lấy cấu hình thời gian từ database
        $timing = OrderStatusTiming::getTiming('shipping', 'delivered');
        if ($timing && $timing->is_active) {
            $minMinutes = $timing->getMinTimeInMinutes();
            $maxMinutes = $timing->getMaxTimeInMinutes();
            $delayMinutes = rand($minMinutes, $maxMinutes);
        } else {
            // Fallback: 6-12 giờ nếu không có cấu hình
            $delayMinutes = rand(360, 720); // 6-12 giờ
        }
        
        DeliverOrder::dispatch($this->frozenOrderId)
            ->delay(now()->addMinutes($delayMinutes));
    }
}
