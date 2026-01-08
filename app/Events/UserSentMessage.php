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

        // Load user từ database với conversation relationship
        $user = \App\Models\User::with('conversation')->find($this->userId);
        
        // Nếu không tìm thấy user hoặc không có conversation, broadcast như cũ
        if (!$user || !$user->conversation) {
            return [
                new PrivateChannel("sent.message"),
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
            \Illuminate\Support\Facades\Log::error('UserSentMessage: Error in broadcastOn', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId
            ]);
            // Return fallback channel
            return [new PrivateChannel("sent.message")];
        }
        
        return $channels;
    }

    public function broadcastAs()
    {
        return 'UserSentMessage';
    }
}
