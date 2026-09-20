<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Chuyển đổi charset bảng frozen_orders và các cột text sang utf8mb4
     * để hỗ trợ ký tự multibyte (tiếng Nhật, Trung, emoji, v.v.)
     */
    public function up(): void
    {
        // Chuyển charset toàn bảng
        DB::statement('ALTER TABLE `frozen_orders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `frozen_orders` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    }
};
