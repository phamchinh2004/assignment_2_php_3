<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet_balance_history;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatisticalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }
    public function tongDoanhThu()
    {
        return view('admin.statistical.tongDoanhThu');
    }

    public function getRevenueData(Request $request)
    {
        try {
            $period = $request->get('period', 30);

            $startDate = $request->get('start_date')
                ? Carbon::parse($request->get('start_date'))->startOfDay()
                : now()->subDays($period);

            $endDate = $request->get('end_date')
                ? Carbon::parse($request->get('end_date'))->endOfDay()
                : now(); // Mặc định 30 ngày


            // Lấy dữ liệu tổng quan
            $summary = $this->getSummaryData($startDate, $endDate);

            // Lấy dữ liệu biểu đồ
            $chartData = $this->getChartData($startDate, $endDate, $period);

            // Lấy giao dịch gần đây
            $recentTransactions = $this->getRecentTransactions();

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
        $totalDeposit = Wallet_balance_history::where('type', 'deposit')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('value');

        // Tổng rút tiền (completed)
        $totalWithdraw = Wallet_balance_history::where('type', 'withdraw')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('value');

        // Tổng doanh thu (nạp tiền - rút tiền)
        $totalRevenue = $totalDeposit - $totalWithdraw;

        // Tổng số giao dịch
        $totalTransactions = Wallet_balance_history::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_deposit' => $totalDeposit,
            'total_withdraw' => $totalWithdraw,
            'total_transactions' => $totalTransactions
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
        $data = Wallet_balance_history::select(
            DB::raw($groupBy . ' as date'),
            DB::raw('SUM(CASE WHEN type = "deposit" AND status = "completed" THEN value ELSE 0 END) as deposit_amount'),
            DB::raw('SUM(CASE WHEN type = "withdraw" AND status = "completed" THEN value ELSE 0 END) as withdraw_amount')
        )
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
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
    private function getRecentTransactions()
    {
        return Wallet_balance_history::with('user:id,full_name,phone')
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'user' => [
                        'id' => $transaction->user->id,
                        'full_name' => $transaction->user->full_name,
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
                : now()->subDays($period);

            $endDate = $request->get('end_date')
                ? Carbon::parse($request->get('end_date'))->endOfDay()
                : now();

            $userStats = User::select(
                'users.id',
                'users.full_name',
                'users.phone',
                DB::raw('SUM(CASE WHEN wbh.type = "deposit" AND wbh.status = "completed" THEN wbh.value ELSE 0 END) as total_deposit'),
                DB::raw('SUM(CASE WHEN wbh.type = "withdraw" AND wbh.status = "completed" THEN wbh.value ELSE 0 END) as total_withdraw'),
                DB::raw('COUNT(wbh.id) as total_transactions')
            )
                ->leftJoin('wallet_balance_histories as wbh', function ($join) use ($startDate, $endDate) {
                    $join->on('users.id', '=', 'wbh.user_id')
                        ->whereBetween('wbh.created_at', [$startDate, $endDate]);
                })
                ->where('users.clone_account', 0)
                ->groupBy('users.id', 'users.full_name', 'users.phone')
                ->having('total_transactions', '>', 0)
                ->orderBy('total_deposit', 'desc')
                ->paginate(20);

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
                : now()->subDays($period);

            $endDate = $request->get('end_date')
                ? Carbon::parse($request->get('end_date'))->endOfDay()
                : now();

            $statusStats = Wallet_balance_history::select(
                'status',
                'type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(value) as total_amount')
            )
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
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
                : now()->subDays($period);

            $endDate = $request->get('end_date')
                ? Carbon::parse($request->get('end_date'))->endOfDay()
                : now();


            $transactions = Wallet_balance_history::with('user:id,full_name,phone')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
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

    public function doanhThuTheoNhanVien()
    {
        return view('admin.statistical.doanhThuTheoNhanVien');
    }
    public function getStaffList()
    {
        try {
            $staffList = User::where('role', User::ROLE_STAFF)
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

            // Query cơ bản
            $query = User::where('role', User::ROLE_STAFF)
                ->with(['invitedUsers' => function ($q) use ($dateFrom, $dateTo) {
                    $q->with(['wallet_balance_histories' => function ($wq) use ($dateFrom, $dateTo) {
                        $wq->where('type', 'deposit')
                            ->where('status', 'completed')
                            ->whereBetween('created_at', [$dateFrom, $dateTo]);
                    }])->where('clone_account', 0);
                }]);

            // Nếu có filter theo nhân viên
            if ($staffId) {
                $query->where('id', $staffId);
            }

            $staffList = $query->get();

            // Tính toán dữ liệu
            $tableData = [];
            $chartLabels = [];
            $chartRevenueData = [];
            $totalRevenue = 0;
            $totalTransactions = 0;

            foreach ($staffList as $staff) {
                $invitedUsers = $staff->invitedUsers;
                $staffRevenue = 0;
                $staffTransactions = 0;

                foreach ($invitedUsers as $user) {
                    $userTransactions = $user->wallet_balance_histories;
                    $staffTransactions += $userTransactions->count();
                    $staffRevenue += $userTransactions->sum('value');
                }

                $tableData[] = [
                    'staff_id' => $staff->id,
                    'staff_name' => $staff->full_name,
                    'staff_email' => $staff->email,
                    'invited_users' => $invitedUsers->count(),
                    'total_transactions' => $staffTransactions,
                    'total_revenue' => $staffRevenue
                ];

                $chartLabels[] = $staff->full_name;
                $chartRevenueData[] = $staffRevenue;
                $totalRevenue += $staffRevenue;
                $totalTransactions += $staffTransactions;
            }

            // Sắp xếp theo doanh thu giảm dần
            usort($tableData, function ($a, $b) {
                return $b['total_revenue'] <=> $a['total_revenue'];
            });

            // Lấy top 5 để hiển thị chart
            $topStaff = array_slice($tableData, 0, 5);
            $topLabels = array_column($topStaff, 'staff_name');
            $topData = array_column($topStaff, 'total_revenue');

            // Tính toán summary
            $summary = [
                'total_staff' => count($staffList),
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
                'avg_revenue' => count($staffList) > 0 ? $totalRevenue / count($staffList) : 0
            ];

            // Dữ liệu cho biểu đồ
            $chartData = [
                'labels' => $chartLabels,
                'revenue_data' => $chartRevenueData,
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
            $staff = User::where('id', $staffId)
                ->where('role', User::ROLE_STAFF)
                ->first();

            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy nhân viên'
                ], 404);
            }

            // Lấy danh sách người dùng được mời bởi nhân viên này
            $invitedUsers = User::where('referrer_id', $staffId)->where('clone_account', 0)->get();
            $invitedUserIds = $invitedUsers->pluck('id')->toArray();

            // Lấy tất cả giao dịch nạp tiền của những người dùng được mời
            $transactions = Wallet_balance_history::whereIn('user_id', $invitedUserIds)
                ->where('type', 'deposit')
                ->where('status', 'completed')
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->with('user:id,full_name,email')
                ->orderBy('created_at', 'desc')
                ->get();

            // Tính toán thống kê
            $statistics = [
                'invited_users' => $invitedUsers->count(),
                'total_transactions' => $transactions->count(),
                'total_revenue' => $transactions->sum('value')
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
     * API xuất Excel báo cáo doanh thu
     */

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
            $dateFrom = Carbon::parse($dateFrom);
            $dateTo = Carbon::parse($dateTo);

            // Tạo query
            $query = DB::table('wallet_balance_histories as wbh')
                ->join('users as u', 'wbh.user_id', '=', 'u.id')
                ->join('users as staff', 'u.referrer_id', '=', 'staff.id')
                ->where('u.clone_account', 0)
                ->where('wbh.type', 'deposit')
                ->where('wbh.status', 'completed')
                ->where('staff.role', User::ROLE_STAFF)
                ->whereBetween('wbh.created_at', [$dateFrom, $dateTo])
                ->select(
                    DB::raw('DATE(wbh.created_at) as date'),
                    DB::raw('SUM(wbh.value) as total_revenue'),
                    DB::raw('COUNT(wbh.id) as total_transactions')
                );

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
        return view('admin.statistical.doanhThuTuKhachHang');
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
            $revenueData = Wallet_balance_history::where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('status', 'completed')
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

            return response()->json([
                'success' => true,
                'data' => [
                    'total_revenue' => $revenueData->total_revenue ?? 0,
                    'total_transactions' => $revenueData->total_transactions ?? 0,
                    'total_customers' => $revenueData->total_customers ?? 0,
                    'avg_transaction' => $revenueData->avg_transaction ?? 0
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

            $revenueData = Wallet_balance_history::where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('status', 'completed')
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

            $topCustomers = Wallet_balance_history::join('users', 'wallet_balance_histories.user_id', '=', 'users.id')
                ->where('users.clone_account', 0)
                ->where('wallet_balance_histories.type', 'deposit')
                ->where('wallet_balance_histories.status', 'completed')
                ->whereBetween('wallet_balance_histories.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    'users.full_name',
                    DB::raw('SUM(wallet_balance_histories.value) as total_revenue')
                )
                ->groupBy('users.id', 'users.full_name')
                ->orderBy('total_revenue', 'desc')
                ->limit($limit)
                ->get();

            $labels = $topCustomers->pluck('full_name')->toArray();
            $values = $topCustomers->pluck('total_revenue')->toArray();

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
            $topCustomers = Wallet_balance_history::join('users', 'wallet_balance_histories.user_id', '=', 'users.id')
                ->where('users.clone_account', 0)
                ->where('wallet_balance_histories.type', 'deposit')
                ->where('wallet_balance_histories.status', 'completed')
                ->whereBetween('wallet_balance_histories.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    'users.full_name',
                    DB::raw('SUM(wallet_balance_histories.value) as total_revenue')
                )
                ->groupBy('users.id', 'users.full_name')
                ->orderBy('total_revenue', 'desc')
                ->limit(5)
                ->get();

            $topRevenue = $topCustomers->sum('total_revenue');

            // Tổng doanh thu
            $totalRevenue = Wallet_balance_history::where('type', 'deposit')
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
                    'values' => $values
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

            $customerRevenue = Wallet_balance_history::join('users', 'wallet_balance_histories.user_id', '=', 'users.id')
                ->where('users.clone_account', 0)
                ->where('wallet_balance_histories.type', 'deposit')
                ->where('wallet_balance_histories.status', 'completed')
                ->whereBetween('wallet_balance_histories.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    'users.full_name',
                    'users.phone',
                    DB::raw('COUNT(wallet_balance_histories.id) as transaction_count'),
                    DB::raw('SUM(wallet_balance_histories.value) as total_revenue'),
                    DB::raw('MAX(wallet_balance_histories.created_at) as last_transaction')
                )
                ->groupBy('users.id', 'users.full_name', 'users.phone')
                ->orderBy('total_revenue', 'desc')
                ->get();

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
     * API: Xuất báo cáo Excel (nếu cần trong tương lai)
     */
    public function exportRevenue(Request $request)
    {
        // Implement export functionality if needed
        return response()->json([
            'success' => false,
            'message' => 'Chức năng xuất báo cáo chưa được triển khai'
        ]);
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

            $staffRevenue = Wallet_balance_history::join('users as staff', 'wallet_balance_histories.by_user_id', '=', 'staff.id')
                ->join('users as customers', 'wallet_balance_histories.user_id', '=', 'customers.id')
                ->where('customers.clone_account', 0)
                ->where('wallet_balance_histories.type', 'deposit')
                ->where('wallet_balance_histories.status', 'completed')
                ->whereNotNull('wallet_balance_histories.by_user_id')
                ->whereBetween('wallet_balance_histories.created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
                ->select(
                    'staff.full_name as staff_name',
                    'staff.email as staff_email',
                    DB::raw('COUNT(wallet_balance_histories.id) as transaction_count'),
                    DB::raw('SUM(wallet_balance_histories.value) as total_revenue'),
                    DB::raw('COUNT(DISTINCT wallet_balance_histories.user_id) as unique_customers')
                )
                ->groupBy('staff.id', 'staff.full_name', 'staff.email')
                ->orderBy('total_revenue', 'desc')
                ->get();

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

            $paymentStats = Wallet_balance_history::where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
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

            $statusStats = Wallet_balance_history::where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ])
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

            $rangeStats = Wallet_balance_history::where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('status', 'completed')
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

            $hourlyStats = Wallet_balance_history::where('type', 'deposit')
                ->whereHas('user', function ($q) {
                    $q->where('clone_account', 0);
                })
                ->where('status', 'completed')
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
    public function doanhThuBanThan()
    {
        return view('admin.statistical.doanhThuBanThan');
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
                return now()->subDays(7);
            case '30_days':
                return now()->subDays(30);
            case '3_months':
                return now()->subMonths(3);
            case '6_months':
                return now()->subMonths(6);
            case '1_year':
                return now()->subYear();
            default:
                return now()->subDays(7);
        }
    }

    private function getDailyRevenue($userId, $startDate, $endDate)
    {
        $data = Wallet_balance_history::where('by_user_id', $userId)
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
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
        $data = Wallet_balance_history::where('by_user_id', $userId)
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
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
        $stats = Wallet_balance_history::where('by_user_id', $userId)
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                SUM(CASE WHEN type = "deposit" THEN value ELSE 0 END) as total_deposit,
                SUM(CASE WHEN type = "withdraw" THEN value ELSE 0 END) as total_withdraw,
                COUNT(CASE WHEN type = "deposit" THEN 1 END) as deposit_count,
                COUNT(CASE WHEN type = "withdraw" THEN 1 END) as withdraw_count
            ')
            ->first();

        $totalRevenue = (float)$stats->total_deposit;
        $totalWithdraw = (float)$stats->total_withdraw;
        $netRevenue = $totalRevenue - $totalWithdraw;

        // Tính toán so với kỳ trước
        $prevStartDate = $startDate->copy()->sub($endDate->diff($startDate));
        $prevEndDate = $startDate->copy()->subDay();

        $prevStats = Wallet_balance_history::where('by_user_id', $userId)
            ->where('status', 'completed')
            ->where('type', 'deposit')
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->sum('value');

        $growth = $prevStats > 0 ? (($totalRevenue - $prevStats) / $prevStats) * 100 : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_withdraw' => $totalWithdraw,
            'net_revenue' => $netRevenue,
            'deposit_count' => $stats->deposit_count,
            'withdraw_count' => $stats->withdraw_count,
            'total_transactions' => $stats->deposit_count + $stats->withdraw_count,
            'growth_rate' => round($growth, 2),
            'avg_transaction_value' => $stats->deposit_count > 0 ? round($totalRevenue / $stats->deposit_count, 2) : 0
        ];
    }

    private function getTransactionTypeStats($userId, $startDate, $endDate)
    {
        $stats = Wallet_balance_history::where('by_user_id', $userId)
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->where('status', 'completed')
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

    /**
     * API lấy danh sách giao dịch chi tiết
     */
    public function getPersonalTransactions(Request $request)
    {
        $userId = Auth::id();
        $perPage = $request->get('per_page', 10);
        $type = $request->get('type'); // deposit, withdraw
        $status = $request->get('status'); // processing, completed, cancelled

        $query = Wallet_balance_history::where('by_user_id', $userId)
            ->whereHas('user', function ($q) {
                $q->where('clone_account', 0);
            })
            ->with(['user:id,full_name,username'])
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $transactions = $query->paginate($perPage);
        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
