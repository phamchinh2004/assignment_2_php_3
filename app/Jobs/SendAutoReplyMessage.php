<?php

namespace App\Jobs;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendAutoReplyMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $conversationId;
    protected $staffId;
    protected $autoReplyMessage;
    protected $userId;
    protected $locale;
    protected $hoursSinceLastMessage;
    protected $triggerCustomerMessageId;

    /**
     * Create a new job instance.
     */
    public function __construct($conversationId, $staffId, $autoReplyMessage, $userId, $locale, $hoursSinceLastMessage, $triggerCustomerMessageId)
    {
        $this->conversationId = $conversationId;
        $this->staffId = $staffId;
        $this->autoReplyMessage = $autoReplyMessage;
        $this->userId = $userId;
        $this->locale = $locale;
        $this->hoursSinceLastMessage = $hoursSinceLastMessage;
        $this->triggerCustomerMessageId = $triggerCustomerMessageId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $autoMessage = DB::transaction(function () {
            $conversation = Conversation::query()
                ->whereKey($this->conversationId)
                ->lockForUpdate()
                ->first();

            if (!$conversation) {
                return null;
            }

            $triggerMessage = Message::query()
                ->whereKey($this->triggerCustomerMessageId)
                ->where('conversation_id', $this->conversationId)
                ->where('sender_id', $this->userId)
                ->first();

            if (!$triggerMessage) {
                return null;
            }

            $autoReplyAlreadySent = Message::query()
                ->where('conversation_id', $this->conversationId)
                ->where('id', '>', $triggerMessage->id)
                ->where('kind', 'auto_reply')
                ->exists();

            if ($autoReplyAlreadySent) {
                return null;
            }

            $staffHasReplied = Message::query()
                ->where('conversation_id', $this->conversationId)
                ->where('id', '>', $triggerMessage->id)
                ->where(function ($query) {
                    $query->whereNull('kind')->orWhere('kind', '!=', 'auto_reply');
                })
                ->whereHas('sender', function ($query) {
                    $query->whereIn('role', \App\Models\User::MANAGEMENT_ROLES);
                })
                ->exists();

            if ($staffHasReplied) {
                Log::info('Bỏ qua auto-reply vì quản lý đã trả lời trong thời gian chờ', [
                    'conversation_id' => $this->conversationId,
                    'user_id' => $this->userId,
                    'trigger_customer_message_id' => $this->triggerCustomerMessageId,
                ]);

                return null;
            }

            return Message::create([
                'conversation_id' => $this->conversationId,
                'sender_id' => $conversation->staff_id ?: $this->staffId,
                'message' => $this->autoReplyMessage,
                'type' => 'text',
                'kind' => 'auto_reply',
                'image_path' => null,
                'is_read' => false,
            ]);
        });

        if (!$autoMessage) {
            return;
        }
        
        // Broadcast đến TẤT CẢ (bao gồm cả staff) vì đây là auto-reply không qua UI
        // Staff cần nhận event này để hiển thị tin nhắn auto-reply trong conversation đang mở
        broadcast(new MessageSent($autoMessage->id));
        
        Log::info('Đã gửi tin nhắn chào tự động', [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'staff_id' => $autoMessage->sender_id,
            'locale' => $this->locale,
            'hours_since_last_message' => $this->hoursSinceLastMessage,
            'trigger_customer_message_id' => $this->triggerCustomerMessageId,
        ]);
    }
}
