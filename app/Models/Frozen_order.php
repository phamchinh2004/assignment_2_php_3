<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class Frozen_order extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Frozen_order $frozenOrder) {
            if ($frozenOrder->isDirty(['custom_price', 'snapshot_order_amount', 'commission_percentage'])) {
                $amount = $frozenOrder->custom_price ?? $frozenOrder->snapshot_order_amount;
                $percentage = $frozenOrder->commission_percentage;
                $frozenOrder->snapshot_commission_amount = $amount !== null && $percentage !== null
                    ? round((float) $amount * ((float) $percentage / 100), 6)
                    : null;
            }
        });

        static::deleting(function (Frozen_order $frozenOrder) {
            static::deleteOwnedSnapshotImage($frozenOrder->snapshot_image);
        });
    }

    protected $fillable = [
        'user_id',
        'order_id',
        'snapshot_order_code',
        'snapshot_order_index',
        'snapshot_name',
        'snapshot_image',
        'snapshot_quantity',
        'snapshot_unit_price',
        'snapshot_order_amount',
        'snapshot_commission_amount',
        'snapshot_payment_method',
        'snapshot_is_paid',
        'snapshot_partner_name',
        'snapshot_api',
        'custom_price',
        'commission_percentage',
        'is_frozen',
        'commission_paid',
        'spun',
        'processing_time_limit',
        'notification_1_remaining_time',
        'notification_2_remaining_time',
        'notification_1_sent_at',
        'notification_2_sent_at',
        'penalty_notification_sent_at',
        'penalty_amount',
        'status',
        'tracking_number',
        'customer_info',
        'platform',
        'order_date',
        'confirmed_at',
        'preparing_at',
        'transit_at',
        'shipping_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'shipping_carrier',
        'shipping_address'
    ];

    protected $casts = [
        'snapshot_unit_price' => 'decimal:6',
        'snapshot_order_amount' => 'decimal:6',
        'snapshot_commission_amount' => 'decimal:6',
        'snapshot_is_paid' => 'boolean',
        'customer_info' => 'array',
        'order_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'transit_at' => 'datetime',
        'shipping_at' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = [
        'snapshot_order_value',
        'snapshot_commission_value',
    ];

    public static function snapshotFromOrder(Order $order, array $attributes = []): self
    {
        $order->loadMissing('partner');
        $amount = array_key_exists('custom_price', $attributes) && $attributes['custom_price'] !== null
            ? (float) $attributes['custom_price']
            : (float) $order->price * (int) $order->quantity;
        $percentage = array_key_exists('commission_percentage', $attributes)
            && $attributes['commission_percentage'] !== null
            && $attributes['commission_percentage'] !== ''
                ? (float) $attributes['commission_percentage']
                : (float) ($order->commission_percentage ?? 0);

        $snapshotImage = static::copySnapshotImage($order->image);

        try {
            return static::create(array_merge([
                'order_id' => $order->id,
                'snapshot_order_code' => $order->order_code,
                'snapshot_order_index' => $order->index,
                'snapshot_name' => $order->name,
                'snapshot_image' => $snapshotImage,
                'snapshot_quantity' => $order->quantity,
                'snapshot_unit_price' => $order->price,
                'snapshot_payment_method' => $order->payment_method,
                'snapshot_is_paid' => $order->is_paid,
                'snapshot_partner_name' => $order->partner?->name,
                'snapshot_api' => $order->api,
                'customer_info' => [
                    'name' => $order->customer_name,
                    'phone' => $order->customer_phone,
                    'address' => $order->customer_address,
                    'note' => $order->customer_note,
                ],
                'platform' => $order->partner?->name,
                'shipping_address' => $order->customer_address,
                'order_date' => now(),
            ], $attributes, [
                'snapshot_image' => $snapshotImage,
                'snapshot_order_amount' => $amount,
                'commission_percentage' => $percentage,
                'snapshot_commission_amount' => round($amount * ($percentage / 100), 6),
            ]));
        } catch (Throwable $exception) {
            static::deleteOwnedSnapshotImage($snapshotImage);
            throw $exception;
        }
    }

    protected static function copySnapshotImage(?string $source): ?string
    {
        if (!$source || !Storage::disk('public')->exists($source)) {
            return null;
        }

        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $destination = 'uploads/images/frozen-orders/' . Str::uuid()
            . ($extension !== '' ? '.' . $extension : '');

        if (!Storage::disk('public')->copy($source, $destination)) {
            throw new \RuntimeException("Không thể snapshot ảnh đơn hàng: {$source}");
        }

        return $destination;
    }

    public static function deleteOwnedSnapshotImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/images/frozen-orders/')) {
            Storage::disk('public')->delete($path);
        }
    }

    public function getSnapshotOrderValueAttribute(): ?float
    {
        $value = $this->custom_price ?? $this->snapshot_order_amount;

        return $value === null ? null : (float) $value;
    }

    public function getSnapshotCommissionValueAttribute(): ?float
    {
        if ($this->snapshot_commission_amount !== null) {
            return (float) $this->snapshot_commission_amount;
        }

        // Legacy special orders already stored both effective values on frozen_orders,
        // so this calculation is deterministic and does not consult mutable orders.
        if ($this->custom_price !== null && $this->commission_percentage !== null) {
            return round((float) $this->custom_price * ((float) $this->commission_percentage / 100), 6);
        }

        return null;
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Lấy lịch sử thay đổi trạng thái của frozen order
     */
    public function statusOrders()
    {
        return $this->hasMany(\App\Models\StatusOrder::class, 'frozen_order_id')->orderBy('created_at', 'desc');
    }

    /**
     * Báo cáo đơn hàng (nếu có)
     */
    public function orderReport()
    {
        return $this->hasOne(\App\Models\OrderReport::class, 'frozen_order_id');
    }
}
