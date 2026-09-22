<?php

namespace App\Events;

use App\Models\User;
use App\Services\ManagementRecipientResolver;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinChat
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $username;
    public $full_name;
    public $userId;

    public function __construct($username, $full_name, $userId = null)
    {
        $this->username = $username;
        $this->full_name = $full_name;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        // Nếu không có userId, broadcast như cũ
        if (!$this->userId) {
            return [
                new PrivateChannel("join.conversation"),
            ];
        }

        $user = User::with(['conversation', 'referrer'])->find($this->userId);
        
        if (!$user) {
            return [
                new PrivateChannel("join.conversation"),
            ];
        }

        try {
            return app(ManagementRecipientResolver::class)
                ->forUser($user)
                ->map(fn (User $recipient) => new PrivateChannel('staff.' . $recipient->id))
                ->all();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserJoinChat: Error in broadcastOn', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId
            ]);
            // Return fallback channel
            return [new PrivateChannel("join.conversation")];
        }
    }

    public function broadcastAs()
    {
        return 'UserJoinChat';
    }

    /**
     * Get the data to broadcast.
     * Chỉ gửi dữ liệu cần thiết thay vì toàn bộ model để tránh vấn đề với Redis
     */
    public function broadcastWith(): array
    {
        $conversationId = null;
        if ($this->userId) {
            $user = \App\Models\User::with('conversation')->find($this->userId);
            $conversationId = $user->conversation->id ?? null;
        }
        
        return [
            'username' => $this->username,
            'full_name' => $this->full_name,
            'user_id' => $this->userId,
            'conversation_id' => $conversationId,
        ];
    }
}
