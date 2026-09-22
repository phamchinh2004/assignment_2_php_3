<?php

namespace App\Services;

use App\Models\LuckyWheelSpin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LuckyWheelRewardService
{
    public function __construct(
        private readonly UserDepositService $depositService
    ) {
    }

    public function approve(
        LuckyWheelSpin $spin,
        ?User $actor = null,
        string $method = LuckyWheelSpin::APPROVAL_MANUAL
    ): LuckyWheelSpin {
        $operation = function () use ($spin, $actor, $method): LuckyWheelSpin {
            $lockedSpin = LuckyWheelSpin::query()
                ->whereKey($spin->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSpin->reward_status === LuckyWheelSpin::STATUS_APPROVED) {
                return $lockedSpin;
            }

            if (
                $lockedSpin->reward_type !== LuckyWheelSpin::REWARD_CASH
                || $lockedSpin->reward_status !== LuckyWheelSpin::STATUS_PENDING
                || (float) $lockedSpin->reward_amount <= 0
            ) {
                throw ValidationException::withMessages([
                    'reward' => 'Phần thưởng này không ở trạng thái có thể duyệt.',
                ]);
            }

            $user = User::whereKey($lockedSpin->user_id)->lockForUpdate()->firstOrFail();
            $actorName = $method === LuckyWheelSpin::APPROVAL_AUTOMATIC
                ? 'Vòng quay may mắn'
                : ($actor?->full_name ?? $actor?->username ?? 'Quản trị viên');

            $deposit = $this->depositService->deposit(
                $user,
                (float) $lockedSpin->reward_amount,
                'bonus',
                $actor,
                $actorName
            );

            $lockedSpin->forceFill([
                'reward_status' => LuckyWheelSpin::STATUS_APPROVED,
                'approval_method' => $method,
                'handled_by' => $actor?->id,
                'handled_at' => now(),
                'wallet_balance_history_id' => $deposit['history']->id,
            ])->save();

            return $lockedSpin->fresh(['handledBy', 'walletHistory']);
        };

        if (DB::transactionLevel() > 0) {
            return $operation();
        }

        return DB::transaction($operation);
    }

    public function reject(LuckyWheelSpin $spin, User $actor): LuckyWheelSpin
    {
        $operation = function () use ($spin, $actor): LuckyWheelSpin {
            $lockedSpin = LuckyWheelSpin::query()
                ->whereKey($spin->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSpin->reward_status === LuckyWheelSpin::STATUS_REJECTED) {
                return $lockedSpin;
            }

            if (
                $lockedSpin->reward_type !== LuckyWheelSpin::REWARD_CASH
                || $lockedSpin->reward_status !== LuckyWheelSpin::STATUS_PENDING
            ) {
                throw ValidationException::withMessages([
                    'reward' => 'Phần thưởng này không ở trạng thái có thể từ chối.',
                ]);
            }

            $lockedSpin->forceFill([
                'reward_status' => LuckyWheelSpin::STATUS_REJECTED,
                'approval_method' => null,
                'handled_by' => $actor->id,
                'handled_at' => now(),
                'wallet_balance_history_id' => null,
            ])->save();

            return $lockedSpin->fresh(['handledBy']);
        };

        if (DB::transactionLevel() > 0) {
            return $operation();
        }

        return DB::transaction($operation);
    }
}
