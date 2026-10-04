<?php

namespace App\Services;

use App\Jobs\PrepareOrder;
use App\Models\Frozen_order;
use App\Models\Transaction_history;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderConfirmationService
{
    public function __construct(private readonly OverdueOrderPenaltyService $overduePenaltyService)
    {
    }

    public function confirm(User $user, Frozen_order $frozen_order, int $changedBy): JsonResponse
    {
        // Kiểm tra quyền truy cập
        if ((int) $frozen_order->user_id !== (int) $user->id) {
            return response()->json([
                'status' => 403,
                'message' => 'Bạn không có quyền thực hiện thao tác này'
            ]);
        }

        // Refresh model để lấy status mới nhất từ database
        $frozen_order->refresh();
        $this->overduePenaltyService->applyIfOverdue($frozen_order);

        // Kiểm tra trạng thái - chỉ cho phép xác nhận khi status là 'pending' hoặc null
        $currentStatus = $frozen_order->status;
        if ($currentStatus && $currentStatus !== 'pending') {
            return response()->json([
                'status' => 400,
                'message' => 'Đơn hàng không thể xác nhận. Trạng thái hiện tại: ' . $currentStatus
            ]);
        }

        // Kiểm tra số dư đủ để xử lý đơn hàng (chỉ kiểm tra khi xác nhận)
        $total_price = $frozen_order->snapshot_order_value;
        if ($total_price === null) {
            return response()->json([
                'status' => 409,
                'message' => 'Đơn hàng cũ chưa có dữ liệu snapshot chính xác. Vui lòng liên hệ quản trị viên.',
            ]);
        }

        // Nếu là đơn hàng giá trị cao, cần kiểm tra số dư + tiền phạt
        $penalty_amount = $frozen_order->penalty_amount ?? 0;
        $total_required = $total_price + $penalty_amount;

        // Kiểm tra số dư: đơn hàng giá trị cao kiểm tra frozen_balance, đơn thường kiểm tra balance
        if ($frozen_order->custom_price != null) {
            // Đơn hàng giá trị cao: kiểm tra frozen_balance
            $available_balance = $user->frozen_balance ?? 0;
            $balance_type = 'số dư đóng băng';
        } else {
            // Đơn thường: kiểm tra balance
            $available_balance = $user->balance;
            $balance_type = 'số dư';
        }

        if ($total_required > $available_balance) {
            return response()->json([
                'status' => 400,
                'message' => __('order.SoDuKhongDu') . ' Số tiền cần: $' . number_format($total_required, 2) . ', ' . $balance_type . ' hiện tại: $' . number_format($available_balance, 2),
            ]);
        }

        // Kiểm tra status 'confirmed' có tồn tại không
        $confirmedStatus = \App\Models\Status::where('name', 'confirmed')->first();
        if (!$confirmedStatus) {
            Log::error('Status confirmed không tồn tại trong database');
            return response()->json([
                'status' => 500,
                'message' => 'Hệ thống chưa được cấu hình đúng. Vui lòng liên hệ quản trị viên!'
            ]);
        }

        // Chuyển trạng thái và trừ tiền hàng trong cùng transaction.
        // Như vậy, nếu xác nhận thất bại thì số dư không bị thay đổi, và các request đồng thời
        // không thể xác nhận/trừ tiền hai lần.
        try {
            $frozen_order = DB::transaction(function () use ($frozen_order, $changedBy) {
                $lockedOrder = Frozen_order::with('order')
                    ->lockForUpdate()
                    ->findOrFail($frozen_order->id);
                $lockedUser = User::lockForUpdate()->findOrFail($lockedOrder->user_id);

                if ($lockedOrder->status && $lockedOrder->status !== 'pending') {
                    throw new \RuntimeException('Đơn hàng đã được xử lý.');
                }

                $orderTotal = $lockedOrder->snapshot_order_value;
                if ($orderTotal === null) {
                    throw new \RuntimeException('Đơn hàng cũ chưa có dữ liệu snapshot chính xác.');
                }
                $this->overduePenaltyService->applyIfOverdue($lockedOrder);
                $penaltyAmount = $lockedOrder->penalty_amount ?? 0;

                if ($lockedOrder->custom_price !== null) {
                    // Tiền nạp thêm vẫn nằm ở balance; gom vào ví đóng băng
                    // trước khi trừ tiền hàng của đơn hàng giá trị cao.
                    $lockedUser->frozen_balance += $lockedUser->balance;
                    $lockedUser->balance = 0;
                    $availableBalance = $lockedUser->frozen_balance;
                } else {
                    $availableBalance = $lockedUser->balance;
                }

                if ((float) $availableBalance < (float) ($orderTotal + $penaltyAmount)) {
                    throw new \RuntimeException('Số dư không đủ để xác nhận đơn hàng.');
                }

                if (!OrderStatusService::changeStatus(
                    $lockedOrder,
                    'confirmed',
                    'Người dùng xác nhận đơn hàng',
                    $changedBy
                )) {
                    throw new \RuntimeException('Không thể thay đổi trạng thái đơn hàng.');
                }

                // Chỉ trừ tiền hàng; tiền phạt (nếu có) vẫn được xử lý khi hoàn tất đơn.
                if ($lockedOrder->custom_price !== null) {
                    $lockedUser->frozen_balance -= $orderTotal;
                    $lockedUser->balance += $lockedUser->frozen_balance;
                    $lockedUser->frozen_balance = 0;
                } else {
                    $lockedUser->balance -= $orderTotal;
                }
                $lockedUser->save();
                $lockedOrder->is_frozen = 0;
                $lockedOrder->save();

                Transaction_history::create([
                    'user_id' => $lockedUser->id,
                    'value' => $orderTotal,
                    'type' => 'order',
                    'note' => $lockedOrder->snapshot_order_code ?? (string) $lockedOrder->order_id,
                ]);

                return $lockedOrder;
            });
        } catch (\Exception $e) {
            Log::error('Không thể xác nhận đơn hàng hoặc trừ tiền trong ví', [
                'frozen_order_id' => $frozen_order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 500,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
        }

        // Lấy cấu hình thời gian từ database
        $timing = \App\Models\OrderStatusTiming::getTiming('confirmed', 'preparing');
        if ($timing && $timing->is_active) {
            $minMinutes = $timing->getMinTimeInMinutes();
            $maxMinutes = $timing->getMaxTimeInMinutes();
            $delayMinutes = rand($minMinutes, $maxMinutes);
        } else {
            // Fallback: 5-10 phút nếu không có cấu hình
            $delayMinutes = rand(5, 10);
        }

        DB::afterCommit(function () use ($frozen_order, $delayMinutes) {
            try {
                PrepareOrder::dispatch($frozen_order->id)
                    ->delay(now()->addMinutes($delayMinutes));
            } catch (\Exception $e) {
                Log::warning('Không thể dispatch job PrepareOrder', [
                    'frozen_order_id' => $frozen_order->id,
                    'error' => $e->getMessage(),
                    'queue_connection' => config('queue.default')
                ]);
            }
        });

        // Refresh lại model để đảm bảo có dữ liệu mới nhất
        $frozen_order->refresh();

        Log::info('Đơn hàng đã được xác nhận', [
            'frozen_order_id' => $frozen_order->id,
            'order_id' => $frozen_order->order_id,
            'platform' => $frozen_order->display_partner_name,
            'status' => $frozen_order->status,
            'is_frozen' => 0
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Đã xác nhận đơn hàng thành công!',
            'frozen_order' => $frozen_order->fresh()
        ]);
    }

}
