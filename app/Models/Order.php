<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
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
    ];
    public function rank()
    {
        return $this->belongsTo(Rank::class);
    }
    public function frozen_orders()
    {
        return $this->hasMany(Frozen_order::class);
    }
}
