<?php

namespace App\Services;

use App\Events\MoneyDeposited;
use App\Jobs\SendDepositNotificationEmail;
use App\Models\Frozen_order;
use App\Models\User;
use App\Models\Wallet_balance_history;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class UserDepositService
{
    public function deposit(
        User $user,
        float $amount,
        string $transactionType = 'normal',
        ?User $actor = null,
        ?string $actorName = null
    ): array {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Số tiền nạp phải lớn hơn 0.');
        }

        if (!in_array($transactionType, ['normal', 'bonus'], true)) {
            throw new InvalidArgumentException('Loại giao dịch nạp tiền không hợp lệ.');
        }

        $operation = function () use ($user, $amount, $transactionType, $actor, $actorName): array {
            $lockedUser = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $initialBalance = (float) ($lockedUser->balance ?? 0);
            $balanceBefore = $initialBalance + (float) ($lockedUser->frozen_balance ?? 0);

            $hasFrozenBalance = (float) ($lockedUser->frozen_balance ?? 0) > 0;
            $hasUnconfirmedHighValueOrder = Frozen_order::where('user_id', $lockedUser->id)
                ->whereNotNull('custom_price')
                ->where('is_frozen', true)
                ->where(function ($query) {
                    $query->where('status', 'pending')->orWhereNull('status');
                })
                ->exists();

            if ($hasFrozenBalance && $hasUnconfirmedHighValueOrder) {
                $lockedUser->frozen_balance = (float) ($lockedUser->frozen_balance ?? 0) + $initialBalance + $amount;
                $lockedUser->balance = 0;
                $balanceType = 'frozen_balance';
                $newBalance = (float) $lockedUser->frozen_balance;
            } else {
                $lockedUser->balance = $initialBalance + $amount;
                $balanceType = 'balance';
                $newBalance = (float) $lockedUser->balance;
            }

            $lockedUser->save();
            $balanceAfter = (float) ($lockedUser->balance ?? 0)
                + (float) ($lockedUser->frozen_balance ?? 0);

            $history = Wallet_balance_history::create([
                'user_id' => $lockedUser->id,
                'value' => $amount,
                'initial_balance' => $initialBalance,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'type' => 'deposit',
                'status' => 'completed',
                'by_user_id' => $actor?->id,
                'transaction_type' => $transactionType,
            ]);

            $notificationName = $actorName
                ?: ($actor?->full_name ?? $actor?->username ?? 'Hệ thống');
            $eventBalance = $balanceType === 'frozen_balance'
                ? (float) $lockedUser->balance
                : $newBalance;
            $email = $lockedUser->email;
            $userId = (int) $lockedUser->id;

            DB::afterCommit(function () use (
                $userId,
                $amount,
                $eventBalance,
                $transactionType,
                $notificationName,
                $email,
                $lockedUser
            ): void {
                event(new MoneyDeposited(
                    $userId,
                    $amount,
                    $eventBalance,
                    $transactionType,
                    $notificationName
                ));

                if (!$email) {
                    return;
                }

                try {
                    SendDepositNotificationEmail::dispatch(
                        $userId,
                        $email,
                        $amount,
                        (float) $lockedUser->balance,
                        $transactionType,
                        $notificationName,
                    );
                } catch (\Throwable $exception) {
                    Log::error('Không thể đưa email nạp tiền vào hàng đợi.', [
                        'user_id' => $userId,
                        'amount' => $amount,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });

            return [
                'user' => $lockedUser,
                'history' => $history,
                'initial_balance' => $initialBalance,
                'new_balance' => $newBalance,
                'balance_type' => $balanceType,
            ];
        };

        if (DB::transactionLevel() > 0) {
            return $operation();
        }

        return DB::transaction($operation);
    }
}
