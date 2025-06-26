<?php

namespace App\Http\Controllers\Admin;

use App\Models\Wallet_balance_history;
use App\Http\Requests\StoreTransaction_historyRequest;
use App\Http\Requests\UpdateTransaction_historyRequest;
use App\Http\Controllers\Controller;
use App\Models\Manager_setting;
use App\Models\User_manager_setting;
use Illuminate\Support\Facades\Auth;

class TransactionHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index_withdraw()
    {
        $query = Wallet_balance_history::with('user', 'byUser')
            ->where('type', 'withdraw');
        if (Auth::user()->role === "staff") {
            $get_quan_ly_tat_ca_giao_dich_nguoi_dung = Manager_setting::where('manager_code', 'quan_ly_tat_ca_giao_dich_nguoi_dung')->first();
            if ($get_quan_ly_tat_ca_giao_dich_nguoi_dung) {
                $get_user_manager_setting_by_user_id = User_manager_setting::where('manager_setting_id', $get_quan_ly_tat_ca_giao_dich_nguoi_dung->id)->where('user_id', Auth::user()->id)->first();
                if ($get_user_manager_setting_by_user_id) {
                    if (!$get_user_manager_setting_by_user_id->is_active) {
                        $query->whereHas('user', function ($q) {
                            $q->where('referrer_id', Auth::user()->id);
                        });
                    }
                }
            }
        }
        $list_withdraw_transactions = $query->orderByRaw("wallet_balance_histories.status='processing' DESC")->get();
        // dd($list_withdraw_transactions); 
        return view('admin.transactions.withdraw', compact('list_withdraw_transactions'));
    }
    public function confirm_withdraw(Wallet_balance_history $transaction)
    {
        if ($transaction) {
            if ($transaction->status === "completed") {
                return back()->with('error', 'Giao dịch đã được xác nhận!');
            } else if ($transaction->status === "cancelled") {
                return back()->with('error', 'Giao dịch đã bị từ chối!');
            } else {
                $transaction->status = "completed";
                $transaction->by_user_id = Auth::user()->id;
                $transaction->save();
                return back()->with('success', 'Đã xác nhận giao dịch thành công!');
            }
        }
        return back()->with('error', 'Giao dịch không xác định!');
    }
    public function cancel_withdraw(Wallet_balance_history $transaction)
    {
        if ($transaction) {
            if ($transaction->status === "completed") {
                return back()->with('error', value: 'Giao dịch đã được xác nhận!');
            } else if ($transaction->status === "cancelled") {
                return back()->with('error', 'Giao dịch đã bị từ chối!');
            } else {
                $transaction->status = "cancelled";
                $transaction->by_user_id = Auth::user()->id;
                $transaction->save();
                return back()->with('success', 'Đã hủy giao dịch thành công!');
            }
        }
        return back()->with('error', 'Giao dịch không xác định!');
    }
    public function index_deposit()
    {
        $query = Wallet_balance_history::with('user', 'byUser')->where('type', 'deposit');
        if (Auth::user()->role === "staff") {
            $get_quan_ly_tat_ca_giao_dich_nguoi_dung = Manager_setting::where('manager_code', 'quan_ly_tat_ca_giao_dich_nguoi_dung')->first();
            if ($get_quan_ly_tat_ca_giao_dich_nguoi_dung) {
                $get_user_manager_setting_by_user_id = User_manager_setting::where('manager_setting_id', $get_quan_ly_tat_ca_giao_dich_nguoi_dung->id)->where('user_id', Auth::user()->id)->first();
                if ($get_user_manager_setting_by_user_id) {
                    if (!$get_user_manager_setting_by_user_id->is_active) {
                        $query->where('by_user_id', Auth::user()->id);
                    }
                }
            }
        }
        $list_deposit_transactions = $query->orderByDesc('id')->get();
        return view('admin.transactions.deposit', compact('list_deposit_transactions'));
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
    public function store(StoreTransaction_historyRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction_history $transaction_history)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction_history $transaction_history)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransaction_historyRequest $request, Transaction_history $transaction_history)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction_history $transaction_history)
    {
        //
    }
}
