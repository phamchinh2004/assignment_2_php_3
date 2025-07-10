<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinChat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $conversationId;

    public function __construct($conversationId)
    {
        $this->conversationId = $conversationId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("join.conversation"),
        ];
    }

    public function broadcastAs()
    {
        return 'UserJoinChat';
    }
}
