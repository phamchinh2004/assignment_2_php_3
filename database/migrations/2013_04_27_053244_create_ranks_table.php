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
            $table->double('commission_percentage')->comment('Phần trăm hoa hồng khi đơn hàng được phân phối thành công');
            $table->double('upgrade_fee')->default(0)->comment('Phí nâng cấp để lên cấp độ này');
            $table->integer('spin_count')->comment('Số lượt quay trong 1 ngày');
            $table->double('value')->comment('Giá trị cấp độ, nghĩa là tổng số tiền của các đơn hàng của cấp độ này');
            $table->integer('maximum_number_of_withdrawals')->default(1)->comment('Số lần rút tối đa trong 1 ngày');
            $table->double('maximum_withdrawal_amount')->default(100)->comment('Số tiền rút tối đa trong 1 lần rút');
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
