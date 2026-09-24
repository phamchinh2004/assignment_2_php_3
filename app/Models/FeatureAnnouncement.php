<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public const TARGET_TYPE_ROLES = 'roles';
    public const TARGET_TYPE_USERS = 'users';
    public const TARGET_TYPES = [
        self::TARGET_TYPE_ROLES,
        self::TARGET_TYPE_USERS,
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
        'target_type',
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

    public function targetedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'feature_announcement_targets',
            'announcement_id',
            'user_id'
        )->withTimestamps();
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

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->where(function (Builder $query) use ($user) {
                $query->where('target_type', self::TARGET_TYPE_ROLES)
                    ->whereJsonContains('target_roles', $user->role);
            })->orWhere(function (Builder $query) use ($user) {
                $query->where('target_type', self::TARGET_TYPE_USERS)
                    ->whereHas('targetedUsers', function (Builder $query) use ($user) {
                        $query->where('users.id', $user->id);
                    });
            });
        });
    }
}
