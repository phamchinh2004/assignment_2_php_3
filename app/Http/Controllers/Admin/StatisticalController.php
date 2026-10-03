<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet_balance_history;
use App\Services\AuthorizationService;
use App\Services\ReactPageService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatisticalController extends Controller
{
    public function __construct(private readonly ReactPageService $reactPage)
    {
    }

    public function tongDoanhThu(AuthorizationService $authorization)
    {
        return $this->reactPage->admin('admin.statistics.overview', [
            'routes' => [
                'revenueData' => route('api.statistical.revenue'),
                'statusStats' => route('api.statistical.transaction.status'),
                'export' => route('api.statistical.export.revenue'),
            ],
            'permissions' => [
                'export' => $authorization->can(Auth::user(), config('authorization.capabilities.statistics_export')),
            ],
        ], 'Thống kê tổng doanh thu');
    }

    public function getRevenueData(Request $request)
    {
        try {
            $period = $request->get('period', 30);

            $startDate = $request->get('start_date')
                ? Carbon::parse($request->get('start_date'))->startOfDay()
                : now()->subDays(max((int) $period - 1, 0))->startOfDay();

            $endDate = $request->get('end_date')
                ? Carbon::parse($request->get('end_date'))->endOfDay()
                : now(); // Mặc định 30 ngày


            // Lấy dữ liệu tổng quan
            $summary = $this->getSummaryData($startDate, $endDate);

            // Lấy dữ liệu biểu đồ
            $chartData = $this->getChartData($startDate, $endDate, $period);

            // Lấy giao dịch gần đây
            $recentTransactions = $this->getRecentTransactions($startDate, $endDate);

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'chart_data' => $chartData,
                'recent_transactions' => $recentTransactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy dữ liệu tổng quan
     */
    private function getSummaryData($startDate, $endDate)
    {
        // Tổng nạp tiền (completed)
        $totalDeposit = (float) $this->scopedWalletHistories()->where('type', 'deposit')
            ->where('transaction_type', 'normal')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('value');

        // Tổng rút tiền (completed)
        $totalWithdraw = (float) $this->scopedWalletHistories()->where('type', 'withdraw')
            ->where('transaction_type', 'normal')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('value');

        // Tổng doanh thu ròng (nạp tiền - rút tiền)
        $totalRevenue = $totalDeposit - $totalWithdraw;

        // Số giao dịch nạp / rút
        $depositCount = $this->scopedWalletHistories()->where('type', 'deposit')
            ->where('transaction_type', 'normal')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $withdrawCount = $this->scopedWalletHistories()->where('type', 'withdraw')
            ->where('transaction_type', 'normal')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Tổng số giao dịch tất cả trạng thái
        $totalTransactions = $this->scopedWalletHistories()->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('transaction_type', 'normal')
            ->count();

        // Số khách hàng thực hiện nạp tiền
        $uniqueCustomers = $this->scopedWalletHistories()->where('type', 'deposit')
            ->where('status', 'completed')
            ->where('transaction_type', 'normal')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count('user_id');

        // Tính kỳ trước để so sánh trend
        $diffInDays = max(
            1,
            $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()) + 1
        );
        $prevEndDate = $startDate->copy()->subSecond();
        $prevStartDate = $startDate->copy()->subDays($diffInDays);

        $prevDeposit = (float) $this->scopedWalletHistories()->where('type', 'deposit')
            ->where('transaction_type', 'normal')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('value');

        $prevWithdraw = (float) $this->scopedWalletHistories()->where('type', 'withdraw')
            ->where('transaction_type', 'normal')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('value');

        $prevRevenue = $prevDeposit - $prevWithdraw;

        $calcGrowth = function ($current, $prev) {
            if ($prev == 0.0) {
                return $current > 0 ? 100.0 : 0.0;
            }
            return round((($current - $prev) / abs($prev)) * 100, 1);
        };

        $revenueGrowth = $calcGrowth($totalRevenue, $prevRevenue);
        $depositGrowth = $calcGrowth($totalDeposit, $prevDeposit);
        $withdrawGrowth = $calcGrowth($totalWithdraw, $prevWithdraw);

        // Giá trị nạp trung bình mỗi lệnh nạp
        $avgDeposit = $depositCount > 0 ? round($totalDeposit / $depositCount, 2) : 0;

        return [
            'total_revenue' => $totalRevenue,
            'revenue_growth' => $revenueGrowth,
            'total_deposit' => $totalDeposit,
            'deposit_growth' => $depositGrowth,
            'deposit_count' => $depositCount,
            'total_withdraw' => $totalWithdraw,
            'withdraw_growth' => $withdrawGrowth,
            'withdraw_count' => $withdrawCount,
            'total_transactions' => $totalTransactions,
            'unique_customers' => $uniqueCustomers,
            'avg_deposit' => $avgDeposit,
            'previous_period' => [
                'revenue' => $prevRevenue,
                'deposit' => $prevDeposit,
                'withdraw' => $prevWithdraw
            ]
        ];
    }

    /**
     * Lấy dữ liệu biểu đồ
     */
    private function getChartData($startDate, $endDate, $period)
    {
        // Xác định format ngày dựa trên khoảng thời gian
        $dateFormat = $period <= 30 ? '%Y-%m-%d' : '%Y-%m';
        $groupBy = $period <= 30 ? 'DATE(created_at)' : 'DATE_FORMAT(created_at, "%Y-%m")';

        // Lấy dữ liệu theo ngày/tháng
        $data = $this->scopedWalletHistories()->select(
            DB::raw($groupBy . ' as date'),
            DB::raw('SUM(CASE WHEN type = "deposit" AND status = "completed" THEN value ELSE 0 END) as deposit_amount'),
            DB::raw('SUM(CASE WHEN type = "withdraw" AND status = "completed" THEN value ELSE 0 END) as withdraw_amount')
        )
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('transaction_type', 'normal')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw($groupBy))
            ->orderBy('date')
            ->get();

        $labels = [];
        $depositData = [];
        $withdrawData = [];
        $revenueData = [];

        foreach ($data as $item) {
            $labels[] = $period <= 30 ?
                Carbon::parse($item->date)->format('d/m') :
                Carbon::parse($item->date . '-01')->format('m/Y');

            $depositData[] = $item->deposit_amount;
            $withdrawData[] = $item->withdraw_amount;
            $revenueData[] = $item->deposit_amount - $item->withdraw_amount;
        }

        // Nếu không có dữ liệu, tạo labels mặc định
        if (empty($labels)) {
            $labels = $this->generateDefaultLabels($period);
            $depositData = array_fill(0, count($labels), 0);
            $withdrawData = array_fill(0, count($labels), 0);
            $revenueData = array_fill(0, count($labels), 0);
        }

        return [
            'labels' => $labels,
            'deposit_data' => $depositData,
            'withdraw_data' => $withdrawData,
            'revenue_data' => $revenueData
        ];
    }

    /**
     * Tạo labels mặc định khi không có dữ liệu
     */
    private function generateDefaultLabels($period)
    {
        $labels = [];
        $now = Carbon::now();

        if ($period <= 30) {
            // Hiển thị theo ngày
            for ($i = $period - 1; $i >= 0; $i--) {
                $labels[] = $now->copy()->subDays($i)->format('d/m');
            }
        } else {
            // Hiển thị theo tháng
            $months = min(12, ceil($period / 30));
            for ($i = $months - 1; $i >= 0; $i--) {
                $labels[] = $now->copy()->subMonths($i)->format('m/Y');
            }
        }

        return $labels;
    }

    /**
     * Lấy giao dịch gần đây
     */
    private function getRecentTransactions($startDate, $endDate)
    {
        return $this->scopedWalletHistories()->with('user:id,full_name,username,phone,avatar')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('transaction_type', 'normal')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'user' => [
                        'id' => $transaction->user->id,
                        'full_name' => $transaction->user->full_name,
                        'username' => $transaction->user->username,
                        'avatar_url' => get_user_avatar($transaction->user),
                        'phone' => $transaction->user->phone
                    ],
                    'type' => $transaction->type,
                    'value' => $transaction->value,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at->toISOString()
                ];
            });
    }

    /**
     * Lấy thống kê chi tiết theo người dùng
     */
    public function getUserRevenueStats(Request $request)
    {
        try {
            $period = $request->get('period', 30);

            $startDate = $request->get('start_date')
                ? Carbon::parse($request->get('start_date'))->startOfDay()
                : now()->subDays(max((int) $period - 1, 0))->startOfDay();

            $endDate = $request->get('end_date')
                ? Carbon::parse($request->get('end_date'))->endOfDay()
                : now();

            $userStats = User::query()
                ->visibleCustomersTo(Auth::user())
                ->select(
                'users.id',
                'users.full_name',
                'users.phone',
                'users.username',
                'users.avatar',
                DB::raw('SUM(CASE WHEN wbh.type = "deposit" AND wbh.status = "completed" THEN wbh.value ELSE 0 END) as total_deposit'),
                DB::raw('SUM(CASE WHEN wbh.type = "withdraw" AND wbh.status = "completed" THEN wbh.value ELSE 0 END) as total_withdraw'),
                DB::raw('COUNT(wbh.id) as total_transactions')
            )
                ->leftJoin('wallet_balance_histories as wbh', function ($join) use ($startDate, $endDate) {
                    $join->on('users.id', '=', 'wbh.user_id')
                        ->whereBetween('wbh.created_at', [$startDate, $endDate])
                        ->where('wbh.transaction_type', 'normal');
                })
                ->where('users.clone_account', 0)
                ->groupBy('users.id', 'users.full_name', 'users.phone', 'users.username', 'users.avatar')
                ->having('total_transactions', '>', 0)
                ->orderBy('total_deposit', 'desc')
                ->paginate(20);

            $userStats->getCollection()->each(fn ($user) => $user->setAttribute('avatar_url', get_user_avatar($user)));

            return response()->json([
                'success' => true,
                'data' => $userStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy thống kê theo trạng thái giao dịch
     */
    public function getTransactionStatusStats(Request $request)
    {
        try {
            $period = $request->get('period', 30);

            $startDate = $request->get('start_date')
                ? Carbon::parse($request->get('start_date'))->startOfDay()
                : now()->subDays(max((int) $period - 1, 0))->startOfDay();

            $endDate = $request->get('end_date')
                ? Carbon::parse($request->get('end_date'))->endOfDay()
                : now();

            $statusStats = $this->scopedWalletHistories()->select(
                'status',
                'type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(value) as total_amount')
            )
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('transaction_type', 'normal')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('status', 'type')
                ->get()
                ->groupBy('status');

            return response()->json([
                'success' => true,
                'data' => $statusStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export dữ liệu thống kê ra Excel
     */
    public function exportRevenueData(Request $request)
    {
        try {
            $period = $request->get('period', 30);

            $startDate = $request->get('start_date')
                ? Carbon::parse($request->get('start_date'))->startOfDay()
                : now()->subDays(max((int) $period - 1, 0))->startOfDay();

            $endDate = $request->get('end_date')
                ? Carbon::parse($request->get('end_date'))->endOfDay()
                : now();


            $transactions = $this->scopedWalletHistories()->with('user:id,full_name,phone')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('transaction_type', 'normal')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->get();

            $fileName = 'thong_ke_doanh_thu_' . Carbon::now()->format('Y_m_d_H_i_s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ];

            $callback = function () use ($transactions) {
                $file = fopen('php://output', 'w');

                // UTF-8 BOM
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                // Header
                fputcsv($file, [
                    'ID',
                    'Người dùng',
                    'Số điện thoại',
                    'Loại giao dịch',
                    'Số tiền',
                    'Số dư ban đầu',
                    'Trạng thái',
                    'Ngày tạo'
                ]);

                // Data
                foreach ($transactions as $transaction) {
                    fputcsv($file, [
                        $transaction->id,
                        $transaction->user->full_name,
                        $transaction->user->phone,
                        $transaction->type === 'deposit' ? 'Nạp tiền' : 'Rút tiền',
                        number_format($transaction->value, 0, ',', '.'),
                        number_format($transaction->initial_balance, 0, ',', '.'),
                        $transaction->status === 'completed' ? 'Hoàn thành' : ($transaction->status === 'processing' ? 'Đang xử lý' : 'Đã hủy'),
                        $transaction->created_at->format('d/m/Y H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function doanhThuTheoNhanVien(AuthorizationService $authorization)
    {
        return $this->reactPage->admin('admin.statistics.staff', [
            'routes' => [
                'staffList' => route('api.staff.list'),
                'revenueByStaff' => route('api.revenue.by.staff'),
                'detail' => route('api.revenue.detail'),
                'chart' => route('admin.revenue.chart'),
                'export' => route('admin.revenue.export'),
            ],
            'permissions' => [
                'export' => $authorization->can(Auth::user(), config('authorization.capabilities.statistics_export')),
            ],
        ], 'Thống kê doanh thu theo nhân viên');
    }
    public function getStaffList()
    {
        try {
            $staffList = $this->visibleRevenueStaffQuery(Auth::user())
                ->select('id', 'full_name', 'email', 'phone')
                ->orderBy('full_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $staffList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage()
            ], 500);
        }
    }

    private function buildStaffRevenueTableData(Carbon $dateFrom, Carbon $dateTo, ?int $staffId = null): array
    {
        $actor = Auth::user();
        $staffQuery = $this->visibleRevenueStaffQuery($actor)
            ->select('id', 'full_name', 'email', 'phone');

        if ($staffId) {
            $staffQuery->whereKey($staffId);
        }

        $staffList = $staffQuery->get();
        $staffIds = $staffList->pluck('id')->map(fn ($id) => (int) $id)->values();

        $aggregates = collect();
        if ($staffIds->isNotEmpty()) {
            $aggregateQuery = DB::table('wallet_balance_histories as wbh')
                ->join('users as customers', 'wbh.user_id', '=', 'customers.id')
                ->where('customers.clone_account', 0)
                ->where('customers.role', User::ROLE_MEMBER)
                ->where('wbh.type', 'deposit')
                ->where('wbh.status', 'completed')
                ->where('wbh.transaction_type', 'normal')
                ->whereBetween('wbh.created_at', [$dateFrom, $dateTo])
                ->where(function ($query) use ($staffIds) {
                    $query->whereIn('wbh.assigned_staff_id', $staffIds)
                        ->orWhere(function ($legacyQuery) use ($staffIds) {
                            $legacyQuery->whereNull('wbh.assigned_staff_id')
                                ->whereIn('customers.referrer_id', $staffIds);
                        });
                });
            $this->scopeJoinedCustomers($aggregateQuery, $actor, 'customers');
            $aggregates = $aggregateQuery
                ->selectRaw('COALESCE(wbh.assigned_staff_id, customers.referrer_id) as staff_id')
                ->selectRaw('COUNT(wbh.id) as total_transactions')
                ->selectRaw('COUNT(DISTINCT wbh.user_id) as invited_users')
                ->selectRaw('SUM(wbh.value) as total_revenue')
                ->selectRaw('SUM(CASE WHEN wbh.assigned_staff_id IS NULL THEN 1 ELSE 0 END) as legacy_transactions')
                ->selectRaw('SUM(CASE WHEN wbh.assigned_staff_id IS NULL THEN wbh.value ELSE 0 END) as legacy_revenue')
                ->groupByRaw('COALESCE(wbh.assigned_staff_id, customers.referrer_id)')
                ->get()
                ->keyBy(fn ($row) => (int) $row->staff_id);
        }

        $legacyTransactions = 0;
        $legacyRevenue = 0.0;
        $tableData = $staffList->map(function (User $staff) use ($aggregates, &$legacyTransactions, &$legacyRevenue) {
            $aggregate = $aggregates->get((int) $staff->id);
            $staffLegacyTransactions = (int) ($aggregate->legacy_transactions ?? 0);
            $staffLegacyRevenue = (float) ($aggregate->legacy_revenue ?? 0);

            $legacyTransactions += $staffLegacyTransactions;
            $legacyRevenue += $staffLegacyRevenue;

            return [
                'staff_id' => (int) $staff->id,
                'staff_name' => $staff->full_name,
                'staff_email' => $staff->email,
                'staff_phone' => $staff->phone ?? '',
                'invited_users' => (int) ($aggregate->invited_users ?? 0),
                'total_transactions' => (int) ($aggregate->total_transactions ?? 0),
                'total_revenue' => (float) ($aggregate->total_revenue ?? 0),
                'legacy_transactions' => $staffLegacyTransactions,
                'legacy_revenue' => $staffLegacyRevenue,
            ];
        })->all();

        return [$tableData, $legacyTransactions, $legacyRevenue];
    }

    private function attributedTransactions(int $staffId, ?User $visibilityActor = null)
    {
        $query = Wallet_balance_history::query()
            ->where(function ($query) use ($staffId) {
                $query->where('assigned_staff_id', $staffId)
                    ->orWhere(function ($legacyQuery) use ($staffId) {
                        $legacyQuery->whereNull('assigned_staff_id')
                            ->whereHas('user', function ($userQuery) use ($staffId) {
                                $userQuery->where('referrer_id', $staffId);
                            });
                    });
            })
            ->whereHas('user', function ($query) {
                $query->where('role', User::ROLE_MEMBER)
                    ->where('clone_account', 0);
            });

        if ($visibilityActor && $visibilityActor->role !== User::ROLE_OWNER) {
            $query->whereHas('user', fn ($userQuery) => $userQuery->visibleCustomersTo($visibilityActor));
        }

        return $query;
    }

    /**
     * API lấy dữ liệu doanh thu theo nhân viên
     */
    public function getRevenueByStaff(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
            $staffId = $request->get('staff_id');

            // Validate dates
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
            $dateTo = Carbon::parse($dateTo)->endOfDay();

            [$tableData, $legacyTransactions, $legacyRevenue] = $this->buildStaffRevenueTableData(
                $dateFrom,
                $dateTo,
                $staffId ? (int) $staffId : null
            );

            $totalRevenue = array_sum(array_column($tableData, 'total_revenue'));
            $totalTransactions = array_sum(array_column($tableData, 'total_transactions'));

            // Sắp xếp theo doanh thu giảm dần
            usort($tableData, function ($a, $b) {
                return $b['total_revenue'] <=> $a['total_revenue'];
            });

            // Gắn thứ hạng và tỷ trọng đóng góp (%)
            foreach ($tableData as $index => &$item) {
                $item['rank'] = $index + 1;
                $item['percent_share'] = $totalRevenue > 0 ? round(($item['total_revenue'] / $totalRevenue) * 100, 1) : 0;
            }
            unset($item);

            // Lấy top 5 để hiển thị chart
            $topStaff = array_slice($tableData, 0, 5);
            $topLabels = array_column($topStaff, 'staff_name');
            $topData = array_column($topStaff, 'total_revenue');

            // Tính toán summary
            $summary = [
                'total_staff' => count($tableData),
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
                'active_staff' => count(array_filter(
                    $tableData,
                    fn ($item) => $item['total_revenue'] > 0
                )),
                'legacy_transactions' => $legacyTransactions,
                'legacy_revenue' => $legacyRevenue,
            ];

            // Dữ liệu cho biểu đồ
            $chartData = [
                'labels' => array_column($tableData, 'staff_name'),
                'revenue_data' => array_column($tableData, 'total_revenue'),
                'top_labels' => $topLabels,
                'top_data' => $topData
            ];

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'chart_data' => $chartData,
                'table_data' => $tableData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy dữ liệu doanh thu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API lấy chi tiết doanh thu của một nhân viên
     */
    public function getRevenueDetail(Request $request)
    {
        try {
            $staffId = $request->get('staff_id');
            $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

            if (!$staffId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn nhân viên'
                ], 400);
            }

            // Validate dates
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
            $dateTo = Carbon::parse($dateTo)->endOfDay();

            // Lấy thông tin nhân viên
            $actor = Auth::user();
            $staff = $this->visibleRevenueStaffQuery($actor)
                ->where('id', $staffId)
                ->first();

            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy nhân viên'
                ], 404);
            }

            $transactions = $this->attributedTransactions((int) $staffId, $actor)
                ->where('type', 'deposit')
                ->where('status', 'completed')
                ->where('transaction_type', 'normal')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->with('user:id,full_name,username,email,avatar')
                ->orderBy('created_at', 'desc')
                ->get();

            // Tính toán thống kê
            $transactions->each(fn ($transaction) => $transaction->user?->setAttribute('avatar_url', get_user_avatar($transaction->user)));
            $statistics = [
                'invited_users' => $transactions->pluck('user_id')->unique()->count(),
                'total_transactions' => $transactions->count(),
                'total_revenue' => $transactions->sum('value'),
                'legacy_transactions' => $transactions->whereNull('assigned_staff_id')->count(),
                'legacy_revenue' => $transactions->whereNull('assigned_staff_id')->sum('value'),
            ];

            return response()->json([
                'success' => true,
                'staff' => [
                    'id' => $staff->id,
                    'full_name' => $staff->full_name,
                    'email' => $staff->email,
                    'phone' => $staff->phone
                ],
                'statistics' => $statistics,
                'transactions' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết doanh thu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API lấy biểu đồ doanh thu theo thời gian
     */
    public function getRevenueChart(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
            $staffId = $request->get('staff_id');

            // Validate dates
            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
            $dateTo = Carbon::parse($dateTo)->endOfDay();

            // Tạo query
            $query = DB::table('wallet_balance_histories as wbh')
                ->join('users as customers', 'wbh.user_id', '=', 'customers.id')
                ->join('users as staff', 'staff.id', '=', DB::raw('COALESCE(wbh.assigned_staff_id, customers.referrer_id)'))
                ->where('customers.clone_account', 0)
                ->where('customers.role', User::ROLE_MEMBER)
                ->where('wbh.type', 'deposit')
                ->where('wbh.status', 'completed')
                ->where('wbh.transaction_type', 'normal')
                ->where('staff.role', User::ROLE_STAFF)
                ->whereBetween('wbh.created_at', [$dateFrom, $dateTo])
                ->select(
                    DB::raw('DATE(wbh.created_at) as date'),
                    DB::raw('SUM(wbh.value) as total_revenue'),
                    DB::raw('COUNT(wbh.id) as total_transactions')
                );

            $actor = Auth::user();
            $this->scopeJoinedCustomers($query, $actor, 'customers');
            $visibleStaffIds = $this->visibleRevenueStaffQuery($actor)->pluck('id');
            $query->whereIn('staff.id', $visibleStaffIds);

            if ($staffId) {
                $query->where('staff.id', $staffId);
            }

            $chartData = $query->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'success' => true,
                'chart_data' => $chartData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy dữ liệu biểu đồ: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function doanhThuTuKhachHang()
    {
        return $this->reactPage->admin('admin.statistics.customers', [
            'routes' => [
                'overview' => route('api.revenue.overview'),
                'chart' => route('api.revenue.chart'),
                'topCustomers' => route('api.revenue.top-customers'),
                'distribution' => route('api.revenue.distribution'),
                'customerDetail' => route('api.revenue.customer-detail'),
            ],
        ], 'Thống kê doanh thu từ khách hàng');
    }

    /**
     * API: Lấy tổng quan doanh thu
     */
    public function revenueOverview(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            // Truy vấn doanh thu từ giao dịch nạp tiền đã hoàn thành
            $revenueData = $this->scopedWalletHistories()->where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('status', 'completed')
                ->where('transaction_type', 'normal')
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    DB::raw('SUM(value) as total_revenue'),
                    DB::raw('COUNT(*) as total_transactions'),
                    DB::raw('COUNT(DISTINCT user_id) as total_customers'),
                    DB::raw('AVG(value) as avg_transaction')
                )
                ->first();

            $currentRevenue = (float) ($revenueData->total_revenue ?? 0);
            $currentCustomers = (int) ($revenueData->total_customers ?? 0);

            // Tính kỳ trước
            $startDateObj = Carbon::parse($startDate)->startOfDay();
            $endDateObj = Carbon::parse($endDate)->endOfDay();
            $diffInDays = max(
                1,
                $startDateObj->copy()->startOfDay()->diffInDays($endDateObj->copy()->startOfDay()) + 1
            );
            $prevEndDate = $startDateObj->copy()->subSecond();
            $prevStartDate = $startDateObj->copy()->subDays($diffInDays);

            $prevRevenueData = $this->scopedWalletHistories()->where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('status', 'completed')
                ->where('transaction_type', 'normal')
                ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
                ->select(
                    DB::raw('SUM(value) as total_revenue'),
                    DB::raw('COUNT(DISTINCT user_id) as total_customers')
                )
                ->first();

            $prevRevenue = (float) ($prevRevenueData->total_revenue ?? 0);
            $prevCustomers = (int) ($prevRevenueData->total_customers ?? 0);

            $revenueGrowth = $prevRevenue == 0.0 ? ($currentRevenue > 0 ? 100.0 : 0.0) : round((($currentRevenue - $prevRevenue) / abs($prevRevenue)) * 100, 1);
            $customersGrowth = $prevCustomers == 0 ? ($currentCustomers > 0 ? 100.0 : 0.0) : round((($currentCustomers - $prevCustomers) / $prevCustomers) * 100, 1);

            // Khách hàng nạp cao nhất
            $topCustomer = $this->scopedWalletHistories()->join('users', 'wallet_balance_histories.user_id', '=', 'users.id')
                ->where('users.clone_account', 0)
                ->where('wallet_balance_histories.type', 'deposit')
                ->where('wallet_balance_histories.status', 'completed')
                ->where('wallet_balance_histories.transaction_type', 'normal')
                ->whereBetween('wallet_balance_histories.created_at', [$startDateObj, $endDateObj])
                ->select('users.id as user_id', 'users.full_name', 'users.username', 'users.avatar', DB::raw('SUM(wallet_balance_histories.value) as total_spent'))
                ->groupBy('users.id', 'users.full_name', 'users.username', 'users.avatar')
                ->orderBy('total_spent', 'desc')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_revenue' => $currentRevenue,
                    'revenue_growth' => $revenueGrowth,
                    'total_transactions' => $revenueData->total_transactions ?? 0,
                    'total_customers' => $currentCustomers,
                    'customers_growth' => $customersGrowth,
                    'avg_transaction' => (float) ($revenueData->avg_transaction ?? 0),
                    'top_customer_name' => $topCustomer ? $topCustomer->full_name : 'Chưa có',
                    'top_customer' => $topCustomer ? $this->revenueCustomerIdentity($topCustomer) : null,
                    'top_customer_amount' => $topCustomer ? (float) $topCustomer->total_spent : 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải dữ liệu tổng quan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy dữ liệu biểu đồ doanh thu theo thời gian
     */
    public function revenueChart(Request $request)
    {
        try {
            $type = $request->get('type', 'daily');
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            // Xác định format ngày và group by theo loại thống kê
            $dateFormat = $this->getDateFormat($type);
            $groupBy = $this->getGroupBy($type);

            $revenueData = $this->scopedWalletHistories()->where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('status', 'completed')
                ->where('transaction_type', 'normal')
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '$dateFormat') as date_label"),
                    DB::raw('SUM(value) as total_revenue')
                )
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '$dateFormat')"))
                ->orderBy('date_label')
                ->get();

            $labels = $revenueData->pluck('date_label')->toArray();
            $values = $revenueData->pluck('total_revenue')->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'values' => $values
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải biểu đồ doanh thu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy top khách hàng theo doanh thu
     */
    public function topCustomers(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
            $limit = $request->get('limit', 10);

            $topCustomers = $this->scopedWalletHistories()->join('users', 'wallet_balance_histories.user_id', '=', 'users.id')
                ->where('users.clone_account', 0)
                ->where('wallet_balance_histories.type', 'deposit')
                ->where('wallet_balance_histories.status', 'completed')
                ->where('wallet_balance_histories.transaction_type', 'normal')
                ->whereBetween('wallet_balance_histories.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    'users.id as user_id',
                    'users.full_name',
                    'users.username',
                    'users.avatar',
                    DB::raw('SUM(wallet_balance_histories.value) as total_revenue')
                )
                ->groupBy('users.id', 'users.full_name', 'users.username', 'users.avatar')
                ->orderBy('total_revenue', 'desc')
                ->limit($limit)
                ->get();

            $labels = $topCustomers->pluck('full_name')->toArray();
            $values = $topCustomers->pluck('total_revenue')->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'values' => $values,
                    'customers' => $topCustomers->map(fn ($customer) => [
                        ...$this->revenueCustomerIdentity($customer),
                        'total_revenue' => (float) $customer->total_revenue,
                    ]),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải top khách hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy phân bố doanh thu theo khách hàng
     */
    public function revenueDistribution(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            // Lấy top 5 khách hàng và nhóm còn lại
            $topCustomers = $this->scopedWalletHistories()->join('users', 'wallet_balance_histories.user_id', '=', 'users.id')
                ->where('users.clone_account', 0)
                ->where('wallet_balance_histories.type', 'deposit')
                ->where('wallet_balance_histories.status', 'completed')
                ->where('wallet_balance_histories.transaction_type', 'normal')
                ->whereBetween('wallet_balance_histories.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    'users.id as user_id',
                    'users.full_name',
                    'users.username',
                    'users.avatar',
                    DB::raw('SUM(wallet_balance_histories.value) as total_revenue')
                )
                ->groupBy('users.id', 'users.full_name', 'users.username', 'users.avatar')
                ->orderBy('total_revenue', 'desc')
                ->limit(5)
                ->get();

            $topRevenue = $topCustomers->sum('total_revenue');

            // Tổng doanh thu
            $totalRevenue = $this->scopedWalletHistories()->where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('transaction_type', 'normal')
                ->where('status', 'completed')
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->sum('value');

            $labels = $topCustomers->pluck('full_name')->toArray();
            $values = $topCustomers->pluck('total_revenue')->toArray();

            // Thêm phần "Khác" nếu có
            if ($totalRevenue > $topRevenue) {
                $labels[] = 'Khác';
                $values[] = $totalRevenue - $topRevenue;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'values' => $values,
                    'customers' => $topCustomers->map(fn ($customer) => [
                        ...$this->revenueCustomerIdentity($customer),
                        'total_revenue' => (float) $customer->total_revenue,
                    ]),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải phân bố doanh thu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy chi tiết doanh thu theo khách hàng
     */
    public function customerRevenueDetail(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            $customerRevenue = $this->scopedWalletHistories()->join('users', 'wallet_balance_histories.user_id', '=', 'users.id')
                ->where('users.clone_account', 0)
                ->where('wallet_balance_histories.type', 'deposit')
                ->where('wallet_balance_histories.status', 'completed')
                ->where('wallet_balance_histories.transaction_type', 'normal')
                ->whereBetween('wallet_balance_histories.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    'users.id as user_id',
                    'users.full_name',
                    'users.username',
                    'users.avatar',
                    'users.phone',
                    DB::raw('COUNT(wallet_balance_histories.id) as transaction_count'),
                    DB::raw('SUM(wallet_balance_histories.value) as total_revenue'),
                    DB::raw('MAX(wallet_balance_histories.created_at) as last_transaction')
                )
                ->groupBy('users.id', 'users.full_name', 'users.username', 'users.avatar', 'users.phone')
                ->orderBy('total_revenue', 'desc')
                ->get();

            $customerRevenue->each(fn ($customer) => $customer->setAttribute('avatar_url', get_user_avatar($customer)));
            return response()->json([
                'success' => true,
                'data' => $customerRevenue
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải chi tiết khách hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xuất báo cáo CSV doanh thu theo nhân viên
     */
    public function exportRevenue(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));
            $staffId = $request->get('staff_id');

            $dateFromParsed = Carbon::parse($dateFrom)->startOfDay();
            $dateToParsed = Carbon::parse($dateTo)->endOfDay();

            [$tableData] = $this->buildStaffRevenueTableData(
                $dateFromParsed,
                $dateToParsed,
                $staffId ? (int) $staffId : null
            );
            $totalAllRevenue = array_sum(array_column($tableData, 'total_revenue'));

            usort($tableData, function ($a, $b) {
                return $b['total_revenue'] <=> $a['total_revenue'];
            });

            $fileName = 'doanh_thu_nhan_vien_' . Carbon::now()->format('Y_m_d_H_i_s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ];

            $callback = function () use ($tableData, $totalAllRevenue) {
                $file = fopen('php://output', 'w');
                // UTF-8 BOM
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

                fputcsv($file, [
                    'Thứ hạng',
                    'Nhân viên',
                    'Email',
                    'Số điện thoại',
                    'Số khách mời',
                    'Tổng giao dịch',
                    'Doanh thu (USD)',
                    'Tỷ trọng (%)'
                ]);

                foreach ($tableData as $index => $item) {
                    $share = $totalAllRevenue > 0 ? round(($item['total_revenue'] / $totalAllRevenue) * 100, 1) : 0;
                    fputcsv($file, [
                        $index + 1,
                        $item['staff_name'],
                        $item['staff_email'],
                        $item['staff_phone'],
                        $item['invited_users'],
                        $item['total_transactions'],
                        number_format($item['total_revenue'], 2, '.', ','),
                        $share . '%'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xuất báo cáo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Lấy định dạng ngày theo loại thống kê
     */
    private function getDateFormat($type)
    {
        switch ($type) {
            case 'daily':
                return '%Y-%m-%d';
            case 'monthly':
                return '%Y-%m';
            case 'yearly':
                return '%Y';
            default:
                return '%Y-%m-%d';
        }
    }

    /**
     * Helper: Lấy group by theo loại thống kê
     */
    private function getGroupBy($type)
    {
        switch ($type) {
            case 'daily':
                return 'DATE(created_at)';
            case 'monthly':
                return 'YEAR(created_at), MONTH(created_at)';
            case 'yearly':
                return 'YEAR(created_at)';
            default:
                return 'DATE(created_at)';
        }
    }

    /**
     * API: Lấy thống kê theo nhân viên xác nhận giao dịch
     */
    public function staffRevenue(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            [$tableData] = $this->buildStaffRevenueTableData(
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            );

            $staffRevenue = collect($tableData)
                ->filter(fn ($row) => $row['total_transactions'] > 0)
                ->sortByDesc('total_revenue')
                ->values()
                ->map(fn ($row) => [
                    'staff_name' => $row['staff_name'],
                    'staff_email' => $row['staff_email'],
                    'transaction_count' => $row['total_transactions'],
                    'total_revenue' => $row['total_revenue'],
                    'unique_customers' => $row['invited_users'],
                ]);

            return response()->json([
                'success' => true,
                'data' => $staffRevenue
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải thống kê nhân viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy thống kê theo phương thức thanh toán
     */
    public function paymentMethodStats(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            $paymentStats = $this->scopedWalletHistories()->where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('transaction_type', 'normal')
                ->where('status', 'completed')
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    'bank_name',
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(value) as total_revenue')
                )
                ->groupBy('bank_name')
                ->orderBy('total_revenue', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $paymentStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải thống kê phương thức thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy thống kê theo trạng thái giao dịch
     */
    public function transactionStatusStats(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            $statusStats = $this->scopedWalletHistories()->where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->where('transaction_type', 'normal')
                ->select(
                    'status',
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(value) as total_amount')
                )
                ->groupBy('status')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $statusStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải thống kê trạng thái: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy thống kê theo khoảng giá trị giao dịch
     */
    public function transactionRangeStats(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            $rangeStats = $this->scopedWalletHistories()->where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('status', 'completed')
                ->where('transaction_type', 'normal')
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    DB::raw('CASE
                        WHEN value < 100000 THEN "Dưới 100k"
                        WHEN value < 500000 THEN "100k - 500k"
                        WHEN value < 1000000 THEN "500k - 1M"
                        WHEN value < 5000000 THEN "1M - 5M"
                        WHEN value < 10000000 THEN "5M - 10M"
                        ELSE "Trên 10M"
                    END as range_label'),
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(value) as total_amount')
                )
                ->groupBy('range_label')
                ->orderBy('total_amount', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $rangeStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải thống kê khoảng giá trị: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy thống kê theo giờ trong ngày
     */
    public function hourlyStats(Request $request)
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

            $hourlyStats = $this->scopedWalletHistories()->where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('status', 'completed')
                ->where('transaction_type', 'normal')
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    DB::raw('HOUR(created_at) as hour'),
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(value) as total_amount')
                )
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $hourlyStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải thống kê theo giờ: ' . $e->getMessage()
            ], 500);
        }
    }

    private function scopedWalletHistories(?User $actor = null)
    {
        $actor ??= Auth::user();
        $query = Wallet_balance_history::query();

        if (!$actor || !in_array($actor->role, User::MANAGEMENT_ROLES, true) || $actor->role === User::ROLE_OWNER) {
            return $query;
        }

        return $query->whereHas(
            'user',
            fn ($userQuery) => $userQuery->visibleCustomersTo($actor)
        );
    }

    private function visibleRevenueStaffQuery(?User $actor)
    {
        $query = User::query()->where('role', User::ROLE_STAFF);

        if (!$actor || !in_array($actor->role, User::MANAGEMENT_ROLES, true) || $actor->role === User::ROLE_OWNER) {
            return $query;
        }

        if ($actor->role === User::ROLE_ADMIN) {
            return $query->where('referrer_id', $actor->id);
        }

        if ($actor->role === User::ROLE_STAFF) {
            return $query->whereKey($actor->id);
        }

        return $query;
    }

    private function scopeJoinedCustomers($query, ?User $actor, string $alias = 'customers')
    {
        if (!$actor || !in_array($actor->role, User::MANAGEMENT_ROLES, true) || $actor->role === User::ROLE_OWNER) {
            return $query;
        }

        $managerIds = $actor->customerManagerIds();
        if (!$managerIds) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where($alias . '.role', User::ROLE_MEMBER)
            ->whereIn($alias . '.referrer_id', $managerIds);
    }

    public function doanhThuBanThan()
    {
        return $this->reactPage->admin('admin.statistics.personal', [
            'routes' => [
                'stats' => route('admin.personal.revenue.stats'),
                'transactions' => route('admin.personal.transactions'),
            ],
        ], 'Thống kê doanh thu cá nhân');
    }

    /**
     * API lấy dữ liệu thống kê doanh thu cá nhân
     */
    public function getPersonalRevenueStats(Request $request)
    {
        $userId = Auth::id();
        $timeRange = $request->get('time_range', '7_days'); // 7_days, 30_days, 3_months, 6_months, 1_year

        // Xác định khoảng thời gian
        $startDate = $this->getStartDate($timeRange);
        $endDate = now();

        // Lấy dữ liệu doanh thu theo ngày
        $dailyRevenue = $this->getDailyRevenue($userId, $startDate, $endDate);

        // Lấy dữ liệu thống kê tổng quan
        $overviewStats = $this->getOverviewStats($userId, $startDate, $endDate);

        // Lấy dữ liệu theo loại giao dịch
        $transactionTypeStats = $this->getTransactionTypeStats($userId, $startDate, $endDate);

        // Lấy dữ liệu theo tháng (nếu khoảng thời gian > 30 ngày)
        $monthlyRevenue = [];
        if (in_array($timeRange, ['3_months', '6_months', '1_year'])) {
            $monthlyRevenue = $this->getMonthlyRevenue($userId, $startDate, $endDate);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'daily_revenue' => $dailyRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'overview_stats' => $overviewStats,
                'transaction_type_stats' => $transactionTypeStats,
                'time_range' => $timeRange,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ]
        ]);
    }

    private function getStartDate($timeRange)
    {
        switch ($timeRange) {
            case '7_days':
                return now()->subDays(6)->startOfDay();
            case '30_days':
                return now()->subDays(29)->startOfDay();
            case '3_months':
                return now()->subMonths(3);
            case '6_months':
                return now()->subMonths(6);
            case '1_year':
                return now()->subYear();
            default:
                return now()->subDays(6)->startOfDay();
        }
    }

    private function getDailyRevenue($userId, $startDate, $endDate)
    {
        $data = $this->attributedTransactions((int) $userId)
            ->where('status', 'completed')
            ->where('transaction_type', 'normal')
            ->where('type', 'deposit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, SUM(value) as total_revenue, COUNT(*) as transaction_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Tạo mảng đầy đủ các ngày trong khoảng thời gian
        $result = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('Y-m-d');
            $dayData = $data->where('date', $dateString)->first();

            $result[] = [
                'date' => $dateString,
                'formatted_date' => $currentDate->format('d/m'),
                'total_revenue' => $dayData ? (float)$dayData->total_revenue : 0,
                'transaction_count' => $dayData ? $dayData->transaction_count : 0
            ];

            $currentDate->addDay();
        }

        return $result;
    }

    private function getMonthlyRevenue($userId, $startDate, $endDate)
    {
        $data = $this->attributedTransactions((int) $userId)
            ->where('status', 'completed')
            ->where('transaction_type', 'normal')
            ->where('type', 'deposit')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(value) as total_revenue, COUNT(*) as transaction_count')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $result = [];
        foreach ($data as $item) {
            $result[] = [
                'year' => $item->year,
                'month' => $item->month,
                'month_name' => Carbon::create($item->year, $item->month)->format('m/Y'),
                'total_revenue' => (float)$item->total_revenue,
                'transaction_count' => $item->transaction_count
            ];
        }

        return $result;
    }

    private function getOverviewStats($userId, $startDate, $endDate)
    {
        $stats = $this->attributedTransactions((int) $userId)
            ->where('status', 'completed')
            ->where('transaction_type', 'normal')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                SUM(CASE WHEN type = "deposit" THEN value ELSE 0 END) as total_deposit,
                SUM(CASE WHEN type = "withdraw" THEN value ELSE 0 END) as total_withdraw,
                COUNT(CASE WHEN type = "deposit" THEN 1 END) as deposit_count,
                COUNT(CASE WHEN type = "withdraw" THEN 1 END) as withdraw_count,
                SUM(CASE WHEN assigned_staff_id IS NULL THEN 1 ELSE 0 END) as legacy_transactions
            ')
            ->first();

        $totalRevenue = (float)$stats->total_deposit;
        $totalWithdraw = (float)$stats->total_withdraw;
        $netRevenue = $totalRevenue - $totalWithdraw;

        // Tính toán so với kỳ trước
        $periodDays = max(1, $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()) + 1);
        $prevEndDate = $startDate->copy()->subSecond();
        $prevStartDate = $startDate->copy()->subDays($periodDays)->startOfDay();

        $prevStats = $this->attributedTransactions((int) $userId)
            ->where('status', 'completed')
            ->where('type', 'deposit')
            ->where('transaction_type', 'normal')
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('value');

        if ($prevStats == 0.0) {
            $growth = $totalRevenue > 0 ? 100 : 0;
        } else {
            $growth = (($totalRevenue - $prevStats) / $prevStats) * 100;
        }

        return [
            'total_revenue' => $totalRevenue,
            'total_withdraw' => $totalWithdraw,
            'net_revenue' => $netRevenue,
            'deposit_count' => $stats->deposit_count,
            'withdraw_count' => $stats->withdraw_count,
            'total_transactions' => $stats->deposit_count + $stats->withdraw_count,
            'legacy_transactions' => (int) ($stats->legacy_transactions ?? 0),
            'growth_rate' => round($growth, 2),
            'avg_transaction_value' => $stats->deposit_count > 0 ? round($totalRevenue / $stats->deposit_count, 2) : 0
        ];
    }

    private function getTransactionTypeStats($userId, $startDate, $endDate)
    {
        $stats = $this->attributedTransactions((int) $userId)
            ->where('status', 'completed')
            ->where('transaction_type', 'normal')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('type, SUM(value) as total_value, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        $result = [];
        foreach ($stats as $stat) {
            $result[] = [
                'type' => $stat->type,
                'type_name' => $stat->type === 'deposit' ? 'Nạp tiền' : 'Rút tiền',
                'total_value' => (float)$stat->total_value,
                'count' => $stat->count
            ];
        }

        return $result;
    }

    private function revenueCustomerIdentity($customer): array
    {
        return [
            'id' => (int) $customer->user_id,
            'full_name' => $customer->full_name,
            'username' => $customer->username,
            'avatar_url' => get_user_avatar($customer),
        ];
    }

    /**
     * API lấy danh sách giao dịch chi tiết
     */
    public function getPersonalTransactions(Request $request)
    {
        $userId = Auth::id();
        $perPage = $request->get('per_page', 10);
        $type = $request->get('type'); // deposit, withdraw
        $status = $request->get('status'); // processing, completed, cancelled

        $query = $this->attributedTransactions((int) $userId)
            ->with(['user:id,full_name,username,avatar'])
            ->where('transaction_type', 'normal')
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $transactions = $query->paginate($perPage);
        $transactions->getCollection()->each(fn ($transaction) => $transaction->user?->setAttribute('avatar_url', get_user_avatar($transaction->user)));
        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
