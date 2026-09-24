<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('chat_quick_messages') || Schema::hasColumn('chat_quick_messages', 'is_deleted')) {
            return;
        }

        Schema::table('chat_quick_messages', function (Blueprint $table) {
            $table->boolean('is_deleted')->default(false)->after('content');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('chat_quick_messages') || !Schema::hasColumn('chat_quick_messages', 'is_deleted')) {
            return;
        }

        Schema::table('chat_quick_messages', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
    }
};
