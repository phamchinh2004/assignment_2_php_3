<?php

namespace App\Jobs;

use App\Models\Frozen_order;
use App\Models\Order;
use App\Models\OrderStatusTiming;
use App\Models\Transaction_history;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CompleteOrder implements ShouldQueue
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
     * Tự động cộng tiền hoa hồng khi đơn hàng đã được giao cho khách hàng
     */
    public function handle(): void
    {
        try {
            Log::info('CompleteOrder job bắt đầu xử lý', [
                'frozen_order_id' => $this->frozenOrderId
            ]);
            
            $frozenOrder = Frozen_order::with('order')->find($this->frozenOrderId);
            
            if (!$frozenOrder) {
                Log::warning('Không tìm thấy frozen order', [
                    'frozen_order_id' => $this->frozenOrderId
                ]);
                return;
            }
            
            Log::info('Tìm thấy frozen order', [
                'frozen_order_id' => $this->frozenOrderId,
                'status' => $frozenOrder->status,
                'delivered_at' => $frozenOrder->delivered_at ? $frozenOrder->delivered_at->toDateTimeString() : 'null'
            ]);
            
            if ($frozenOrder->status !== 'delivered') {
                Log::info('Đơn hàng không ở trạng thái delivered, bỏ qua', [
                    'frozen_order_id' => $this->frozenOrderId,
                    'current_status' => $frozenOrder->status
                ]);
                return;
            }
        
            // Kiểm tra đã đủ thời gian từ khi delivered chưa (lấy từ database)
        if ($frozenOrder->delivered_at) {
            // Lấy cấu hình thời gian từ database
            $timing = OrderStatusTiming::getTiming('delivered', 'completed');
            if ($timing && $timing->is_active) {
                $minMinutes = $timing->getMinTimeInMinutes();
            } else {
                // Fallback: 14 ngày nếu không có cấu hình
                $minMinutes = 14 * 24 * 60; // 14 ngày
            }
            
            $minutesSinceDelivered = $frozenOrder->delivered_at->diffInMinutes(now());
            
            Log::info('Kiểm tra thời gian từ khi delivered', [
                'frozen_order_id' => $this->frozenOrderId,
                'delivered_at' => $frozenOrder->delivered_at->toDateTimeString(),
                'minutes_since_delivered' => $minutesSinceDelivered,
                'min_minutes_required' => $minMinutes
            ]);
            
            if ($minutesSinceDelivered < $minMinutes) {
                // Chưa đủ thời gian, dispatch lại job sau
                $remainingMinutes = $minMinutes - $minutesSinceDelivered;
                Log::info('Chưa đủ thời gian, sẽ dispatch lại job sau', [
                    'frozen_order_id' => $this->frozenOrderId,
                    'remaining_minutes' => $remainingMinutes
                ]);
                CompleteOrder::dispatch($this->frozenOrderId)
                    ->delay(now()->addMinutes($remainingMinutes));
                return;
            }
        }

        // Kiểm tra đã cộng tiền hoa hồng chưa
        Log::info('Kiểm tra trạng thái cộng tiền', [
            'frozen_order_id' => $this->frozenOrderId,
            'commission_paid' => $frozenOrder->commission_paid ?? 'null',
            'status' => $frozenOrder->status
        ]);
        
        if ($frozenOrder->commission_paid) {
            // Nếu status đã là completed rồi thì không làm gì
            if ($frozenOrder->status === 'completed') {
                Log::info('Đơn hàng đã hoàn thành và đã được cộng tiền trước đó', [
                    'frozen_order_id' => $this->frozenOrderId
                ]);
                return;
            }
            
            // Nếu đã cộng tiền nhưng status chưa là completed, chỉ cập nhật status
            Log::warning('Đơn hàng đã được cộng tiền nhưng status chưa là completed, đang cập nhật status', [
                'frozen_order_id' => $this->frozenOrderId,
                'current_status' => $frozenOrder->status
            ]);
            
            // Chỉ cập nhật status, không cộng tiền lại
            $success = OrderStatusService::changeStatus(
                $frozenOrder,
                'completed',
                'Đơn hàng đã hoàn thành (đã được cộng tiền trước đó)'
            );
            
            if ($success) {
                Log::info('Đã cập nhật status sang completed cho đơn hàng đã được cộng tiền', [
                    'frozen_order_id' => $this->frozenOrderId
                ]);
            }
            
            return;
        }

        Log::info('Đơn hàng chưa được cộng tiền, bắt đầu xử lý cộng tiền và cập nhật status', [
            'frozen_order_id' => $this->frozenOrderId
        ]);

        $user = User::find($frozenOrder->user_id);
        $order = $frozenOrder->order;
        
        if (!$user || !$order) {
            Log::error('Không tìm thấy user hoặc order', [
                'frozen_order_id' => $this->frozenOrderId,
                'user_id' => $frozenOrder->user_id,
                'order_id' => $frozenOrder->order_id
            ]);
            return;
        }

        // Tính tổng giá trị đơn hàng
        $total_price = $frozenOrder->custom_price 
            ? $frozenOrder->custom_price 
            : $order->price * $order->quantity;
        
        // Tính chiết khấu
        $rose = $total_price * $order->commission_percentage;
        
        // Trừ tiền phạt nếu có
        $penalty_amount = $frozenOrder->penalty_amount ?? 0;
        $actual_profit = $rose - $penalty_amount;
        
        // Cập nhật trạng thái đơn hàng sử dụng OrderStatusService
        $success = OrderStatusService::changeStatus(
            $frozenOrder,
            'completed',
            'Đơn hàng đã hoàn thành'
        );
        
        if (!$success) {
            Log::error('Không thể chuyển trạng thái sang completed', [
                'frozen_order_id' => $this->frozenOrderId
            ]);
            return;
        }
        
        // Đánh dấu đã cộng tiền hoa hồng
        $frozenOrder->commission_paid = true;
        $frozenOrder->save();
        
        // Cộng tiền hoa hồng vào số dư
        $user->balance += $actual_profit;
        $user->todays_discount += $actual_profit;
        $user->distribution_today += 1;
        $user->save();
        
        // Lưu lịch sử giao dịch
        Transaction_history::create([
            'user_id' => $user->id,
            'value' => $total_price,
            'type' => "order",
            'note' => $order->order_code
        ]);
        
        Transaction_history::create([
            'user_id' => $user->id,
            'value' => $rose,
            'type' => "profit",
            'note' => $order->order_code
        ]);
        
        // Lưu lịch sử phạt nếu có
        if ($penalty_amount > 0) {
            Transaction_history::create([
                'user_id' => $user->id,
                'value' => $penalty_amount,
                'type' => "penalty",
                'note' => $order->order_code
            ]);
        }

        Log::info('Đơn hàng đã hoàn thành và cộng tiền tự động', [
            'frozen_order_id' => $this->frozenOrderId,
            'user_id' => $user->id,
            'order_code' => $order->order_code,
            'total_price' => $total_price,
            'commission' => $rose,
            'penalty_amount' => $penalty_amount,
            'actual_profit' => $actual_profit,
            'new_balance' => $user->balance
        ]);

            // Có thể thêm event/notification ở đây để thông báo cho user
            // event(new OrderCompleted($frozenOrder, $user, $actual_profit));
        } catch (\Exception $e) {
            Log::error('Lỗi khi xử lý CompleteOrder job', [
                'frozen_order_id' => $this->frozenOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw để Laravel đánh dấu job failed
        }
    }
}
