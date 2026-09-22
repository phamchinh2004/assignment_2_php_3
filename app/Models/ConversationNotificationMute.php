<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ConversationNotificationMute extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'muted_until',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'conversation_id' => 'integer',
        'muted_until' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNull('muted_until')
                ->orWhere('muted_until', '>', now());
        });
    }

    public static function isMutedFor(int $userId, int $conversationId): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('conversation_id', $conversationId)
            ->active()
            ->exists();
    }
}
