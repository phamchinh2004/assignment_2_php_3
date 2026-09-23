<?php

namespace App\Services;

use App\Jobs\CompleteOrder;
use App\Jobs\DeliverOrder;
use App\Jobs\PrepareOrder;
use App\Jobs\ShipOrder;
use App\Jobs\TransitOrder;
use App\Models\Frozen_order;
use App\Models\OrderStatusTiming;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AdminOrderTransitionService
{
    /** The existing queued workflow; pending confirmation remains the customer's financial action. */
    public const NEXT = [
        'confirmed' => 'preparing',
        'preparing' => 'transit',
        'transit' => 'shipping',
        'shipping' => 'delivered',
        'delivered' => 'completed',
    ];

    private const JOBS = [
        'confirmed' => PrepareOrder::class,
        'preparing' => TransitOrder::class,
        'transit' => ShipOrder::class,
        'shipping' => DeliverOrder::class,
        'delivered' => CompleteOrder::class,
    ];

    private const STATUS_TIMESTAMPS = [
        'confirmed' => 'confirmed_at',
        'preparing' => 'preparing_at',
        'transit' => 'transit_at',
        'shipping' => 'shipping_at',
        'delivered' => 'delivered_at',
    ];

    /** Existing job fallbacks when no active timing rule is configured, in minutes. */
    private const FALLBACK_TIMING = [
        'confirmed' => [5, 10],
        'preparing' => [60, 180],
        'transit' => [720, 2880],
        'shipping' => [360, 720],
        'delivered' => [20160, 20160],
    ];

    public function describe(Frozen_order $order): array
    {
        $current = $order->status;
        $next = self::NEXT[$current ?? ''] ?? null;
        if (!$next) {
            return ['next' => null, 'ready' => false, 'reason' => match ($current) {
                null, 'pending' => 'Đang chờ người dùng xác nhận đơn và thanh toán theo quy trình hiện tại.',
                'completed', 'cancelled' => 'Đơn hàng đã kết thúc, không còn bước tiếp theo.',
                default => 'Trạng thái này chưa có bước chuyển được xác định trong hệ thống.',
            }, 'available_at' => null];
        }

        if (!$order->updated_at) {
            return ['next' => $next, 'ready' => false, 'reason' => 'Bản ghi cũ thiếu thời điểm cập nhật để kiểm tra xung đột.', 'available_at' => null];
        }

        if (!Status::query()->where('name', $next)->where('is_active', true)->exists()) {
            return ['next' => $next, 'ready' => false, 'reason' => 'Trạng thái đích chưa được kích hoạt trong cấu hình.', 'available_at' => null];
        }

        if ($next === 'completed' && ($order->commission_paid || $order->snapshot_order_value === null || $order->snapshot_commission_value === null)) {
            return ['next' => $next, 'ready' => false, 'reason' => 'Chưa đủ điều kiện quyết toán hoặc hoa hồng đã được trả.', 'available_at' => null];
        }

        $timestamp = $order->{self::STATUS_TIMESTAMPS[$current]};
        if (!$timestamp) {
            return ['next' => $next, 'ready' => false, 'reason' => 'Đơn chưa ghi nhận thời điểm bắt đầu trạng thái hiện tại.', 'available_at' => null];
        }

        [$minimum] = $this->timing($current, $next);
        $availableAt = $timestamp->copy()->addMinutes($minimum);
        if (now()->lt($availableAt)) {
            return [
                'next' => $next,
                'ready' => false,
                'reason' => 'Chưa đủ thời gian tối thiểu theo quy trình xử lý đơn.',
                'available_at' => $availableAt,
            ];
        }

        return ['next' => $next, 'ready' => true, 'reason' => null, 'available_at' => $availableAt];
    }

    /** Lock, recheck the exact status/version and reuse the existing job's business effects. */
    public function advance(Frozen_order $order, string $expectedStatus, string $expectedUpdatedAt): string
    {
        return DB::transaction(function () use ($order, $expectedStatus, $expectedUpdatedAt) {
            $locked = Frozen_order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->status !== $expectedStatus || $locked->updated_at?->toISOString() !== $expectedUpdatedAt) {
                throw new ConflictHttpException('Đơn hàng đã được cập nhật ở nơi khác. Hãy tải lại Audit để xem trạng thái mới.');
            }

            $transition = $this->describe($locked);
            if (!$transition['next'] || !$transition['ready']) {
                throw new ConflictHttpException($transition['reason'] ?? 'Không có bước chuyển hợp lệ.');
            }

            $jobClass = self::JOBS[$expectedStatus];
            $job = $expectedStatus === 'delivered'
                ? new $jobClass($locked->id)
                : new $jobClass($locked->id, false);
            $job->handle();

            $updated = $locked->fresh();
            if ($updated->status !== $transition['next']) {
                throw new \RuntimeException('Không thể hoàn tất bước chuyển trạng thái. Không có dữ liệu nào được ghi nhận.');
            }

            $this->scheduleFollowingStep($updated->id, $updated->status);

            return $updated->status;
        });
    }

    private function timing(string $from, string $to): array
    {
        $rule = OrderStatusTiming::getTiming($from, $to);
        if (!$rule) {
            return self::FALLBACK_TIMING[$from];
        }

        $minimum = max(0, $rule->getMinTimeInMinutes());
        return [$minimum, max($minimum, $rule->getMaxTimeInMinutes())];
    }

    private function scheduleFollowingStep(int $id, string $current): void
    {
        // The sync queue ignores delays; the existing auto-process command handles due steps in development.
        if (config('queue.default') === 'sync' || !isset(self::NEXT[$current])) {
            return;
        }

        $next = self::NEXT[$current];
        [$minimum, $maximum] = $this->timing($current, $next);
        $delay = random_int($minimum, $maximum);
        $jobClass = self::JOBS[$current];

        DB::afterCommit(function () use ($jobClass, $id, $delay) {
            try {
                $jobClass::dispatch($id)->delay(now()->addMinutes($delay));
            } catch (\Throwable $exception) {
                Log::warning('Không thể lập lịch bước xử lý tiếp theo; lệnh auto-process có thể tiếp tục xử lý.', [
                    'frozen_order_id' => $id,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}
