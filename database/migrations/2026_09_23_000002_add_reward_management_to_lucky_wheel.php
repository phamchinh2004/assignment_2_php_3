<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lucky_wheel_spins', function (Blueprint $table) {
            if (!Schema::hasColumn('lucky_wheel_spins', 'prize_index')) {
                $table->unsignedTinyInteger('prize_index')->nullable()->after('prize');
            }
            if (!Schema::hasColumn('lucky_wheel_spins', 'reward_type')) {
                $table->string('reward_type', 24)->default('legacy')->after('spin_type');
            }
            if (!Schema::hasColumn('lucky_wheel_spins', 'reward_amount')) {
                $table->decimal('reward_amount', 15, 2)->nullable()->after('reward_type');
            }
            if (!Schema::hasColumn('lucky_wheel_spins', 'reward_status')) {
                $table->string('reward_status', 24)->default('legacy')->after('reward_amount');
            }
            if (!Schema::hasColumn('lucky_wheel_spins', 'approval_method')) {
                $table->string('approval_method', 24)->nullable()->after('reward_status');
            }
            if (!Schema::hasColumn('lucky_wheel_spins', 'handled_by')) {
                $table->foreignId('handled_by')
                    ->nullable()
                    ->after('approval_method')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('lucky_wheel_spins', 'handled_at')) {
                $table->timestamp('handled_at')->nullable()->after('handled_by');
            }
            if (!Schema::hasColumn('lucky_wheel_spins', 'wallet_balance_history_id')) {
                $table->foreignId('wallet_balance_history_id')
                    ->nullable()
                    ->after('handled_at')
                    ->constrained('wallet_balance_histories')
                    ->nullOnDelete();
            }
        });

        if (!Schema::hasIndex('lucky_wheel_spins', 'lucky_wheel_spins_reward_status_index')) {
            Schema::table('lucky_wheel_spins', function (Blueprint $table) {
                $table->index('reward_status', 'lucky_wheel_spins_reward_status_index');
            });
        }

        if (!Schema::hasTable('lucky_wheel_settings')) {
            Schema::create('lucky_wheel_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('auto_approve_rewards')->default(false);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        DB::table('lucky_wheel_settings')->updateOrInsert(
            ['id' => 1],
            [
                'auto_approve_rewards' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('lucky_wheel_settings');

        if (Schema::hasIndex('lucky_wheel_spins', 'lucky_wheel_spins_reward_status_index')) {
            Schema::table('lucky_wheel_spins', function (Blueprint $table) {
                $table->dropIndex('lucky_wheel_spins_reward_status_index');
            });
        }

        Schema::table('lucky_wheel_spins', function (Blueprint $table) {
            if (Schema::hasColumn('lucky_wheel_spins', 'wallet_balance_history_id')) {
                $table->dropConstrainedForeignId('wallet_balance_history_id');
            }
            if (Schema::hasColumn('lucky_wheel_spins', 'handled_by')) {
                $table->dropConstrainedForeignId('handled_by');
            }

            $columns = [
                'prize_index',
                'reward_type',
                'reward_amount',
                'reward_status',
                'approval_method',
                'handled_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('lucky_wheel_spins', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
