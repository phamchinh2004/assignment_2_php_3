<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_balance_histories', function (Blueprint $table) {
            $table->decimal('balance_before', 18, 6)
                ->nullable()
                ->after('initial_balance')
                ->comment('Tổng số dư khả dụng và đóng băng trước giao dịch');
            $table->decimal('balance_after', 18, 6)
                ->nullable()
                ->after('balance_before')
                ->comment('Tổng số dư khả dụng và đóng băng sau giao dịch');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_balance_histories', function (Blueprint $table) {
            $table->dropColumn(['balance_before', 'balance_after']);
        });
    }
};
