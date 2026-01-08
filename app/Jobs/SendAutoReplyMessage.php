<?php

namespace App\Jobs;

use App\Events\MessageSent;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

    /**
     * Create a new job instance.
     */
    public function __construct($conversationId, $staffId, $autoReplyMessage, $userId, $locale, $hoursSinceLastMessage)
    {
        $this->conversationId = $conversationId;
        $this->staffId = $staffId;
        $this->autoReplyMessage = $autoReplyMessage;
        $this->userId = $userId;
        $this->locale = $locale;
        $this->hoursSinceLastMessage = $hoursSinceLastMessage;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Tạo tin nhắn tự động trong queue
        $autoMessage = Message::create([
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->staffId,
            'message' => $this->autoReplyMessage,
            'type' => 'text',
            'image_path' => null,
            'is_read' => false,
        ]);
        
        // Broadcast đến TẤT CẢ (bao gồm cả staff) vì đây là auto-reply không qua UI
        // Staff cần nhận event này để hiển thị tin nhắn auto-reply trong conversation đang mở
        broadcast(new MessageSent($autoMessage));
        
        Log::info('Đã gửi tin nhắn chào tự động', [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'staff_id' => $this->staffId,
            'locale' => $this->locale,
            'hours_since_last_message' => $this->hoursSinceLastMessage,
        ]);
    }
}

