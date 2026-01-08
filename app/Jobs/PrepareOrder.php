<?php

namespace App\Jobs;

use App\Models\Frozen_order;
use App\Models\OrderStatusTiming;
use App\Jobs\TransitOrder;
use App\Services\OrderStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PrepareOrder implements ShouldQueue
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
     * Chuyển từ confirmed → preparing (đang chuẩn bị hàng hóa)
     */
    public function handle(): void
    {
        $frozenOrder = Frozen_order::find($this->frozenOrderId);
        
        if (!$frozenOrder || $frozenOrder->status !== 'confirmed') {
            return;
        }

        // Chuyển sang trạng thái preparing sử dụng OrderStatusService
        $success = OrderStatusService::changeStatus(
            $frozenOrder,
            'preparing',
            'Người bán chuẩn bị hàng hóa'
        );
        
        if (!$success) {
            Log::error('Không thể chuyển trạng thái sang preparing', [
                'frozen_order_id' => $this->frozenOrderId
            ]);
            return;
        }

        Log::info('Đơn hàng bắt đầu chuẩn bị hàng hóa', [
            'frozen_order_id' => $this->frozenOrderId,
            'order_id' => $frozenOrder->order_id
        ]);

        // Lấy cấu hình thời gian từ database
        $timing = OrderStatusTiming::getTiming('preparing', 'transit');
        if ($timing && $timing->is_active) {
            $minMinutes = $timing->getMinTimeInMinutes();
            $maxMinutes = $timing->getMaxTimeInMinutes();
            $delayMinutes = rand($minMinutes, $maxMinutes);
        } else {
            // Fallback: 1-3 giờ nếu không có cấu hình
            $delayMinutes = rand(60, 180);
        }
        
        TransitOrder::dispatch($this->frozenOrderId)
            ->delay(now()->addMinutes($delayMinutes));
    }
}
