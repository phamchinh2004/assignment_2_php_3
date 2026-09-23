<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ConversationAssigned implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public int $conversationId,
        public int $previousOperatorId,
        public int $operatorId
    ) {}

    public function broadcastOn(): array
    {
        $ids = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OWNER])
            ->pluck('id')
            ->push($this->previousOperatorId)
            ->push($this->operatorId)
            ->unique();

        return $ids->map(fn ($id) => new PrivateChannel('staff.' . $id))->all();
    }

    public function broadcastAs(): string
    {
        return 'ConversationAssigned';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
