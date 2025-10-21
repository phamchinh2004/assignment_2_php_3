<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction_history;
use App\Models\Wallet_balance_history;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BalanceFluctuationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tab = request()->get('tab', 'overview');
        $user = Auth::user();
        
        // Lấy dữ liệu theo tab
        $list_distribution = null;
        $list_deposit = null;
        $list_withdraw = null;
        
        if ($tab === "distribution") {
            $list_distribution = Transaction_history::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->get();
        } else if ($tab === "deposit") {
            $list_deposit = Wallet_balance_history::where('user_id', $user->id)
                ->where('type', 'deposit')
                ->orderByDesc('created_at')
                ->get();
        } else if ($tab === "withdraw") {
            $list_withdraw = Wallet_balance_history::where('user_id', $user->id)
                ->where('type', 'withdraw')
                ->orderByDesc('created_at')
                ->get();
        }
        
        // Dữ liệu cho biểu đồ - số dư theo thời gian
        $chartData = $this->getBalanceChartData($user->id);
        
        // Thống kê tổng quan
        $stats = [
            'current_balance' => $user->balance,
            'total_profit' => Transaction_history::where('user_id', $user->id)
                ->where('type', 'profit')
                ->sum('value'),
            'total_deposit' => Wallet_balance_history::where('user_id', $user->id)
                ->where('type', 'deposit')
                ->sum('value'),
            'total_withdraw' => Wallet_balance_history::where('user_id', $user->id)
                ->where('type', 'withdraw')
                ->where('status', 'completed')
                ->sum('value'),
            'total_orders' => Transaction_history::where('user_id', $user->id)
                ->where('type', 'order')
                ->count(),
        ];
        
        return view('user.balance_fluctuation', compact(
            'list_distribution', 
            'list_deposit', 
            'list_withdraw',
            'chartData',
            'stats',
            'tab'
        ));
    }
    
    /**
     * Lấy dữ liệu biểu đồ số dư theo thời gian
     */
    private function getBalanceChartData($userId)
    {
        // Lấy tất cả giao dịch ảnh hưởng đến số dư
        $transactions = collect();
        
        // Lấy deposits
        $deposits = Wallet_balance_history::where('user_id', $userId)
            ->where('type', 'deposit')
            ->select('value', 'created_at', DB::raw("'deposit' as trans_type"))
            ->get();
        
        // Lấy withdraws
        $withdraws = Wallet_balance_history::where('user_id', $userId)
            ->where('type', 'withdraw')
            ->where('status', 'completed')
            ->select('value', 'created_at', DB::raw("'withdraw' as trans_type"))
            ->get();
        
        // Lấy transactions (profit/order)
        $transHistory = Transaction_history::where('user_id', $userId)
            ->select('value', 'created_at', 'type as trans_type')
            ->get();
        
        // Merge và sắp xếp theo thời gian
        $allTransactions = $deposits
            ->concat($withdraws)
            ->concat($transHistory)
            ->sortBy('created_at')
            ->values();
        
        // Tính số dư tại mỗi thời điểm
        $balanceHistory = [];
        $currentBalance = 0;
        
        foreach ($allTransactions as $trans) {
            if ($trans->trans_type === 'deposit' || $trans->trans_type === 'profit') {
                $currentBalance += $trans->value;
            } else if ($trans->trans_type === 'withdraw' || $trans->trans_type === 'order' || $trans->trans_type === 'penalty') {
                $currentBalance -= $trans->value;
            }
            
            $balanceHistory[] = [
                'date' => Carbon::parse($trans->created_at)->format('Y-m-d H:i'),
                'balance' => round($currentBalance, 2),
                'type' => $trans->trans_type
            ];
        }
        
        // Thêm điểm hiện tại
        $user = User::find($userId);
        if (count($balanceHistory) === 0 || end($balanceHistory)['balance'] !== $user->balance) {
            $balanceHistory[] = [
                'date' => Carbon::now()->format('Y-m-d H:i'),
                'balance' => round($user->balance, 2),
                'type' => 'current'
            ];
        }
        
        return $balanceHistory;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
