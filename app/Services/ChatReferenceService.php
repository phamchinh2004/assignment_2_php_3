<?php

namespace App\Services;

use App\Models\Frozen_order;
use App\Models\Transaction_history;
use App\Models\Wallet_balance_history;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChatReferenceService
{
    public function __construct(
        private readonly FrozenOrderSettlementService $settlementService,
    ) {
    }

    public function recentOrders(int $userId, int $limit = 12): array
    {
        return Frozen_order::query()
            ->where('user_id', $userId)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Frozen_order $order) => $this->orderPayload($order))
            ->all();
    }

    public function recentTransactions(int $userId, int $limit = 12): array
    {
        $wallet = Wallet_balance_history::query()
            ->where('user_id', $userId)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Wallet_balance_history $transaction) => $this->walletPayload($transaction));

        $ledger = Transaction_history::query()
            ->where('user_id', $userId)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Transaction_history $transaction) => $this->ledgerPayload($transaction));

        return $wallet->concat($ledger)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->all();
    }

    public function orderForUser(int $userId, int $orderId): array
    {
        $order = Frozen_order::query()
            ->where('user_id', $userId)
            ->find($orderId);

        if (!$order) {
            throw (new ModelNotFoundException())->setModel(Frozen_order::class, [$orderId]);
        }

        return $this->orderPayload($order);
    }

    public function transactionForUser(int $userId, string $source, int $transactionId): array
    {
        $transaction = match ($source) {
            'wallet' => Wallet_balance_history::query()->where('user_id', $userId)->find($transactionId),
            'ledger' => Transaction_history::query()->where('user_id', $userId)->find($transactionId),
            default => null,
        };

        $payload = match (true) {
            $transaction instanceof Wallet_balance_history => $this->walletPayload($transaction),
            $transaction instanceof Transaction_history => $this->ledgerPayload($transaction),
            default => null,
        };

        if (!$payload) {
            throw (new ModelNotFoundException())->setModel('transaction', [$transactionId]);
        }

        return $payload;
    }

    private function orderPayload(Frozen_order $order): array
    {
        $refundAmount = $this->resolveRefundAmount($order);

        return [
            'id' => $order->id,
            'code' => $order->snapshot_order_code ?: '#' . $order->id,
            'name' => $order->snapshot_name,
            'amount' => $order->snapshot_order_value,
            'quantity' => $order->snapshot_quantity === null ? null : (int) $order->snapshot_quantity,
            'unit_price' => $order->snapshot_unit_price === null ? null : (float) $order->snapshot_unit_price,
            'commission_percentage' => $order->commission_percentage === null ? null : (float) $order->commission_percentage,
            'commission_amount' => $order->snapshot_commission_value,
            'penalty_amount' => $order->settled_penalty_amount !== null
                ? (float) $order->settled_penalty_amount
                : ($order->penalty_amount === null ? null : (float) $order->penalty_amount),
            'refund_amount' => $refundAmount,
            'partner_name' => $order->snapshot_partner_name,
            'status' => $order->status ?: 'pending',
            'image_path' => $order->snapshot_image,
            'created_at' => optional($order->order_date ?? $order->created_at)->toIso8601String(),
        ];
    }

    private function resolveRefundAmount(Frozen_order $order): ?float
    {
        if ($order->settled_refund_amount !== null) {
            return (float) $order->settled_refund_amount;
        }

        if ($order->status !== 'completed' || !$order->commission_paid) {
            return null;
        }

        $settlement = $this->settlementService->resolve($order);

        if (!$settlement['is_exact'] || $settlement['refund_amount'] === null) {
            return null;
        }

        return (float) $settlement['refund_amount'];
    }

    private function walletPayload(Wallet_balance_history $transaction): array
    {
        return [
            'id' => $transaction->id,
            'source' => 'wallet',
            'type' => $transaction->type,
            'amount' => (float) $transaction->value,
            'status' => $transaction->status,
            'detail' => $transaction->transaction_type,
            'note' => null,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }

    private function ledgerPayload(Transaction_history $transaction): array
    {
        return [
            'id' => $transaction->id,
            'source' => 'ledger',
            'type' => $transaction->type,
            'amount' => (float) $transaction->value,
            'status' => 'recorded',
            'detail' => null,
            'note' => $transaction->note,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }
}
