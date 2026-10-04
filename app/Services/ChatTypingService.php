<?php

namespace App\Services;

use App\Events\ChatTyping;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ChatTypingService
{
    public function update(User $actor, int $conversationId, bool $typing): void
    {
        $conversation = Conversation::with('staff:id,role,referrer_id')->find($conversationId);
        abort_unless($conversation && app(AuthorizationService::class)->canViewConversation($actor, $conversation), 403);
        $key = 'chat-typing:'.$conversationId.':'.$actor->id;
        if ($typing && !Cache::add($key, true, 2)) return;
        if (!$typing) Cache::forget($key);
        broadcast(new ChatTyping($conversationId, $actor->id, (string) $actor->full_name, $typing));
    }
}
