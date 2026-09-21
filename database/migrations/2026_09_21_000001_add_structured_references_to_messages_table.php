<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('kind', 32)->default('text')->after('type')->index();
            $table->string('reference_type', 32)->nullable()->after('image_path');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            $table->json('reference_payload')->nullable()->after('reference_id');
            $table->index(['reference_type', 'reference_id']);
        });

        DB::table('messages')->where('type', 'image')->update(['kind' => 'image']);
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['reference_type', 'reference_id']);
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'reference_type', 'reference_id', 'reference_payload']);
        });
    }
};
