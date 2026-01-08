<?php

namespace App\Jobs;

use App\Models\Frozen_order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessOrderStatus implements ShouldQueue
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
        
        if (!$frozenOrder || $frozenOrder->status !== 'pending') {
            return;
        }

        // Chuyển sang trạng thái processing
        $frozenOrder->status = 'processing';
        $frozenOrder->processing_at = now();
        $frozenOrder->save();

        Log::info('Đơn hàng bắt đầu xử lý', [
            'frozen_order_id' => $this->frozenOrderId,
            'order_id' => $frozenOrder->order_id
        ]);

        // Lên lịch chuyển sang shipping sau 3-7 phút ngẫu nhiên
        $delayMinutes = rand(3, 7);
        ShipOrder::dispatch($this->frozenOrderId)
            ->delay(now()->addMinutes($delayMinutes));
    }
}
