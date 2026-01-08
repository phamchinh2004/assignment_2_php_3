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
        Schema::table('orders', function (Blueprint $table) {
            // Thêm cột customer_name nếu chưa có
            if (!Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('name')->comment('Họ tên người đặt hàng');
            }

            // Thêm cột customer_phone nếu chưa có
            if (!Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone')->nullable()->after('customer_name')->comment('Số điện thoại người đặt hàng');
            }

            // Thêm cột customer_address nếu chưa có
            if (!Schema::hasColumn('orders', 'customer_address')) {
                $table->text('customer_address')->nullable()->after('customer_phone')->comment('Địa chỉ nhận hàng');
            }

            // Thêm cột customer_note nếu chưa có
            if (!Schema::hasColumn('orders', 'customer_note')) {
                $table->text('customer_note')->nullable()->after('customer_address')->comment('Ghi chú từ khách hàng');
            }

            // Thêm cột is_paid nếu chưa có
            if (!Schema::hasColumn('orders', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('commission_percentage')->comment('Đã thanh toán hay chưa');
            }

            // Thêm cột payment_method nếu chưa có
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->enum('payment_method', ['COD', 'vnpay', 'momo', 'paypal', 'bank_transfer', 'other'])
                      ->nullable()
                      ->after('is_paid')
                      ->comment('Hình thức thanh toán');
            }

            // Thêm cột partner_id nếu chưa có
            if (!Schema::hasColumn('orders', 'partner_id')) {
                $table->unsignedBigInteger('partner_id')->nullable()->after('rank_id')->comment('ID nền tảng bán hàng (Shopee, Lazada, TikTok Shop, ...)');
            }

            // Thêm cột api nếu chưa có
            if (!Schema::hasColumn('orders', 'api')) {
                $table->string('api')->nullable()->after('partner_id')->comment('Chuỗi API để theo dõi đơn hàng trên nền tảng quản lý');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = [
                'customer_name',
                'customer_phone',
                'customer_address',
                'customer_note',
                'is_paid',
                'payment_method',
                'partner_id',
                'api'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
