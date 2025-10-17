<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserSentMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $full_name;
    public $message;

    public function __construct($full_name, $message)
    {
        $this->full_name = $full_name;
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("sent.message"),
        ];
    }

    public function broadcastAs()
    {
        return 'UserSentMessage';
    }
}
