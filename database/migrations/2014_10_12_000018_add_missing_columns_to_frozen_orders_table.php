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
        if (!Schema::hasTable('frozen_orders')) {
            return;
        }

        // Kiểm tra và thêm từng cột nếu chưa có
        if (!Schema::hasColumn('frozen_orders', 'status')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->enum('status', ['pending', 'confirmed', 'preparing', 'transit', 'shipping', 'delivered', 'completed', 'cancelled'])
                      ->default('pending')
                      ->nullable()
                      ->after('spun')
                      ->comment('Trạng thái đơn hàng');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'customer_info')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->json('customer_info')->nullable()->comment('Thông tin khách hàng đặt hàng');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'platform')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->string('platform')->nullable()->comment('Nền tảng đặt hàng: shopee, lazada, tiktokshop, etc');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'order_date')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->timestamp('order_date')->nullable()->comment('Ngày đặt hàng');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'tracking_number')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->string('tracking_number')->nullable()->comment('Mã vận đơn');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'shipping_carrier')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->string('shipping_carrier')->nullable()->comment('Đơn vị vận chuyển');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'shipping_address')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->text('shipping_address')->nullable()->comment('Địa chỉ giao hàng');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'confirmed_at')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->timestamp('confirmed_at')->nullable()->comment('Thời gian xác nhận đơn hàng');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'preparing_at')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->timestamp('preparing_at')->nullable()->comment('Thời gian bắt đầu chuẩn bị hàng hóa');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'transit_at')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->timestamp('transit_at')->nullable()->comment('Thời gian bắt đầu trung chuyển');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'shipping_at')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->timestamp('shipping_at')->nullable()->comment('Thời gian bắt đầu vận chuyển đến khách hàng');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'delivered_at')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->timestamp('delivered_at')->nullable()->comment('Thời gian đã giao');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'completed_at')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->timestamp('completed_at')->nullable()->comment('Thời gian hoàn thành (cộng tiền)');
            });
        }

        if (!Schema::hasColumn('frozen_orders', 'cancelled_at')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                $table->timestamp('cancelled_at')->nullable()->comment('Thời gian hủy đơn');
            });
        }
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
