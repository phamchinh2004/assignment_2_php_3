<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'staff_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'staff_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Conversation $conversation) {
            if (!$conversation->public_id) {
                $conversation->public_id = (string) Str::uuid();
            }
        });
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /** Personal unread counts and the assignee's pending customer messages are independent. */
    public function scopeWithInboxStateFor(Builder $query, int $viewerId): Builder
    {
        return $query->withCount(['messages as unread_count' => fn ($messages) => $messages->unreadFor($viewerId)])
            ->withExists(['messages as awaiting_reply' => function ($messages) {
                $messages->whereColumn('messages.sender_id', 'conversations.user_id')
                    ->whereNotExists(function ($reads) {
                        $reads->selectRaw('1')->from('message_reads')
                            ->whereColumn('message_reads.message_id', 'messages.id')
                            ->whereColumn('message_reads.user_id', 'conversations.staff_id');
                    });
            }]);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
