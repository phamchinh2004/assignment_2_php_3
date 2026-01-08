<?php

namespace App\Jobs;

use App\Models\Frozen_order;
use App\Models\OrderStatusTiming;
use App\Jobs\ShipOrder;
use App\Services\OrderStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TransitOrder implements ShouldQueue
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
     * Chuyển từ preparing → transit (đang trung chuyển)
     */
    public function handle(): void
    {
        $frozenOrder = Frozen_order::find($this->frozenOrderId);
        
        if (!$frozenOrder || $frozenOrder->status !== 'preparing') {
            return;
        }

        // Chuyển sang trạng thái transit sử dụng OrderStatusService
        $success = OrderStatusService::changeStatus(
            $frozenOrder,
            'transit',
            'Đơn hàng đang trung chuyển'
        );
        
        if (!$success) {
            Log::error('Không thể chuyển trạng thái sang transit', [
                'frozen_order_id' => $this->frozenOrderId
            ]);
            return;
        }

        Log::info('Đơn hàng bắt đầu trung chuyển', [
            'frozen_order_id' => $this->frozenOrderId,
            'order_id' => $frozenOrder->order_id
        ]);

        // Lấy cấu hình thời gian từ database
        $timing = OrderStatusTiming::getTiming('transit', 'shipping');
        if ($timing && $timing->is_active) {
            $minMinutes = $timing->getMinTimeInMinutes();
            $maxMinutes = $timing->getMaxTimeInMinutes();
            $delayMinutes = rand($minMinutes, $maxMinutes);
        } else {
            // Fallback: 12-48 giờ nếu không có cấu hình
            $delayMinutes = rand(720, 2880); // 12h - 2 ngày
        }
        
        ShipOrder::dispatch($this->frozenOrderId)
            ->delay(now()->addMinutes($delayMinutes));
    }
}
