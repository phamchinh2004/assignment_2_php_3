<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('wallet_balance_histories', 'assigned_staff_id')) {
            Schema::table('wallet_balance_histories', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_staff_id')
                    ->nullable()
                    ->after('by_user_id')
                    ->index()
                    ->comment('Nhân viên phụ trách khách hàng tại thời điểm phát sinh giao dịch');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('wallet_balance_histories', 'assigned_staff_id')) {
            Schema::table('wallet_balance_histories', function (Blueprint $table) {
                $table->dropIndex(['assigned_staff_id']);
                $table->dropColumn('assigned_staff_id');
            });
        }
    }
};
