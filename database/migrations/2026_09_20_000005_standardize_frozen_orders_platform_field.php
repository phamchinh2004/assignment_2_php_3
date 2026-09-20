<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('frozen_orders')) {
            // 1. Backfill compatibility: copy platform sang snapshot_partner_name nếu snapshot_partner_name null
            if (Schema::hasColumn('frozen_orders', 'platform') && Schema::hasColumn('frozen_orders', 'snapshot_partner_name')) {
                DB::table('frozen_orders')
                    ->whereNull('snapshot_partner_name')
                    ->whereNotNull('platform')
                    ->where('platform', '!=', '')
                    ->update([
                        'snapshot_partner_name' => DB::raw('platform')
                    ]);
            }

            // 2. Drop cột platform
            if (Schema::hasColumn('frozen_orders', 'platform')) {
                Schema::table('frozen_orders', function (Blueprint $table) {
                    $table->dropColumn('platform');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('frozen_orders')) {
            if (!Schema::hasColumn('frozen_orders', 'platform')) {
                Schema::table('frozen_orders', function (Blueprint $table) {
                    $table->string('platform')->nullable()->after('snapshot_partner_name')->comment('Nền tảng đặt hàng: shopee, lazada, tiktokshop, etc');
                });
            }

            // Khôi phục giá trị platform từ snapshot_partner_name
            DB::table('frozen_orders')
                ->whereNotNull('snapshot_partner_name')
                ->update([
                    'platform' => DB::raw('snapshot_partner_name')
                ]);
        }
    }
};
