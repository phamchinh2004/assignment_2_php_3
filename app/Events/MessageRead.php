<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $conversationId;

    /**
     * Create a new event instance.
     */
    public function __construct($messageId, $conversationId)
    {
        $this->messageId = $messageId;
        $this->conversationId = $conversationId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.conversation.' . $this->conversationId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MessageRead';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Load message từ database để lấy is_read status
        $message = \App\Models\Message::find($this->messageId);
        
        return [
            'message_id' => $this->messageId,
            'is_read' => $message ? ($message->is_read ?? false) : false,
            'read_at' => now()->toDateTimeString(),
        ];
    }
}

