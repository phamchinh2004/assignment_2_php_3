<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kiểm tra bảng và cột có tồn tại không trước khi cập nhật
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_method') && Schema::hasColumn('orders', 'is_paid')) {
            // Backfill: COD không được là đã thanh toán
            DB::table('orders')
                ->where('payment_method', 'COD')
                ->update(['is_paid' => false]);
        }
    }

    public function down(): void
    {
        // Không thể restore trạng thái is_paid trước đó một cách an toàn.
    }
};


