<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class AdminHeaderService
{
    public function __construct(private AuthorizationService $authorization)
    {
    }

    public function state(User $user, int $notificationLimit = 6, int $messageLimit = 6): array
    {
        return [
            'notifications' => $this->notifications($user, $notificationLimit),
            'messages' => $this->messages($user, $messageLimit),
        ];
    }

    private function notifications(User $user, int $limit): array
    {
        $limit = max(1, min($limit, 30));

        $notifications = $user->notifications()
            ->latest()
            ->limit($limit)
            ->get();

        $conversationIds = $notifications
            ->pluck('data.conversation_id')
            ->filter()
            ->map(fn ($conversationId) => (int) $conversationId)
            ->unique()
            ->values();

        $conversationPublicIds = $conversationIds->isEmpty()
            ? collect()
            : Conversation::query()
                ->whereIn('id', $conversationIds)
                ->pluck('public_id', 'id');

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count' => $user->notifications()->count(),
            'items' => $notifications
                ->map(fn (DatabaseNotification $notification) => $this->notificationItem(
                    $notification,
                    $conversationPublicIds
                ))
                ->values()
                ->all(),
        ];
    }

    private function notificationItem(DatabaseNotification $notification, $conversationPublicIds): array
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $type = (string) ($data['type'] ?? class_basename($notification->type));

        return [
            'id' => $notification->id,
            'title' => (string) ($data['title'] ?? 'Thông báo'),
            'message' => (string) ($data['message'] ?? $data['body'] ?? ''),
            'type' => $type,
            'icon' => $this->notificationIcon($type),
            'target_url' => $this->notificationTarget($data, $type, $conversationPublicIds),
            'is_read' => $notification->read_at !== null,
            'created_at' => optional($notification->created_at)->toIso8601String(),
        ];
    }

    private function notificationTarget(array $data, string $type, $conversationPublicIds): ?string
    {
        $conversationId = (int) ($data['conversation_id'] ?? 0);
        $conversationPublicId = $conversationId > 0
            ? $conversationPublicIds->get($conversationId)
            : null;

        if ($conversationPublicId && Str::contains(Str::lower($type), ['message', 'chat'])) {
            return route('chat-panel', ['conversation' => $conversationPublicId]);
        }

        $target = $data['target_url'] ?? $data['url'] ?? null;

        return is_string($target) && $target !== '' ? $target : null;
    }

    private function notificationIcon(string $type): string
    {
        $type = Str::lower($type);

        return match (true) {
            Str::contains($type, ['message', 'chat']) => 'fa-envelope',
            Str::contains($type, ['order']) => 'fa-shopping-bag',
            Str::contains($type, ['deposit', 'withdraw', 'transaction', 'money']) => 'fa-dollar-sign',
            Str::contains($type, ['warning', 'report', 'alert']) => 'fa-exclamation-triangle',
            default => 'fa-bell',
        };
    }

    private function messages(User $user, int $limit): array
    {
        $limit = max(1, min($limit, 12));

        $unreadQuery = Message::query()
            ->unreadFor($user->id)
            ->whereHas('conversation', function ($query) use ($user) {
                $this->scopeVisibleConversations($query, $user);
            });

        $conversationsQuery = Conversation::query()
            ->with([
                'user:id,full_name,username',
                'latestMessage.sender:id,full_name,username,role',
            ])
            ->withCount([
                'messages as unread_count' => fn ($query) => $query
                    ->unreadFor($user->id),
            ])
            ->whereHas('messages');

        $this->scopeVisibleConversations($conversationsQuery, $user);

        $conversations = $conversationsQuery
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();

        return [
            'unread_count' => $unreadQuery->count(),
            'conversations' => $conversations
                ->map(function (Conversation $conversation) use ($user) {
                    $message = $conversation->latestMessage;

                    return [
                        'id' => $conversation->id,
                        'participant_name' => $conversation->user?->full_name
                            ?: $conversation->user?->username
                            ?: 'Người dùng',
                        'sender_name' => $message?->sender?->full_name
                            ?: $message?->sender?->username,
                        'preview' => ($message && (int) $message->sender_id === (int) $user->id ? 'Bạn: ' : '') . match ($message?->kind ?? $message?->type) {
                            'order_reference' => 'Đã gửi đơn hàng liên quan',
                            'transaction_reference' => 'Đã gửi giao dịch liên quan',
                            'image' => 'Đã gửi hình ảnh',
                            default => Str::limit(trim(strip_tags((string) $message?->message)), 90),
                        },
                        'unread_count' => (int) $conversation->unread_count,
                        'is_unread' => (int) $conversation->unread_count > 0,
                        'created_at' => optional($message?->created_at)->toIso8601String(),
                        'target_url' => route('chat-panel', [
                            'conversation' => $conversation->public_id,
                        ]),
                    ];
                })
                ->values()
                ->all(),
            'all_messages_url' => route('chat-panel'),
        ];
    }

    private function scopeVisibleConversations($query, User $user): void
    {
        if ($this->authorization->isSuperuser($user)) {
            $query->where(function ($conversation) use ($user) {
                $conversation->where('staff_id', $user->id)
                    ->orWhereHas('staff', fn ($staff) => $staff
                        ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN]));
            });
            return;
        }

        if (
            $user->role === User::ROLE_ADMIN
            && $this->authorization->can($user, config('authorization.capabilities.manage_all_chats'))
        ) {
            $query->where(function ($conversation) use ($user) {
                $conversation->where('staff_id', $user->id)
                    ->orWhereHas('staff', fn ($staff) => $staff->where('role', User::ROLE_STAFF));
            });
            return;
        }

        $query->where('staff_id', $user->id);
    }
}
