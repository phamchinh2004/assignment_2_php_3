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
        if (!Schema::hasTable('statuses')) {
            Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->enum('name', [
                'pending',
                'confirmed',
                'preparing',
                'transit',
                'shipping',
                'delivered',
                'completed',
                'cancelled'
            ])->unique()->comment('Tên trạng thái');
            $table->string('display_name')->comment('Tên hiển thị');
            $table->string('color')->nullable()->comment('Màu sắc hiển thị (hex)');
            $table->integer('sort_order')->default(0)->comment('Thứ tự sắp xếp');
            $table->tinyInteger('is_active')->default(1)->comment('Trạng thái: 1-Kích hoạt, 0-Vô hiệu hóa');
            $table->timestamps();

            // Indexes
            $table->index('name');
            $table->index('sort_order');
        });
        }

        // Tạo bảng status_orders
        if (!Schema::hasTable('status_orders')) {
            Schema::create('status_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('frozen_order_id')->comment('ID đơn hàng bị đóng băng');
            $table->unsignedBigInteger('status_id')->comment('ID trạng thái');
            $table->text('notes')->nullable()->comment('Ghi chú thay đổi trạng thái');
            $table->unsignedBigInteger('changed_by')->nullable()->comment('ID người thay đổi trạng thái');
            $table->timestamps();

            // Foreign keys
            $table->foreign('frozen_order_id')->references('id')->on('frozen_orders')->onDelete('cascade');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->nullOnDelete();

            // Indexes
            $table->index('frozen_order_id');
            $table->index('status_id');
            $table->index('created_at');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_orders');
        Schema::dropIfExists('statuses');
    }
};
