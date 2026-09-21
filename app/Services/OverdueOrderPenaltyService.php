<?php

namespace App\Services;

use App\Models\Frozen_order;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class OverdueOrderPenaltyService
{
    public const PENALTY_RATE = 0.30;

    public function processingStartedAt(Frozen_order $frozenOrder): ?Carbon
    {
        $startedAt = $frozenOrder->processing_started_at
            ?? $frozenOrder->updated_at
            ?? $frozenOrder->order_date
            ?? $frozenOrder->created_at;

        return $startedAt ? Carbon::parse($startedAt) : null;
    }

    public function deadlineAt(Frozen_order $frozenOrder): ?Carbon
    {
        $startedAt = $this->processingStartedAt($frozenOrder);
        if (!$startedAt) {
            return null;
        }

        return $startedAt->copy()->addHours((int) ($frozenOrder->processing_time_limit ?? 24));
    }

    public function isOverdue(Frozen_order $frozenOrder, ?CarbonInterface $now = null): bool
    {
        if (!(bool) $frozenOrder->spun || !(bool) $frozenOrder->is_frozen) {
            return false;
        }

        if (in_array($frozenOrder->status, ['completed', 'cancelled', 'canceled'], true)) {
            return false;
        }

        $deadlineAt = $this->deadlineAt($frozenOrder);
        if (!$deadlineAt) {
            return false;
        }

        return Carbon::instance($now ?? now())->greaterThanOrEqualTo($deadlineAt);
    }

    public function calculatePenalty(Frozen_order $frozenOrder): float
    {
        $orderValue = (float) ($frozenOrder->snapshot_order_value ?? 0);

        return $orderValue > 0
            ? round($orderValue * self::PENALTY_RATE, 2)
            : 0.0;
    }

    public function applyIfOverdue(Frozen_order $frozenOrder, ?CarbonInterface $now = null): bool
    {
        if ((float) ($frozenOrder->penalty_amount ?? 0) > 0 || !$this->isOverdue($frozenOrder, $now)) {
            return false;
        }

        $penaltyAmount = $this->calculatePenalty($frozenOrder);
        if ($penaltyAmount <= 0) {
            return false;
        }

        $timestamps = $frozenOrder->timestamps;
        $frozenOrder->timestamps = false;

        try {
            $frozenOrder->penalty_amount = $penaltyAmount;
            $frozenOrder->save();
        } finally {
            $frozenOrder->timestamps = $timestamps;
        }

        return true;
    }

    public function applyPendingForUser(int $userId, ?CarbonInterface $now = null): int
    {
        $applied = 0;

        Frozen_order::query()
            ->where('user_id', $userId)
            ->where('spun', true)
            ->where('is_frozen', true)
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 'pending');
            })
            ->get()
            ->each(function (Frozen_order $frozenOrder) use (&$applied, $now) {
                if ($this->applyIfOverdue($frozenOrder, $now)) {
                    $applied++;
                }
            });

        return $applied;
    }
}
