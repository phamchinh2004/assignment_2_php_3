<?php

namespace App\Jobs;

use App\Mail\ChatNotificationMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendChatNotificationEmail implements ShouldQueue
{
    use Queueable;

    public $user;
    public $messageContent;
    public $conversationId;
    public $recipientEmail;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $messageContent, $conversationId, $recipientEmail)
    {
        $this->user = $user;
        $this->messageContent = $messageContent;
        $this->conversationId = $conversationId;
        $this->recipientEmail = $recipientEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($this->recipientEmail)->send(
                new ChatNotificationMail(
                    $this->user,
                    $this->messageContent,
                    $this->conversationId
                )
            );

            Log::info('Email chat notification đã gửi thành công qua queue', [
                'recipient' => $this->recipientEmail,
                'user_id' => $this->user->id,
                'conversation_id' => $this->conversationId
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi gửi email qua queue: ' . $e->getMessage(), [
                'recipient' => $this->recipientEmail,
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            
            // Retry nếu gặp lỗi (Laravel tự động retry)
            throw $e;
        }
    }
}
