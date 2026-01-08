<?php

use App\Models\Order;
use App\Models\User;
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
        if (!Schema::hasTable('frozen_orders')) {
            Schema::create('frozen_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->comment('Người dùng bị áp dụng giá giả');
            $table->foreignIdFor(Order::class)->comment('Đơn hàng bị đóng băng');
            $table->double('custom_price')->nullable()->comment('Giá giả dùng để đóng băng đơn hàng');
            $table->double('commission_percentage')->default(10)->nullable()->comment('Phần trăm hoa hồng (dùng cho đơn đặc biệt, nếu null thì lấy từ orders)');
            $table->boolean('is_frozen')->default(true)->comment('Trạng thái đơn hàng này có đang bị đóng băng với user hay không');
            $table->boolean('commission_paid')->default(false)->comment('Đã cộng tiền hoa hồng cho user chưa');
            $table->boolean('spun')->default(false)->comment('Đã quay đến đơn hàng này chưa');
            // Trạng thái đơn hàng
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'transit', 'shipping', 'delivered', 'completed', 'cancelled'])
                  ->default('pending')
                  ->comment('Trạng thái đơn hàng');
            // Thông tin khách hàng và nền tảng
            $table->json('customer_info')->nullable()->comment('Thông tin khách hàng đặt hàng');
            $table->string('platform')->nullable()->comment('Nền tảng đặt hàng: shopee, lazada, tiktokshop, etc');
            $table->timestamp('order_date')->nullable()->comment('Ngày đặt hàng');
            // Mã vận đơn và thông tin vận chuyển
            $table->string('tracking_number')->nullable()->comment('Mã vận đơn');
            $table->string('shipping_carrier')->nullable()->comment('Đơn vị vận chuyển');
            $table->text('shipping_address')->nullable()->comment('Địa chỉ giao hàng');
            // Thời gian các bước
            $table->timestamp('confirmed_at')->nullable()->comment('Thời gian xác nhận đơn hàng');
            $table->timestamp('preparing_at')->nullable()->comment('Thời gian bắt đầu chuẩn bị hàng hóa');
            $table->timestamp('transit_at')->nullable()->comment('Thời gian bắt đầu trung chuyển');
            $table->timestamp('shipping_at')->nullable()->comment('Thời gian bắt đầu vận chuyển đến khách hàng');
            $table->timestamp('delivered_at')->nullable()->comment('Thời gian đã giao');
            $table->timestamp('completed_at')->nullable()->comment('Thời gian hoàn thành (cộng tiền)');
            $table->timestamp('cancelled_at')->nullable()->comment('Thời gian hủy đơn');
            // Tracking email
            $table->timestamp('reminder_sent_at')->nullable()->comment('Thời gian gửi mail nhắc nhở');
            $table->timestamp('penalty_sent_at')->nullable()->comment('Thời gian gửi mail phạt');
            $table->boolean('reminder_sent')->default(false)->comment('Đã gửi mail nhắc nhở chưa');
            $table->boolean('penalty_sent')->default(false)->comment('Đã gửi mail phạt chưa');
            $table->decimal('penalty_amount', 10, 2)->nullable()->comment('Số tiền phạt (30% giá trị đơn hàng)');
            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frozen_orders');
    }
};
