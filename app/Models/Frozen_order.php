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

    public const SNAPSHOT_REQUIRED_FIELDS = [
        'snapshot_order_code',
        'snapshot_name',
        'snapshot_quantity',
        'snapshot_unit_price',
        'snapshot_order_amount',
        'commission_percentage',
        'snapshot_commission_amount',
    ];

    public const SNAPSHOT_FIELD_LABELS = [
        'snapshot_order_code' => 'Mã đơn',
        'snapshot_name' => 'Tên sản phẩm',
        'snapshot_quantity' => 'Số lượng',
        'snapshot_unit_price' => 'Đơn giá',
        'snapshot_order_amount' => 'Tổng giá trị',
        'commission_percentage' => 'Tỷ lệ commission',
        'snapshot_commission_amount' => 'Tiền commission',
    ];

    public const SNAPSHOT_FINANCIAL_FIELDS = [
        'snapshot_quantity',
        'snapshot_unit_price',
        'snapshot_order_amount',
        'commission_percentage',
        'snapshot_commission_amount',
    ];

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
        'snapshot_customer_name',
        'snapshot_customer_phone',
        'snapshot_customer_address',
        'snapshot_customer_note',
        'snapshot_source',
        'snapshot_captured_at',
        'snapshot_restored_at',
        'snapshot_restored_by',
        'settled_order_amount',
        'settled_commission_amount',
        'settled_penalty_amount',
        'settled_refund_amount',
        'settled_balance_destination',
        'settled_at',
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
        'order_date',
        'confirmed_at',
        'preparing_at',
        'transit_at',
        'shipping_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'shipping_carrier'
    ];

    protected $casts = [
        'snapshot_unit_price' => 'decimal:6',
        'snapshot_order_amount' => 'decimal:6',
        'snapshot_commission_amount' => 'decimal:6',
        'snapshot_is_paid' => 'boolean',
        'snapshot_captured_at' => 'datetime',
        'snapshot_restored_at' => 'datetime',
        'settled_order_amount' => 'decimal:6',
        'settled_commission_amount' => 'decimal:6',
        'settled_penalty_amount' => 'decimal:6',
        'settled_refund_amount' => 'decimal:6',
        'settled_at' => 'datetime',
        'penalty_notification_sent_at' => 'datetime',
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
        'snapshot_state',
        'snapshot_missing_fields',
        'uses_snapshot_fallback',
        'display_order_code',
        'display_name',
        'display_image',
        'display_quantity',
        'display_unit_price',
        'display_order_amount',
        'display_commission_percentage',
        'display_commission_amount',
        'display_customer_name',
        'display_customer_phone',
        'display_customer_address',
        'display_customer_note',
        'display_partner_name',
        'display_payment_method',
        'display_is_paid',
        'display_api',
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
                'snapshot_customer_name' => $order->customer_name,
                'snapshot_customer_phone' => $order->customer_phone,
                'snapshot_customer_address' => $order->customer_address,
                'snapshot_customer_note' => $order->customer_note,
                'snapshot_source' => 'captured',
                'snapshot_captured_at' => now(),
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

    public static function copySnapshotImage(?string $source): ?string
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

    public function getSnapshotMissingFieldsAttribute(): array
    {
        return collect(self::SNAPSHOT_REQUIRED_FIELDS)
            ->filter(fn (string $field) => $this->getAttribute($field) === null)
            ->values()
            ->all();
    }

    public function getSnapshotStateAttribute(): string
    {
        $hasNoSnapshot = $this->snapshot_order_code === null
            && $this->snapshot_name === null
            && $this->snapshot_order_amount === null
            && $this->snapshot_commission_amount === null;

        if ($hasNoSnapshot) {
            return 'legacy';
        }

        if ($this->snapshot_missing_fields !== []) {
            return 'incomplete';
        }

        $amount = (float) $this->snapshot_order_amount;
        $expectedAmount = $this->custom_price !== null
            ? (float) $this->custom_price
            : (float) $this->snapshot_unit_price * (int) $this->snapshot_quantity;
        $expectedCommission = $amount * ((float) $this->commission_percentage / 100);

        if ((int) $this->snapshot_quantity <= 0
            || $amount < 0
            || (float) $this->commission_percentage < 0
            || abs($amount - $expectedAmount) > 0.01
            || abs((float) $this->snapshot_commission_amount - $expectedCommission) > 0.01) {
            return 'invalid';
        }

        return 'complete';
    }

    public function getUsesSnapshotFallbackAttribute(): bool
    {
        return $this->snapshot_missing_fields !== []
            || ($this->snapshot_customer_name === null && $this->order?->customer_name !== null)
            || ($this->snapshot_customer_phone === null && $this->order?->customer_phone !== null)
            || ($this->snapshot_customer_address === null && $this->order?->customer_address !== null)
            || ($this->snapshot_customer_note === null && $this->order?->customer_note !== null)
            || ($this->snapshot_partner_name === null && $this->order?->partner?->name !== null)
            || ($this->snapshot_payment_method === null && $this->order?->payment_method !== null)
            || ($this->snapshot_is_paid === null && $this->order !== null)
            || ($this->snapshot_api === null && $this->order?->api !== null);
    }

    public function getDisplayOrderCodeAttribute(): ?string
    {
        return $this->snapshot_order_code ?? $this->order?->order_code;
    }

    public function getDisplayNameAttribute(): ?string
    {
        return $this->snapshot_name ?? $this->order?->name;
    }

    public function getDisplayImageAttribute(): ?string
    {
        return $this->snapshot_image ?? $this->order?->image;
    }

    public function getDisplayQuantityAttribute(): ?int
    {
        $value = $this->snapshot_quantity ?? $this->order?->quantity;
        return $value === null ? null : (int) $value;
    }

    public function getDisplayUnitPriceAttribute(): ?float
    {
        $value = $this->snapshot_unit_price ?? $this->order?->price;
        return $value === null ? null : (float) $value;
    }

    public function getDisplayOrderAmountAttribute(): ?float
    {
        if ($this->snapshot_order_value !== null) {
            return $this->snapshot_order_value;
        }

        return $this->order
            ? (float) $this->order->price * (int) $this->order->quantity
            : null;
    }

    public function getDisplayCommissionPercentageAttribute(): ?float
    {
        $value = $this->commission_percentage ?? $this->order?->commission_percentage;
        return $value === null ? null : (float) $value;
    }

    public function getDisplayCommissionAmountAttribute(): ?float
    {
        if ($this->snapshot_commission_value !== null) {
            return $this->snapshot_commission_value;
        }

        return $this->display_order_amount !== null && $this->display_commission_percentage !== null
            ? round($this->display_order_amount * ($this->display_commission_percentage / 100), 6)
            : null;
    }

    public function getDisplayCustomerNameAttribute(): ?string
    {
        return $this->snapshot_customer_name ?? $this->order?->customer_name;
    }

    public function getDisplayCustomerPhoneAttribute(): ?string
    {
        return $this->snapshot_customer_phone ?? $this->order?->customer_phone;
    }

    public function getDisplayCustomerAddressAttribute(): ?string
    {
        return $this->snapshot_customer_address ?? $this->order?->customer_address;
    }

    public function getDisplayCustomerNoteAttribute(): ?string
    {
        return $this->snapshot_customer_note ?? $this->order?->customer_note;
    }

    /**
     * Backward-compatibility accessor for legacy code expecting customer_info array
     */
    public function getCustomerInfoAttribute(): ?array
    {
        $name = $this->display_customer_name;
        $phone = $this->display_customer_phone;
        $address = $this->display_customer_address;
        $note = $this->display_customer_note;

        if ($name === null && $phone === null && $address === null && $note === null) {
            return null;
        }

        return [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'note' => $note,
        ];
    }

    public function getDisplayPartnerNameAttribute(): ?string
    {
        return $this->snapshot_partner_name ?? $this->order?->partner?->name;
    }

    /**
     * Backward-compatibility accessor for legacy code expecting platform
     */
    public function getPlatformAttribute(): ?string
    {
        return $this->display_partner_name;
    }

    public function getDisplayPaymentMethodAttribute(): ?string
    {
        return $this->snapshot_payment_method ?? $this->order?->payment_method;
    }

    public function getDisplayIsPaidAttribute(): ?bool
    {
        if ($this->snapshot_is_paid !== null) {
            return (bool) $this->snapshot_is_paid;
        }

        return $this->order ? (bool) $this->order->is_paid : null;
    }

    public function getDisplayApiAttribute(): ?string
    {
        return $this->snapshot_api ?? $this->order?->api;
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function snapshotRestoredBy()
    {
        return $this->belongsTo(User::class, 'snapshot_restored_by');
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
