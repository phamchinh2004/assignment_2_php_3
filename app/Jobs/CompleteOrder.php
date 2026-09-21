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
use Illuminate\Support\Facades\DB;
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
                
                // Nếu đang dùng queue driver sync, không nên dispatch lại để tránh vòng lặp vô tận
                if (config('queue.default') !== 'sync') {
                    CompleteOrder::dispatch($this->frozenOrderId)
                        ->delay(now()->addMinutes($remainingMinutes));
                }
                return;
            }
        }

        $completion = DB::transaction(function () {
            $frozenOrder = Frozen_order::with('order')
                ->lockForUpdate()
                ->find($this->frozenOrderId);

            if (!$frozenOrder || $frozenOrder->status !== 'delivered') {
                return null;
            }

            if ($frozenOrder->commission_paid) {
                return null;
            }

            $user = User::lockForUpdate()->find($frozenOrder->user_id);
            if (!$user) {
                throw new \RuntimeException('Không tìm thấy user.');
            }

            $totalPrice = $frozenOrder->snapshot_order_value;
            $commission = $frozenOrder->snapshot_commission_value;
            if ($totalPrice === null || $commission === null) {
                throw new \RuntimeException('Frozen order cũ chưa có dữ liệu snapshot tài chính chính xác.');
            }
            $penaltyAmount = $frozenOrder->penalty_amount ?? 0;
            $actualProfit = $commission - $penaltyAmount;
            $creditAmount = $totalPrice + $actualProfit;

            $hasUnconfirmedHvo = Frozen_order::where('user_id', $user->id)
                ->where('id', '!=', $frozenOrder->id)
                ->whereNotNull('custom_price')
                ->where('is_frozen', true)
                ->where('spun', true)
                ->where(function ($query) {
                    $query->where('status', 'pending')->orWhereNull('status');
                })
                ->exists();

            if ($hasUnconfirmedHvo) {
                $user->frozen_balance += $creditAmount;
            } else {
                $user->balance += $creditAmount;
            }
            $user->todays_discount += $actualProfit;
            $user->save();

            Transaction_history::create([
                'user_id' => $user->id,
                'value' => $commission,
                'type' => 'profit',
                'note' => $frozenOrder->snapshot_order_code ?? (string) $frozenOrder->order_id
            ]);

            if ($penaltyAmount > 0) {
                Transaction_history::create([
                    'user_id' => $user->id,
                    'value' => $penaltyAmount,
                    'type' => 'penalty',
                    'note' => $frozenOrder->snapshot_order_code ?? (string) $frozenOrder->order_id
                ]);
            }

            $frozenOrder->settled_order_amount = $totalPrice;
            $frozenOrder->settled_commission_amount = $commission;
            $frozenOrder->settled_penalty_amount = $penaltyAmount;
            $frozenOrder->settled_refund_amount = $creditAmount;
            $frozenOrder->settled_balance_destination = $hasUnconfirmedHvo
                ? 'frozen_balance'
                : 'balance';
            $frozenOrder->settled_at = now();
            $frozenOrder->commission_paid = true;
            if (!OrderStatusService::changeStatus(
                $frozenOrder,
                'completed',
                'Đơn hàng đã hoàn thành'
            )) {
                throw new \RuntimeException('Không thể chuyển trạng thái sang completed.');
            }

            return [
                'user_id' => $user->id,
                'order_code' => $frozenOrder->snapshot_order_code ?? (string) $frozenOrder->order_id,
                'total_price' => $totalPrice,
                'commission' => $commission,
                'penalty_amount' => $penaltyAmount,
                'actual_profit' => $actualProfit,
                'credit_amount' => $creditAmount,
                'credited_to_frozen_balance' => $hasUnconfirmedHvo,
                'new_balance' => $user->balance,
                'new_frozen_balance' => $user->frozen_balance,
            ];
        });

        if ($completion) {
            Log::info('Đơn hàng đã hoàn thành và cộng tiền tự động', [
                'frozen_order_id' => $this->frozenOrderId,
                ...$completion,
            ]);
        }

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
