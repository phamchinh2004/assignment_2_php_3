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
        if (!Schema::hasTable('orders')) {
            return;
        }

        // Kiểm tra và thêm từng cột nếu chưa có
        if (!Schema::hasColumn('orders', 'customer_name')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('customer_name')->nullable()->after('name')->comment('Họ tên người đặt hàng');
            });
        }

        if (!Schema::hasColumn('orders', 'customer_phone')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('customer_phone')->nullable()->after('customer_name')->comment('Số điện thoại người đặt hàng');
            });
        }

        if (!Schema::hasColumn('orders', 'customer_address')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('customer_address')->nullable()->after('customer_phone')->comment('Địa chỉ nhận hàng');
            });
        }

        if (!Schema::hasColumn('orders', 'customer_note')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('customer_note')->nullable()->after('customer_address')->comment('Ghi chú từ khách hàng');
            });
        }

        if (!Schema::hasColumn('orders', 'is_paid')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->boolean('is_paid')->default(false)->after('commission_percentage')->comment('Đã thanh toán hay chưa');
            });
        }

        if (!Schema::hasColumn('orders', 'payment_method')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->enum('payment_method', ['COD', 'vnpay', 'momo', 'paypal', 'bank_transfer', 'other'])
                      ->nullable()
                      ->after('is_paid')
                      ->comment('Hình thức thanh toán');
            });
        }

        if (!Schema::hasColumn('orders', 'partner_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('partner_id')->nullable()->after('rank_id')->comment('ID nền tảng bán hàng (Shopee, Lazada, TikTok Shop, ...)');
            });
        }

        if (!Schema::hasColumn('orders', 'api')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('api')->nullable()->after('partner_id')->comment('Chuỗi API để theo dõi đơn hàng trên nền tảng quản lý');
            });
        }
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
