<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill: COD không được là đã thanh toán
        DB::table('orders')
            ->where('payment_method', 'COD')
            ->update(['is_paid' => false]);
    }

    public function down(): void
    {
        // Không thể restore trạng thái is_paid trước đó một cách an toàn.
    }
};


