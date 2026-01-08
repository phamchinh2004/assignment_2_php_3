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
        Schema::create('order_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('frozen_order_id')->comment('ID frozen order bị báo cáo');
            $table->unsignedBigInteger('order_id')->nullable()->comment('ID order gốc (để join nhanh)');
            $table->unsignedBigInteger('reported_by')->comment('ID người báo cáo (nhân viên)');
            $table->text('reason')->nullable()->comment('Lý do báo cáo đơn hàng ảo');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('pending: chờ xử lý; approved: admin xác nhận đơn ảo (hủy đơn); rejected: admin xác nhận đơn thật (xác nhận đơn)');
            $table->unsignedBigInteger('resolved_by')->nullable()->comment('ID admin xử lý');
            $table->text('resolved_note')->nullable()->comment('Ghi chú xử lý của admin');
            $table->timestamp('resolved_at')->nullable()->comment('Thời điểm xử lý');
            $table->timestamps();

            $table->foreign('frozen_order_id')->references('id')->on('frozen_orders')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('reported_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();

            // Mỗi frozen_order chỉ cho phép 1 báo cáo (tránh spam / trùng)
            $table->unique('frozen_order_id');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_reports');
    }
};


