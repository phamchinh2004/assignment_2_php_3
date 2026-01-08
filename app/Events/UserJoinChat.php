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
    public $user;

    public function __construct($username, $full_name, $user = null)
    {
        $this->username = $username;
        $this->full_name = $full_name;
        $this->user = $user;
    }

    public function broadcastOn(): array
    {
        // Nếu không có thông tin user, broadcast như cũ
        if (!$this->user || !$this->user->conversation) {
            return [
                new PrivateChannel("join.conversation"),
            ];
        }

        $channels = [];
        $broadcastedIds = [];
        
        // Broadcast đến người được assign conversation này (có thể là staff hoặc admin)
        if ($this->user->conversation->staff_id) {
            $channels[] = new PrivateChannel('staff.' . $this->user->conversation->staff_id);
            $broadcastedIds[] = $this->user->conversation->staff_id;
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
