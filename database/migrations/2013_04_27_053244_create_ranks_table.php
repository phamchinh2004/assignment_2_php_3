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
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable()->comment('Hình ảnh cấp độ');
            $table->string('name')->comment('Tên cấp độ');
            $table->double('commission_percentage')->comment('Phần trăm hoa hồng');
            $table->double('upgrade_fee')->default(0)->comment('Phí nâng cấp');
            $table->integer('spin_count')->comment('Số lượt quay');
            $table->double('value')->comment('Giá trị cấp độ');
            $table->integer('maximum_number_of_withdrawals')->default(1)->comment('Số lần rút tối đa');
            $table->double('maximum_withdrawal_amount')->default(100)->comment('Số tiền rút tối đa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranks');
    }
};
