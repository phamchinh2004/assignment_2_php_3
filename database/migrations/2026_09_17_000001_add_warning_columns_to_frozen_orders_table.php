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
        if (!Schema::hasTable('frozen_orders')) {
            return;
        }

        Schema::table('frozen_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('frozen_orders', 'processing_time_limit')) {
                $table->unsignedInteger('processing_time_limit')->default(24)->comment('Tổng thời gian xử lý đơn hàng theo giờ');
            }
            if (!Schema::hasColumn('frozen_orders', 'notification_1_remaining_time')) {
                $table->unsignedInteger('notification_1_remaining_time')->default(12)->comment('Thời gian còn lại trước deadline để gửi email cảnh báo lần 1');
            }
            if (!Schema::hasColumn('frozen_orders', 'notification_2_remaining_time')) {
                $table->unsignedInteger('notification_2_remaining_time')->default(1)->comment('Thời gian còn lại trước deadline để gửi email cảnh báo lần 2');
            }
            if (!Schema::hasColumn('frozen_orders', 'notification_1_sent_at')) {
                $table->timestamp('notification_1_sent_at')->nullable()->comment('Thời điểm gửi cảnh báo lần 1');
            }
            if (!Schema::hasColumn('frozen_orders', 'notification_2_sent_at')) {
                $table->timestamp('notification_2_sent_at')->nullable()->comment('Thời điểm gửi cảnh báo lần 2');
            }
            if (!Schema::hasColumn('frozen_orders', 'penalty_notification_sent_at')) {
                $table->timestamp('penalty_notification_sent_at')->nullable()->comment('Thời điểm gửi email phạt');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $columns = [
                'processing_time_limit',
                'notification_1_remaining_time',
                'notification_2_remaining_time',
                'notification_1_sent_at',
                'notification_2_sent_at',
                'penalty_notification_sent_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('frozen_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
