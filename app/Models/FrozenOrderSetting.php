<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrozenOrderSetting extends Model
{
    use HasFactory;

    protected $table = 'frozen_order_settings';

    protected $fillable = [
        'processing_time_limit',
        'notification_1_remaining_time',
        'notification_2_remaining_time',
    ];

    public static function defaults(): self
    {
        $setting = static::query()->first();

        if ($setting) {
            return $setting;
        }

        return static::query()->create([
            'processing_time_limit' => 24,
            'notification_1_remaining_time' => 12,
            'notification_2_remaining_time' => 1,
        ]);
    }
}
