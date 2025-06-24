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
        'is_frozen',
        'spun'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
