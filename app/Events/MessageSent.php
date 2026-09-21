<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;

    public function __construct($messageId)
    {
        $this->messageId = $messageId;
    }

    public function broadcastOn()
    {
        // Load message từ database với relationships
        $message = \App\Models\Message::with(['sender', 'conversation.user'])->find($this->messageId);

        if (!$message) {
            return [];
        }

        $channels = [
            new PrivateChannel('chat.conversation.' . $message->conversation_id)
        ];

        $broadcastedIds = []; // Tránh duplicate channels

        // Ưu tiên tài khoản quản lý hiện tại của user theo referrer_id.
        $managerId = $message->conversation?->user?->referrer_id;
        if (!$managerId) {
            $managerId = $message->conversation?->staff_id;
        }

        if ($managerId) {
            $channels[] = new PrivateChannel('staff.' . $managerId);
            $broadcastedIds[] = (int) $managerId;
        }

        // Broadcast đến tất cả admin, kể cả admin không quản lý trực tiếp user.
        $admins = \App\Models\User::where('role', 'admin')->pluck('id');

        foreach ($admins as $adminId) {
            if (!in_array((int) $adminId, $broadcastedIds, true)) {
                $channels[] = new PrivateChannel('staff.' . $adminId);
            }
        }

        return $channels;
    }

    public function broadcastWith()
    {
        // Load message từ database với relationships
        $message = \App\Models\Message::with('sender', 'conversation')->find($this->messageId);

        if (!$message) {
            return ['message' => null];
        }

        return [
            'message' => [
                'id' => $message->id,
                'message' => $message->message,
                'image_path' => $message->image_path,
                'type' => $message->type,
                'kind' => $message->kind ?: $message->type,
                'reference_type' => $message->reference_type,
                'reference_id' => $message->reference_id,
                'reference_payload' => $message->reference_payload,
                'sender_id' => $message->sender_id,
                'conversation_id' => $message->conversation_id,
                'is_read' => $message->is_read ?? false,
                'created_at' => $message->created_at,
                'sender' => $message->sender ? [
                    'id' => $message->sender->id,
                    'full_name' => $message->sender->full_name,
                    'role' => $message->sender->role,
                ] : null
            ]
        ];
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }
}
