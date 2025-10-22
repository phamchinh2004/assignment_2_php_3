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
        // Thêm giá trị 'penalty' vào ENUM của cột type
        DB::statement("ALTER TABLE transaction_histories MODIFY COLUMN type ENUM('deposit', 'order', 'profit', 'withdraw', 'penalty') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Xóa giá trị 'penalty' khỏi ENUM
        DB::statement("ALTER TABLE transaction_histories MODIFY COLUMN type ENUM('deposit', 'order', 'profit', 'withdraw') NOT NULL");
    }
};
