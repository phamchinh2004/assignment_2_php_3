<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuthorizationService;
use App\Services\ReactPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReactPageService $reactPage,
        private readonly AuthorizationService $authorization,
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $capabilities = config('authorization.capabilities');

        $definitions = [
            [
                'key' => 'statistics',
                'group' => 'insight',
                'label' => 'Thống kê tổng quan',
                'description' => 'Theo dõi dòng tiền, giao dịch và xu hướng doanh thu.',
                'route' => 'tong.doanh.thu',
                'permission' => $capabilities['statistics_view_overview'],
            ],
            [
                'key' => 'statistics-staff',
                'group' => 'insight',
                'label' => 'Doanh thu nhân viên',
                'description' => 'Theo dõi doanh thu và giao dịch theo từng nhân viên.',
                'route' => 'doanh.thu.theo.nhan.vien',
                'permission' => $capabilities['statistics_view_staff'],
            ],
            [
                'key' => 'statistics-customers',
                'group' => 'insight',
                'label' => 'Doanh thu từ khách hàng',
                'description' => 'Theo dõi doanh thu và giao dịch phát sinh từ khách hàng.',
                'route' => 'doanh.thu.tu.khach.hang',
                'permission' => $capabilities['statistics_view_customers'],
            ],
            [
                'key' => 'statistics-personal',
                'group' => 'insight',
                'label' => 'Doanh thu bản thân',
                'description' => 'Theo dõi doanh thu và giao dịch của tài khoản hiện tại.',
                'route' => 'doanh.thu.ban.than',
                'permission' => $capabilities['statistics_view_personal'],
            ],
            [
                'key' => 'customers',
                'group' => 'operations',
                'label' => 'Khách hàng',
                'description' => 'Tra cứu hồ sơ, trạng thái và các thao tác quản trị khách hàng.',
                'route' => 'user.index',
                'permission' => $capabilities['customers_view'],
            ],
            [
                'key' => 'orders',
                'group' => 'operations',
                'label' => 'Đơn hàng',
                'description' => 'Theo dõi danh sách đơn và tiến trình xử lý.',
                'route' => 'order.index',
                'permission' => $capabilities['orders_view'],
            ],
            [
                'key' => 'order-reports',
                'group' => 'operations',
                'label' => 'Đơn bị báo cáo',
                'description' => 'Xử lý các báo cáo cần xác minh và giữ dấu vết quyết định.',
                'route' => 'order_reports.index',
                'permission' => $capabilities['order_reports_view'],
            ],
            [
                'key' => 'order-distributions',
                'group' => 'operations',
                'label' => 'Phân phối đơn',
                'description' => 'Audit nguồn phân phối, người nhận và trạng thái đơn đóng băng.',
                'route' => 'order_distributions.index',
                'permission' => $capabilities['order_distributions_view'],
            ],
            [
                'key' => 'deposits',
                'group' => 'finance',
                'label' => 'Nạp tiền',
                'description' => 'Đối chiếu lịch sử nạp tiền thực, tiền thưởng và snapshot số dư.',
                'route' => 'deposit_transaction',
                'permission' => $capabilities['deposits_view'],
            ],
            [
                'key' => 'withdrawals',
                'group' => 'finance',
                'label' => 'Rút tiền',
                'description' => 'Kiểm duyệt yêu cầu rút tiền theo quyền được cấp.',
                'route' => 'withdraw_transaction',
                'permission' => $capabilities['withdrawals_view'],
            ],
            [
                'key' => 'lucky-wheel',
                'group' => 'finance',
                'label' => 'Thưởng vòng quay',
                'description' => 'Duyệt phần thưởng tiền mặt và theo dõi lịch sử chi thưởng.',
                'route' => 'lucky_wheel_rewards.index',
                'permission' => $capabilities['lucky_wheel_rewards_view'],
            ],
            [
                'key' => 'announcements',
                'group' => 'configuration',
                'label' => 'Thông báo tính năng',
                'description' => 'Quản lý nội dung thông báo và báo cáo đã đọc.',
                'route' => 'feature_announcements.index',
                'permission' => $capabilities['feature_announcements_view'],
            ],
            [
                'key' => 'order-timing',
                'group' => 'configuration',
                'label' => 'Thời gian trạng thái đơn',
                'description' => 'Cấu hình thời gian tự động chuyển giữa các bước xử lý đơn.',
                'route' => 'admin.order_status_timing.index',
                'permission' => $capabilities['order_timing_view'],
            ],
            [
                'key' => 'frozen-settings',
                'group' => 'configuration',
                'label' => 'Cấu hình đơn đóng băng',
                'description' => 'Quản lý thiết lập liên quan đến đơn hàng đóng băng.',
                'route' => 'frozen_order_settings.index',
                'permission' => $capabilities['frozen_order_settings_view'],
            ],
        ];

        $links = [];
        foreach ($definitions as $item) {
            if ($this->authorization->can($user, $item['permission'])) {
                $links[] = [
                    'key' => $item['key'],
                    'group' => $item['group'],
                    'label' => $item['label'],
                    'description' => $item['description'],
                    'href' => route($item['route']),
                ];
            }
        }

        return $this->reactPage->admin('admin.dashboard', [
            'links' => $links,
        ], 'Dashboard');
    }
}
