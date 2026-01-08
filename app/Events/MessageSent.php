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

    public $messageId;

    public function __construct($messageId)
    {
        $this->messageId = $messageId;
    }

    public function broadcastOn()
    {
        // Reload message từ database (sau khi deserialize từ queue)
        $message = Message::with(['sender', 'conversation'])->find($this->messageId);
        
        if (!$message) {
            \Illuminate\Support\Facades\Log::error('MessageSent: Message not found', [
                'message_id' => $this->messageId
            ]);
            return [];
        }
        
        // Kiểm tra conversation_id tồn tại
        if (!$message->conversation_id) {
            \Illuminate\Support\Facades\Log::error('MessageSent: conversation_id is null', [
                'message_id' => $message->id
            ]);
            return [];
        }
        
        $channels = [
            new PrivateChannel('chat.conversation.' . $message->conversation_id)
        ];
        
        $broadcastedIds = []; // Tránh duplicate channels
        
        // Broadcast đến người được assign conversation này (có thể là staff hoặc admin)
        if ($message->conversation && $message->conversation->staff_id) {
            $channels[] = new PrivateChannel('staff.' . $message->conversation->staff_id);
            $broadcastedIds[] = $message->conversation->staff_id;
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
        // Reload message từ database (sau khi deserialize từ queue)
        $message = Message::with('sender')->find($this->messageId);
        
        if (!$message) {
            return ['message' => null];
        }
        
        // Xử lý an toàn khi sender không tồn tại
        $senderData = null;
        if ($message->sender) {
            $senderData = [
                'id' => $message->sender->id,
                'full_name' => $message->sender->full_name ?? '',
                'role' => $message->sender->role ?? 'member',
            ];
        }
        
        return [
            'message' => [
                'id' => $message->id,
                'message' => $message->message ?? '',
                'image_path' => $message->image_path,
                'type' => $message->type ?? 'text',
                'sender_id' => $message->sender_id,
                'conversation_id' => $message->conversation_id,
                'is_read' => $message->is_read ?? false,
                'created_at' => $message->created_at ? $message->created_at->toIso8601String() : now()->toIso8601String(),
                'sender' => $senderData
            ]
        ];
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }
}
