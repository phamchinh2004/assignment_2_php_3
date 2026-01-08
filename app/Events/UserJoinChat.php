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

        // Reload user từ database (sau khi deserialize từ queue)
        $user = \App\Models\User::with('conversation')->find($this->userId);
        
        if (!$user || !$user->conversation) {
            return [
                new PrivateChannel("join.conversation"),
            ];
        }

        $channels = [];
        $broadcastedIds = [];
        
        // Broadcast đến người được assign conversation này (có thể là staff hoặc admin)
        if ($user->conversation->staff_id) {
            $channels[] = new PrivateChannel('staff.' . $user->conversation->staff_id);
            $broadcastedIds[] = $user->conversation->staff_id;
        }
        
        // Broadcast đến tất cả admin (trừ người đã nhận ở trên)
        $admins = \App\Models\User::where('role', 'admin')->pluck('id');
        foreach ($admins as $adminId) {
            if (!in_array($adminId, $broadcastedIds)) {
                $channels[] = new PrivateChannel('staff.' . $adminId);
            }
        }
        
        return $channels;
    }

    public function broadcastAs()
    {
        return 'UserJoinChat';
    }
}
