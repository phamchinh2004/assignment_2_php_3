<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureAnnouncement extends Model
{
    use HasFactory;

    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_IMPORTANT = 'important';
    public const PRIORITY_CRITICAL = 'critical';
    public const PRIORITIES = [
        self::PRIORITY_NORMAL,
        self::PRIORITY_IMPORTANT,
        self::PRIORITY_CRITICAL,
    ];

    public const TARGET_ROLES = [
        User::ROLE_OWNER,
        User::ROLE_ADMIN,
        User::ROLE_STAFF,
    ];

    protected $fillable = [
        'title',
        'content',
        'priority',
        'starts_at',
        'ends_at',
        'is_active',
        'version',
        'target_roles',
        'action_text',
        'action_url',
        'image_path',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'version' => 'integer',
        'target_roles' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(FeatureAnnouncementRead::class, 'announcement_id');
    }

    public function scopeCurrentlyVisible(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where(function (Builder $query) use ($now) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->whereJsonContains('target_roles', $role);
    }
}
