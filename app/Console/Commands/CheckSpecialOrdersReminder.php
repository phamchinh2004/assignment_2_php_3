<?php

namespace App\Console\Commands;

use App\Models\Frozen_order;
use App\Models\User;
use App\Mail\SpecialOrderReminderMail;
use App\Mail\SpecialOrderWarningMail;
use App\Mail\SpecialOrderPenaltyMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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
     * Tính tổng giá trị đơn hàng để tính phạt theo quy tắc 30%.
     */
    protected function getOrderValue(Frozen_order $frozenOrder): float
    {
        if ($frozenOrder->custom_price !== null && $frozenOrder->custom_price !== '') {
            return (float) $frozenOrder->custom_price;
        }

        if ($frozenOrder->order) {
            return (float) ($frozenOrder->order->price * $frozenOrder->order->quantity);
        }

        return 0.0;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Bắt đầu kiểm tra frozen order chưa phân phối...');

        $unprocessedOrders = Frozen_order::with(['user', 'order'])
            ->where('is_frozen', true)
            ->where('spun', true)
            ->get();

        $reminderCount = 0;
        $penaltyCount = 0;

        foreach ($unprocessedOrders as $frozenOrder) {
            if (!$frozenOrder->user || !$frozenOrder->order) {
                Log::warning('Bỏ qua frozen order thiếu user hoặc order liên kết', [
                    'frozen_order_id' => $frozenOrder->id,
                    'user_id' => $frozenOrder->user_id,
                    'order_id' => $frozenOrder->order_id,
                ]);
                continue;
            }

            // updated_at is the timestamp shown by the user countdown when the order is received.
            $createdAt = $frozenOrder->updated_at ?? $frozenOrder->created_at ?? Carbon::now();
            $processingLimitHours = (int) ($frozenOrder->processing_time_limit ?? 24);
            $notification1Hours = (int) ($frozenOrder->notification_1_remaining_time ?? 12);
            $notification2Hours = (int) ($frozenOrder->notification_2_remaining_time ?? 1);

            $deadlineAt = $createdAt->copy()->addHours($processingLimitHours);
            $now = Carbon::now();
            $remainingHours = (int) max(0, ceil($now->diffInHours($deadlineAt, false)));
            $hoursPassed = (int) max(0, floor($createdAt->diffInHours($now)));

            if ($remainingHours <= $notification1Hours && $remainingHours > $notification2Hours && empty($frozenOrder->notification_1_sent_at)) {
                try {
                    Mail::to($frozenOrder->user->email)->send(
                        new SpecialOrderWarningMail($frozenOrder->user, $frozenOrder, $hoursPassed, $remainingHours, 'first', $notification1Hours)
                    );

                    $frozenOrder->timestamps = false;
                    $frozenOrder->update([
                        'notification_1_sent_at' => Carbon::now(),
                    ]);
                    $frozenOrder->timestamps = true;

                    $this->warn("⚠ Đã gửi cảnh báo lần 1 cho user {$frozenOrder->user->name} - Đơn hàng {$frozenOrder->order->order_code}");
                    $reminderCount++;
                } catch (\Exception $e) {
                    Log::error('Lỗi gửi cảnh báo lần 1', [
                        'user_id' => $frozenOrder->user->id,
                        'email' => $frozenOrder->user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($remainingHours <= $notification2Hours && $remainingHours > 0 && empty($frozenOrder->notification_2_sent_at)) {
                try {
                    Mail::to($frozenOrder->user->email)->send(
                        new SpecialOrderWarningMail($frozenOrder->user, $frozenOrder, $hoursPassed, $remainingHours, 'second', $notification2Hours)
                    );

                    $frozenOrder->timestamps = false;
                    $frozenOrder->update([
                        'notification_2_sent_at' => Carbon::now(),
                    ]);

                    $reminderCount++;
                    $frozenOrder->timestamps = true;

                    $this->warn("⚠ Đã gửi cảnh báo lần 2 cho user {$frozenOrder->user->name} - Đơn hàng {$frozenOrder->order->order_code}");
                } catch (\Exception $e) {
                    Log::error('Lỗi gửi cảnh báo lần 2', [
                        'user_id' => $frozenOrder->user->id,
                        'email' => $frozenOrder->user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($remainingHours <= 0 && empty($frozenOrder->penalty_notification_sent_at)) {
                try {
                    $orderValue = $this->getOrderValue($frozenOrder);
                    if ($orderValue <= 0) {
                        continue;
                    }

                    $penaltyAmount = $orderValue * 0.3;

                    Mail::to($frozenOrder->user->email)->send(
                        new SpecialOrderPenaltyMail($frozenOrder->user, $frozenOrder, $hoursPassed, $penaltyAmount)
                    );

                    $frozenOrder->timestamps = false;
                    $frozenOrder->update([
                        'penalty_notification_sent_at' => Carbon::now(),
                        'penalty_amount' => $penaltyAmount,
                    ]);
                    $frozenOrder->timestamps = true;

                    $this->warn("⚠ Đã gửi mail phạt cho user {$frozenOrder->user->name} - Đơn hàng {$frozenOrder->order->order_code} - Số tiền phạt: $" . number_format($penaltyAmount, 2));
                    $penaltyCount++;
                } catch (\Exception $e) {
                    Log::error('Lỗi gửi mail phạt', [
                        'user_id' => $frozenOrder->user->id,
                        'email' => $frozenOrder->user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info('Hoàn thành kiểm tra!');
        $this->info("Tổng số cảnh báo đã gửi: {$reminderCount}");
        $this->info("Tổng số mail phạt đã gửi: {$penaltyCount}");
        $this->info('Tổng số frozen order được kiểm tra: ' . $unprocessedOrders->count());

        return Command::SUCCESS;
    }
}
