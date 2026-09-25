<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Frozen_order;
use App\Models\Status;
use App\Models\User;
use App\Services\AdminOrderTransitionService;
use App\Services\AuthorizationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class OrderDistributionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'preparing', 'transit', 'shipping', 'delivered', 'completed', 'cancelled', 'unknown'])],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'assigned_by' => ['nullable', 'integer', 'min:1'],
            'order_id' => ['nullable', 'integer', 'min:1'],
            'source' => ['nullable', Rule::in(['admin', 'spin', 'unknown'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'updated_from' => ['nullable', 'date_format:Y-m-d'],
            'updated_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:updated_from'],
            'sort' => ['nullable', Rule::in(['id', 'created_at', 'updated_at', 'status'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $query = Frozen_order::query()->with([
            'user:id,full_name,username',
            'assignedBy:id,full_name,username',
            'latestStatusOrder.changedBy:id,full_name,username',
        ]);
        $this->applyFilters($query, $filters);

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $frozenOrders = $query->orderBy($sort, $direction)
            ->when($sort !== 'id', fn (Builder $q) => $q->orderByDesc('id'))
            ->paginate(25)->withQueryString();

        $base = Frozen_order::query();
        $stats = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where(function (Builder $q) {
                $q->whereNull('status')->orWhere('status', 'pending');
            })->count(),
            'processing' => (clone $base)->whereIn('status', ['confirmed', 'preparing', 'transit', 'shipping', 'delivered'])->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
        ];
        $statuses = Status::query()->orderBy('sort_order')->get(['name', 'display_name']);
        $assigners = User::query()->whereIn('id', Frozen_order::query()->select('assigned_by')->whereNotNull('assigned_by'))
            ->orderBy('full_name')->get(['id', 'full_name', 'username']);

        return view('admin.order_distributions.index', compact('frozenOrders', 'stats', 'statuses', 'assigners'));
    }

    public function show(Frozen_order $frozenOrder, AdminOrderTransitionService $workflow, AuthorizationService $authorization)
    {
        $frozenOrder->load([
            'user:id,full_name,username',
            'assignedBy:id,full_name,username',
            'order:id,order_code',
            'statusOrders.status',
            'statusOrders.changedBy:id,full_name,username',
        ]);
        $transition = $workflow->describe($frozenOrder);
        $actor = Auth::user();
        $canAdvance = $authorization->can(
            $actor,
            config('authorization.capabilities.order_distributions_transition')
        )
            && ($transition['next'] !== 'completed'
                || $authorization->can(
                    $actor,
                    config('authorization.capabilities.order_distributions_complete')
                ));
        $statusLabels = Status::query()->pluck('display_name', 'name');

        return view('admin.order_distributions.show', compact('frozenOrder', 'transition', 'canAdvance', 'statusLabels'));
    }

    public function transition(
        Request $request,
        Frozen_order $frozenOrder,
        AdminOrderTransitionService $workflow,
        AuthorizationService $authorization
    ) {
        $data = $request->validate([
            'expected_status' => ['required', Rule::in(array_keys(AdminOrderTransitionService::NEXT))],
            'expected_updated_at' => ['required', 'string', 'max:50'],
        ]);

        abort_unless(
            $authorization->can(
                Auth::user(),
                config('authorization.capabilities.order_distributions_transition')
            ),
            403
        );
        if ($data['expected_status'] === 'delivered') {
            abort_unless(
                $authorization->can(
                    Auth::user(),
                    config('authorization.capabilities.order_distributions_complete')
                ),
                403
            );
        }

        try {
            $status = $workflow->advance($frozenOrder, $data['expected_status'], $data['expected_updated_at']);
        } catch (ConflictHttpException $exception) {
            return $request->expectsJson()
                ? response()->json(['message' => $exception->getMessage(), 'conflict' => true], 409)
                : back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            $message = 'Không thể chuyển trạng thái. Dữ liệu chưa được cập nhật; vui lòng thử lại.';
            return $request->expectsJson()
                ? response()->json(['message' => $message], 500)
                : back()->with('error', $message);
        }

        $message = 'Đã chuyển đơn hàng sang trạng thái ' . $status . '.';
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'status' => $status])
            : back()->with('success', $message);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if ($search = trim((string) ($filters['q'] ?? ''))) {
            $query->where(function (Builder $q) use ($search) {
                if (ctype_digit($search)) {
                    $q->where('id', $search)->orWhere('order_id', $search);
                }
                $q->orWhere('snapshot_order_code', 'like', "%{$search}%")
                    ->orWhere('snapshot_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%"));
            });
        }

        foreach (['user_id', 'assigned_by', 'order_id'] as $column) {
            if (isset($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        if (isset($filters['status'])) {
            $filters['status'] === 'unknown'
                ? $query->whereNull('status')
                : $query->where('status', $filters['status']);
        }
        if (isset($filters['source'])) {
            $filters['source'] === 'unknown'
                ? $query->whereNull('assignment_source')
                : $query->where('assignment_source', $filters['source']);
        }

        foreach (['from' => ['created_at', '>='], 'to' => ['created_at', '<='],
            'updated_from' => ['updated_at', '>='], 'updated_to' => ['updated_at', '<=']] as $key => [$column, $operator]) {
            if (isset($filters[$key])) {
                $query->whereDate($column, $operator, $filters[$key]);
            }
        }
    }
}
