<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;

class ChatContextService
{
    public function forConversation(User $actor, Conversation $conversation): array
    {
        $authorization = app(AuthorizationService::class);
        abort_unless($authorization->isManagementUser($actor) && $authorization->canViewConversation($actor, $conversation), 403);
        $canViewContext = $authorization->can($actor, config('authorization.capabilities.chats_view_context'));

        return [
            'images' => $conversation->messages()->whereNotNull('image_path')->where('image_path', '!=', '')
                ->latest('id')->limit(24)->get(['id', 'image_path', 'created_at'])->toArray(),
            'can_view_context' => $canViewContext,
            'orders' => $canViewContext ? app(ChatReferenceService::class)->recentOrders($conversation->user_id, 8) : [],
            'transactions' => $canViewContext ? app(ChatReferenceService::class)->recentTransactions($conversation->user_id, 8) : [],
        ];
    }
}
