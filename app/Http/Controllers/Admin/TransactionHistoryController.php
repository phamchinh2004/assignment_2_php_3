<?php

namespace App\Http\Controllers\Admin;

use App\Models\Wallet_balance_history;
use App\Http\Requests\StoreTransaction_historyRequest;
use App\Http\Requests\UpdateTransaction_historyRequest;
use App\Http\Controllers\Controller;
use App\Models\Transaction_history;
use App\Models\User;
use App\Services\AuthorizationService;
use App\Services\ReactPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionHistoryController extends Controller
{
    public function __construct(private readonly ReactPageService $reactPage)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index_withdraw(AuthorizationService $authorization): View|JsonResponse
    {
        $query = Wallet_balance_history::with('user', 'byUser')
            // ->whereHas('user', function ($q) {
            //     $q->where('clone_account', 0);
            // })
            ->where('type', 'withdraw');
        $actor = Auth::user();
        $canViewAll = $authorization->can(
            $actor,
            config('authorization.capabilities.withdrawals_view_all')
        );
        $query->whereHas('user', fn ($q) => $q->visibleCustomersTo($actor, $canViewAll));
        $list_withdraw_transactions = $query->orderByDesc("wallet_balance_histories.id")->get();
        $list_withdraw_transactions->each(function (Wallet_balance_history $transaction) {
            $transaction->user?->setAttribute('avatar_url', get_user_avatar($transaction->user));
        });
        return $this->reactPage->admin('admin.transactions.withdraw', [
            'transactions' => $list_withdraw_transactions,
            'routes' => [
                'confirm' => route('confirm.withdraw', ['transaction' => '__TRANSACTION_ID__']),
                'cancel' => route('cancel.withdraw', ['transaction' => '__TRANSACTION_ID__']),
                'customerShow' => route('user.show', ['user' => '__USER_ID__']),
            ],
            'permissions' => [
                'confirm' => $authorization->can($actor, config('authorization.capabilities.withdrawals_confirm')),
                'cancel' => $authorization->can($actor, config('authorization.capabilities.withdrawals_cancel')),
                'viewCustomerDetail' => $authorization->can($actor, config('authorization.capabilities.customers_view_detail')),
            ],
        ], 'Quản lý rút tiền');
    }
    public function confirm_withdraw(
        Request $request,
        Wallet_balance_history $transaction,
        AuthorizationService $authorization
    ) {
        $validated = $request->validate([
            'transaction_type' => ['required', Rule::in(['normal', 'virtual_withdraw'])],
        ]);

        $result = DB::transaction(function () use ($transaction, $authorization, $validated) {
            $lockedTransaction = Wallet_balance_history::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $this->authorizeTransactionAccess($lockedTransaction, $authorization, 'withdraw');

            if ($lockedTransaction->status === 'completed') {
                return ['error', 'Giao dịch đã được xác nhận!'];
            }

            if ($lockedTransaction->status === 'cancelled') {
                return ['error', 'Giao dịch đã bị từ chối!'];
            }

            $lockedTransaction->status = 'completed';
            $lockedTransaction->transaction_type = $validated['transaction_type'];
            $lockedTransaction->by_user_id = Auth::id();
            $lockedTransaction->save();

            return ['success', 'Đã xác nhận giao dịch thành công!'];
        });

        return back()->with($result[0], $result[1]);
    }

    public function cancel_withdraw(Wallet_balance_history $transaction, AuthorizationService $authorization)
    {
        $result = DB::transaction(function () use ($transaction, $authorization) {
            $lockedTransaction = Wallet_balance_history::query()
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $this->authorizeTransactionAccess($lockedTransaction, $authorization, 'withdraw');

            if ($lockedTransaction->status === 'completed') {
                return ['error', 'Giao dịch đã được xác nhận!'];
            }

            if ($lockedTransaction->status === 'cancelled') {
                return ['error', 'Giao dịch đã bị từ chối!'];
            }

            $user = User::query()->lockForUpdate()->find($lockedTransaction->user_id);
            if (!$user) {
                return ['error', 'Không thể hủy giao dịch vì tài khoản khách hàng không còn tồn tại.'];
            }

            $user->balance += $lockedTransaction->value;
            $user->save();

            $lockedTransaction->status = 'cancelled';
            $lockedTransaction->by_user_id = Auth::id();
            $lockedTransaction->save();

            return ['success', 'Đã hủy giao dịch thành công!'];
        });

        return back()->with($result[0], $result[1]);
    }
    public function index_deposit(AuthorizationService $authorization): View|JsonResponse
    {
        $query = Wallet_balance_history::with('user', 'byUser')
            // ->whereHas('user', function ($q) {
            //     $q->where('clone_account', 0);
            // })
            ->where('type', 'deposit');
        $actor = Auth::user();
        $canViewAll = $authorization->can(
            $actor,
            config('authorization.capabilities.deposits_view_all')
        );
        $query->whereHas('user', fn ($q) => $q->visibleCustomersTo($actor, $canViewAll));
        $list_deposit_transactions = $query->orderByDesc('id')->get();
        $list_deposit_transactions->each(function (Wallet_balance_history $transaction) {
            $transaction->user?->setAttribute('avatar_url', get_user_avatar($transaction->user));
        });
        return $this->reactPage->admin('admin.transactions.deposit', [
            'transactions' => $list_deposit_transactions,
            'routes' => [
                'changeType' => route('change.deposit.transaction.type', ['transaction' => '__TRANSACTION_ID__']),
                'destroy' => route('destroy.deposit', ['transaction' => '__TRANSACTION_ID__']),
                'customerShow' => route('user.show', ['user' => '__USER_ID__']),
            ],
            'permissions' => [
                'changeType' => $authorization->can($actor, config('authorization.capabilities.deposits_change_type')),
                'delete' => $authorization->can($actor, config('authorization.capabilities.deposits_delete')),
                'viewCustomerDetail' => $authorization->can($actor, config('authorization.capabilities.customers_view_detail')),
            ],
        ], 'Lịch sử nạp tiền');
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

        $transaction->loadMissing('user');
        abort_unless(
            $transaction->user && $actor->canAccessCustomer($transaction->user),
            403
        );
    }
}
