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
    const ONLINE_THRESHOLD_MINUTES = 5;
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
        'frozen_balance',
        'transaction_password',
        'distribution_today',
        'todays_discount',
        'count_withdrawals',
        'role',
        'status',
        'rank_id',
        'referrer_id',
        'register_ip',
        'clone_account',
        'warehouse_area',
        'warehouse_address',
        'location_permission',
        'location_latitude',
        'location_longitude',
        'location_accuracy',
        'location_country_code',
        'location_country',
        'location_city',
        'location_updated_at',
        'last_seen'
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
        'last_seen' => 'datetime',
        'location_updated_at' => 'datetime',
        'location_latitude' => 'float',
        'location_longitude' => 'float',
        'location_accuracy' => 'float',
        'password' => 'hashed',
    ];

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'user.' . $this->getKey();
    }

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
    /**
     * Tính số tiền hoa hồng tạm tính (từ các đơn hàng chưa completed)
     */
    public function getTemporaryCommissionAttribute()
    {
        $frozenOrders = $this->frozen_orders()
            ->whereIn('status', ['pending', 'confirmed', 'preparing', 'transit', 'shipping', 'delivered'])
            ->where('is_frozen', 1)
            ->get();
        
        $totalCommission = 0;
        
        foreach ($frozenOrders as $frozenOrder) {
            $commission = $frozenOrder->snapshot_commission_value;
            if ($commission === null) continue;
            
            // Trừ tiền phạt nếu có
            $penaltyAmount = $frozenOrder->penalty_amount ?? 0;
            $actualCommission = $commission - $penaltyAmount;
            
            $totalCommission += max(0, $actualCommission); // Đảm bảo không âm
        }
        
        return $totalCommission;
    }
    
    /**
     * Tính hoa hồng thực nhận (từ các đơn đã completed)
     */
    public function getReceivedCommissionAttribute()
    {
        // Tính từ transaction_history với type = 'profit'
        $totalProfit = Transaction_history::where('user_id', $this->id)
            ->where('type', 'profit')
            ->sum('value');
        
        // Trừ tiền phạt đã trừ
        $totalPenalty = Transaction_history::where('user_id', $this->id)
            ->where('type', 'penalty')
            ->sum('value');
        
        return $totalProfit - $totalPenalty;
    }
    
    /**
     * Tính số tiền hoàn nhập (số dư + tiền hoa hồng tạm tính)
     */
    public function getAvailableBalanceAttribute()
    {
        return $this->balance + $this->temporary_commission;
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
    public function memberConversations()
    {
        return $this->hasMany(Conversation::class, 'user_id');
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
    /**
     * Get invited users sorted by latest conversation updated_at
     * Note: Sorting is done in collection after query to avoid join conflicts
     */
    public function invitedUsersOrdered()
    {
        // Load users với latestConversation, sort sẽ được xử lý trong collection
        return $this->hasMany(User::class, 'referrer_id')
            ->where('role', 'member')
            ->with(['latestConversation.messages'])
            ->orderBy('created_at', 'desc'); // Base order nếu không có conversation
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

    /**
     * Kiểm tra user có online không (hoạt động trong ONLINE_THRESHOLD_MINUTES phút gần đây)
     */
    public function isOnline()
    {
        if (!$this->last_seen) {
            return false;
        }
        
        return $this->last_seen->gt(now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES));
    }

    /**
     * Kiểm tra user có online không (hoạt động trong X phút gần đây)
     */
    public function isOnlineWithin($minutes = self::ONLINE_THRESHOLD_MINUTES)
    {
        if (!$this->last_seen) {
            return false;
        }
        
        return $this->last_seen->gt(now()->subMinutes($minutes));
    }

    /**
     * Lấy thời gian lần cuối user online (tương đối)
     */
    public function getLastSeenTextAttribute()
    {
        if (!$this->last_seen) {
            return 'Chưa từng online';
        }

        if ($this->isOnline()) {
            return 'Đang online';
        }

        return $this->last_seen->diffForHumans();
    }

    /**
     * Lấy thời gian lần cuối user online (tuyệt đối)
     */
    public function getLastSeenFormattedAttribute()
    {
        if (!$this->last_seen) {
            return 'Chưa từng online';
        }

        return $this->last_seen->format('d/m/Y H:i:s');
    }

    /**
     * Kiểm tra user có đơn hàng bị phạt không
     */
    public function hasPenalizedOrders()
    {
        return $this->frozen_orders()
            ->where('is_frozen', true)
            ->where('penalty_amount', '>', 0)
            ->exists();
    }

    /**
     * Lấy tổng số tiền phạt
     */
    public function getTotalPenaltyAmountAttribute()
    {
        return $this->frozen_orders()
            ->where('is_frozen', true)
            ->sum('penalty_amount');
    }

    /**
     * Lấy tổng giá trị đơn hàng bị frozen
     */
    public function getTotalFrozenOrdersValueAttribute()
    {
        return $this->frozen_orders()
            ->where('is_frozen', true)
            ->where('spun', true)
            ->get()
            ->sum(function($frozenOrder) {
                return $frozenOrder->snapshot_order_value ?? 0;
            });
    }

    /**
     * Tính số tiền cần nạp để mở khóa đơn hàng
     * Công thức: Tổng giá trị đơn hàng + Tiền phạt - Số dư hiện tại
     */
    public function getRequiredDepositAmountAttribute()
    {
        $totalRequired = $this->total_frozen_orders_value + $this->total_penalty_amount;
        $needToDeposit = $totalRequired - $this->balance;
        
        return max(0, $needToDeposit); // Không trả về số âm
    }

    /**
     * Lấy thông tin chi tiết về phạt
     */
    public function getPenaltyInfoAttribute()
    {
        if (!$this->hasPenalizedOrders()) {
            return null;
        }

        return [
            'total_penalty' => $this->total_penalty_amount,
            'total_frozen_value' => $this->total_frozen_orders_value,
            'current_balance' => $this->balance,
            'required_deposit' => $this->required_deposit_amount,
            'frozen_orders_count' => $this->frozen_orders()
                ->where('is_frozen', true)
                ->where('spun', true)
                ->whereNotNull('penalty_amount')
                ->where('penalty_amount', '>', 0)
                ->count()
        ];
    }

    /**
     * Kiểm tra user có đơn hàng giá trị cao chưa phân phối không
     * (custom_price IS NOT NULL AND is_frozen = 1: đơn hàng giá trị cao chưa hoàn thành)
     */
    public function hasHighValueOrders()
    {
        return $this->frozen_orders()
            ->where('is_frozen', true)
            ->where('spun', true)
            ->whereNotNull('custom_price')
            ->exists();
    }

    /**
     * Lấy tổng giá trị đơn hàng giá trị cao (chưa hoàn thành)
     */
    public function getTotalHighValueOrdersValueAttribute()
    {
        return $this->frozen_orders()
            ->where('is_frozen', true)
            ->where('spun', true)
            ->whereNotNull('custom_price')
            ->get()
            ->sum(function($frozenOrder) {
                return $frozenOrder->snapshot_order_value ?? 0;
            });
    }

    /**
     * Tính số tiền cần nạp cho đơn hàng giá trị cao (không tính tiền phạt)
     */
    public function getRequiredDepositForHighValueOrdersAttribute()
    {
        $needToDeposit = $this->total_high_value_orders_value - $this->balance;
        return max(0, $needToDeposit);
    }

    /**
     * Lấy thông tin chi tiết về đơn hàng giá trị cao (chưa hoàn thành)
     */
    public function getHighValueOrdersInfoAttribute()
    {
        if (!$this->hasHighValueOrders()) {
            return null;
        }

        return [
            'total_value' => $this->total_high_value_orders_value,
            'current_balance' => $this->balance,
            'required_deposit' => $this->required_deposit_for_high_value_orders,
            'orders_count' => $this->frozen_orders()->where('is_frozen', true)->where('spun', true)->whereNotNull('custom_price')->count(),
            'bonus_amount' => $this->total_high_value_orders_value * 0.1 // 10% thưởng
        ];
    }
}
