<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LuckyWheelSpin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'prize',
        'spin_date',
    ];

    protected $casts = [
        'spin_date' => 'date',
    ];

    /**
     * Quan hệ với User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kiểm tra user đã quay trong ngày chưa
     */
    public static function hasSpunToday(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->whereDate('spin_date', today())
            ->exists();
    }

    /**
     * Lưu lịch sử quay
     */
    public static function recordSpin(int $userId, string $prize): self
    {
        return self::create([
            'user_id' => $userId,
            'prize' => $prize,
            'spin_date' => today(),
        ]);
    }
}

