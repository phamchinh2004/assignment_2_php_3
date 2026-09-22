<?php

use App\Models\Conversation;
use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('staff.{staffId}', function ($user, $staffId) {
    return (int) $user->id === (int) $staffId;
});
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
Broadcast::channel('join.conversation', function () {
    return true;
});
Broadcast::channel('sent.message', function () {
    return true;
});
Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::with(['user', 'staff'])->find($conversationId);

    Log::debug('Auth for user:', [
        'user_id' => $user->id ?? null,
        'role' => $user->role ?? null,
        'conversation_id' => $conversationId,
        'conversation_exists' => !!$conversation,
    ]);

    if (!$conversation) {
        Log::warning('Conversation not found', ['conversation_id' => $conversationId]);
        return false;
    }

    $hasAccess = app(AuthorizationService::class)->canViewConversation($user, $conversation);
    Log::debug('Conversation access check', ['has_access' => $hasAccess]);

    return $hasAccess;
});
