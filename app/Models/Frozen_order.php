<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frozen_order extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'order_id',
        'custom_price',
        'commission_percentage',
        'is_frozen',
        'commission_paid',
        'spun',
        'reminder_sent_at',
        'penalty_sent_at',
        'reminder_sent',
        'penalty_sent',
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
     * Báo cáo đơn hàng ảo (nếu có)
     */
    public function orderReport()
    {
        return $this->hasOne(\App\Models\OrderReport::class, 'frozen_order_id');
    }
}
