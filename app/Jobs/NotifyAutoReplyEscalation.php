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
    public $triggerCustomerMessageId;
    public $cancelIfManagementReplied;

    public function __construct(
        $conversationId,
        $userId,
        $autoReplyMessage,
        array $recipientEmails,
        int $afterMinutes,
        $triggerCustomerMessageId,
        bool $cancelIfManagementReplied
    ) {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->autoReplyMessage = $autoReplyMessage;
        $this->recipientEmails = array_values(array_unique(array_filter($recipientEmails)));
        $this->afterMinutes = $afterMinutes;
        $this->triggerCustomerMessageId = $triggerCustomerMessageId;
        $this->cancelIfManagementReplied = $cancelIfManagementReplied;
    }

    public function handle(): void
    {
        try {
            if ($this->cancelIfManagementReplied) {
                $triggerMessage = Message::query()
                    ->whereKey($this->triggerCustomerMessageId)
                    ->where('conversation_id', $this->conversationId)
                    ->where('sender_id', $this->userId)
                    ->first();

                if (!$triggerMessage) {
                    return;
                }

                $managementHasReplied = Message::query()
                    ->where('conversation_id', $this->conversationId)
                    ->where('id', '>', $triggerMessage->id)
                    ->where(function ($query) {
                        $query->whereNull('kind')->orWhere('kind', '!=', 'auto_reply');
                    })
                    ->whereHas('sender', function ($query) {
                        $query->whereIn('role', \App\Models\User::MANAGEMENT_ROLES);
                    })
                    ->exists();

                if ($managementHasReplied) {
                    return;
                }
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
