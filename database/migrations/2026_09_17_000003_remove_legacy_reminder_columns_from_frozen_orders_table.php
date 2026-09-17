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
        if (!Schema::hasTable('frozen_orders')) {
            return;
        }

        $columns = [
            'reminder_sent_at',
            'penalty_sent_at',
            'reminder_sent',
            'penalty_sent',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('frozen_orders', $column)) {
                Schema::table('frozen_orders', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('frozen_orders')) {
            return;
        }

        Schema::table('frozen_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('frozen_orders', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->comment('Legacy: thời gian gửi mail nhắc nhở');
            }
            if (!Schema::hasColumn('frozen_orders', 'penalty_sent_at')) {
                $table->timestamp('penalty_sent_at')->nullable()->comment('Legacy: thời gian gửi mail phạt');
            }
            if (!Schema::hasColumn('frozen_orders', 'reminder_sent')) {
                $table->boolean('reminder_sent')->default(false)->comment('Legacy: đã gửi mail nhắc nhở chưa');
            }
            if (!Schema::hasColumn('frozen_orders', 'penalty_sent')) {
                $table->boolean('penalty_sent')->default(false)->comment('Legacy: đã gửi mail phạt chưa');
            }
        });
    }
};
