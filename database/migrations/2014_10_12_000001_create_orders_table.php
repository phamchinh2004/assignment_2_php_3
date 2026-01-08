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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('index')->comment('Số thứ tự đơn hàng');
            $table->string('order_code')->nullable()->comment('Mã đơn hàng');
            $table->string('image')->nullable()->comment('Hình ảnh đơn hàng');
            $table->string('name')->comment('Tên đơn hàng');
            // Thông tin người đặt hàng
            $table->string('customer_name')->nullable()->comment('Họ tên người đặt hàng');
            $table->string('customer_phone')->nullable()->comment('Số điện thoại người đặt hàng');
            $table->text('customer_address')->nullable()->comment('Địa chỉ nhận hàng');
            $table->text('customer_note')->nullable()->comment('Ghi chú từ khách hàng');
            $table->integer('quantity')->comment('Số lượng');
            $table->double('price')->comment('Giá');
            $table->double('commission_percentage')->nullable()->comment('Phần trăm hoa hồng/chiết khấu');
            // Thông tin thanh toán
            $table->boolean('is_paid')->default(false)->comment('Đã thanh toán hay chưa');
            $table->enum('payment_method', ['COD', 'vnpay', 'momo', 'paypal', 'bank_transfer', 'other'])->nullable()->comment('Hình thức thanh toán');
            $table->tinyInteger(column: 'status')->default(1)->comment('Trạng thái kích hoạt, mặc định là 1 (đã được kích hoạt), 0 là bị khóa');
            $table->foreignIdFor(Rank::class)->comment('Đơn hàng này thuộc rank nào');
            // ID nền tảng bán hàng (tạo cột trước, thêm foreign key constraint sau khi bảng partners được tạo)
            $table->unsignedBigInteger('partner_id')->nullable()->comment('ID nền tảng bán hàng (Shopee, Lazada, TikTok Shop, ...)');
            $table->string('api')->nullable()->comment('Chuỗi API để theo dõi đơn hàng trên nền tảng quản lý');
            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
