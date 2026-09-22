<?php

namespace App\Events;

use App\Models\User;
use App\Services\ManagementRecipientResolver;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserSentMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $full_name;
    public $message;
    public $userId;

    public function __construct($full_name, $message, $userId = null)
    {
        $this->full_name = $full_name;
        $this->message = $message;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        // Nếu không có userId, broadcast như cũ
        if (!$this->userId) {
            return [
                new PrivateChannel("sent.message"),
            ];
        }

        $user = User::with(['conversation', 'referrer'])->find($this->userId);
        
        if (!$user) {
            return [
                new PrivateChannel("sent.message"),
            ];
        }

        try {
            return app(ManagementRecipientResolver::class)
                ->forUser($user)
                ->map(fn (User $recipient) => new PrivateChannel('staff.' . $recipient->id))
                ->all();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserSentMessage: Error in broadcastOn', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId
            ]);
            // Return fallback channel
            return [new PrivateChannel("sent.message")];
        }
    }

    public function broadcastAs()
    {
        return 'UserSentMessage';
    }
}
