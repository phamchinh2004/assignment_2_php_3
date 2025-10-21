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
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->decimal('penalty_amount', 10, 2)->nullable()->after('penalty_sent')->comment('Số tiền phạt (30% giá trị đơn hàng)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->dropColumn('penalty_amount');
        });
    }
};
