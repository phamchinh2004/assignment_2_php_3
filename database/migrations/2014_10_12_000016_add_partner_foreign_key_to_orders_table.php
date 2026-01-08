<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm foreign key constraint cho partner_id sau khi bảng partners đã được tạo.
     */
    public function up(): void
    {
        // Chỉ thêm foreign key constraint nếu cả hai bảng đều tồn tại
        if (Schema::hasTable('orders') && Schema::hasTable('partners') && Schema::hasColumn('orders', 'partner_id')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreign('partner_id')
                          ->references('id')
                          ->on('partners')
                          ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Bỏ qua nếu foreign key đã tồn tại hoặc có lỗi khác
                // (ví dụ: trên một số hệ thống đã có constraint với tên khác)
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'partner_id')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropForeign(['partner_id']);
                });
            } catch (\Exception $e) {
                // Bỏ qua nếu foreign key không tồn tại
            }
        }
    }
};
