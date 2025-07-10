<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\ManagerSettingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\RankController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StatisticalController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TransactionHistoryController;
use App\Http\Controllers\ConversationController;
use App\Models\Language;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:staff|admin', 'checkBanned', 'auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::middleware(['checkPermission:quan_ly_don_hang'])->group(function () {
        Route::get('/order/update-commission-percentage', [OrderController::class, 'orderUpdateCommissionPercentage'])->name('order.update.commission.percentage');
        Route::get('/order/change-status-order/{order}', [OrderController::class, 'changeStatusOrder'])->name('order.change.status');
        Route::resource('order', OrderController::class);
    });
    Route::middleware(['checkPermission:quan_ly_cap_do'])->group(function () {
        Route::resource('rank', RankController::class);
    });
    Route::middleware(['checkPermission:quan_ly_banner'])->group(function () {
        Route::resource('banner', BannerController::class);
        Route::get('/banner/change-status/{banner}', [BannerController::class, 'change_status_banner'])->name('banner.change.status');
    });
    Route::middleware(['checkPermission:quan_ly_thong_tin_trang_web'])->group(function () {
        Route::resource('section', SectionController::class);
        Route::get('/section/change-status/{section}', [SectionController::class, 'change_status_section'])->name('section.change.status');
    });
    Route::middleware(['checkPermission:quan_ly_doi_tac'])->group(function () {
        Route::resource('partner', PartnerController::class);
    });
    Route::middleware(['checkPermission:quan_ly_ngon_ngu'])->group(function () {
        Route::resource('language', LanguageController::class);
    });

    // Đã kiểm tra
    Route::resource('user', UserController::class);
    Route::get('/user/change-status-user/{user}', [UserController::class, 'changeStatusUser'])->name('user.change.status');
    Route::get('/user/frozen-order/{user}', [UserController::class, 'frozenOrderInterface'])->name('user.frozen.order.interface');
    Route::post('/user/frozen-order/{user}', [UserController::class, 'frozenOrder'])->name('user.frozen.order');
    Route::get('/user/edit-frozen-order/{user}/{id}', [UserController::class, 'editFrozenOrderInterface'])->name('user.edit.frozen.order.interface');
    Route::put('/user/edit-frozen-order/{user}/{id}', [UserController::class, 'updateFrozenOrder'])->name('user.update.frozen.order');
    Route::post('/user/plus-money', [UserController::class, 'plus_money'])->name('plus_money');

    // Đã kiểm tra
    Route::get('/withdraw-transaction', [TransactionHistoryController::class, 'index_withdraw'])->name(name: 'withdraw_transaction');
    Route::get('/confirm-withdraw/{transaction}', [TransactionHistoryController::class, 'confirm_withdraw'])->name('confirm.withdraw');
    Route::get('/cancel-withdraw/{transaction}', [TransactionHistoryController::class, 'cancel_withdraw'])->name('cancel.withdraw');
    Route::get('/deposit-transaction', [TransactionHistoryController::class, 'index_deposit'])->name('deposit_transaction');
    Route::delete('/destroy-deposit/{transaction}', [TransactionHistoryController::class, 'destroy_deposit'])->name('destroy.deposit');
    // Đã kiểm tra
    Route::get('/chat-panel', [ConversationController::class, 'index'])->name('chat-panel');
});

Route::middleware(['role:admin'])->group(function () {
    Route::resource('staffs', StaffController::class);
    Route::resource('manager_setting', ManagerSettingController::class);
    Route::resource('staff', StaffController::class);
    Route::get('/staff/change-status/{id}', [StaffController::class, 'change_status_staff'])->name('staff.change.status');
    Route::get('/staff/edit-permissions/{id}', [StaffController::class, 'edit_permissions'])->name('staff.edit.permissions');
    Route::post('/staff/change-status-permission', [StaffController::class, 'change_status_permission'])->name('staff.change.status.permission');

    // Tổng doanh thu
    Route::get('tong-doanh-thu', [StatisticalController::class, 'tongDoanhThu'])->name('tong.doanh.thu');
    Route::get('statistical/revenue', [StatisticalController::class, 'tongDoanhThu'])->name('admin.statistical.revenue');

    // Routes API có thể truy cập từ web
    Route::get('api/revenue-data', [StatisticalController::class, 'getRevenueData'])->name('api.statistical.revenue');
    Route::get('api/user-revenue-stats', [StatisticalController::class, 'getUserRevenueStats'])->name('api.statistical.user.revenue');
    Route::get('api/transaction-status-stats', [StatisticalController::class, 'getTransactionStatusStats'])->name('api.statistical.transaction.status');
    Route::get('api/export-revenue-data', [StatisticalController::class, 'exportRevenueData'])->name('api.statistical.export.revenue');

    // Route cho các trang thống kê khác
    Route::prefix('statistical')->name('admin.statistical.')->group(function () {
        Route::get('users', [StatisticalController::class, 'userStats'])->name('users');
        Route::get('transactions', [StatisticalController::class, 'transactionStats'])->name('transactions');
        Route::get('monthly-report', [StatisticalController::class, 'monthlyReport'])->name('monthly.report');
    });

    Route::get('doanh-thu-theo-nhan-vien', [StatisticalController::class, 'doanhThuTheoNhanVien'])->name('doanh.thu.theo.nhan.vien');
    Route::get('staff-list', [StatisticalController::class, 'getStaffList'])
        ->name('api.staff.list');

    // Lấy dữ liệu doanh thu theo nhân viên
    Route::get('by-staff', [StatisticalController::class, 'getRevenueByStaff'])
        ->name('api.revenue.by.staff');

    // Lấy chi tiết doanh thu của một nhân viên
    Route::get('detail', [StatisticalController::class, 'getRevenueDetail'])
        ->name('api.revenue.detail');

    // Xuất Excel báo cáo doanh thu
    Route::get('export', [StatisticalController::class, 'exportRevenue'])
        ->name('api.revenue.export');

    // Lấy biểu đồ doanh thu theo thời gian
    Route::get('chart', [StatisticalController::class, 'getRevenueChart'])
        ->name('api.revenue.chart');
    Route::get('doanh-thu-tu-khach-hang', [StatisticalController::class, 'doanhThuTuKhachHang'])->name('doanh.thu.tu.khach.hang');
    Route::get('doanh-thu-ban-than', [StatisticalController::class, 'doanhThuBanThan'])->name('doanh.thu.ban.than');
});
