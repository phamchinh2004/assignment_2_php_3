<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->decimal('settled_order_amount', 20, 6)->nullable()->after('snapshot_restored_by');
            $table->decimal('settled_commission_amount', 20, 6)->nullable()->after('settled_order_amount');
            $table->decimal('settled_penalty_amount', 20, 6)->nullable()->after('settled_commission_amount');
            $table->decimal('settled_refund_amount', 20, 6)->nullable()->after('settled_penalty_amount');
            $table->string('settled_balance_destination', 32)->nullable()->after('settled_refund_amount');
            $table->timestamp('settled_at')->nullable()->after('settled_balance_destination');
        });
    }

    public function down(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->dropColumn([
                'settled_order_amount',
                'settled_commission_amount',
                'settled_penalty_amount',
                'settled_refund_amount',
                'settled_balance_destination',
                'settled_at',
            ]);
        });
    }
};
