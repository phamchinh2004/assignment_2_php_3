<?php

namespace App\Services;

use App\Models\Frozen_order;
use App\Models\Transaction_history;
use Illuminate\Support\Collection;

class FrozenOrderSettlementService
{
    public function detail(Frozen_order $frozenOrder): array
    {
        $transactions = $this->transactionsFor($frozenOrder)
            ->filter(function (Transaction_history $transaction) use ($frozenOrder) {
                if ($frozenOrder->created_at && $transaction->created_at?->lt($frozenOrder->created_at)) {
                    return false;
                }

                $terminalAt = $frozenOrder->completed_at ?? $frozenOrder->cancelled_at;

                return !$terminalAt
                    || !$transaction->created_at
                    || $transaction->created_at->lte($terminalAt->copy()->addMinute());
            })
            ->sortBy('created_at')
            ->values();
        $settlement = $this->resolve($frozenOrder, $transactions);
        $isCancelled = in_array($frozenOrder->status, ['cancelled', 'canceled'], true);
        $orderTransactions = $transactions->where('type', 'order');
        $profitTransactions = $transactions->where('type', 'profit');
        $penaltyTransactions = $transactions->where('type', 'penalty');

        $deductedAmount = $settlement['is_exact']
            ? $settlement['deducted_amount']
            : $this->uniqueValue($orderTransactions, 'order');
        $commissionPaid = $settlement['is_exact']
            ? $settlement['commission_amount']
            : $this->uniqueValue($profitTransactions, 'profit');
        $penaltyPaid = $settlement['is_exact']
            ? $settlement['penalty_amount']
            : $this->uniqueValue($penaltyTransactions, 'penalty');

        if ($isCancelled && $orderTransactions->isEmpty()) {
            $deductedAmount = 0.0;
        }

        $refundAmount = $settlement['is_exact'] ? $settlement['refund_amount'] : null;
        if ($isCancelled && (float) $deductedAmount === 0.0) {
            $refundAmount = 0.0;
        }

        $settlementState = 'pending';
        $settlementLabel = 'Chưa quyết toán';
        if ($isCancelled && (float) $deductedAmount === 0.0) {
            $settlementState = 'not_required';
            $settlementLabel = 'Không phát sinh quyết toán';
        } elseif ($settlement['is_exact']) {
            $settlementState = 'settled';
            $settlementLabel = 'Đã quyết toán';
        } elseif ($isCancelled && $deductedAmount !== null) {
            $settlementState = 'needs_review';
            $settlementLabel = 'Cần đối soát hoàn nhập';
        }

        return [
            'is_cancelled' => $isCancelled,
            'is_completed' => $frozenOrder->status === 'completed',
            'has_penalty' => (float) ($frozenOrder->penalty_amount ?? 0) > 0,
            'order_amount' => $frozenOrder->snapshot_order_value,
            'expected_commission' => $frozenOrder->snapshot_commission_value,
            'deducted_amount' => $deductedAmount,
            'commission_paid' => $commissionPaid,
            'penalty_paid' => $penaltyPaid,
            'refund_amount' => $refundAmount,
            'balance_destination' => $settlement['balance_destination'],
            'settled_at' => $settlement['settled_at'],
            'settlement_state' => $settlementState,
            'settlement_label' => $settlementLabel,
            'settlement_source' => $settlement['source_label'],
            'settlement_reason' => $settlement['reason'],
            'transactions' => $transactions,
        ];
    }

    public function resolve(Frozen_order $frozenOrder, ?Collection $transactions = null): array
    {
        if ($frozenOrder->settled_refund_amount !== null) {
            return [
                'is_exact' => true,
                'source' => 'settlement_snapshot',
                'source_label' => 'Snapshot khi hoàn tất đơn',
                'order_amount' => (float) $frozenOrder->settled_order_amount,
                'deducted_amount' => (float) $frozenOrder->settled_order_amount,
                'commission_amount' => (float) $frozenOrder->settled_commission_amount,
                'penalty_amount' => (float) $frozenOrder->settled_penalty_amount,
                'refund_amount' => (float) $frozenOrder->settled_refund_amount,
                'balance_destination' => $frozenOrder->settled_balance_destination,
                'settled_at' => optional($frozenOrder->settled_at)->toISOString(),
                'reason' => null,
            ];
        }

        if ($frozenOrder->status !== 'completed' || !$frozenOrder->commission_paid) {
            return $this->unavailable('Khoản hoàn nhập chưa phát sinh.');
        }

        $transactions ??= $this->transactionsFor($frozenOrder);
        $transactions = $transactions->filter(function (Transaction_history $transaction) use ($frozenOrder) {
            if ($frozenOrder->created_at && $transaction->created_at?->lt($frozenOrder->created_at)) {
                return false;
            }

            return !$frozenOrder->completed_at
                || !$transaction->created_at
                || $transaction->created_at->lte($frozenOrder->completed_at->copy()->addMinute());
        });
        $orderAmount = $this->uniqueValue($transactions, 'order');
        $commissionAmount = $this->uniqueValue($transactions, 'profit');
        $penaltyAmount = (float) ($frozenOrder->penalty_amount ?? 0);

        if ($penaltyAmount > 0) {
            $ledgerPenalty = $this->uniqueValue($transactions, 'penalty');
            if ($ledgerPenalty === null || abs($ledgerPenalty - $penaltyAmount) > 0.000001) {
                return $this->unavailable('Không có giao dịch phạt khớp chính xác trong transaction history.');
            }
        }

        if ($orderAmount === null || $commissionAmount === null) {
            return $this->unavailable('Transaction history thiếu hoặc có nhiều giá trị mâu thuẫn.');
        }

        $settledTransaction = $transactions
            ->whereIn('type', ['profit', 'penalty'])
            ->sortByDesc('created_at')
            ->first();

        return [
            'is_exact' => true,
            'source' => 'transaction_history',
            'source_label' => 'Khôi phục từ transaction history',
            'order_amount' => $orderAmount,
            'deducted_amount' => $orderAmount,
            'commission_amount' => $commissionAmount,
            'penalty_amount' => $penaltyAmount,
            'refund_amount' => round($orderAmount + $commissionAmount - $penaltyAmount, 6),
            'balance_destination' => null,
            'settled_at' => optional($settledTransaction?->created_at ?? $frozenOrder->completed_at)->toISOString(),
            'reason' => null,
        ];
    }

    public function transactionsFor(Frozen_order $frozenOrder): Collection
    {
        $notes = $this->possibleNotes($frozenOrder);
        if ($notes === []) {
            return collect();
        }

        return Transaction_history::query()
            ->where('user_id', $frozenOrder->user_id)
            ->whereIn('note', $notes)
            ->whereIn('type', ['order', 'profit', 'penalty'])
            ->get();
    }

    public function possibleNotes(Frozen_order $frozenOrder): array
    {
        return collect([
            $frozenOrder->snapshot_order_code,
            $frozenOrder->display_order_code,
            (string) $frozenOrder->order_id,
        ])->filter(fn ($value) => $value !== null && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function uniqueValue(Collection $transactions, string $type): ?float
    {
        $values = $transactions
            ->where('type', $type)
            ->pluck('value')
            ->map(fn ($value) => round((float) $value, 6))
            ->unique()
            ->values();

        return $values->count() === 1 ? (float) $values->first() : null;
    }

    private function unavailable(string $reason): array
    {
        return [
            'is_exact' => false,
            'source' => null,
            'source_label' => 'Chưa xác định chính xác',
            'order_amount' => null,
            'deducted_amount' => null,
            'commission_amount' => null,
            'penalty_amount' => null,
            'refund_amount' => null,
            'balance_destination' => null,
            'settled_at' => null,
            'reason' => $reason,
        ];
    }
}
