<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChatMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private int $conversationId,
        private string $senderName,
        private string $message
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'chat_message',
            'title' => $this->senderName . ' đã gửi tin nhắn',
            'message' => $this->message,
            'conversation_id' => $this->conversationId,
            'target_url' => route('chat-panel') . '#conversation-' . $this->conversationId,
        ];
    }
}
