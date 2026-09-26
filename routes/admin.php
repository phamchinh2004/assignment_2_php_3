<?php

use App\Http\Controllers\Admin\AuthorizationController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FeatureAnnouncementController;
use App\Http\Controllers\Admin\HeaderStateController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\LuckyWheelRewardController;
use App\Http\Controllers\Admin\ManagerSettingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderDistributionController;
use App\Http\Controllers\Admin\OrderReportController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\RankController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StatisticalController;
use App\Http\Controllers\Admin\TransactionHistoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:staff|admin|own', 'checkBanned', 'auth'])->group(function () {
    $capabilities = config('authorization.capabilities');

    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:' . $capabilities['statistics_view_overview'])
        ->name('admin.dashboard');

    Route::get('/authorization-state', [AuthorizationController::class, 'state'])->name('authorization.state');
    Route::get('/header-state', [HeaderStateController::class, 'show'])->name('header.state');
    Route::post('/header/notifications/read-all', [HeaderStateController::class, 'markAllNotificationsRead'])
        ->name('header.notifications.read-all');
    Route::post('/header/notifications/{notification}/read', [HeaderStateController::class, 'markNotificationRead'])
        ->name('header.notifications.read');

    Route::get('/feature-announcements/unread', [FeatureAnnouncementController::class, 'unread'])
        ->name('feature_announcements.unread');
    Route::get('/feature-announcements', [FeatureAnnouncementController::class, 'index'])
        ->middleware('permission:' . $capabilities['feature_announcements_view'])
        ->name('feature_announcements.index');
    Route::get('/feature-announcements/create', [FeatureAnnouncementController::class, 'create'])
        ->middleware('permission:' . $capabilities['feature_announcements_create'])
        ->name('feature_announcements.create');
    Route::post('/feature-announcements', [FeatureAnnouncementController::class, 'store'])
        ->middleware('permission:' . $capabilities['feature_announcements_create'])
        ->name('feature_announcements.store');
    Route::get('/feature-announcements/{feature_announcement}/edit', [FeatureAnnouncementController::class, 'edit'])
        ->middleware('permission:' . $capabilities['feature_announcements_update'])
        ->name('feature_announcements.edit');
    Route::put('/feature-announcements/{feature_announcement}', [FeatureAnnouncementController::class, 'update'])
        ->middleware('permission:' . $capabilities['feature_announcements_update'])
        ->name('feature_announcements.update');
    Route::post('/feature-announcements/{feature_announcement}/toggle', [FeatureAnnouncementController::class, 'toggle'])
        ->middleware('permission:' . $capabilities['feature_announcements_toggle'])
        ->name('feature_announcements.toggle');
    Route::delete('/feature-announcements/{feature_announcement}', [FeatureAnnouncementController::class, 'destroy'])
        ->middleware('permission:' . $capabilities['feature_announcements_delete'])
        ->name('feature_announcements.destroy');
    Route::get('/feature-announcements/{feature_announcement}', [FeatureAnnouncementController::class, 'show'])
        ->middleware('permission:' . $capabilities['feature_announcements_view_report'])
        ->name('feature_announcements.show');
    Route::post('/feature-announcements/{feature_announcement}/acknowledge', [FeatureAnnouncementController::class, 'acknowledge'])
        ->name('feature_announcements.acknowledge');

    Route::get('/order/change-status-order/{order}', [OrderController::class, 'changeStatusOrder'])
        ->middleware('permission:' . $capabilities['orders_change_status'])
        ->name('order.change.status');
    Route::get('/order', [OrderController::class, 'index'])
        ->middleware('permission:' . $capabilities['orders_view'])
        ->name('order.index');
    Route::get('/order/create', [OrderController::class, 'create'])
        ->middleware('permission:' . $capabilities['orders_create'])
        ->name('order.create');
    Route::post('/order', [OrderController::class, 'store'])
        ->middleware('permission:' . $capabilities['orders_create'])
        ->name('order.store');
    Route::get('/order/{order}/edit', [OrderController::class, 'edit'])
        ->middleware('permission:' . $capabilities['orders_update'])
        ->name('order.edit');
    Route::match(['put', 'patch'], '/order/{order}', [OrderController::class, 'update'])
        ->whereNumber('order')
        ->middleware('permission:' . $capabilities['orders_update'])
        ->name('order.update');
    Route::get('/order/{order}', [OrderController::class, 'show'])
        ->whereNumber('order')
        ->middleware('permission:' . $capabilities['orders_view_detail'])
        ->name('order.show');

    Route::get('/order-reports', [OrderReportController::class, 'index'])
        ->middleware('permission:' . $capabilities['order_reports_view'])
        ->name('order_reports.index');
    Route::get('/order-reports/{orderReport}', [OrderReportController::class, 'show'])
        ->middleware('permission:' . $capabilities['order_reports_view_detail'])
        ->name('order_reports.show');
    Route::post('/order-reports/{orderReport}/confirm', [OrderReportController::class, 'confirm'])
        ->middleware('permission:' . $capabilities['order_reports_confirm'])
        ->name('order_reports.confirm');
    Route::post('/order-reports/{orderReport}/cancel', [OrderReportController::class, 'cancel'])
        ->middleware('permission:' . $capabilities['order_reports_cancel'])
        ->name('order_reports.cancel');

    Route::get('/rank', [RankController::class, 'index'])->middleware('permission:' . $capabilities['ranks_view'])->name('rank.index');
    Route::get('/rank/create', [RankController::class, 'create'])->middleware('permission:' . $capabilities['ranks_create'])->name('rank.create');
    Route::post('/rank', [RankController::class, 'store'])->middleware('permission:' . $capabilities['ranks_create'])->name('rank.store');
    Route::get('/rank/{rank}/edit', [RankController::class, 'edit'])->middleware('permission:' . $capabilities['ranks_update'])->name('rank.edit');
    Route::match(['put', 'patch'], '/rank/{rank}', [RankController::class, 'update'])->middleware('permission:' . $capabilities['ranks_update'])->name('rank.update');
    Route::delete('/rank/{rank}', [RankController::class, 'destroy'])->middleware('permission:' . $capabilities['ranks_delete'])->name('rank.destroy');
    Route::get('/rank/{rank}', [RankController::class, 'show'])->middleware('permission:' . $capabilities['ranks_view_detail'])->name('rank.show');

    Route::get('/banner/change-status/{banner}', [BannerController::class, 'change_status_banner'])
        ->middleware('permission:' . $capabilities['banners_change_status'])
        ->name('banner.change.status');
    Route::get('/banner', [BannerController::class, 'index'])->middleware('permission:' . $capabilities['banners_view'])->name('banner.index');
    Route::get('/banner/create', [BannerController::class, 'create'])->middleware('permission:' . $capabilities['banners_create'])->name('banner.create');
    Route::post('/banner', [BannerController::class, 'store'])->middleware('permission:' . $capabilities['banners_create'])->name('banner.store');
    Route::get('/banner/{banner}/edit', [BannerController::class, 'edit'])->middleware('permission:' . $capabilities['banners_update'])->name('banner.edit');
    Route::match(['put', 'patch'], '/banner/{banner}', [BannerController::class, 'update'])->middleware('permission:' . $capabilities['banners_update'])->name('banner.update');
    Route::delete('/banner/{banner}', [BannerController::class, 'destroy'])->middleware('permission:' . $capabilities['banners_delete'])->name('banner.destroy');
    Route::get('/banner/{banner}', [BannerController::class, 'show'])->middleware('permission:' . $capabilities['banners_view_detail'])->name('banner.show');

    Route::get('/section/change-status/{section}', [SectionController::class, 'change_status_section'])
        ->middleware('permission:' . $capabilities['site_content_change_status'])
        ->name('section.change.status');
    Route::get('/section', [SectionController::class, 'index'])->middleware('permission:' . $capabilities['site_content_view'])->name('section.index');
    Route::get('/section/create', [SectionController::class, 'create'])->middleware('permission:' . $capabilities['site_content_create'])->name('section.create');
    Route::post('/section', [SectionController::class, 'store'])->middleware('permission:' . $capabilities['site_content_create'])->name('section.store');
    Route::get('/section/{section}/edit', [SectionController::class, 'edit'])->middleware('permission:' . $capabilities['site_content_update'])->name('section.edit');
    Route::match(['put', 'patch'], '/section/{section}', [SectionController::class, 'update'])->middleware('permission:' . $capabilities['site_content_update'])->name('section.update');
    Route::get('/section/{section}', [SectionController::class, 'show'])->middleware('permission:' . $capabilities['site_content_view_detail'])->name('section.show');

    Route::get('/partner', [PartnerController::class, 'index'])->middleware('permission:' . $capabilities['partners_view'])->name('partner.index');
    Route::get('/partner/create', [PartnerController::class, 'create'])->middleware('permission:' . $capabilities['partners_create'])->name('partner.create');
    Route::post('/partner', [PartnerController::class, 'store'])->middleware('permission:' . $capabilities['partners_create'])->name('partner.store');
    Route::get('/partner/{partner}/edit', [PartnerController::class, 'edit'])->middleware('permission:' . $capabilities['partners_update'])->name('partner.edit');
    Route::match(['put', 'patch'], '/partner/{partner}', [PartnerController::class, 'update'])->middleware('permission:' . $capabilities['partners_update'])->name('partner.update');
    Route::delete('/partner/{partner}', [PartnerController::class, 'destroy'])->middleware('permission:' . $capabilities['partners_delete'])->name('partner.destroy');
    Route::get('/partner/{partner}', [PartnerController::class, 'show'])->middleware('permission:' . $capabilities['partners_view_detail'])->name('partner.show');

    Route::get('/language', [LanguageController::class, 'index'])->middleware('permission:' . $capabilities['languages_view'])->name('language.index');
    Route::get('/language/create', [LanguageController::class, 'create'])->middleware('permission:' . $capabilities['languages_create'])->name('language.create');
    Route::post('/language', [LanguageController::class, 'store'])->middleware('permission:' . $capabilities['languages_create'])->name('language.store');
    Route::get('/language/{language}/edit', [LanguageController::class, 'edit'])->middleware('permission:' . $capabilities['languages_update'])->name('language.edit');
    Route::match(['put', 'patch'], '/language/{language}', [LanguageController::class, 'update'])->middleware('permission:' . $capabilities['languages_update'])->name('language.update');
    Route::get('/language/{language}', [LanguageController::class, 'show'])->middleware('permission:' . $capabilities['languages_view_detail'])->name('language.show');

    Route::middleware(['authorization.context:' . $capabilities['customers_view_all']])->group(function () use ($capabilities) {
        Route::get('/user-online-statuses', [UserController::class, 'getOnlineStatuses'])
            ->middleware('permission:' . $capabilities['customers_view'])
            ->name('user.online.statuses');
        Route::post('/user/{user}/location/refresh', [UserController::class, 'refreshApproximateLocation'])
            ->middleware('permission:' . $capabilities['customers_manage_location'])
            ->name('user.location.refresh');
        Route::delete('/user/{user}/location', [UserController::class, 'destroyLocation'])
            ->middleware('permission:' . $capabilities['customers_manage_location'])
            ->name('user.location.destroy');
        Route::get('/user/change-status-user/{user}', [UserController::class, 'changeStatusUser'])
            ->middleware('permission:' . $capabilities['customers_change_status'])
            ->name('user.change.status');
        Route::get('/user/frozen-order/{user}', [UserController::class, 'frozenOrderInterface'])
            ->middleware('permission:' . $capabilities['customers_manage_frozen_orders'])
            ->name('user.frozen.order.interface');
        Route::post('/user/frozen-order/{user}', [UserController::class, 'frozenOrder'])
            ->middleware('permission:' . $capabilities['customers_manage_frozen_orders'])
            ->name('user.frozen.order');
        Route::delete('/user/{user}/frozen-orders/{frozenOrder}', [UserController::class, 'unfrozenOrder'])
            ->middleware('permission:' . $capabilities['customers_manage_frozen_orders'])
            ->name('user.unfrozen.order');
        Route::put('/user/{user}/frozen-orders/{frozenOrder}', [UserController::class, 'updateFrozenOrder'])
            ->middleware('permission:' . $capabilities['customers_manage_frozen_orders'])
            ->name('user.update.frozen.order');
        Route::put('/user/{user}/frozen-orders/{frozenOrder}/image', [UserController::class, 'updateOrderImage'])
            ->middleware('permission:' . $capabilities['customers_manage_frozen_orders'])
            ->name('user.update.frozen.order.image');
        Route::post('/user/plus-money', [UserController::class, 'plus_money'])
            ->middleware('permission:' . $capabilities['customers_adjust_balance'])
            ->name('plus_money');
        Route::get('/user', [UserController::class, 'index'])
            ->middleware('permission:' . $capabilities['customers_view'])
            ->name('user.index');
        Route::get('/user/create', [UserController::class, 'create'])
            ->middleware('permission:' . $capabilities['customers_create'])
            ->name('user.create');
        Route::post('/user', [UserController::class, 'store'])
            ->middleware('permission:' . $capabilities['customers_create'])
            ->name('user.store');
        Route::get('/user/{user}/edit', [UserController::class, 'edit'])
            ->middleware('permission:' . $capabilities['customers_update'])
            ->name('user.edit');
        Route::match(['put', 'patch'], '/user/{user}', [UserController::class, 'update'])
            ->middleware('permission:' . $capabilities['customers_update'])
            ->name('user.update');
        Route::get('/user/{user}', [UserController::class, 'show'])
            ->middleware('permission:' . $capabilities['customers_view_detail'])
            ->name('user.show');
    });

    Route::get('/frozen-order-settings', [\App\Http\Controllers\Admin\FrozenOrderSettingController::class, 'index'])
        ->middleware('permission:' . $capabilities['frozen_order_settings_view'])
        ->name('frozen_order_settings.index');
    Route::post('/frozen-order-settings', [\App\Http\Controllers\Admin\FrozenOrderSettingController::class, 'store'])
        ->middleware('permission:' . $capabilities['frozen_order_settings_update'])
        ->name('frozen_order_settings.store');

    Route::middleware([
        'authorization.context:' . $capabilities['withdrawals_view_all'] . ',' . $capabilities['deposits_view_all'],
    ])->group(function () use ($capabilities) {
        Route::get('/withdraw-transaction', [TransactionHistoryController::class, 'index_withdraw'])
            ->middleware('permission:' . $capabilities['withdrawals_view'])
            ->name('withdraw_transaction');
        Route::post('/confirm-withdraw/{transaction}', [TransactionHistoryController::class, 'confirm_withdraw'])
            ->middleware('permission:' . $capabilities['withdrawals_confirm'])
            ->name('confirm.withdraw');
        Route::post('/cancel-withdraw/{transaction}', [TransactionHistoryController::class, 'cancel_withdraw'])
            ->middleware('permission:' . $capabilities['withdrawals_cancel'])
            ->name('cancel.withdraw');
        Route::get('/change-withdraw-transaction-type/{transaction}', [TransactionHistoryController::class, 'change_withdraw_transaction_type'])
            ->middleware('permission:' . $capabilities['withdrawals_change_type'])
            ->name('change.withdraw.transaction.type');
        Route::get('/deposit-transaction', [TransactionHistoryController::class, 'index_deposit'])
            ->middleware('permission:' . $capabilities['deposits_view'])
            ->name('deposit_transaction');
        Route::delete('/destroy-deposit/{transaction}', [TransactionHistoryController::class, 'destroy_deposit'])
            ->middleware('permission:' . $capabilities['deposits_delete'])
            ->name('destroy.deposit');
        Route::get('/change-deposit-transaction-type/{transaction}', [TransactionHistoryController::class, 'change_deposit_transaction_type'])
            ->middleware('permission:' . $capabilities['deposits_change_type'])
            ->name('change.deposit.transaction.type');
    });

    Route::prefix('lucky-wheel-rewards')->name('lucky_wheel_rewards.')->group(function () use ($capabilities) {
        Route::get('/', [LuckyWheelRewardController::class, 'index'])
            ->middleware('permission:' . $capabilities['lucky_wheel_rewards_view'])
            ->name('index');
        Route::post('/{spin}/approve', [LuckyWheelRewardController::class, 'approve'])
            ->middleware('permission:' . $capabilities['lucky_wheel_rewards_approve'])
            ->name('approve');
        Route::post('/{spin}/reject', [LuckyWheelRewardController::class, 'reject'])
            ->middleware('permission:' . $capabilities['lucky_wheel_rewards_reject'])
            ->name('reject');
        Route::post('/settings/auto-approval', [LuckyWheelRewardController::class, 'updateAutoApproval'])
            ->middleware('permission:' . $capabilities['lucky_wheel_rewards_configure_auto_approval'])
            ->name('auto_approval');
    });

    Route::get('/chat-panel', [ConversationController::class, 'index'])->name('chat-panel');

    Route::get('/order-distributions', [OrderDistributionController::class, 'index'])
        ->middleware('permission:' . $capabilities['order_distributions_view'])
        ->name('order_distributions.index');
    Route::get('/order-distributions/{frozenOrder}', [OrderDistributionController::class, 'show'])
        ->middleware('permission:' . $capabilities['order_distributions_view_detail'])
        ->name('order_distributions.show');
    Route::post('/order-distributions/{frozenOrder}/transition', [OrderDistributionController::class, 'transition'])
        ->middleware('permission:' . $capabilities['order_distributions_transition'])
        ->name('order_distributions.transition');

    Route::middleware(['role:admin|own'])->group(function () use ($capabilities) {
        Route::get('/staffs', [StaffController::class, 'index'])->middleware('permission:' . $capabilities['staff_view'])->name('staffs.index');
        Route::get('/staffs/create', [StaffController::class, 'create'])->middleware('permission:' . $capabilities['staff_create'])->name('staffs.create');
        Route::post('/staffs', [StaffController::class, 'store'])->middleware('permission:' . $capabilities['staff_create'])->name('staffs.store');
        Route::get('/staffs/{staff}/edit', [StaffController::class, 'edit'])->middleware('permission:' . $capabilities['staff_update'])->name('staffs.edit');
        Route::match(['put', 'patch'], '/staffs/{staff}', [StaffController::class, 'update'])->middleware('permission:' . $capabilities['staff_update'])->name('staffs.update');
        Route::get('/staffs/{staff}', [StaffController::class, 'show'])->middleware('permission:' . $capabilities['staff_view_detail'])->name('staffs.show');

        Route::get('/staff', [StaffController::class, 'index'])->middleware('permission:' . $capabilities['staff_view'])->name('staff.index');
        Route::get('/staff/create', [StaffController::class, 'create'])->middleware('permission:' . $capabilities['staff_create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->middleware('permission:' . $capabilities['staff_create'])->name('staff.store');
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->middleware('permission:' . $capabilities['staff_update'])->name('staff.edit');
        Route::match(['put', 'patch'], '/staff/{staff}', [StaffController::class, 'update'])->middleware('permission:' . $capabilities['staff_update'])->name('staff.update');
        Route::get('/staff/{staff}', [StaffController::class, 'show'])->middleware('permission:' . $capabilities['staff_view_detail'])->name('staff.show');
        Route::get('/staff-online-statuses', [StaffController::class, 'getOnlineStatuses'])
            ->middleware('permission:' . $capabilities['staff_view'])
            ->name('staff.online.statuses');
        Route::get('/staff/change-status/{id}', [StaffController::class, 'change_status_staff'])
            ->middleware('permission:' . $capabilities['staff_change_status'])
            ->name('staff.change.status');

        Route::get('/staff/edit-permissions/{id}', [StaffController::class, 'edit_permissions'])
            ->middleware('permission:' . $capabilities['staff_permissions_view'])
            ->name('staff.edit.permissions');
        Route::post('/staff/change-status-permission', [StaffController::class, 'change_status_permission'])
            ->middleware('permission:' . $capabilities['staff_permissions_assign'])
            ->name('staff.change.status.permission');
        Route::post('/staff/change-status-permissions', [StaffController::class, 'change_status_permissions'])
            ->middleware('permission:' . $capabilities['staff_permissions_assign'])
            ->name('staff.change.status.permissions');
    });

    Route::middleware(['role:own'])->group(function () {
        Route::resource('manager_setting', ManagerSettingController::class);
    });

    Route::get('/order-status-timing', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'index'])
        ->middleware('permission:' . $capabilities['order_timing_view'])
        ->name('admin.order_status_timing.index');
    Route::get('/order-status-timing/{orderStatusTiming}/edit', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'edit'])
        ->middleware('permission:' . $capabilities['order_timing_update'])
        ->name('admin.order_status_timing.edit');
    Route::put('/order-status-timing/{orderStatusTiming}', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'update'])
        ->middleware('permission:' . $capabilities['order_timing_update'])
        ->name('admin.order_status_timing.update');
    Route::post('/order-status-timing/update-multiple', [\App\Http\Controllers\Admin\OrderStatusTimingController::class, 'updateMultiple'])
        ->middleware('permission:' . $capabilities['order_timing_update'])
        ->name('admin.order_status_timing.update_multiple');

    Route::middleware(['permission:' . $capabilities['statistics_view_overview']])->group(function () {
        Route::get('tong-doanh-thu', [StatisticalController::class, 'tongDoanhThu'])->name('tong.doanh.thu');
        Route::get('statistical/revenue', [StatisticalController::class, 'tongDoanhThu'])->name('admin.statistical.revenue');
        Route::prefix('statistical')->name('admin.statistical.')->group(function () {
            Route::get('users', [StatisticalController::class, 'userStats'])->name('users');
            Route::get('transactions', [StatisticalController::class, 'transactionStats'])->name('transactions');
            Route::get('monthly-report', [StatisticalController::class, 'monthlyReport'])->name('monthly.report');
        });
    });
    Route::middleware(['permission:' . $capabilities['statistics_view_staff']])->group(function () {
        Route::get('doanh-thu-theo-nhan-vien', [StatisticalController::class, 'doanhThuTheoNhanVien'])->name('doanh.thu.theo.nhan.vien');
        Route::get('staff-list', [StatisticalController::class, 'getStaffList'])->name('api.staff.list');
        Route::get('by-staff', [StatisticalController::class, 'getRevenueByStaff'])->name('api.revenue.by.staff');
        Route::get('detail', [StatisticalController::class, 'getRevenueDetail'])->name('api.revenue.detail');
        Route::get('chart', [StatisticalController::class, 'getRevenueChart'])->name('admin.revenue.chart');
    });
    Route::middleware(['permission:' . $capabilities['statistics_view_customers']])->group(function () {
        Route::get('doanh-thu-tu-khach-hang', [StatisticalController::class, 'doanhThuTuKhachHang'])->name('doanh.thu.tu.khach.hang');
    });
    Route::get('export', [StatisticalController::class, 'exportRevenue'])
        ->middleware(['permission:' . $capabilities['statistics_export'], 'permission:' . $capabilities['statistics_view_staff']])
        ->name('admin.revenue.export');

    Route::middleware(['permission:' . $capabilities['statistics_view_personal']])->group(function () {
    Route::get('doanh-thu-ban-than', [StatisticalController::class, 'doanhThuBanThan'])->name('doanh.thu.ban.than');
    Route::get('/personal-revenue-stats', [StatisticalController::class, 'getPersonalRevenueStats']);
    Route::get('/personal-transactions', [StatisticalController::class, 'getPersonalTransactions']);
    });
});
