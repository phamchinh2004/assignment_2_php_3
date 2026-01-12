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
use App\Http\Controllers\Admin\OrderReportController;
use App\Http\Controllers\ConversationController;
use App\Models\Language;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:staff|admin', 'checkBanned', 'auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::middleware(['checkPermission:quan_ly_don_hang'])->group(function () {
        Route::get('/order/update-commission-percentage', [OrderController::class, 'orderUpdateCommissionPercentage'])->name('order.update.commission.percentage');
        Route::get('/order/add-customer-info', [OrderController::class, 'addCustomerInfoToOrders'])->name('order.add.customer.info');
        Route::get('/order/update-status-history', [OrderController::class, 'updateOrderStatusHistory'])->name('order.update.status.history');
        Route::get('/order/update-commission-paid', [OrderController::class, 'updateCommissionPaid'])->name('order.update.commission.paid');
        Route::get('/order/update-frozen-commission-percentage', [OrderController::class, 'updateFrozenCommissionPercentage'])->name('order.update.frozen.commission.percentage');
        Route::get('/order/change-status-order/{order}', [OrderController::class, 'changeStatusOrder'])->name('order.change.status');
        Route::resource('order', OrderController::class);

        // Danh sách đơn hàng bị báo cáo (đơn hàng ảo)
        Route::get('/order-reports', [OrderReportController::class, 'index'])->name('order_reports.index');
        Route::get('/order-reports/{orderReport}', [OrderReportController::class, 'show'])->name('order_reports.show');
        Route::post('/order-reports/{orderReport}/confirm', [OrderReportController::class, 'confirm'])->name('order_reports.confirm');
        Route::post('/order-reports/{orderReport}/cancel', [OrderReportController::class, 'cancel'])->name('order_reports.cancel');
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
    Route::delete('user/{user}/frozen-orders/{frozenOrder}', [UserController::class, 'unfrozenOrder'])->name('user.unfrozen.order');
    Route::put('user/{user}/frozen-orders/{frozenOrder}', [UserController::class, 'updateFrozenOrder'])->name('user.update.frozen.order');
    Route::put('user/{user}/frozen-orders/{frozenOrder}/image', [UserController::class, 'updateOrderImage'])->name('user.update.frozen.order.image');
    // Route::get('/user/edit-frozen-order/{user}/{id}', [UserController::class, 'editFrozenOrderInterface'])->name('user.edit.frozen.order.interface');
    // Route::put('/user/edit-frozen-order/{user}/{id}', [UserController::class, 'updateFrozenOrder'])->name('user.update.frozen.order');
    Route::post('/user/plus-money', [UserController::class, 'plus_money'])->name('plus_money');

    // Đã kiểm tra
    Route::get('/withdraw-transaction', [TransactionHistoryController::class, 'index_withdraw'])->name(name: 'withdraw_transaction');
    Route::get('/confirm-withdraw/{transaction}', [TransactionHistoryController::class, 'confirm_withdraw'])->name('confirm.withdraw');
    Route::get('/cancel-withdraw/{transaction}', [TransactionHistoryController::class, 'cancel_withdraw'])->name('cancel.withdraw');
    Route::get('/change-withdraw-transaction-type/{transaction}', [TransactionHistoryController::class, 'change_withdraw_transaction_type'])->name(name: 'change.withdraw.transaction.type');
    Route::get('/deposit-transaction', [TransactionHistoryController::class, 'index_deposit'])->name('deposit_transaction');
    Route::delete('/destroy-deposit/{transaction}', [TransactionHistoryController::class, 'destroy_deposit'])->name('destroy.deposit');
    Route::get('/change-deposit-transaction-type/{transaction}', [TransactionHistoryController::class, 'change_deposit_transaction_type'])->name(name: 'change.deposit.transaction.type');
    // Đã kiểm tra
    Route::get('/chat-panel', [ConversationController::class, 'index'])->name('chat-panel');
});

Route::middleware(['role:admin'])->group(function () {
    Route::resource('staffs', StaffController::class);
    Route::resource('manager_setting', ManagerSettingController::class);
    Route::resource('staff', StaffController::class);
    
    // Quản lý thời gian chuyển trạng thái đơn hàng
    Route::get('/order-status-timing', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'index'])->name('admin.order_status_timing.index');
    Route::get('/order-status-timing/{orderStatusTiming}/edit', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'edit'])->name('admin.order_status_timing.edit');
    Route::put('/order-status-timing/{orderStatusTiming}', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'update'])->name('admin.order_status_timing.update');
    Route::post('/order-status-timing/update-multiple', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'updateMultiple'])->name('admin.order_status_timing.update_multiple');
    Route::get('/staff/change-status/{id}', [StaffController::class, 'change_status_staff'])->name('staff.change.status');
    Route::get('/staff/edit-permissions/{id}', [StaffController::class, 'edit_permissions'])->name('staff.edit.permissions');
    Route::post('/staff/change-status-permission', [StaffController::class, 'change_status_permission'])->name('staff.change.status.permission');

    // Tổng doanh thu
    Route::get('tong-doanh-thu', [StatisticalController::class, 'tongDoanhThu'])->name('tong.doanh.thu');
    Route::get('statistical/revenue', [StatisticalController::class, 'tongDoanhThu'])->name('admin.statistical.revenue');

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

    // Xuất Excel báo cáo doanh thu (route dành cho admin, tránh trùng với API)
    Route::get('export', [StatisticalController::class, 'exportRevenue'])
        ->name('admin.revenue.export');
    
    // Lấy biểu đồ doanh thu theo thời gian (route dành cho admin, tránh trùng với API)
    Route::get('chart', [StatisticalController::class, 'getRevenueChart'])
        ->name('admin.revenue.chart');
    Route::get('doanh-thu-tu-khach-hang', [StatisticalController::class, 'doanhThuTuKhachHang'])->name('doanh.thu.tu.khach.hang');
});
Route::get('doanh-thu-ban-than', [StatisticalController::class, 'doanhThuBanThan'])->name('doanh.thu.ban.than');
Route::get('/personal-revenue-stats', [StatisticalController::class, 'getPersonalRevenueStats']);
Route::middleware('auth')->get('/personal-transactions', [StatisticalController::class, 'getPersonalTransactions']);
