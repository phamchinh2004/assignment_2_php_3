<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->string('snapshot_customer_name')->nullable()->after('snapshot_api');
            $table->string('snapshot_customer_phone')->nullable()->after('snapshot_customer_name');
            $table->text('snapshot_customer_address')->nullable()->after('snapshot_customer_phone');
            $table->text('snapshot_customer_note')->nullable()->after('snapshot_customer_address');
            $table->string('snapshot_source', 32)->nullable()->after('snapshot_customer_note');
            $table->timestamp('snapshot_captured_at')->nullable()->after('snapshot_source');
            $table->timestamp('snapshot_restored_at')->nullable()->after('snapshot_captured_at');
            $table->unsignedBigInteger('snapshot_restored_by')->nullable()->after('snapshot_restored_at');

            $table->foreign('snapshot_restored_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->dropForeign(['snapshot_restored_by']);
            $table->dropColumn([
                'snapshot_customer_name',
                'snapshot_customer_phone',
                'snapshot_customer_address',
                'snapshot_customer_note',
                'snapshot_source',
                'snapshot_captured_at',
                'snapshot_restored_at',
                'snapshot_restored_by',
            ]);
        });
    }
};
