<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('order_status_timings')) {
            Schema::create('order_status_timings', function (Blueprint $table) {
                $table->id();
                $table->string('from_status')->comment('Trạng thái bắt đầu');
                $table->string('to_status')->comment('Trạng thái đích');
                $table->integer('min_time')->comment('Thời gian tối thiểu (phút)');
                $table->integer('max_time')->comment('Thời gian tối đa (phút)');
                $table->string('time_unit')->default('minutes')->comment('Đơn vị thời gian: minutes, hours, days');
                $table->text('description')->nullable()->comment('Mô tả');
                $table->tinyInteger('is_active')->default(1)->comment('Trạng thái: 1-Kích hoạt, 0-Vô hiệu hóa');
                $table->timestamps();

                $table->unique(['from_status', 'to_status']);
                $table->index('from_status');
                $table->index('to_status');
                $table->index('is_active');
            });
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_timings');
    }
};
