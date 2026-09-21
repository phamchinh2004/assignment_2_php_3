<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('frozen_orders') || Schema::hasColumn('frozen_orders', 'processing_started_at')) {
            return;
        }

        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->timestamp('processing_started_at')
                ->nullable()
                ->after('spun')
                ->comment('Mốc bắt đầu tính thời hạn xử lý, không thay đổi theo updated_at');
        });

        DB::table('frozen_orders')
            ->where('spun', true)
            ->whereNull('processing_started_at')
            ->update([
                'processing_started_at' => DB::raw('COALESCE(updated_at, order_date, created_at)'),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('frozen_orders') || !Schema::hasColumn('frozen_orders', 'processing_started_at')) {
            return;
        }

        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->dropColumn('processing_started_at');
        });
    }
};
