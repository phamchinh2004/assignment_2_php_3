<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    const ROLE_ADMIN = 'admin';
    const ROLE_STAFF = 'staff';
    const ROLE_MEMBER = 'member';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'username',
        'email',
        'phone',
        'password',
        'referral_code',
        'username_bank',
        'bank_name',
        'account_number',
        'balance',
        'transaction_password',
        'distribution_today',
        'todays_discount',
        'count_withdrawals',
        'role',
        'status',
        'rank_id',
        'referrer_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function user_manager_settings()
    {
        return $this->hasMany(User_manager_setting::class);
    }
    public function frozen_orders()
    {
        return $this->hasMany(Frozen_order::class);
    }
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }
    public function invitedUsers()
    {
        return $this->hasMany(User::class, 'referrer_id');
    }
    public function rank()
    {
        return $this->belongsTo(Rank::class, 'rank_id');
    }
    public function transaction_histories()
    {
        return $this->hasMany(Transaction_history::class);
    }
    public function wallet_balance_histories()
    {
        return $this->hasMany(Wallet_balance_history::class);
    }
}
