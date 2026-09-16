<?php

namespace App\Http\Controllers\Admin;

use App\Models\Wallet_balance_history;
use App\Http\Requests\StoreTransaction_historyRequest;
use App\Http\Requests\UpdateTransaction_historyRequest;
use App\Http\Controllers\Controller;
use App\Models\Manager_setting;
use App\Models\Transaction_history;
use App\Models\User;
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
            // ->whereHas('user', function ($q) {
            //     $q->where('clone_account', 0);
            // })
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
        $list_withdraw_transactions = $query->orderByDesc("wallet_balance_histories.id")->get();
        return view('admin.transactions.withdraw', compact('list_withdraw_transactions'));
    }
    public function confirm_withdraw(Wallet_balance_history $transaction)
    {
        $transaction_type = isset($_GET['transaction_type']) && $_GET['transaction_type'] === "true";
        if ($transaction) {
            if ($transaction->status === "completed") {
                return back()->with('error', 'Giao dịch đã được xác nhận!');
            } else if ($transaction->status === "cancelled") {
                return back()->with('error', 'Giao dịch đã bị từ chối!');
            } else {
                $transaction->status = "completed";
                $transaction->transaction_type = $transaction_type ? "normal" : "virtual_withdraw";
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
                $get_user = User::find($transaction->user_id);
                if (!$get_user) {
                    return back()->with('error', 'Không thể hủy giao dịch vì tài khoản khách hàng không còn tồn tại.');
                }
                $get_user->balance += $transaction->value;
                $get_user->save();
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
        $query = Wallet_balance_history::with('user', 'byUser')
            // ->whereHas('user', function ($q) {
            //     $q->where('clone_account', 0);
            // })
            ->where('type', 'deposit');
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
    public function destroy_deposit(Wallet_balance_history $transaction)
    {
        if (!$transaction) {
            return back()->with('error', 'Giao dịch không xác định!');
        }

        if ($transaction->type !== 'deposit') {
            return back()->with('error', 'Chỉ có thể xóa giao dịch nạp tiền!');
        }
        $user = User::find($transaction->user_id);
        if (!$user) {
            return back()->with('error', 'Không thể xóa giao dịch vì tài khoản khách hàng không còn tồn tại.');
        }
        $user->balance -= $transaction->value;
        $user->save();
        $transaction->delete();
        return back()->with('success', 'Xóa giao dịch thành công!');
    }
    /**
     * Show the form for creating a new resource.
     */
    public function change_withdraw_transaction_type(Wallet_balance_history $transaction)
    {
        if ($transaction) {
            if ($transaction->transaction_type === "normal") {
                $transaction->transaction_type = "virtual_withdraw";
            } else {
                $transaction->transaction_type = "normal";
            }
            $transaction->save();
            return back()->with('success', 'Thay đổi loại giao dịch thành công!');
        } else {
            return back()->with('success', 'Giao dịch không xác định!');
        }
    }
    public function change_deposit_transaction_type(Wallet_balance_history $transaction)
    {
        if ($transaction) {
            if ($transaction->transaction_type === "normal") {
                $transaction->transaction_type = "bonus";
            } else {
                $transaction->transaction_type = "normal";
            }
            $transaction->save();
            return back()->with('success', 'Thay đổi loại giao dịch thành công!');
        } else {
            return back()->with('success', 'Giao dịch không xác định!');
        }
    }
}
