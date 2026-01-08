<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Order $order) {
            // COD (thanh toán khi nhận hàng) không được tự động là "đã thanh toán"
            if (is_string($order->payment_method) && strtoupper(trim($order->payment_method)) === 'COD') {
                $order->payment_method = 'COD'; // chuẩn hoá giá trị enum
                $order->is_paid = false;
            }
        });
    }
    protected $fillable = [
        'index',
        'order_code',
        'image',
        'name',
        'quantity',
        'price',
        'fake_price',
        'commission_percentage',
        'rank_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_note',
        'is_paid',
        'payment_method',
        'partner_id',
        'api',
    ];
    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
    public function frozen_orders()
    {
        return $this->hasMany(Frozen_order::class);
    }
    
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

}
