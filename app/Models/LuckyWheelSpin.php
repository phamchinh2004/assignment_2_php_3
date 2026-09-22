<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LuckyWheelSpin extends Model
{
    use HasFactory;

    public const TYPE_DAILY_COMPLETION = 'daily_completion';
    public const TYPE_ADMIN_BONUS = 'admin_bonus';

    protected $fillable = [
        'user_id',
        'prize',
        'spin_type',
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
            ->where('spin_type', self::TYPE_DAILY_COMPLETION)
            ->whereDate('spin_date', today())
            ->exists();
    }

    /**
     * Lưu lịch sử quay
     */
    public static function recordSpin(
        int $userId,
        string $prize,
        string $spinType = self::TYPE_DAILY_COMPLETION
    ): self
    {
        return self::create([
            'user_id' => $userId,
            'prize' => $prize,
            'spin_type' => $spinType,
            'spin_date' => today(),
        ]);
    }
}
