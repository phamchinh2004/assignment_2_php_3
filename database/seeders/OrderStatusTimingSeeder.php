<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OrderStatusTiming;

class OrderStatusTimingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timings = [
            [
                'from_status' => 'confirmed',
                'to_status' => 'preparing',
                'min_time' => 5,
                'max_time' => 10,
                'time_unit' => 'minutes',
                'description' => 'Thời gian từ khi xác nhận đến khi bắt đầu chuẩn bị hàng hóa',
                'is_active' => 1,
            ],
            [
                'from_status' => 'preparing',
                'to_status' => 'transit',
                'min_time' => 1,
                'max_time' => 3,
                'time_unit' => 'hours',
                'description' => 'Thời gian từ khi chuẩn bị hàng đến khi trung chuyển',
                'is_active' => 1,
            ],
            [
                'from_status' => 'transit',
                'to_status' => 'shipping',
                'min_time' => 12,
                'max_time' => 48,
                'time_unit' => 'hours',
                'description' => 'Thời gian từ khi trung chuyển đến khi vận chuyển đến khách hàng (12h - 2 ngày)',
                'is_active' => 1,
            ],
            [
                'from_status' => 'shipping',
                'to_status' => 'delivered',
                'min_time' => 6,
                'max_time' => 12,
                'time_unit' => 'hours',
                'description' => 'Thời gian từ khi vận chuyển đến khi giao hàng',
                'is_active' => 1,
            ],
            [
                'from_status' => 'delivered',
                'to_status' => 'completed',
                'min_time' => 14,
                'max_time' => 14,
                'time_unit' => 'days',
                'description' => 'Thời gian từ khi giao hàng đến khi hoàn thành (cộng tiền hoa hồng)',
                'is_active' => 1,
            ],
        ];

        foreach ($timings as $timing) {
            OrderStatusTiming::updateOrCreate(
                [
                    'from_status' => $timing['from_status'],
                    'to_status' => $timing['to_status'],
                ],
                $timing
            );
        }
    }
}
