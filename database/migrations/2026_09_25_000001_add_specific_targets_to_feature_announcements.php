<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_announcements', function (Blueprint $table) {
            $table->string('target_type', 20)
                ->default('roles')
                ->after('target_roles')
                ->index();
        });

        Schema::create('feature_announcement_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')
                ->constrained('feature_announcements')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['announcement_id', 'user_id'],
                'feature_announcement_targets_unique'
            );
            $table->index('user_id', 'feature_announcement_targets_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_announcement_targets');

        Schema::table('feature_announcements', function (Blueprint $table) {
            $table->dropIndex('feature_announcements_target_type_index');
            $table->dropColumn('target_type');
        });
    }
};
