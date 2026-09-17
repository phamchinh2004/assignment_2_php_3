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
        if (!Schema::hasTable('frozen_order_settings')) {
            Schema::create('frozen_order_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('processing_time_limit')->default(24)->comment('Tổng thời gian xử lý đơn hàng theo giờ');
                $table->unsignedInteger('notification_1_remaining_time')->default(12)->comment('Thời gian cảnh báo lần 1 còn lại trước deadline');
                $table->unsignedInteger('notification_2_remaining_time')->default(1)->comment('Thời gian cảnh báo lần 2 còn lại trước deadline');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frozen_order_settings');
    }
};
