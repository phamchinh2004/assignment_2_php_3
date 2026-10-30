<?php

namespace App\Jobs;

use App\Mail\ChatEscalationMail;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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
            $shouldSend = DB::transaction(function () {
                $conversation = Conversation::query()
                    ->whereKey($this->conversationId)
                    ->lockForUpdate()
                    ->first();

                if (!$conversation) {
                    return false;
                }

                $triggerMessage = Message::query()
                    ->whereKey($this->triggerCustomerMessageId)
                    ->where('conversation_id', $this->conversationId)
                    ->where('sender_id', $this->userId)
                    ->first();

                if (!$triggerMessage) {
                    return false;
                }

                // Lấy ID tin nhắn cuối cùng của nhân viên quản lý (bỏ qua auto_reply)
                $lastManagementReplyId = Message::query()
                    ->where('conversation_id', $this->conversationId)
                    ->where(fn ($query) => $query->whereNull('kind')->orWhere('kind', '!=', 'auto_reply'))
                    ->whereHas('sender', fn ($query) => $query->whereIn('role', User::MANAGEMENT_ROLES))
                    ->max('messages.id') ?? 0;

                // Nếu quản lý đã trả lời sau trigger message -> Nhân viên đã phản hồi, hủy bỏ gửi mail
                if ($this->cancelIfManagementReplied && $lastManagementReplyId > $triggerMessage->id) {
                    Log::info('Hủy gửi mail escalation vì quản lý đã trả lời cuộc hội thoại', [
                        'conversation_id' => $this->conversationId,
                        'user_id' => $this->userId,
                        'trigger_customer_message_id' => $this->triggerCustomerMessageId,
                        'last_management_reply_id' => $lastManagementReplyId,
                    ]);
                    return false;
                }

                // Kiểm tra xem đợt chờ hiện tại (tính từ lastManagementReplyId) đã gửi mail chưa
                if (
                    $conversation->last_escalated_reply_id !== null &&
                    (int) $conversation->last_escalated_reply_id === (int) $lastManagementReplyId
                ) {
                    Log::info('Bỏ qua gửi mail escalation vì đợt chờ này đã gửi 1 mail trước đó', [
                        'conversation_id' => $this->conversationId,
                        'user_id' => $this->userId,
                        'last_management_reply_id' => $lastManagementReplyId,
                        'last_escalated_reply_id' => $conversation->last_escalated_reply_id,
                        'last_escalated_at' => $conversation->last_escalated_at,
                    ]);
                    return false;
                }

                // Đánh dấu đã gửi mail cho đợt chờ này
                $conversation->update([
                    'last_escalated_reply_id' => $lastManagementReplyId,
                    'last_escalated_at' => now(),
                ]);

                return true;
            });

            if (!$shouldSend) {
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
