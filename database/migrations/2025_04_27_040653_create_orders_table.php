<?php

use App\Models\Rank;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('index')->comment('Số thứ tự đơn hàng');
            $table->string('order_code')->nullable()->comment('Mã đơn hàng');
            $table->string('image')->nullable()->comment('Hình ảnh đơn hàng');
            $table->string('name')->comment('Tên đơn hàng');
            $table->integer('quantity')->comment('Số lượng');
            $table->double('price')->comment('Giá');
            $table->double('commission_percentage')->nullable()->comment('Phần trăm hoa hồng/chiết khấu');
            $table->tinyInteger(column: 'status')->default(1)->comment('Trạng thái kích hoạt, mặc định là 1 (đã được kích hoạt), 0 là bị khóa');
            $table->foreignIdFor(Rank::class)->comment('Đơn hàng này thuộc rank nào');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
