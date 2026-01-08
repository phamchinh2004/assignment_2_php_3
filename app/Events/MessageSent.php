<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load('sender', 'conversation');
    }

    public function broadcastOn()
    {
        // Reload relationships sau khi deserialize từ queue
        // Đảm bảo relationships tồn tại khi broadcast
        if ($this->message && !$this->message->relationLoaded('sender')) {
            $this->message->load('sender');
        }
        if ($this->message && !$this->message->relationLoaded('conversation')) {
            $this->message->load('conversation');
        }
        
        $channels = [
            new PrivateChannel('chat.conversation.' . $this->message->conversation_id)
        ];
        
        $broadcastedIds = []; // Tránh duplicate channels
        
        // Broadcast đến người được assign conversation này (có thể là staff hoặc admin)
        if ($this->message->conversation && $this->message->conversation->staff_id) {
            $channels[] = new PrivateChannel('staff.' . $this->message->conversation->staff_id);
            $broadcastedIds[] = $this->message->conversation->staff_id;
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
        
        return $channels;
    }

    public function broadcastWith()
    {
        // Reload relationships nếu chưa được load (sau khi deserialize từ queue)
        if ($this->message && !$this->message->relationLoaded('sender')) {
            $this->message->load('sender');
        }
        
        return [
            'message' => [
                'id' => $this->message->id,
                'message' => $this->message->message,
                'image_path' => $this->message->image_path,
                'type' => $this->message->type,
                'sender_id' => $this->message->sender_id,
                'conversation_id' => $this->message->conversation_id,
                'is_read' => $this->message->is_read ?? false,
                'created_at' => $this->message->created_at,
                'sender' => [
                    'id' => $this->message->sender->id,
                    'full_name' => $this->message->sender->full_name,
                    'role' => $this->message->sender->role,
                ]
            ]
        ];
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }
}
