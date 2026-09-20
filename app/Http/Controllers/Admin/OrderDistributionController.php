<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Frozen_order;
use App\Models\Order;
use App\Models\User;
use App\Services\FrozenOrderSnapshotService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderDistributionController extends Controller
{
    public function __construct(private readonly FrozenOrderSnapshotService $snapshotService)
    {
    }

    public function index(Request $request)
    {
        $query = Frozen_order::query()->with(['user:id,full_name,username,phone', 'order.partner']);

        $this->applyFilters($query, $request);

        $sort = in_array($request->input('sort'), ['id', 'created_at', 'updated_at', 'status'], true)
            ? $request->input('sort')
            : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $frozenOrders = $query->orderBy($sort, $direction)->paginate(25)->withQueryString();
        $missingDetails = $frozenOrders->getCollection()->mapWithKeys(fn (Frozen_order $frozenOrder) => [
            $frozenOrder->id => $this->snapshotService->diagnoseMissingFields($frozenOrder),
        ]);

        $base = Frozen_order::query();
        $stats = [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->where(function (Builder $query) {
                $query->whereNull('status')->orWhereNotIn('status', ['completed', 'cancelled']);
            })->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'legacy' => $this->applyLegacySnapshotFilter(clone $base)->count(),
            'incomplete' => $this->applyIncompleteSnapshotFilter(clone $base)->count(),
            'invalid' => $this->applyInvalidSnapshotFilter(clone $base)->count(),
        ];

        $users = User::query()
            ->whereHas('frozen_orders')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'username']);
        $orders = Order::query()
            ->whereHas('frozen_orders')
            ->orderByDesc('id')
            ->get(['id', 'order_code', 'name']);

        return view('admin.order_distributions.index', compact(
            'frozenOrders',
            'stats',
            'users',
            'orders',
            'missingDetails'
        ));
    }

    public function show(Frozen_order $frozenOrder)
    {
        $frozenOrder->load([
            'user:id,full_name,username,phone,email',
            'order.partner',
            'snapshotRestoredBy:id,full_name,username',
            'statusOrders.status',
            'statusOrders.changedBy:id,full_name,username',
        ]);

        $canRestoreFinancials = $this->snapshotService->canRestoreFinancials($frozenOrder);
        $missingDetails = $this->snapshotService->diagnoseMissingFields($frozenOrder);

        return view('admin.order_distributions.show', compact('frozenOrder', 'canRestoreFinancials', 'missingDetails'));
    }

    public function restore(Request $request, Frozen_order $frozenOrder)
    {
        $data = $request->validate([
            'include_financials' => ['nullable', 'boolean'],
        ]);

        try {
            $filled = $this->snapshotService->restoreFromCurrentOrder(
                $frozenOrder,
                Auth::id(),
                (bool) ($data['include_financials'] ?? false)
            );

            $message = $filled === []
                ? 'Snapshot không có field trống phù hợp để phục hồi.'
                : 'Đã phục hồi ' . count($filled) . ' field từ Order hiện tại. Dữ liệu được đánh dấu là fallback, không phải lịch sử đã xác minh.';

            return back()->with($filled === [] ? 'info' : 'success', $message);
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function bulkRestore(Request $request)
    {
        $data = $request->validate([
            'frozen_order_ids' => ['required', 'array', 'min:1', 'max:100'],
            'frozen_order_ids.*' => ['integer', 'distinct', 'exists:frozen_orders,id'],
        ]);

        $updated = 0;
        $skipped = 0;
        $stillMissing = 0;

        Frozen_order::query()
            ->whereIn('id', $data['frozen_order_ids'])
            ->with('order.partner')
            ->each(function (Frozen_order $frozenOrder) use (&$updated, &$skipped, &$stillMissing) {
                try {
                    $fields = $this->snapshotService->restoreFromCurrentOrder(
                        $frozenOrder,
                        Auth::id(),
                        $this->snapshotService->canRestoreFinancials($frozenOrder)
                    );
                    $fields === [] ? $skipped++ : $updated++;
                    $stillMissing += count($frozenOrder->fresh()->snapshot_missing_fields);
                } catch (\Throwable) {
                    $skipped++;
                }
            });

        return back()->with(
            'success',
            "Đã bổ sung field trống cho {$updated} đơn; bỏ qua {$skipped} đơn; còn {$stillMissing} field không thể bổ sung an toàn. Snapshot có sẵn không bị ghi đè."
        );
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($search = trim((string) $request->input('q'))) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('snapshot_order_code', 'like', "%{$search}%")
                    ->orWhere('snapshot_name', 'like', "%{$search}%")
                    ->orWhereHas('order', fn (Builder $order) => $order
                        ->where('order_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        $query->when($request->filled('user_id'), fn (Builder $q) => $q->where('user_id', $request->integer('user_id')));
        $query->when($request->filled('order_id'), fn (Builder $q) => $q->where('order_id', $request->integer('order_id')));
        $query->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')));
        $query->when($request->filled('from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->input('from')));
        $query->when($request->filled('to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->input('to')));

        if ($request->input('snapshot') === 'legacy') {
            $this->applyLegacySnapshotFilter($query);
        } elseif ($request->input('snapshot') === 'complete') {
            foreach (Frozen_order::SNAPSHOT_REQUIRED_FIELDS as $field) {
                $query->whereNotNull($field);
            }
            $query->whereRaw('ABS(snapshot_order_amount - COALESCE(custom_price, snapshot_unit_price * snapshot_quantity)) <= 0.01')
                ->whereRaw('ABS(snapshot_commission_amount - (snapshot_order_amount * commission_percentage / 100)) <= 0.01')
                ->where('snapshot_quantity', '>', 0)
                ->where('snapshot_order_amount', '>=', 0)
                ->where('commission_percentage', '>=', 0);
        } elseif ($request->input('snapshot') === 'incomplete') {
            $this->applyIncompleteSnapshotFilter($query);
        } elseif ($request->input('snapshot') === 'restored') {
            $query->where('snapshot_source', 'restored_current');
        } elseif ($request->input('snapshot') === 'invalid') {
            $this->applyInvalidSnapshotFilter($query);
        }
    }

    private function applyInvalidSnapshotFilter(Builder $query): Builder
    {
        foreach (Frozen_order::SNAPSHOT_REQUIRED_FIELDS as $field) {
            $query->whereNotNull($field);
        }

        return $query->where(function (Builder $query) {
            $query->where('snapshot_quantity', '<=', 0)
                ->orWhere('snapshot_order_amount', '<', 0)
                ->orWhere('commission_percentage', '<', 0)
                ->orWhereRaw('ABS(snapshot_order_amount - COALESCE(custom_price, snapshot_unit_price * snapshot_quantity)) > 0.01')
                ->orWhereRaw('ABS(snapshot_commission_amount - (snapshot_order_amount * commission_percentage / 100)) > 0.01');
        });
    }

    private function applyLegacySnapshotFilter(Builder $query): Builder
    {
        return $query
            ->whereNull('snapshot_order_code')
            ->whereNull('snapshot_name')
            ->whereNull('snapshot_order_amount')
            ->whereNull('snapshot_commission_amount');
    }

    private function applyIncompleteSnapshotFilter(Builder $query): Builder
    {
        $query->where(function (Builder $query) {
            $query->whereNotNull('snapshot_order_code')
                ->orWhereNotNull('snapshot_name')
                ->orWhereNotNull('snapshot_order_amount')
                ->orWhereNotNull('snapshot_commission_amount');
        });

        return $query->where(function (Builder $query) {
            foreach (Frozen_order::SNAPSHOT_REQUIRED_FIELDS as $field) {
                $query->orWhereNull($field);
            }
        });
    }
}
