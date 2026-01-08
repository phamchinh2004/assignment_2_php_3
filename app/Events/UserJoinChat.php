<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
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

        // Load user từ database với conversation relationship
        $user = \App\Models\User::with('conversation')->find($this->userId);
        
        // Nếu không tìm thấy user hoặc không có conversation, broadcast như cũ
        if (!$user || !$user->conversation) {
            return [
                new PrivateChannel("join.conversation"),
            ];
        }

        $channels = [];
        $broadcastedIds = [];
        
        try {
            // Broadcast đến người được assign conversation này (có thể là staff hoặc admin)
            if ($user->conversation->staff_id) {
                $channels[] = new PrivateChannel('staff.' . $user->conversation->staff_id);
                $broadcastedIds[] = $user->conversation->staff_id;
            }
            
            // Broadcast đến tất cả admin (trừ người đã nhận ở trên) - Cache 5 phút
            $admins = \Illuminate\Support\Facades\Cache::remember('admin_ids', 300, function () {
                return \App\Models\User::where('role', 'admin')->pluck('id')->toArray();
            });
            
            foreach ($admins as $adminId) {
                if (!in_array($adminId, $broadcastedIds)) {
                    $channels[] = new PrivateChannel('staff.' . $adminId);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserJoinChat: Error in broadcastOn', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId
            ]);
            // Return fallback channel
            return [new PrivateChannel("join.conversation")];
        }
        
        return $channels;
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
