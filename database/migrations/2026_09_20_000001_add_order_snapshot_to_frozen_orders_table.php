<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->string('snapshot_order_code')->nullable()->after('order_id');
            $table->unsignedInteger('snapshot_order_index')->nullable()->after('snapshot_order_code');
            $table->string('snapshot_name')->nullable()->after('snapshot_order_index');
            $table->string('snapshot_image')->nullable()->after('snapshot_name');
            $table->unsignedInteger('snapshot_quantity')->nullable()->after('snapshot_image');
            $table->decimal('snapshot_unit_price', 20, 6)->nullable()->after('snapshot_quantity');
            $table->decimal('snapshot_order_amount', 20, 6)->nullable()->after('snapshot_unit_price');
            $table->decimal('snapshot_commission_amount', 20, 6)->nullable()->after('commission_percentage');
            $table->string('snapshot_payment_method')->nullable()->after('snapshot_commission_amount');
            $table->boolean('snapshot_is_paid')->nullable()->after('snapshot_payment_method');
            $table->string('snapshot_partner_name')->nullable()->after('snapshot_is_paid');
            $table->string('snapshot_api')->nullable()->after('snapshot_partner_name');
        });
    }

    public function down(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->dropColumn([
                'snapshot_order_code', 'snapshot_order_index', 'snapshot_name', 'snapshot_image',
                'snapshot_quantity', 'snapshot_unit_price', 'snapshot_order_amount',
                'snapshot_commission_amount', 'snapshot_payment_method', 'snapshot_is_paid',
                'snapshot_partner_name', 'snapshot_api',
            ]);
        });
    }
};
