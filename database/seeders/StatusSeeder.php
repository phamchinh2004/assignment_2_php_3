<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'pending',
                'display_name' => 'Chờ xử lý',
                'color' => '#FF9800',
                'sort_order' => 1,
                'is_active' => 1,
            ],
            [
                'name' => 'confirmed',
                'display_name' => 'Đã xác nhận',
                'color' => '#2196F3',
                'sort_order' => 2,
                'is_active' => 1,
            ],
            [
                'name' => 'preparing',
                'display_name' => 'Đang chuẩn bị hàng hóa',
                'color' => '#9C27B0',
                'sort_order' => 3,
                'is_active' => 1,
            ],
            [
                'name' => 'transit',
                'display_name' => 'Đang trung chuyển',
                'color' => '#673AB7',
                'sort_order' => 4,
                'is_active' => 1,
            ],
            [
                'name' => 'shipping',
                'display_name' => 'Đang vận chuyển đến khách hàng',
                'color' => '#3F51B5',
                'sort_order' => 5,
                'is_active' => 1,
            ],
            [
                'name' => 'delivered',
                'display_name' => 'Đã giao hàng',
                'color' => '#4CAF50',
                'sort_order' => 6,
                'is_active' => 1,
            ],
            [
                'name' => 'completed',
                'display_name' => 'Hoàn thành',
                'color' => '#8BC34A',
                'sort_order' => 7,
                'is_active' => 1,
            ],
            [
                'name' => 'cancelled',
                'display_name' => 'Đã hủy',
                'color' => '#F44336',
                'sort_order' => 8,
                'is_active' => 1,
            ],
        ];

        foreach ($statuses as $status) {
            Status::updateOrCreate(
                ['name' => $status['name']],
                $status
            );
        }
    }
}
