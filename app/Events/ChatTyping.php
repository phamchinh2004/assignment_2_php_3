<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatTyping implements ShouldBroadcastNow
{
    public function __construct(public int $conversationId, public int $userId, public string $name, public bool $typing) {}

    public function broadcastOn(): array { return [new PrivateChannel('chat.conversation.'.$this->conversationId)]; }
    public function broadcastAs(): string { return 'ChatTyping'; }
    public function broadcastWith(): array
    {
        return ['conversation_id' => $this->conversationId, 'user_id' => $this->userId, 'name' => $this->name, 'typing' => $this->typing];
    }
}
