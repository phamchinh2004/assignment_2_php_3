<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\AuthorizationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HeaderStateController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\LuckyWheelRewardController;
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
use App\Http\Controllers\Admin\OrderDistributionController;
use App\Http\Controllers\ConversationController;
use App\Models\Language;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:staff|admin|own', 'checkBanned', 'auth'])->group(function () {
    $capabilities = config('authorization.capabilities');

    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:' . $capabilities['system_statistics'])
        ->name('admin.dashboard');
    Route::get('/authorization-state', [AuthorizationController::class, 'state'])->name('authorization.state');
    Route::get('/header-state', [HeaderStateController::class, 'show'])->name('header.state');
    Route::post('/header/notifications/read-all', [HeaderStateController::class, 'markAllNotificationsRead'])
        ->name('header.notifications.read-all');
    Route::post('/header/notifications/{notification}/read', [HeaderStateController::class, 'markNotificationRead'])
        ->name('header.notifications.read');

    Route::middleware(['permission:' . $capabilities['orders']])->group(function () {
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
    Route::middleware(['permission:' . $capabilities['ranks']])->group(function () {
        Route::resource('rank', RankController::class);
    });
    Route::middleware(['permission:' . $capabilities['banners']])->group(function () {
        Route::resource('banner', BannerController::class);
        Route::get('/banner/change-status/{banner}', [BannerController::class, 'change_status_banner'])->name('banner.change.status');
    });
    Route::middleware(['permission:' . $capabilities['site_content']])->group(function () {
        Route::resource('section', SectionController::class);
        Route::get('/section/change-status/{section}', [SectionController::class, 'change_status_section'])->name('section.change.status');
    });
    Route::middleware(['permission:' . $capabilities['partners']])->group(function () {
        Route::resource('partner', PartnerController::class);
    });
    Route::middleware(['permission:' . $capabilities['languages']])->group(function () {
        Route::resource('language', LanguageController::class);
    });

    Route::middleware(['authorization.context:' . $capabilities['manage_all_users']])->group(function () {
        Route::resource('user', UserController::class);
        Route::get('/user-online-statuses', [UserController::class, 'getOnlineStatuses'])->name('user.online.statuses');
        Route::post('/user/{user}/location/refresh', [UserController::class, 'refreshApproximateLocation'])->name('user.location.refresh');
        Route::delete('/user/{user}/location', [UserController::class, 'destroyLocation'])->name('user.location.destroy');
        Route::get('/user/change-status-user/{user}', [UserController::class, 'changeStatusUser'])->name('user.change.status');
        Route::get('/user/frozen-order/{user}', [UserController::class, 'frozenOrderInterface'])->name('user.frozen.order.interface');
        Route::post('/user/frozen-order/{user}', [UserController::class, 'frozenOrder'])->name('user.frozen.order');
        Route::delete('user/{user}/frozen-orders/{frozenOrder}', [UserController::class, 'unfrozenOrder'])->name('user.unfrozen.order');
        Route::put('user/{user}/frozen-orders/{frozenOrder}', [UserController::class, 'updateFrozenOrder'])->name('user.update.frozen.order');
        Route::put('user/{user}/frozen-orders/{frozenOrder}/image', [UserController::class, 'updateOrderImage'])->name('user.update.frozen.order.image');
        Route::post('/user/plus-money', [UserController::class, 'plus_money'])->name('plus_money');
    });

    Route::middleware(['permission:' . $capabilities['order_processing_time_alert_settings']])->group(function () {
        Route::get('/frozen-order-settings', [\App\Http\Controllers\Admin\FrozenOrderSettingController::class, 'index'])->name('frozen_order_settings.index');
        Route::post('/frozen-order-settings', [\App\Http\Controllers\Admin\FrozenOrderSettingController::class, 'store'])->name('frozen_order_settings.store');
    });

    Route::middleware(['authorization.context:' . $capabilities['manage_all_user_transactions']])->group(function () {
        Route::get('/withdraw-transaction', [TransactionHistoryController::class, 'index_withdraw'])->name(name: 'withdraw_transaction');
        Route::get('/confirm-withdraw/{transaction}', [TransactionHistoryController::class, 'confirm_withdraw'])->name('confirm.withdraw');
        Route::get('/cancel-withdraw/{transaction}', [TransactionHistoryController::class, 'cancel_withdraw'])->name('cancel.withdraw');
        Route::get('/change-withdraw-transaction-type/{transaction}', [TransactionHistoryController::class, 'change_withdraw_transaction_type'])->name(name: 'change.withdraw.transaction.type');
        Route::get('/deposit-transaction', [TransactionHistoryController::class, 'index_deposit'])->name('deposit_transaction');
        Route::delete('/destroy-deposit/{transaction}', [TransactionHistoryController::class, 'destroy_deposit'])->name('destroy.deposit');
        Route::get('/change-deposit-transaction-type/{transaction}', [TransactionHistoryController::class, 'change_deposit_transaction_type'])->name(name: 'change.deposit.transaction.type');
    });

    Route::middleware([
        'role:admin|own',
        'permission:' . $capabilities['manage_all_user_transactions'],
    ])->prefix('lucky-wheel-rewards')->name('lucky_wheel_rewards.')->group(function () {
        Route::get('/', [LuckyWheelRewardController::class, 'index'])->name('index');
        Route::post('/{spin}/approve', [LuckyWheelRewardController::class, 'approve'])->name('approve');
        Route::post('/{spin}/reject', [LuckyWheelRewardController::class, 'reject'])->name('reject');
        Route::post('/settings/auto-approval', [LuckyWheelRewardController::class, 'updateAutoApproval'])->name('auto_approval');
    });
    // Đã kiểm tra
    Route::get('/chat-panel', [ConversationController::class, 'index'])->name('chat-panel');
});

Route::middleware(['role:staff|admin|own', 'checkBanned', 'auth'])->group(function () {
    $capabilities = config('authorization.capabilities');
    Route::middleware(['permission:' . $capabilities['order_distributions']])->group(function () {
        Route::get('/order-distributions', [OrderDistributionController::class, 'index'])->name('order_distributions.index');
        Route::get('/order-distributions/{frozenOrder}', [OrderDistributionController::class, 'show'])->name('order_distributions.show');
        Route::post('/order-distributions/{frozenOrder}/transition', [OrderDistributionController::class, 'transition'])
            ->name('order_distributions.transition');
    });

    Route::middleware(['role:admin|own', 'permission:' . $capabilities['manage_staff']])->group(function () {
        Route::resource('staffs', StaffController::class)->except(['destroy']);
        Route::resource('staff', StaffController::class)->except(['destroy']);
        Route::get('/staff-online-statuses', [StaffController::class, 'getOnlineStatuses'])->name('staff.online.statuses');
        Route::get('/staff/change-status/{id}', [StaffController::class, 'change_status_staff'])->name('staff.change.status');
    });

    Route::middleware(['role:admin|own', 'permission:' . $capabilities['manage_staff_permissions']])->group(function () {
        Route::get('/staff/edit-permissions/{id}', [StaffController::class, 'edit_permissions'])->name('staff.edit.permissions');
        Route::post('/staff/change-status-permission', [StaffController::class, 'change_status_permission'])->name('staff.change.status.permission');
        Route::post('/staff/change-status-permissions', [StaffController::class, 'change_status_permissions'])->name('staff.change.status.permissions');
    });

    Route::middleware(['role:own'])->group(function () {
        Route::resource('manager_setting', ManagerSettingController::class);
    });
    
    Route::middleware(['permission:' . $capabilities['order_timing_settings']])->group(function () {
        Route::get('/order-status-timing', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'index'])->name('admin.order_status_timing.index');
        Route::get('/order-status-timing/{orderStatusTiming}/edit', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'edit'])->name('admin.order_status_timing.edit');
        Route::put('/order-status-timing/{orderStatusTiming}', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'update'])->name('admin.order_status_timing.update');
        Route::post('/order-status-timing/update-multiple', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'updateMultiple'])->name('admin.order_status_timing.update_multiple');
    });

    Route::middleware(['permission:' . $capabilities['system_statistics']])->group(function () {
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
});
Route::middleware(['role:staff|admin|own', 'checkBanned', 'auth'])->group(function () {
    Route::get('doanh-thu-ban-than', [StatisticalController::class, 'doanhThuBanThan'])->name('doanh.thu.ban.than');
    Route::get('/personal-revenue-stats', [StatisticalController::class, 'getPersonalRevenueStats']);
    Route::get('/personal-transactions', [StatisticalController::class, 'getPersonalTransactions']);
});
