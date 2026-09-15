<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $messageId)
    {
    }

    public function broadcastOn(): array
    {
        $message = Message::find($this->messageId);

        return $message
            ? [new PrivateChannel('chat.conversation.' . $message->conversation_id)]
            : [];
    }

    public function broadcastWith(): array
    {
        $message = Message::with('sender:id,full_name,role')->find($this->messageId);

        return [
            'message' => $message ? [
                'id' => $message->id,
                'message' => $message->message,
                'type' => $message->type,
                'image_path' => $message->image_path,
                'sender_id' => $message->sender_id,
                'conversation_id' => $message->conversation_id,
                'is_read' => (bool) $message->is_read,
                'created_at' => $message->created_at,
                'sender' => [
                    'id' => $message->sender->id,
                    'full_name' => $message->sender->full_name,
                    'role' => $message->sender->role,
                ],
            ] : null,
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageUpdated';
    }
}
