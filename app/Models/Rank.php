<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rank extends Model
{
    use HasFactory;
    protected $fillable = [
        'image',
        'name',
        'commission_percentage',
        'upgrade_fee',
        'spin_count',
        'value',
        'maximum_number_of_withdrawals',
        'maximum_withdrawal_amount'
    ];
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
