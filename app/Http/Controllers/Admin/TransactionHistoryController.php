<?php

namespace App\Http\Controllers\Admin;

use App\Models\Wallet_balance_history;
use App\Http\Requests\StoreTransaction_historyRequest;
use App\Http\Requests\UpdateTransaction_historyRequest;
use App\Http\Controllers\Controller;
use App\Models\Transaction_history;
use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Support\Facades\Auth;

class TransactionHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index_withdraw(AuthorizationService $authorization)
    {
        $query = Wallet_balance_history::with('user', 'byUser')
            // ->whereHas('user', function ($q) {
            //     $q->where('clone_account', 0);
            // })
            ->where('type', 'withdraw');
        $actor = Auth::user();
        if (
            $actor->role === User::ROLE_STAFF
            && !$authorization->can($actor, config('authorization.capabilities.manage_all_user_transactions'))
        ) {
            $query->whereHas('user', function ($q) use ($actor) {
                $q->where('referrer_id', $actor->id);
            });
        }
        $list_withdraw_transactions = $query->orderByDesc("wallet_balance_histories.id")->get();
        return view('admin.transactions.withdraw', compact('list_withdraw_transactions'));
    }
    public function confirm_withdraw(Wallet_balance_history $transaction, AuthorizationService $authorization)
    {
        $this->authorizeTransactionAccess($transaction, $authorization, 'withdraw');
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
    public function cancel_withdraw(Wallet_balance_history $transaction, AuthorizationService $authorization)
    {
        $this->authorizeTransactionAccess($transaction, $authorization, 'withdraw');
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
    public function index_deposit(AuthorizationService $authorization)
    {
        $query = Wallet_balance_history::with('user', 'byUser')
            // ->whereHas('user', function ($q) {
            //     $q->where('clone_account', 0);
            // })
            ->where('type', 'deposit');
        $actor = Auth::user();
        if (
            $actor->role === User::ROLE_STAFF
            && !$authorization->can($actor, config('authorization.capabilities.manage_all_user_transactions'))
        ) {
            $query->where('by_user_id', $actor->id);
        }
        $list_deposit_transactions = $query->orderByDesc('id')->get();
        return view('admin.transactions.deposit', compact('list_deposit_transactions'));
    }
    public function destroy_deposit(Wallet_balance_history $transaction, AuthorizationService $authorization)
    {
        $this->authorizeTransactionAccess($transaction, $authorization, 'deposit');
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
    public function change_withdraw_transaction_type(Wallet_balance_history $transaction, AuthorizationService $authorization)
    {
        $this->authorizeTransactionAccess($transaction, $authorization, 'withdraw');
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
    public function change_deposit_transaction_type(Wallet_balance_history $transaction, AuthorizationService $authorization)
    {
        $this->authorizeTransactionAccess($transaction, $authorization, 'deposit');
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

    private function authorizeTransactionAccess(
        Wallet_balance_history $transaction,
        AuthorizationService $authorization,
        string $expectedType
    ): void {
        abort_unless($transaction->type === $expectedType, 404);

        $actor = Auth::user();

        if (
            $actor->role !== User::ROLE_STAFF
            || $authorization->can($actor, config('authorization.capabilities.manage_all_user_transactions'))
        ) {
            return;
        }

        if ($expectedType === 'deposit') {
            abort_unless((int) $transaction->by_user_id === (int) $actor->id, 403);
            return;
        }

        $transaction->loadMissing('user');
        abort_unless(
            $transaction->user && (int) $transaction->user->referrer_id === (int) $actor->id,
            403
        );
    }
}
