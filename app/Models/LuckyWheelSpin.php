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

    public const REWARD_CASH = 'cash';
    public const REWARD_ITEM = 'item';
    public const REWARD_NONE = 'none';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_NO_REWARD = 'no_reward';
    public const STATUS_LEGACY = 'legacy';

    public const APPROVAL_MANUAL = 'manual';
    public const APPROVAL_AUTOMATIC = 'automatic';

    protected $fillable = [
        'user_id',
        'prize',
        'prize_index',
        'spin_type',
        'reward_type',
        'reward_amount',
        'reward_status',
        'approval_method',
        'handled_by',
        'handled_at',
        'wallet_balance_history_id',
        'spin_date',
    ];

    protected $casts = [
        'spin_date' => 'date',
        'reward_amount' => 'decimal:2',
        'handled_at' => 'datetime',
    ];

    /**
     * Quan hệ với User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function walletHistory(): BelongsTo
    {
        return $this->belongsTo(Wallet_balance_history::class, 'wallet_balance_history_id');
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
        array $prize,
        string $spinType = self::TYPE_DAILY_COMPLETION
    ): self {
        $rewardType = $prize['reward_type'] ?? self::REWARD_NONE;

        return self::create([
            'user_id' => $userId,
            'prize' => $prize['name'],
            'prize_index' => $prize['index'],
            'spin_type' => $spinType,
            'reward_type' => $rewardType,
            'reward_amount' => $prize['amount'] ?? null,
            'reward_status' => $rewardType === self::REWARD_NONE
                ? self::STATUS_NO_REWARD
                : self::STATUS_PENDING,
            'spin_date' => today(),
        ]);
    }

    public static function drawPrize(): array
    {
        $definitions = self::prizeDefinitions();
        $totalWeight = array_sum(array_column($definitions, 'weight'));
        $roll = random_int(1, $totalWeight);
        $cursor = 0;

        foreach ($definitions as $definition) {
            $cursor += $definition['weight'];
            if ($definition['weight'] > 0 && $roll <= $cursor) {
                return $definition;
            }
        }

        return $definitions[7];
    }

    public static function prizeDefinitions(): array
    {
        return [
            [
                'index' => 0,
                'name' => '18 Pro Max',
                'reward_type' => self::REWARD_ITEM,
                'amount' => null,
                'weight' => 0,
            ],
            [
                'index' => 1,
                'name' => '$2',
                'reward_type' => self::REWARD_CASH,
                'amount' => 2.00,
                'weight' => 4,
            ],
            [
                'index' => 2,
                'name' => 'Chúc bạn may mắn lần sau',
                'reward_type' => self::REWARD_NONE,
                'amount' => null,
                'weight' => 6,
            ],
            [
                'index' => 3,
                'name' => '$10',
                'reward_type' => self::REWARD_CASH,
                'amount' => 10.00,
                'weight' => 1,
            ],
            [
                'index' => 4,
                'name' => '$2',
                'reward_type' => self::REWARD_CASH,
                'amount' => 2.00,
                'weight' => 4,
            ],
            [
                'index' => 5,
                'name' => '$5',
                'reward_type' => self::REWARD_CASH,
                'amount' => 5.00,
                'weight' => 3,
            ],
            [
                'index' => 6,
                'name' => 'Chúc bạn may mắn lần sau',
                'reward_type' => self::REWARD_NONE,
                'amount' => null,
                'weight' => 6,
            ],
            [
                'index' => 7,
                'name' => '$2',
                'reward_type' => self::REWARD_CASH,
                'amount' => 2.00,
                'weight' => 4,
            ],
        ];
    }

    public function rewardStatusLabel(): string
    {
        return match ($this->reward_status) {
            self::STATUS_PENDING => 'Đang chờ duyệt',
            self::STATUS_APPROVED => $this->approval_method === self::APPROVAL_AUTOMATIC
                ? 'Đã tự động duyệt'
                : 'Đã duyệt',
            self::STATUS_REJECTED => 'Đã từ chối',
            self::STATUS_NO_REWARD => 'Không trúng thưởng',
            default => 'Lịch sử cũ',
        };
    }
}
