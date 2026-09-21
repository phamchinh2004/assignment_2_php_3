<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuthorizationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $staffId,
        public string $version
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("staff.{$this->staffId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AuthorizationUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'version' => $this->version,
        ];
    }
}
