<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->foreignId('assigned_by')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
            $table->string('assignment_source', 20)->nullable()->after('assigned_by');
            $table->index(['assignment_source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $table->dropIndex(['assignment_source', 'created_at']);
            $table->dropConstrainedForeignId('assigned_by');
            $table->dropColumn('assignment_source');
        });
    }
};
