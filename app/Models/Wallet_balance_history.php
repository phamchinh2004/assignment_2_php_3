<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet_balance_history extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'value',
        'initial_balance',
        'type',
        'status',
        'by_user_id',
        'username_bank',
        'bank_name',
        'account_number',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function byUser()
    {
        return $this->belongsTo(User::class, 'by_user_id');
    }
}
