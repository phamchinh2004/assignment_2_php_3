<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ManagerSettingController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\RankController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TransactionHistoryController;
use App\Models\Manager_setting;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:admin'])->group(function () {
    Route::resource('staffs', StaffController::class);
});
Route::middleware(['role:staff|admin', 'checkBanned'])->group(function () {
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
    Route::resource('user', UserController::class);
    Route::get('/user/change-status-user/{user}', [UserController::class, 'changeStatusUser'])->name('user.change.status');
    Route::get('/user/frozen-order/{user}', [UserController::class, 'frozenOrderInterface'])->name('user.frozen.order.interface');
    Route::post('/user/frozen-order/{user}', [UserController::class, 'frozenOrder'])->name('user.frozen.order');
    Route::get('/user/edit-frozen-order/{user}/{id}', [UserController::class, 'editFrozenOrderInterface'])->name('user.edit.frozen.order.interface');
    Route::put('/user/edit-frozen-order/{user}/{id}', [UserController::class, 'updateFrozenOrder'])->name('user.update.frozen.order');
    Route::post('/user/plus-money', [UserController::class, 'plus_money'])->name('plus_money');

    Route::get('/withdraw-transaction', [TransactionHistoryController::class, 'index_withdraw'])->name(name: 'withdraw_transaction');
    Route::get('/confirm-withdraw/{transaction}', [TransactionHistoryController::class, 'confirm_withdraw'])->name('confirm.withdraw');
    Route::get('/cancel-withdraw/{transaction}', [TransactionHistoryController::class, 'cancel_withdraw'])->name('cancel.withdraw');
    Route::get('/deposit-transaction', [TransactionHistoryController::class, 'index_deposit'])->name('deposit_transaction');
});
Route::middleware(['role:admin'])->group(function () {
    Route::resource('manager_setting', ManagerSettingController::class);
    Route::resource('staff', StaffController::class);
    Route::get('/staff/change-status/{id}', [StaffController::class, 'change_status_staff'])->name('staff.change.status');
    Route::get('/staff/edit-permissions/{id}', [StaffController::class, 'edit_permissions'])->name('staff.edit.permissions');
    Route::post('/staff/change-status-permission', [StaffController::class, 'change_status_permission'])->name('staff.change.status.permission');
});
