<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusTiming extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_status',
        'to_status',
        'min_time',
        'max_time',
        'time_unit',
        'description',
        'is_active',
    ];

    protected $casts = [
        'min_time' => 'integer',
        'max_time' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Lấy thời gian chuyển trạng thái (tính bằng phút)
     */
    public function getMinTimeInMinutes(): int
    {
        return match($this->time_unit) {
            'minutes' => $this->min_time,
            'hours' => $this->min_time * 60,
            'days' => $this->min_time * 24 * 60,
            default => $this->min_time,
        };
    }

    /**
     * Lấy thời gian chuyển trạng thái tối đa (tính bằng phút)
     */
    public function getMaxTimeInMinutes(): int
    {
        return match($this->time_unit) {
            'minutes' => $this->max_time,
            'hours' => $this->max_time * 60,
            'days' => $this->max_time * 24 * 60,
            default => $this->max_time,
        };
    }

    /**
     * Lấy cấu hình theo from_status và to_status
     */
    public static function getTiming(string $fromStatus, string $toStatus): ?self
    {
        return self::where('from_status', $fromStatus)
            ->where('to_status', $toStatus)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Scope để lấy các timing đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
