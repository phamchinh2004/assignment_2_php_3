<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\UserTransactionStatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BalanceFluctuationController extends Controller
{
    public function index(Request $request, UserTransactionStatisticsService $statistics)
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
        return view('user.balance_fluctuation', [
            'statistics' => $statistics->build($user, $start, $end, $type),
            'user' => $user,
            'selectedRange' => $range,
            'selectedType' => $type,
        ]);
    }
}
