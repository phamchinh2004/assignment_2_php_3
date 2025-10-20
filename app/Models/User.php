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
        'register_ip',
        'clone_account'
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
    public function deposits_made()
    {
        return $this->hasMany(Wallet_balance_history::class, 'by_user_id');
    }
    public function user_spin_progress()
    {
        return $this->belongsTo(User_spin_progress::class, 'user_id');
    }
    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'staff_id');
    }
    public function conversation()
    {
        return $this->hasOne(Conversation::class, 'user_id');
    }

    public function latestConversation()
    {
        return $this->hasOne(Conversation::class, 'user_id')
            ->latestOfMany('updated_at');
    }
    public function invitedUsersOrdered()
    {
        return $this->hasMany(User::class, 'referrer_id')
            ->where('role', 'member')
            ->with('latestConversation')
            ->leftJoin('conversations', 'conversations.user_id', '=', 'users.id')
            ->selectRaw('users.id,users.full_name,users.username,users.role, MAX(conversations.updated_at) as last_message_time')
            ->groupBy(
                'users.id',
                'users.full_name',
                'users.username',
                'users.role',
            )
            ->orderByDesc('last_message_time');
    }

    /**
     * Tính tổng số tiền đã nạp (chỉ tính các giao dịch đã hoàn thành)
     */
    public function getTotalDepositAttribute()
    {
        return $this->wallet_balance_histories()
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('value');
    }

    /**
     * Tính tổng số tiền đã rút (chỉ tính các giao dịch đã hoàn thành)
     */
    public function getTotalWithdrawAttribute()
    {
        return $this->wallet_balance_histories()
            ->where('type', 'withdraw')
            ->where('status', 'completed')
            ->sum('value');
    }

    /**
     * Tính lợi nhuận đơn giản (Tổng rút - Tổng nạp)
     * Cho thấy số tiền thực tế đã rút được từ hệ thống
     */
    public function getProfitAttribute()
    {
        return $this->total_withdraw - $this->total_deposit;
    }

    /**
     * Tính lợi nhuận từ hoạt động (chỉ tính các giao dịch bonus)
     */
    public function getBusinessProfitAttribute()
    {
        return $this->wallet_balance_histories()
            ->where('transaction_type', 'bonus')
            ->where('status', 'completed')
            ->sum('value');
    }

    /**
     * Tính tổng số tiền đã rút thực tế (không bao gồm virtual_withdraw)
     */
    public function getRealWithdrawAttribute()
    {
        return $this->wallet_balance_histories()
            ->where('type', 'withdraw')
            ->where('status', 'completed')
            ->where('transaction_type', '!=', 'virtual_withdraw')
            ->sum('value');
    }

    /**
     * Đếm số giao dịch hôm nay
     */
    public function getTodayTransactionsAttribute()
    {
        return $this->wallet_balance_histories()
            ->whereDate('created_at', today())
            ->count();
    }

    /**
     * Đếm số giao dịch trong tháng hiện tại
     */
    public function getThisMonthTransactionsAttribute()
    {
        return $this->wallet_balance_histories()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    /**
     * Lấy giao dịch gần đây nhất
     */
    public function getLatestTransactionAttribute()
    {
        return $this->wallet_balance_histories()
            ->latest()
            ->first();
    }
}
