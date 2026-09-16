<?php

namespace App\Jobs;

use App\Mail\ChatEscalationMail;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAutoReplyEscalation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conversationId;
    public $userId;
    public $autoReplyMessage;
    public $recipientEmails;
    public $afterMinutes;

    public function __construct($conversationId, $userId, $autoReplyMessage, array $recipientEmails, int $afterMinutes)
    {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->autoReplyMessage = $autoReplyMessage;
        $this->recipientEmails = $recipientEmails;
        $this->afterMinutes = $afterMinutes;
    }

    public function handle(): void
    {
        try {
            $latestStaffReply = Message::where('conversation_id', $this->conversationId)
                ->whereHas('sender', function ($query) {
                    $query->whereIn('role', ['admin', 'staff']);
                })
                ->orderBy('created_at', 'desc')
                ->first();

            if ($latestStaffReply && $latestStaffReply->created_at->diffInMinutes(now()) < $this->afterMinutes) {
                return;
            }

            foreach ($this->recipientEmails as $email) {
                Mail::to($email)->send(new \App\Mail\ChatEscalationMail(
                    $this->conversationId,
                    $this->userId,
                    $this->autoReplyMessage,
                    $this->afterMinutes
                ));
            }

            Log::info('Đã gửi email cảnh báo auto-reply escalation', [
                'conversation_id' => $this->conversationId,
                'user_id' => $this->userId,
                'recipient_count' => count($this->recipientEmails),
                'after_minutes' => $this->afterMinutes,
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi gửi email cảnh báo escalation: ' . $e->getMessage(), [
                'conversation_id' => $this->conversationId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
