<?php

namespace App\Services;

use App\Models\Frozen_order;
use Illuminate\Support\Facades\DB;
use Throwable;

class FrozenOrderSnapshotService
{
    public function canRestoreFinancials(Frozen_order $frozenOrder): bool
    {
        return !$frozenOrder->commission_paid
            && in_array($frozenOrder->status, [null, 'pending'], true);
    }

    public function diagnoseMissingFields(Frozen_order $frozenOrder): array
    {
        $frozenOrder->loadMissing('order.partner');
        $order = $frozenOrder->order;
        $currentOrderAmount = $order && $order->price !== null && $order->quantity !== null
            ? (float) $order->price * (int) $order->quantity
            : null;
        $sources = [
            'snapshot_order_code' => $order?->order_code,
            'snapshot_name' => $order?->name,
            'snapshot_quantity' => $order?->quantity,
            'snapshot_unit_price' => $order?->price,
            'snapshot_order_amount' => $frozenOrder->custom_price
                ?? $currentOrderAmount,
            'commission_percentage' => $order?->commission_percentage,
            'snapshot_commission_amount' => null,
        ];
        $sourceNames = [
            'snapshot_order_code' => 'orders.order_code',
            'snapshot_name' => 'orders.name',
            'snapshot_quantity' => 'orders.quantity',
            'snapshot_unit_price' => 'orders.price',
            'snapshot_order_amount' => $frozenOrder->custom_price !== null
                ? 'frozen_orders.custom_price'
                : 'orders.price × orders.quantity',
            'commission_percentage' => 'orders.commission_percentage',
            'snapshot_commission_amount' => 'snapshot amount × snapshot/current commission rate',
        ];

        $commissionAmountSource = $frozenOrder->custom_price
            ?? $frozenOrder->snapshot_order_amount
            ?? $currentOrderAmount;
        $commissionRateSource = $frozenOrder->commission_percentage
            ?? $order?->commission_percentage;
        if ($commissionAmountSource !== null && $commissionRateSource !== null) {
            $sources['snapshot_commission_amount'] = round(
                (float) $commissionAmountSource * ((float) $commissionRateSource / 100),
                6
            );
        }

        return collect($frozenOrder->snapshot_missing_fields)
            ->map(function (string $field) use ($frozenOrder, $order, $sources, $sourceNames) {
                $isFinancial = in_array($field, Frozen_order::SNAPSHOT_FINANCIAL_FIELDS, true);
                $sourceAvailable = array_key_exists($field, $sources) && $sources[$field] !== null && $sources[$field] !== '';

                if (!$order) {
                    $reason = 'Order nguồn không còn tồn tại.';
                } elseif (!$sourceAvailable) {
                    $reason = 'Field nguồn tương ứng trong Order đang trống.';
                } elseif ($isFinancial && !$this->canRestoreFinancials($frozenOrder)) {
                    $reason = 'Đã khóa: đơn không còn pending hoặc commission đã trả. Dùng giá trị Order hiện tại có thể làm sai lịch sử tài chính.';
                } else {
                    $reason = 'Có thể bổ sung từ Order hiện tại; dữ liệu sẽ được đánh dấu chưa xác minh.';
                }

                return [
                    'field' => $field,
                    'label' => Frozen_order::SNAPSHOT_FIELD_LABELS[$field] ?? $field,
                    'source_field' => $sourceNames[$field] ?? 'không xác định',
                    'source' => $sources[$field] ?? null,
                    'source_available' => $sourceAvailable,
                    'can_restore' => $sourceAvailable && (!$isFinancial || $this->canRestoreFinancials($frozenOrder)),
                    'reason' => $reason,
                ];
            })
            ->values()
            ->all();
    }

    public function restoreFromCurrentOrder(
        Frozen_order $frozenOrder,
        int $adminId,
        bool $includeFinancials = false
    ): array {
        $filled = [];
        $copiedImage = null;

        try {
            DB::beginTransaction();
            $frozenOrder = Frozen_order::query()
                ->lockForUpdate()
                ->findOrFail($frozenOrder->id);
            $frozenOrder->load('order.partner');
            $order = $frozenOrder->order;

            if (!$order) {
                throw new \RuntimeException('Order nguồn không còn tồn tại.');
            }

            if ($includeFinancials && !$this->canRestoreFinancials($frozenOrder)) {
                throw new \RuntimeException('Không được phục hồi tài chính cho đơn đã xác nhận, hoàn thành hoặc đã trả commission.');
            }

            $put = function (string $field, mixed $value) use ($frozenOrder, &$filled): void {
                if ($frozenOrder->getAttribute($field) === null && $value !== null && $value !== '') {
                    $frozenOrder->setAttribute($field, $value);
                    $filled[] = $field;
                }
            };

            $put('snapshot_order_code', $order->order_code);
            $put('snapshot_order_index', $order->index);
            $put('snapshot_name', $order->name);

            if ($frozenOrder->snapshot_image === null && $order->image) {
                $copiedImage = Frozen_order::copySnapshotImage($order->image);
                $put('snapshot_image', $copiedImage);
            }

            $put('snapshot_payment_method', $order->payment_method);
            $put('snapshot_is_paid', $order->is_paid);
            $put('snapshot_partner_name', $order->partner?->name);
            $put('snapshot_api', $order->api);
            $put('snapshot_customer_name', $order->customer_name);
            $put('snapshot_customer_phone', $order->customer_phone);
            $put('snapshot_customer_address', $order->customer_address);
            $put('snapshot_customer_note', $order->customer_note);

            if ($includeFinancials) {
                $put('snapshot_quantity', $order->quantity);
                $put('snapshot_unit_price', $order->price);
                $put('snapshot_order_amount', $frozenOrder->custom_price
                    ?? ((float) $order->price * (int) $order->quantity));
                $put('commission_percentage', $order->commission_percentage ?? 0);

                $amount = $frozenOrder->custom_price ?? $frozenOrder->snapshot_order_amount;
                $percentage = $frozenOrder->commission_percentage;
                if ($frozenOrder->snapshot_commission_amount === null
                    && $amount !== null
                    && $percentage !== null) {
                    $frozenOrder->snapshot_commission_amount = round(
                        (float) $amount * ((float) $percentage / 100),
                        6
                    );
                    $filled[] = 'snapshot_commission_amount';
                }
            }

            if ($filled !== []) {
                $frozenOrder->snapshot_source = 'restored_current';
                $frozenOrder->snapshot_restored_at = now();
                $frozenOrder->snapshot_restored_by = $adminId;
                $frozenOrder->save();
            }

            DB::commit();
            return $filled;
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if ($copiedImage) {
                Frozen_order::deleteOwnedSnapshotImage($copiedImage);
            }
            throw $exception;
        }
    }
}
