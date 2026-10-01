<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Frozen_order;
use App\Models\Status;
use App\Models\User;
use App\Services\AdminOrderTransitionService;
use App\Services\AuthorizationService;
use App\Services\ReactPageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class OrderDistributionController extends Controller
{
    public function __construct(
        private readonly ReactPageService $reactPage,
        private readonly AuthorizationService $authorization,
    ) {
    }

    public function index(Request $request): View|JsonResponse
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
        $this->scopeToVisibleCustomers($query, $request->user());
        $this->applyFilters($query, $filters);

        $sort = $filters['sort'] ?? 'created_at';
        $direction = $filters['direction'] ?? 'desc';
        $frozenOrders = $query->orderBy($sort, $direction)
            ->when($sort !== 'id', fn (Builder $q) => $q->orderByDesc('id'))
            ->paginate(25)->withQueryString();

        $frozenOrders->getCollection()->each(function (Frozen_order $item) {
            $item->setAttribute('snapshot_image_url', $item->snapshot_image ? Storage::url($item->snapshot_image) : null);
        });

        $base = Frozen_order::query();
        $this->scopeToVisibleCustomers($base, $request->user());
        $stats = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where(function (Builder $q) {
                $q->whereNull('status')->orWhere('status', 'pending');
            })->count(),
            'processing' => (clone $base)->whereIn('status', ['confirmed', 'preparing', 'transit', 'shipping', 'delivered'])->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
        ];
        $statuses = Status::query()->orderBy('sort_order')->get(['name', 'display_name']);
        $visibleFrozenOrders = Frozen_order::query()->select('assigned_by')->whereNotNull('assigned_by');
        $this->scopeToVisibleCustomers($visibleFrozenOrders, $request->user());
        $assigners = User::query()->whereIn('id', $visibleFrozenOrders)
            ->orderBy('full_name')->get(['id', 'full_name', 'username']);
        $actor = $request->user() ?? Auth::user();

        return $this->reactPage->admin('admin.order-distributions.index', [
            'frozenOrders' => $frozenOrders,
            'stats' => $stats,
            'statuses' => $statuses,
            'assigners' => $assigners,
            'filters' => (object) $filters,
            'routes' => [
                'index' => route('order_distributions.index'),
                'show' => route('order_distributions.show', ['frozenOrder' => '__FROZEN_ID__']),
            ],
            'permissions' => [
                'viewDetail' => $actor
                    ? $this->authorization->can($actor, config('authorization.capabilities.order_distributions_view_detail'))
                    : false,
            ],
        ], 'Phân phối đơn hàng');
    }

    public function show(Frozen_order $frozenOrder, AdminOrderTransitionService $workflow): View|JsonResponse
    {
        $this->authorizeFrozenOrderAccess($frozenOrder);
        $frozenOrder->load([
            'user:id,full_name,username',
            'assignedBy:id,full_name,username',
            'order:id,order_code',
            'statusOrders.status',
            'statusOrders.changedBy:id,full_name,username',
        ]);
        $transition = $workflow->describe($frozenOrder);
        $actor = Auth::user();
        $canAdvance = $this->authorization->can(
            $actor,
            config('authorization.capabilities.order_distributions_transition')
        )
            && ($transition['next'] !== 'completed'
                || $this->authorization->can(
                    $actor,
                    config('authorization.capabilities.order_distributions_complete')
                ));
        $canViewOrder = $this->authorization->can(
            $actor,
            config('authorization.capabilities.orders_view_detail')
        );
        $statusLabels = Status::query()->pluck('display_name', 'name');

        $frozenOrder->setAttribute('snapshot_image_url', $frozenOrder->snapshot_image ? Storage::url($frozenOrder->snapshot_image) : null);

        return $this->reactPage->admin('admin.order-distributions.show', [
            'frozenOrder' => $frozenOrder,
            'transition' => $transition,
            'statusLabels' => $statusLabels,
            'permissions' => [
                'advance' => $canAdvance,
                'viewOrder' => $canViewOrder,
            ],
            'routes' => [
                'index' => route('order_distributions.index'),
                'transition' => route('order_distributions.transition', ['frozenOrder' => $frozenOrder]),
                'orderShow' => route('order.show', ['order' => '__ORDER_ID__']),
            ],
        ], 'Audit phân phối #' . $frozenOrder->id);
    }

    public function transition(
        Request $request,
        Frozen_order $frozenOrder,
        AdminOrderTransitionService $workflow,
        AuthorizationService $authorization
    ) {
        $this->authorizeFrozenOrderAccess($frozenOrder);
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

    private function scopeToVisibleCustomers(Builder $query, ?User $actor): Builder
    {
        if (!$actor || $actor->role === User::ROLE_OWNER) {
            return $query;
        }

        return $query->whereHas('user', fn (Builder $userQuery) => $userQuery->visibleCustomersTo($actor));
    }

    private function authorizeFrozenOrderAccess(Frozen_order $frozenOrder): void
    {
        $actor = Auth::user();
        if ($actor->role === User::ROLE_OWNER) {
            return;
        }

        $frozenOrder->loadMissing('user');
        abort_unless($frozenOrder->user && $actor->canAccessCustomer($frozenOrder->user), 403);
    }
}
