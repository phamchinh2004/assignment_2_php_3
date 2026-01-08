<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'frozen_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->double('frozen_balance')->default(0)->after('balance')->comment('Số dư đóng băng khi nhận đơn hàng đặc biệt');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'frozen_balance')) {
                $table->dropColumn('frozen_balance');
            }
        });
    }
};
