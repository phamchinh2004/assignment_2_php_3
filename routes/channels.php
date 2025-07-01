<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

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

use Illuminate\Support\Facades\Log;

Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    Log::debug('Auth for user:', [
        'user_id' => $user->id ?? null,
        'role' => $user->role ?? null,
        'conversation_id' => $conversationId,
        'matched' => $conversation?->user_id === $user->id,
    ]);

    if (!$conversation) return false;

    if ($user->role === User::ROLE_ADMIN) return true;
    if ($user->role === User::ROLE_STAFF) return $conversation->staff_id === $user->id;
    if ($user->role === User::ROLE_MEMBER) return $conversation->user_id === $user->id;

    return false;
});
