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
        // Reload message từ database nếu cần (sau khi deserialize từ queue)
        // SerializesModels có thể làm mất relationships
        if (!$this->message) {
            \Illuminate\Support\Facades\Log::error('MessageSent: Message is null');
            return [];
        }
        
        // Nếu message không tồn tại trong database, reload bằng ID
        if (!$this->message->exists) {
            $messageId = $this->message->id ?? null;
            if ($messageId) {
                $this->message = \App\Models\Message::find($messageId);
            }
        }
        
        // Nếu vẫn không có message, return empty array
        if (!$this->message || !$this->message->exists) {
            \Illuminate\Support\Facades\Log::error('MessageSent: Message not found', [
                'message_id' => $this->message->id ?? 'unknown'
            ]);
            return [];
        }
        
        // Reload relationships sau khi deserialize từ queue
        // Đảm bảo relationships tồn tại khi broadcast
        if (!$this->message->relationLoaded('sender')) {
            $this->message->load('sender');
        }
        if (!$this->message->relationLoaded('conversation')) {
            $this->message->load('conversation');
        }
        
        // Kiểm tra conversation_id tồn tại
        if (!$this->message->conversation_id) {
            \Illuminate\Support\Facades\Log::error('MessageSent: conversation_id is null', [
                'message_id' => $this->message->id
            ]);
            return [];
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
        try {
            $admins = \Illuminate\Support\Facades\Cache::remember('admin_ids', 300, function () {
                return \App\Models\User::where('role', 'admin')->pluck('id')->toArray();
            });
            
            foreach ($admins as $adminId) {
                if (!in_array($adminId, $broadcastedIds)) {
                    $channels[] = new PrivateChannel('staff.' . $adminId);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('MessageSent: Error loading admins', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $channels;
    }

    public function broadcastWith()
    {
        // Reload message nếu cần (sau khi deserialize từ queue)
        if (!$this->message) {
            return ['message' => null];
        }
        
        // Nếu message không tồn tại trong database, reload bằng ID
        if (!$this->message->exists) {
            $messageId = $this->message->id ?? null;
            if ($messageId) {
                $this->message = \App\Models\Message::find($messageId);
            }
        }
        
        if (!$this->message || !$this->message->exists) {
            return ['message' => null];
        }
        
        // Reload relationships nếu chưa được load (sau khi deserialize từ queue)
        if (!$this->message->relationLoaded('sender')) {
            $this->message->load('sender');
        }
        
        // Xử lý an toàn khi sender không tồn tại
        $senderData = null;
        if ($this->message->sender) {
            $senderData = [
                'id' => $this->message->sender->id,
                'full_name' => $this->message->sender->full_name ?? '',
                'role' => $this->message->sender->role ?? 'member',
            ];
        }
        
        return [
            'message' => [
                'id' => $this->message->id,
                'message' => $this->message->message ?? '',
                'image_path' => $this->message->image_path,
                'type' => $this->message->type ?? 'text',
                'sender_id' => $this->message->sender_id,
                'conversation_id' => $this->message->conversation_id,
                'is_read' => $this->message->is_read ?? false,
                'created_at' => $this->message->created_at ? $this->message->created_at->toIso8601String() : now()->toIso8601String(),
                'sender' => $senderData
            ]
        ];
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }
}
