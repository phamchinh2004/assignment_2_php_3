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
            $table->timestamp('reminder_sent_at')->nullable()->comment('Thời gian gửi mail nhắc nhở');
            $table->timestamp('penalty_sent_at')->nullable()->comment('Thời gian gửi mail phạt');
            $table->boolean('reminder_sent')->default(false)->comment('Đã gửi mail nhắc nhở chưa');
            $table->boolean('penalty_sent')->default(false)->comment('Đã gửi mail phạt chưa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->dropColumn(['reminder_sent_at', 'penalty_sent_at', 'reminder_sent', 'penalty_sent']);
        });
    }
};
