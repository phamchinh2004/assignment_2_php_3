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
    public $user;

    public function __construct($full_name, $message, $user = null)
    {
        $this->full_name = $full_name;
        $this->message = $message;
        $this->user = $user;
    }

    public function broadcastOn(): array
    {
        // Reload user từ database nếu cần (sau khi deserialize từ queue)
        if ($this->user) {
            // Nếu user không tồn tại trong database, reload bằng ID
            if (!$this->user->exists) {
                $userId = $this->user->id ?? null;
                if ($userId) {
                    $this->user = \App\Models\User::find($userId);
                }
            }
            
            // Reload relationship conversation sau khi deserialize từ queue
            if ($this->user && !$this->user->relationLoaded('conversation')) {
                $this->user->load('conversation');
            }
        }
        
        // Nếu không có thông tin user hoặc conversation, broadcast như cũ
        if (!$this->user || !$this->user->conversation) {
            return [
                new PrivateChannel("sent.message"),
            ];
        }

        $channels = [];
        $broadcastedIds = [];
        
        try {
            // Broadcast đến người được assign conversation này (có thể là staff hoặc admin)
            if ($this->user->conversation && $this->user->conversation->staff_id) {
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
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('UserSentMessage: Error in broadcastOn', [
                'error' => $e->getMessage(),
                'user_id' => $this->user->id ?? null
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
