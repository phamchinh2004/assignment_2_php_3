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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'warehouse_area')) {
                $table->string('warehouse_area', 191)
                    ->nullable()
                    ->after('clone_account')
                    ->comment('Khu vực phân phối/kho');
            }
            if (!Schema::hasColumn('users', 'warehouse_address')) {
                $table->text('warehouse_address')
                    ->nullable()
                    ->after('warehouse_area')
                    ->comment('Địa chỉ kho hiện tại');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'warehouse_address')) {
                $table->dropColumn('warehouse_address');
            }
            if (Schema::hasColumn('users', 'warehouse_area')) {
                $table->dropColumn('warehouse_area');
            }
        });
    }
};

