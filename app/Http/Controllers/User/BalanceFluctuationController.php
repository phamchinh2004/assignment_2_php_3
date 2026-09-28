<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\UserTransactionStatisticsService;
use App\Services\ReactPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BalanceFluctuationController extends Controller
{
    public function index(Request $request, UserTransactionStatisticsService $statistics, ReactPageService $reactPage)
    {
        $validated = $request->validate([
            'range' => ['nullable', 'in:today,7d,30d,month,custom'],
            'type' => ['nullable', 'in:all,wallet,order,profit,penalty,settlement,refund'],
            'start_date' => ['nullable', 'date', 'required_if:range,custom'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date', 'required_if:range,custom'],
        ]);
        $range = $validated['range'] ?? '30d';
        $type = $validated['type'] ?? match ($request->get('tab')) {
            'deposit', 'withdraw' => 'wallet',
            default => 'all',
        };
        [$start, $end] = match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '7d' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            'custom' => [Carbon::parse($validated['start_date'])->startOfDay(), Carbon::parse($validated['end_date'])->endOfDay()],
            default => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
        };
        if ($start->diffInDays($end) > 730) {
            return back()->withErrors(['start_date' => 'Khoảng thống kê tối đa là 730 ngày.'])->withInput();
        }

        $user = Auth::user();
        $data = $statistics->build($user, $start, $end, $type);
        $transactions = $data['transactions'];

        return $reactPage->user('user.balance-fluctuation', [
            'statistics' => [
                'summary' => $data['summary'],
                'profitLossSeries' => $data['profit_loss_series'],
                'breakdown' => $data['breakdown']->map(fn ($item) => [
                    'type' => $item->type,
                    'direction' => $item->direction,
                    'transaction_count' => (int) $item->transaction_count,
                    'total_amount' => (float) $item->total_amount,
                ])->values(),
                'range' => $data['range'],
                'transactions' => [
                    'data' => $transactions->getCollection()->map(fn ($item) => [
                        'sourceId' => $item->source_id,
                        'type' => $item->type,
                        'direction' => $item->direction,
                        'status' => $item->status,
                        'detail' => $item->detail,
                        'note' => $item->note,
                        'value' => (float) $item->value,
                        'createdAt' => $item->created_at,
                    ])->values(),
                    'total' => $transactions->total(),
                    'currentPage' => $transactions->currentPage(),
                    'lastPage' => $transactions->lastPage(),
                    'previousPageUrl' => $transactions->previousPageUrl(),
                    'nextPageUrl' => $transactions->nextPageUrl(),
                ],
            ],
            'user' => [
                'balance' => (float) $user->balance,
                'frozenBalance' => (float) $user->frozen_balance,
            ],
            'selectedRange' => $range,
            'selectedType' => $type,
            'startDate' => $request->input('start_date', $data['range']['start']),
            'endDate' => $request->input('end_date', $data['range']['end']),
            'routes' => [
                'home' => route('home'),
                'balanceFluctuation' => route('balance_fluctuation'),
            ],
        ], 'Thống kê giao dịch');
    }
}
