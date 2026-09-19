<?php

namespace App\Jobs;

use App\Mail\DepositNotificationMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDepositNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public int $userId,
        public string $recipientEmail,
        public float $amount,
        public float $newBalance,
        public string $transactionType,
        public string $adminName,
    ) {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning('Không gửi được email nạp tiền vì người dùng không còn tồn tại.', [
                'user_id' => $this->userId,
                'amount' => $this->amount,
            ]);

            return;
        }

        Mail::to($this->recipientEmail)->send(new DepositNotificationMail(
            $user,
            $this->amount,
            $this->newBalance,
            $this->transactionType,
            $this->adminName,
        ));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Gửi email thông báo nạp tiền qua queue thất bại.', [
            'user_id' => $this->userId,
            'recipient' => $this->recipientEmail,
            'amount' => $this->amount,
            'error' => $exception?->getMessage(),
        ]);
    }
}
