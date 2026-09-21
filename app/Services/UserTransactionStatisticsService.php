<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserTransactionStatisticsService
{
    public function build(User $user, Carbon $start, Carbon $end, string $type = 'all'): array
    {
        $duration = max(1, $start->diffInSeconds($end) + 1);
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subSeconds($duration - 1);
        $summary = $this->summary($user->id, $start, $end);
        $previous = $this->summary($user->id, $previousStart, $previousEnd);
        $summary['net_growth'] = $this->growth($summary['net_movement'], $previous['net_movement']);
        $summary['commission_growth'] = $this->growth($summary['commission_amount'], $previous['commission_amount']);
        $summary['deposit_growth'] = $this->growth($summary['deposit_amount'], $previous['deposit_amount']);

        return [
            'summary' => $summary,
            'profit_loss_series' => $this->profitLossSeries($user->id, $start, $end, $type),
            'breakdown' => $this->breakdown($user->id, $start, $end),
            'transactions' => $this->transactions($user->id, $start, $end, $type),
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
        ];
    }

    private function summary(int $userId, Carbon $start, Carbon $end): array
    {
        $wallet = DB::table('wallet_balance_histories')->where('user_id', $userId)->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw("SUM(CASE WHEN type='deposit' AND status='completed' THEN value ELSE 0 END) as deposit_amount")
            ->selectRaw("SUM(CASE WHEN type='withdraw' AND status='completed' THEN value ELSE 0 END) as withdraw_amount")
            ->selectRaw("SUM(CASE WHEN type='withdraw' AND status='processing' THEN value ELSE 0 END) as pending_withdraw_amount")
            ->selectRaw("SUM(CASE WHEN type='withdraw' AND status='cancelled' THEN value ELSE 0 END) as cancelled_withdraw_amount")
            ->selectRaw("SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) as pending_count")
            ->selectRaw("SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled_count")->first();
        $ledger = DB::table('transaction_histories')->where('user_id', $userId)->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw("SUM(CASE WHEN type='order' THEN value ELSE 0 END) as order_amount")
            ->selectRaw("SUM(CASE WHEN type='profit' THEN value ELSE 0 END) as commission_amount")
            ->selectRaw("SUM(CASE WHEN type='penalty' THEN value ELSE 0 END) as penalty_amount")->first();
        $settlement = DB::table('frozen_orders')->where('user_id', $userId)->whereNotNull('settled_at')->whereNotNull('settled_refund_amount')->whereBetween('settled_at', [$start, $end])
            ->selectRaw('COUNT(*) as settlement_count, COALESCE(SUM(settled_refund_amount),0) as settlement_amount')->first();
        $legacyRefund = DB::query()->fromSub($this->legacyRefundsQuery($userId, $start, $end), 'legacy_refunds')
            ->selectRaw('COUNT(*) as refund_count, COALESCE(SUM(refund_amount),0) as refund_amount')->first();
        $withdrawRefund = DB::table('wallet_balance_histories')->where('user_id', $userId)
            ->where('type', 'withdraw')->where('status', 'cancelled')->whereBetween('updated_at', [$start, $end])
            ->selectRaw('COUNT(*) as refund_count, COALESCE(SUM(value),0) as refund_amount')->first();
        $completedOrderCount = DB::table('frozen_orders')->where('user_id', $userId)
            ->where('status', 'completed')->whereBetween('completed_at', [$start, $end])->count();
        $highValueOrderReceivedCount = DB::table('frozen_orders')->where('user_id', $userId)
            ->whereNotNull('custom_price')->whereBetween('order_date', [$start, $end])->count();
        $pendingCommission = (float) DB::table('frozen_orders')->where('user_id', $userId)
            ->whereIn('status', ['confirmed', 'preparing', 'transit', 'shipping', 'delivered'])->where('commission_paid', false)->sum('snapshot_commission_amount');
        $deposit = (float) ($wallet->deposit_amount ?? 0); $withdraw = (float) ($wallet->withdraw_amount ?? 0);
        $pendingWithdraw = (float) ($wallet->pending_withdraw_amount ?? 0); $orders = (float) ($ledger->order_amount ?? 0);
        $cancelledWithdraw = (float) ($wallet->cancelled_withdraw_amount ?? 0);
        $snapshotRefund = (float) ($settlement->settlement_amount ?? 0);
        $legacyRefundAmount = (float) ($legacyRefund->refund_amount ?? 0);
        $withdrawRefundAmount = (float) ($withdrawRefund->refund_amount ?? 0);
        $refundAmount = $snapshotRefund + $legacyRefundAmount + $withdrawRefundAmount;

        return [
            'transaction_count' => (int) ($wallet->transaction_count ?? 0) + (int) ($ledger->transaction_count ?? 0) + (int) ($settlement->settlement_count ?? 0) + (int) ($legacyRefund->refund_count ?? 0) + (int) ($withdrawRefund->refund_count ?? 0),
            'deposit_amount' => $deposit, 'withdraw_amount' => $withdraw, 'pending_withdraw_amount' => $pendingWithdraw,
            'order_amount' => $orders, 'completed_order_count' => $completedOrderCount,
            'high_value_order_received_count' => $highValueOrderReceivedCount,
            'commission_amount' => (float) ($ledger->commission_amount ?? 0),
            'penalty_amount' => (float) ($ledger->penalty_amount ?? 0), 'refund_amount' => $refundAmount,
            'order_refund_amount' => $snapshotRefund + $legacyRefundAmount, 'withdraw_refund_amount' => $withdrawRefundAmount,
            'refund_count' => (int) ($settlement->settlement_count ?? 0) + (int) ($legacyRefund->refund_count ?? 0) + (int) ($withdrawRefund->refund_count ?? 0),
            'pending_commission' => $pendingCommission,
            'net_movement' => round($deposit + $snapshotRefund + $legacyRefundAmount + $withdrawRefundAmount - $withdraw - $pendingWithdraw - $cancelledWithdraw - $orders, 6),
            'completed_count' => (int) ($wallet->completed_count ?? 0), 'pending_count' => (int) ($wallet->pending_count ?? 0),
            'cancelled_count' => (int) ($wallet->cancelled_count ?? 0),
        ];
    }

    private function breakdown(int $userId, Carbon $start, Carbon $end)
    {
        return $this->activityQuery($userId, $start, $end, 'all')->select('type', 'direction')
            ->selectRaw('COUNT(*) as transaction_count, SUM(value) as total_amount')->groupBy('type', 'direction')->orderByDesc('total_amount')->get();
    }

    private function profitLossSeries(int $userId, Carbon $start, Carbon $end, string $type): array
    {
        $types = match ($type) {
            'all' => ['profit', 'penalty'],
            'profit', 'penalty' => [$type],
            default => [],
        };
        if ($types === []) return [];

        $events = DB::table('transaction_histories')->where('user_id', $userId)->whereIn('type', $types)
            ->whereBetween('created_at', [$start, $end])->select('id', 'type', 'value', 'note', 'created_at')
            ->orderBy('created_at')->orderBy('id')->get();
        if ($events->isEmpty()) return [];

        $cumulative = 0.0;
        $points = [[
            'occurred_at' => $start->toIso8601String(),
            'commission' => 0.0,
            'penalty' => 0.0,
            'change' => 0.0,
            'cumulative' => 0.0,
            'event_type' => 'baseline',
            'order_code' => null,
            'transaction_id' => null,
        ]];
        foreach ($events as $event) {
            $value = (float) $event->value;
            $isCommission = $event->type === 'profit';
            $commission = $isCommission ? $value : 0.0;
            $penalty = $isCommission ? 0.0 : $value;
            $change = $isCommission ? $value : -$value;
            $cumulative = round($cumulative + $change, 6);
            $points[] = [
                'occurred_at' => Carbon::parse($event->created_at, config('app.timezone'))->toIso8601String(),
                'commission' => $commission,
                'penalty' => $penalty,
                'change' => round($change, 6),
                'cumulative' => $cumulative,
                'event_type' => $isCommission ? 'commission' : 'penalty',
                'order_code' => $event->note,
                'transaction_id' => (int) $event->id,
            ];
        }

        return $points;
    }

    private function transactions(int $userId, Carbon $start, Carbon $end, string $type): LengthAwarePaginator
    {
        return $this->activityQuery($userId, $start, $end, $type)->orderByDesc('created_at')->orderByDesc('source_id')->paginate(20)->withQueryString();
    }

    private function activityQuery(int $userId, Carbon $start, Carbon $end, string $type): Builder
    {
        $wallet = DB::table('wallet_balance_histories')->where('user_id', $userId)->whereBetween('created_at', [$start, $end])
            ->selectRaw("id as source_id, 'wallet' as source, type, value, CASE WHEN type='deposit' THEN 'in' ELSE 'out' END as direction, status, transaction_type as detail, NULL as note, created_at");
        $ledger = DB::table('transaction_histories')->where('user_id', $userId)->whereBetween('created_at', [$start, $end])
            ->selectRaw("id as source_id, 'ledger' as source, type, value, CASE WHEN type='profit' THEN 'info' ELSE 'out' END as direction, 'recorded' as status, NULL as detail, note, created_at");
        $settlements = DB::table('frozen_orders')->where('user_id', $userId)->whereNotNull('settled_at')->whereNotNull('settled_refund_amount')->whereBetween('settled_at', [$start, $end])
            ->selectRaw("id as source_id, 'settlement' as source, 'settlement' as type, settled_refund_amount as value, 'in' as direction, 'completed' as status, settled_balance_destination as detail, snapshot_order_code as note, settled_at as created_at");
        $legacySettlements = DB::query()->fromSub($this->legacyRefundsQuery($userId, $start, $end), 'legacy_refunds')
            ->selectRaw("source_id, 'legacy_settlement' as source, 'settlement' as type, refund_amount as value, 'in' as direction, 'completed' as status, NULL as detail, note, created_at");
        $withdrawRefunds = DB::table('wallet_balance_histories')->where('user_id', $userId)->where('type', 'withdraw')
            ->where('status', 'cancelled')->whereBetween('updated_at', [$start, $end])
            ->selectRaw("id as source_id, 'wallet_refund' as source, 'refund' as type, value, 'in' as direction, 'completed' as status, 'balance' as detail, NULL as note, updated_at as created_at");
        $query = DB::query()->fromSub($wallet->unionAll($ledger)->unionAll($settlements)->unionAll($legacySettlements)->unionAll($withdrawRefunds), 'activity');
        if ($type === 'wallet') {
            $query->whereIn('source', ['wallet', 'wallet_refund']);
        } elseif ($type === 'refund') {
            $query->whereIn('type', ['settlement', 'refund']);
        } elseif ($type !== 'all') {
            $query->where('type', $type);
        }
        return $query;
    }

    private function legacyRefundsQuery(int $userId, Carbon $start, Carbon $end): Builder
    {
        $grouped = DB::table('frozen_orders as orders')
            ->leftJoin('transaction_histories as ledger', function ($join) {
                $join->on('ledger.user_id', '=', 'orders.user_id')
                    ->whereIn('ledger.type', ['order', 'profit', 'penalty'])
                    ->whereRaw('(ledger.note = orders.snapshot_order_code OR ledger.note = CAST(orders.order_id AS CHAR))')
                    ->whereColumn('ledger.created_at', '>=', 'orders.created_at')
                    ->whereRaw('ledger.created_at <= DATE_ADD(orders.completed_at, INTERVAL 1 MINUTE)');
            })
            ->where('orders.user_id', $userId)->where('orders.status', 'completed')->where('orders.commission_paid', true)
            ->whereNull('orders.settled_refund_amount')->whereBetween('orders.completed_at', [$start, $end])
            ->groupBy('orders.id', 'orders.snapshot_order_code', 'orders.order_id', 'orders.completed_at', 'orders.penalty_amount')
            ->selectRaw('orders.id as source_id, COALESCE(orders.snapshot_order_code, CAST(orders.order_id AS CHAR)) as note, orders.completed_at as created_at, COALESCE(orders.penalty_amount,0) as expected_penalty')
            ->selectRaw("COUNT(DISTINCT CASE WHEN ledger.type='order' THEN ledger.value END) as order_values")
            ->selectRaw("COUNT(DISTINCT CASE WHEN ledger.type='profit' THEN ledger.value END) as profit_values")
            ->selectRaw("COUNT(DISTINCT CASE WHEN ledger.type='penalty' THEN ledger.value END) as penalty_values")
            ->selectRaw("MAX(CASE WHEN ledger.type='order' THEN ledger.value END) as order_amount")
            ->selectRaw("MAX(CASE WHEN ledger.type='profit' THEN ledger.value END) as profit_amount")
            ->selectRaw("MAX(CASE WHEN ledger.type='penalty' THEN ledger.value END) as penalty_amount");

        return DB::query()->fromSub($grouped, 'legacy')->where('order_values', 1)->where('profit_values', 1)
            ->where(function ($query) {
                $query->where('expected_penalty', '<=', 0)->orWhere(function ($penalty) {
                    $penalty->where('penalty_values', 1)->whereRaw('ABS(penalty_amount - expected_penalty) <= 0.000001');
                });
            })
            ->select('source_id', 'note', 'created_at')
            ->selectRaw('(order_amount + profit_amount - COALESCE(penalty_amount,0)) as refund_amount');
    }

    private function growth(float $current, float $previous): float
    {
        if (abs($previous) < 0.000001) return abs($current) < 0.000001 ? 0.0 : 100.0;
        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
