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
        Schema::table('frozen_orders', function (Blueprint $table) {
            // Thêm cột status nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'status')) {
                $table->enum('status', ['pending', 'confirmed', 'preparing', 'transit', 'shipping', 'delivered', 'completed', 'cancelled'])
                      ->default('pending')
                      ->nullable()
                      ->after('spun')
                      ->comment('Trạng thái đơn hàng');
            }

            // Thêm cột customer_info nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'customer_info')) {
                $table->json('customer_info')->nullable()->after('status')->comment('Thông tin khách hàng đặt hàng');
            }

            // Thêm cột platform nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'platform')) {
                $table->string('platform')->nullable()->after('customer_info')->comment('Nền tảng đặt hàng: shopee, lazada, tiktokshop, etc');
            }

            // Thêm cột order_date nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'order_date')) {
                $table->timestamp('order_date')->nullable()->after('platform')->comment('Ngày đặt hàng');
            }

            // Thêm cột tracking_number nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('order_date')->comment('Mã vận đơn');
            }

            // Thêm cột shipping_carrier nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'shipping_carrier')) {
                $table->string('shipping_carrier')->nullable()->after('tracking_number')->comment('Đơn vị vận chuyển');
            }

            // Thêm cột shipping_address nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'shipping_address')) {
                $table->text('shipping_address')->nullable()->after('shipping_carrier')->comment('Địa chỉ giao hàng');
            }

            // Thêm cột confirmed_at nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('shipping_address')->comment('Thời gian xác nhận đơn hàng');
            }

            // Thêm cột preparing_at nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'preparing_at')) {
                $table->timestamp('preparing_at')->nullable()->after('confirmed_at')->comment('Thời gian bắt đầu chuẩn bị hàng hóa');
            }

            // Thêm cột transit_at nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'transit_at')) {
                $table->timestamp('transit_at')->nullable()->after('preparing_at')->comment('Thời gian bắt đầu trung chuyển');
            }

            // Thêm cột shipping_at nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'shipping_at')) {
                $table->timestamp('shipping_at')->nullable()->after('transit_at')->comment('Thời gian bắt đầu vận chuyển đến khách hàng');
            }

            // Thêm cột delivered_at nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('shipping_at')->comment('Thời gian đã giao');
            }

            // Thêm cột completed_at nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('delivered_at')->comment('Thời gian hoàn thành (cộng tiền)');
            }

            // Thêm cột cancelled_at nếu chưa có
            if (!Schema::hasColumn('frozen_orders', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('completed_at')->comment('Thời gian hủy đơn');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('frozen_orders', function (Blueprint $table) {
            $columns = [
                'status',
                'customer_info',
                'platform',
                'order_date',
                'tracking_number',
                'shipping_carrier',
                'shipping_address',
                'confirmed_at',
                'preparing_at',
                'transit_at',
                'shipping_at',
                'delivered_at',
                'completed_at',
                'cancelled_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('frozen_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
