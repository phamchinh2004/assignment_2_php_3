<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'lucky_wheel_bonus_spins')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('lucky_wheel_bonus_spins')
                    ->default(0)
                    ->after('count_withdrawals')
                    ->comment('Số lượt quay may mắn admin cấp còn lại');
            });
        }

        // The legacy UNIQUE(user_id, spin_date) is currently also the index
        // MySQL uses for the user_id foreign key. Give that FK its own index
        // before dropping the unique constraint.
        if (!Schema::hasIndex('lucky_wheel_spins', 'lucky_wheel_spins_user_id_index')) {
            Schema::table('lucky_wheel_spins', function (Blueprint $table) {
                $table->index('user_id', 'lucky_wheel_spins_user_id_index');
            });
        }

        if (Schema::hasIndex('lucky_wheel_spins', 'lucky_wheel_spins_user_id_spin_date_unique')) {
            Schema::table('lucky_wheel_spins', function (Blueprint $table) {
                $table->dropUnique('lucky_wheel_spins_user_id_spin_date_unique');
            });
        }

        if (!Schema::hasColumn('lucky_wheel_spins', 'spin_type')) {
            Schema::table('lucky_wheel_spins', function (Blueprint $table) {
                $table->string('spin_type', 32)
                    ->default('daily_completion')
                    ->after('prize')
                    ->comment('daily_completion hoặc admin_bonus');
            });
        }

        if (!Schema::hasIndex('lucky_wheel_spins', 'lucky_wheel_spins_user_date_type_index')) {
            Schema::table('lucky_wheel_spins', function (Blueprint $table) {
                $table->index(
                    ['user_id', 'spin_date', 'spin_type'],
                    'lucky_wheel_spins_user_date_type_index'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lucky_wheel_spins', 'spin_type')) {
            // Bonus spins can create multiple rows for the same user/day.
            // Remove feature-specific history before restoring the legacy key.
            DB::table('lucky_wheel_spins')
                ->where('spin_type', 'admin_bonus')
                ->delete();
        }

        if (Schema::hasIndex('lucky_wheel_spins', 'lucky_wheel_spins_user_date_type_index')) {
            Schema::table('lucky_wheel_spins', function (Blueprint $table) {
                $table->dropIndex('lucky_wheel_spins_user_date_type_index');
            });
        }

        if (Schema::hasColumn('lucky_wheel_spins', 'spin_type')) {
            Schema::table('lucky_wheel_spins', function (Blueprint $table) {
                $table->dropColumn('spin_type');
            });
        }

        if (!Schema::hasIndex('lucky_wheel_spins', 'lucky_wheel_spins_user_id_spin_date_unique')) {
            Schema::table('lucky_wheel_spins', function (Blueprint $table) {
                $table->unique(['user_id', 'spin_date']);
            });
        }

        if (Schema::hasIndex('lucky_wheel_spins', 'lucky_wheel_spins_user_id_index')) {
            Schema::table('lucky_wheel_spins', function (Blueprint $table) {
                $table->dropIndex('lucky_wheel_spins_user_id_index');
            });
        }

        if (Schema::hasColumn('users', 'lucky_wheel_bonus_spins')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('lucky_wheel_bonus_spins');
            });
        }
    }
};
