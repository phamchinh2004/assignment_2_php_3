<?php

use App\Http\Controllers\Admin\StatisticalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// API Routes cho thống kê doanh thu
Route::prefix('statistical')->name('api.statistical.')->group(function () {
    // API lấy dữ liệu thống kê doanh thu chính
    Route::get('revenue-data', [StatisticalController::class, 'getRevenueData'])->name('revenue');

    // API lấy thống kê chi tiết theo người dùng
    Route::get('user-revenue-stats', [StatisticalController::class, 'getUserRevenueStats'])->name('user.revenue');

    // API lấy thống kê theo trạng thái giao dịch
    Route::get('transaction-status-stats', [StatisticalController::class, 'getTransactionStatusStats'])->name('transaction.status');

    // API export dữ liệu thống kê
    Route::get('export-revenue-data', [StatisticalController::class, 'exportRevenueData'])->name('export.revenue');
});

// Middleware bảo vệ API (nếu cần)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/statistical')->name('api.admin.statistical.')->group(function () {
    // Các API chỉ dành cho admin
    Route::get('revenue-data', [StatisticalController::class, 'getRevenueData'])->name('revenue');
    Route::get('user-revenue-stats', [StatisticalController::class, 'getUserRevenueStats'])->name('user.revenue');
    Route::get('transaction-status-stats', [StatisticalController::class, 'getTransactionStatusStats'])->name('transaction.status');
    Route::get('export-revenue-data', [StatisticalController::class, 'exportRevenueData'])->name('export.revenue');
});
Route::prefix('revenue')->group(function () {
    // Tổng quan doanh thu
    Route::get('overview', [StatisticalController::class, 'revenueOverview'])->name('api.revenue.overview');

    // Biểu đồ doanh thu theo thời gian
    Route::get('chart', [StatisticalController::class, 'revenueChart'])->name('api.revenue.chart');

    // Top khách hàng theo doanh thu
    Route::get('top-customers', [StatisticalController::class, 'topCustomers'])->name('api.revenue.top-customers');

    // Phân bố doanh thu theo khách hàng
    Route::get('distribution', [StatisticalController::class, 'revenueDistribution'])->name('api.revenue.distribution');

    // Chi tiết doanh thu theo khách hàng
    Route::get('customer-detail', [StatisticalController::class, 'customerRevenueDetail'])->name('api.revenue.customer-detail');

    // Thống kê theo nhân viên
    Route::get('staff', [StatisticalController::class, 'staffRevenue'])->name('api.revenue.staff');

    // Thống kê theo phương thức thanh toán
    Route::get('payment-method', [StatisticalController::class, 'paymentMethodStats'])->name('api.revenue.payment-method');

    // Thống kê theo trạng thái giao dịch
    Route::get('transaction-status', [StatisticalController::class, 'transactionStatusStats'])->name('api.revenue.transaction-status');

    // Thống kê theo khoảng giá trị
    Route::get('transaction-range', [StatisticalController::class, 'transactionRangeStats'])->name('api.revenue.transaction-range');

    // Thống kê theo giờ
    Route::get('hourly', [StatisticalController::class, 'hourlyStats'])->name('api.revenue.hourly');

    // Xuất báo cáo (nếu cần)
    Route::get('export', [StatisticalController::class, 'exportRevenue'])->name('api.revenue.export');
});

// Routes tương thích với frontend JavaScript
Route::get('revenue-overview', [StatisticalController::class, 'revenueOverview']);
Route::get('revenue-chart', [StatisticalController::class, 'revenueChart']);
Route::get('top-customers', [StatisticalController::class, 'topCustomers']);
Route::get('revenue-distribution', [StatisticalController::class, 'revenueDistribution']);
Route::get('customer-revenue-detail', [StatisticalController::class, 'customerRevenueDetail']);


Route::get('/personal-revenue-stats', [StatisticalController::class, 'getPersonalRevenueStats']);
Route::get('/personal-transactions', [StatisticalController::class, 'getPersonalTransactions']);
