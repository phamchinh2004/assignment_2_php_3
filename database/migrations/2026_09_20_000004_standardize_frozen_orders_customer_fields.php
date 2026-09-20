<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('frozen_orders')) {
            // 1. Backfill compatibility: đảm bảo mọi dữ liệu cũ từ customer_info / shipping_address
            // được sao chép vào snapshot_customer_* trước khi drop cột.
            $orders = DB::table('frozen_orders')->get([
                'id',
                'customer_info',
                'shipping_address',
                'snapshot_customer_name',
                'snapshot_customer_phone',
                'snapshot_customer_address',
                'snapshot_customer_note',
            ]);

            foreach ($orders as $order) {
                $updates = [];
                $info = [];
                if (!empty($order->customer_info)) {
                    $decoded = json_decode($order->customer_info, true);
                    if (is_array($decoded)) {
                        $info = $decoded;
                    }
                }

                if ($order->snapshot_customer_name === null && !empty($info['name'])) {
                    $updates['snapshot_customer_name'] = $info['name'];
                }
                if ($order->snapshot_customer_phone === null && !empty($info['phone'])) {
                    $updates['snapshot_customer_phone'] = $info['phone'];
                }
                if ($order->snapshot_customer_address === null) {
                    if (!empty($info['address'])) {
                        $updates['snapshot_customer_address'] = $info['address'];
                    } elseif (!empty($order->shipping_address)) {
                        $updates['snapshot_customer_address'] = $order->shipping_address;
                    }
                }
                if ($order->snapshot_customer_note === null && isset($info['note']) && $info['note'] !== '') {
                    $updates['snapshot_customer_note'] = $info['note'];
                }

                if (!empty($updates)) {
                    DB::table('frozen_orders')->where('id', $order->id)->update($updates);
                }
            }

            // 2. Drop các cột legacy trùng lặp
            Schema::table('frozen_orders', function (Blueprint $table) {
                $columnsToDrop = [];
                if (Schema::hasColumn('frozen_orders', 'customer_info')) {
                    $columnsToDrop[] = 'customer_info';
                }
                if (Schema::hasColumn('frozen_orders', 'shipping_address')) {
                    $columnsToDrop[] = 'shipping_address';
                }
                if (!empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('frozen_orders')) {
            Schema::table('frozen_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('frozen_orders', 'customer_info')) {
                    $table->json('customer_info')->nullable()->comment('Thông tin khách hàng đặt hàng');
                }
                if (!Schema::hasColumn('frozen_orders', 'shipping_address')) {
                    $table->text('shipping_address')->nullable()->comment('Địa chỉ giao hàng');
                }
            });

            // Khôi phục dữ liệu customer_info từ snapshot_customer_*
            $orders = DB::table('frozen_orders')->get([
                'id',
                'snapshot_customer_name',
                'snapshot_customer_phone',
                'snapshot_customer_address',
                'snapshot_customer_note',
            ]);

            foreach ($orders as $order) {
                $info = [
                    'name' => $order->snapshot_customer_name,
                    'phone' => $order->snapshot_customer_phone,
                    'address' => $order->snapshot_customer_address,
                    'note' => $order->snapshot_customer_note,
                ];

                DB::table('frozen_orders')->where('id', $order->id)->update([
                    'customer_info' => json_encode($info, JSON_UNESCAPED_UNICODE),
                    'shipping_address' => $order->snapshot_customer_address,
                ]);
            }
        }
    }
};
