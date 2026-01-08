<?php

namespace App\Jobs;

use App\Models\Frozen_order;
use App\Models\OrderStatusTiming;
use App\Jobs\CompleteOrder;
use App\Services\OrderStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeliverOrder implements ShouldQueue
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
        
        if (!$frozenOrder || $frozenOrder->status !== 'shipping') {
            return;
        }

        // Chuyển sang trạng thái delivered sử dụng OrderStatusService
        $success = OrderStatusService::changeStatus(
            $frozenOrder,
            'delivered',
            'Đơn hàng đã được giao cho khách hàng'
        );
        
        if (!$success) {
            Log::error('Không thể chuyển trạng thái sang delivered', [
                'frozen_order_id' => $this->frozenOrderId
            ]);
            return;
        }

        Log::info('Đơn hàng đã được giao cho khách hàng', [
            'frozen_order_id' => $this->frozenOrderId,
            'tracking_number' => $frozenOrder->tracking_number
        ]);

        // Lấy cấu hình thời gian từ database
        $timing = OrderStatusTiming::getTiming('delivered', 'completed');
        if ($timing && $timing->is_active) {
            $minMinutes = $timing->getMinTimeInMinutes();
            $maxMinutes = $timing->getMaxTimeInMinutes();
            $delayMinutes = rand($minMinutes, $maxMinutes);
        } else {
            // Fallback: 14 ngày nếu không có cấu hình
            $delayMinutes = 14 * 24 * 60; // 14 ngày
        }
        
        CompleteOrder::dispatch($this->frozenOrderId)
            ->delay(now()->addMinutes($delayMinutes));
    }
}
