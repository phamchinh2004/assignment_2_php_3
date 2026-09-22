<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LuckyWheelSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'auto_approve_rewards',
        'updated_by',
    ];

    protected $casts = [
        'auto_approve_rewards' => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            ['auto_approve_rewards' => false]
        );
    }
}
