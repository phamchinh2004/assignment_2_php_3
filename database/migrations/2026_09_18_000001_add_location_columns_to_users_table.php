<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('location_permission', ['prompt', 'granted', 'denied'])
                ->default('prompt')
                ->after('last_seen')
                ->comment('Quyền truy cập vị trí của trình duyệt');
            $table->decimal('location_latitude', 10, 7)->nullable()->after('location_permission');
            $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
            $table->decimal('location_accuracy', 10, 2)->nullable()->after('location_longitude');
            $table->string('location_country_code', 2)->nullable()->after('location_accuracy');
            $table->string('location_country')->nullable()->after('location_country_code');
            $table->string('location_city')->nullable()->after('location_country');
            $table->timestamp('location_updated_at')->nullable()->after('location_city');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'location_permission',
                'location_latitude',
                'location_longitude',
                'location_accuracy',
                'location_country_code',
                'location_country',
                'location_city',
                'location_updated_at',
            ]);
        });
    }
};