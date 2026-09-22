<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('approx_location_country_code', 2)->nullable()->after('location_updated_at');
            $table->string('approx_location_country')->nullable()->after('approx_location_country_code');
            $table->timestamp('approx_location_updated_at')->nullable()->after('approx_location_country');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'approx_location_country_code',
                'approx_location_country',
                'approx_location_updated_at',
            ]);
        });
    }
};
