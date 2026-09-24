<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureAnnouncementRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'announcement_version',
        'acknowledged_at',
    ];

    protected $casts = [
        'announcement_version' => 'integer',
        'acknowledged_at' => 'datetime',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(FeatureAnnouncement::class, 'announcement_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
