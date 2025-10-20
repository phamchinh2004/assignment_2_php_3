<?php

namespace App\Console\Commands;

use App\Models\Frozen_order;
use App\Models\User;
use App\Mail\SpecialOrderReminderMail;
use App\Mail\SpecialOrderPenaltyMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CheckSpecialOrdersReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:check-special-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra và gửi mail nhắc nhở cho đơn hàng đặc biệt chưa phân phối';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bắt đầu kiểm tra đơn hàng đặc biệt chưa phân phối...');

        // Lấy tất cả đơn hàng đặc biệt chưa phân phối (is_frozen = true, spun = true)
        $unprocessedOrders = Frozen_order::with(['user', 'order'])
            ->where('is_frozen', true)
            ->where('spun', true)
            ->whereNotNull('custom_price')
            ->get();

        $reminderCount = 0;
        $penaltyCount = 0;

        foreach ($unprocessedOrders as $frozenOrder) {
            $hoursPassed = $frozenOrder->updated_at->diffInHours(Carbon::now());
            
            // Kiểm tra nếu đã qua 8 tiếng nhưng chưa qua 24 tiếng - gửi mail nhắc nhở
            if ($hoursPassed >= 8 && $hoursPassed < 24 && !$frozenOrder->reminder_sent) {
                try {
                    Mail::to($frozenOrder->user->email)->send(
                        new SpecialOrderReminderMail($frozenOrder->user, $frozenOrder, $hoursPassed)
                    );
                    
                    // Cập nhật trạng thái đã gửi mail nhắc nhở
                    $frozenOrder->update([
                        'reminder_sent' => true,
                        'reminder_sent_at' => Carbon::now()
                    ]);
                    
                    $this->info("Đã gửi mail nhắc nhở cho user {$frozenOrder->user->name} (ID: {$frozenOrder->user->id}) - Đơn hàng {$frozenOrder->order->order_code}");
                    $reminderCount++;
                } catch (\Exception $e) {
                    $this->error("Lỗi gửi mail nhắc nhở cho user {$frozenOrder->user->name}: " . $e->getMessage());
                }
            }
            
            // Kiểm tra nếu đã qua 24 tiếng - gửi mail phạt
            if ($hoursPassed >= 24 && !$frozenOrder->penalty_sent) {
                try {
                    $penaltyAmount = $frozenOrder->custom_price * 0.3; // 30% tổng giá trị đơn hàng
                    
                    Mail::to($frozenOrder->user->email)->send(
                        new SpecialOrderPenaltyMail($frozenOrder->user, $frozenOrder, $hoursPassed, $penaltyAmount)
                    );
                    
                    // Cập nhật trạng thái đã gửi mail phạt
                    $frozenOrder->update([
                        'penalty_sent' => true,
                        'penalty_sent_at' => Carbon::now()
                    ]);
                    
                    $this->warn("Đã gửi mail phạt cho user {$frozenOrder->user->name} (ID: {$frozenOrder->user->id}) - Đơn hàng {$frozenOrder->order->order_code} - Số tiền phạt: $" . number_format($penaltyAmount, 2));
                    $penaltyCount++;
                } catch (\Exception $e) {
                    $this->error("Lỗi gửi mail phạt cho user {$frozenOrder->user->name}: " . $e->getMessage());
                }
            }
        }

        $this->info("Hoàn thành kiểm tra!");
        $this->info("Tổng số mail nhắc nhở đã gửi: {$reminderCount}");
        $this->info("Tổng số mail phạt đã gửi: {$penaltyCount}");
        $this->info("Tổng số đơn hàng đặc biệt được kiểm tra: " . $unprocessedOrders->count());

        return Command::SUCCESS;
    }
}
