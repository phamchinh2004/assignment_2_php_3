<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->after('id');
        });

        DB::table('conversations')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($conversations) {
                foreach ($conversations as $conversation) {
                    DB::table('conversations')
                        ->where('id', $conversation->id)
                        ->update(['public_id' => (string) Str::uuid()]);
                }
            });

        Schema::table('conversations', function (Blueprint $table) {
            $table->uuid('public_id')->nullable(false)->change();
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
