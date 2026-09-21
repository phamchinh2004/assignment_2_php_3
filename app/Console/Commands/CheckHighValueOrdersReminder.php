<?php

namespace App\Console\Commands;

use App\Models\Frozen_order;
use App\Mail\HighValueOrderWarningMail;
use App\Mail\HighValueOrderPenaltyMail;
use App\Services\OverdueOrderPenaltyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckHighValueOrdersReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:check-hvo-reminder';

    // Backward compatibility for existing cron/deployment hooks.
    protected $aliases = ['orders:check-special-reminder'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra và gửi mail nhắc nhở cho đơn hàng giá trị cao chưa phân phối';

    /**
     * Execute the console command.
     */
    public function handle(OverdueOrderPenaltyService $penaltyService)
    {
        $this->info('Bắt đầu kiểm tra frozen order chưa phân phối...');

        $unprocessedOrders = Frozen_order::with(['user', 'order'])
            ->where('is_frozen', true)
            ->where('spun', true)
            ->get();

        $reminderCount = 0;
        $penaltyCount = 0;

        foreach ($unprocessedOrders as $frozenOrder) {
            if (!$frozenOrder->user) {
                Log::warning('Bỏ qua frozen order thiếu user hoặc order liên kết', [
                    'frozen_order_id' => $frozenOrder->id,
                    'user_id' => $frozenOrder->user_id,
                    'order_id' => $frozenOrder->order_id,
                ]);
                continue;
            }

            $createdAt = $penaltyService->processingStartedAt($frozenOrder) ?? Carbon::now();
            $processingLimitHours = (int) ($frozenOrder->processing_time_limit ?? 24);
            $notification1Hours = (int) ($frozenOrder->notification_1_remaining_time ?? 12);
            $notification2Hours = (int) ($frozenOrder->notification_2_remaining_time ?? 1);

            $deadlineAt = $penaltyService->deadlineAt($frozenOrder)
                ?? $createdAt->copy()->addHours($processingLimitHours);
            $now = Carbon::now();
            $remainingHours = (int) max(0, ceil($now->diffInHours($deadlineAt, false)));
            $hoursPassed = (int) max(0, floor($createdAt->diffInHours($now)));

            if ($remainingHours <= $notification1Hours && $remainingHours > $notification2Hours && empty($frozenOrder->notification_1_sent_at)) {
                try {
                    Mail::to($frozenOrder->user->email)->send(
                        new HighValueOrderWarningMail($frozenOrder->user, $frozenOrder, $hoursPassed, $remainingHours, 'first', $notification1Hours)
                    );

                    $frozenOrder->timestamps = false;
                    $frozenOrder->update([
                        'notification_1_sent_at' => Carbon::now(),
                    ]);
                    $frozenOrder->timestamps = true;

                    $this->warn("⚠ Đã gửi cảnh báo lần 1 cho user {$frozenOrder->user->name} - Đơn hàng " . ($frozenOrder->snapshot_order_code ?? $frozenOrder->order_id));
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
                        new HighValueOrderWarningMail($frozenOrder->user, $frozenOrder, $hoursPassed, $remainingHours, 'second', $notification2Hours)
                    );

                    $frozenOrder->timestamps = false;
                    $frozenOrder->update([
                        'notification_2_sent_at' => Carbon::now(),
                    ]);

                    $reminderCount++;
                    $frozenOrder->timestamps = true;

                    $this->warn("⚠ Đã gửi cảnh báo lần 2 cho user {$frozenOrder->user->name} - Đơn hàng " . ($frozenOrder->snapshot_order_code ?? $frozenOrder->order_id));
                } catch (\Exception $e) {
                    Log::error('Lỗi gửi cảnh báo lần 2', [
                        'user_id' => $frozenOrder->user->id,
                        'email' => $frozenOrder->user->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($remainingHours <= 0) {
                if ($penaltyService->applyIfOverdue($frozenOrder, $now)) {
                    $penaltyCount++;
                }

                $penaltyAmount = (float) ($frozenOrder->penalty_amount ?? 0);
                if ($penaltyAmount <= 0 || !empty($frozenOrder->penalty_notification_sent_at)) {
                    continue;
                }

                try {
                    Mail::to($frozenOrder->user->email)->send(
                        new HighValueOrderPenaltyMail($frozenOrder->user, $frozenOrder, $hoursPassed, $penaltyAmount)
                    );

                    $frozenOrder->timestamps = false;
                    $frozenOrder->update([
                        'penalty_notification_sent_at' => Carbon::now(),
                    ]);
                    $frozenOrder->timestamps = true;

                    $this->warn("⚠ Đã gửi mail phạt cho user {$frozenOrder->user->name} - Đơn hàng " . ($frozenOrder->snapshot_order_code ?? $frozenOrder->order_id) . " - Số tiền phạt: $" . number_format($penaltyAmount, 2));
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
        $this->info("Tổng số đơn mới áp dụng phạt: {$penaltyCount}");
        $this->info('Tổng số frozen order được kiểm tra: ' . $unprocessedOrders->count());

        return Command::SUCCESS;
    }
}
