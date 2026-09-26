<?php

$modules = [
    'statistics' => [
        'label' => 'Thống kê hệ thống',
        'permissions' => [
            'view_overview' => ['code' => 'statistics.view-overview', 'label' => 'Doanh thu tổng quan'],
            'view_staff' => ['code' => 'statistics.view-staff', 'label' => 'Doanh thu nhân viên'],
            'view_customers' => ['code' => 'statistics.view-customers', 'label' => 'Doanh thu từ khách hàng'],
            'view_personal' => ['code' => 'statistics.view-personal', 'label' => 'Doanh thu bản thân'],
            'export' => ['code' => 'statistics.export', 'label' => 'Xuất báo cáo thống kê'],
        ],
    ],
    'customers' => [
        'label' => 'Quản lý khách hàng',
        'permissions' => [
            'view' => ['code' => 'customers.view', 'label' => 'Xem danh sách'],
            'view_all' => ['code' => 'customers.view-all', 'label' => 'Xem toàn bộ khách hàng'],
            'view_detail' => ['code' => 'customers.view-detail', 'label' => 'Xem chi tiết'],
            'view_financials' => ['code' => 'customers.view-financials', 'label' => 'Xem số dư và lịch sử tài chính'],
            'create' => ['code' => 'customers.create', 'label' => 'Tạo khách hàng'],
            'update' => ['code' => 'customers.update', 'label' => 'Cập nhật thông tin'],
            'change_status' => ['code' => 'customers.change-status', 'label' => 'Khóa, mở khóa và đổi trạng thái'],
            'manage_location' => ['code' => 'customers.manage-location', 'label' => 'Quản lý dữ liệu vị trí'],
            'manage_frozen_orders' => ['code' => 'customers.manage-frozen-orders', 'label' => 'Quản lý đơn hàng đóng băng'],
            'adjust_balance' => ['code' => 'customers.adjust-balance', 'label' => 'Điều chỉnh và nạp số dư'],
            'manage_spin' => ['code' => 'customers.manage-spin', 'label' => 'Quản lý lượt quay và tiến trình'],
        ],
    ],
    'orders' => [
        'label' => 'Quản lý đơn hàng',
        'permissions' => [
            'view' => ['code' => 'orders.view', 'label' => 'Xem danh sách'],
            'view_detail' => ['code' => 'orders.view-detail', 'label' => 'Xem chi tiết'],
            'create' => ['code' => 'orders.create', 'label' => 'Tạo đơn hàng'],
            'update' => ['code' => 'orders.update', 'label' => 'Cập nhật đơn hàng'],
            'change_status' => ['code' => 'orders.change-status', 'label' => 'Thay đổi trạng thái'],
        ],
    ],
    'order_reports' => [
        'label' => 'Đơn hàng bị báo cáo',
        'permissions' => [
            'view' => ['code' => 'order-reports.view', 'label' => 'Xem danh sách báo cáo'],
            'view_detail' => ['code' => 'order-reports.view-detail', 'label' => 'Xem chi tiết báo cáo'],
            'confirm' => ['code' => 'order-reports.confirm', 'label' => 'Xác nhận báo cáo'],
            'cancel' => ['code' => 'order-reports.cancel', 'label' => 'Hủy báo cáo'],
        ],
    ],
    'order_distributions' => [
        'label' => 'Phân phối đơn hàng',
        'permissions' => [
            'view' => ['code' => 'order-distributions.view', 'label' => 'Xem danh sách phân phối'],
            'view_detail' => ['code' => 'order-distributions.view-detail', 'label' => 'Xem chi tiết phân phối'],
            'transition' => ['code' => 'order-distributions.transition', 'label' => 'Chuyển trạng thái xử lý'],
            'complete' => ['code' => 'order-distributions.complete', 'label' => 'Hoàn tất đơn và ghi nhận tài chính'],
        ],
    ],
    'withdrawals' => [
        'label' => 'Giao dịch rút tiền',
        'permissions' => [
            'view' => ['code' => 'withdrawals.view', 'label' => 'Xem danh sách'],
            'view_all' => ['code' => 'withdrawals.view-all', 'label' => 'Xem giao dịch của toàn bộ khách hàng'],
            'confirm' => ['code' => 'withdrawals.confirm', 'label' => 'Xác nhận rút tiền'],
            'cancel' => ['code' => 'withdrawals.cancel', 'label' => 'Từ chối và hoàn tiền'],
            'change_type' => ['code' => 'withdrawals.change-type', 'label' => 'Thay đổi loại giao dịch'],
        ],
    ],
    'deposits' => [
        'label' => 'Giao dịch nạp tiền',
        'permissions' => [
            'view' => ['code' => 'deposits.view', 'label' => 'Xem danh sách'],
            'view_all' => ['code' => 'deposits.view-all', 'label' => 'Xem giao dịch của toàn bộ nhân viên'],
            'delete' => ['code' => 'deposits.delete', 'label' => 'Xóa giao dịch và trừ lại số dư'],
            'change_type' => ['code' => 'deposits.change-type', 'label' => 'Thay đổi loại giao dịch'],
        ],
    ],
    'lucky_wheel_rewards' => [
        'label' => 'Phần thưởng vòng quay',
        'permissions' => [
            'view' => ['code' => 'lucky-wheel-rewards.view', 'label' => 'Xem danh sách phần thưởng'],
            'approve' => ['code' => 'lucky-wheel-rewards.approve', 'label' => 'Duyệt phần thưởng'],
            'reject' => ['code' => 'lucky-wheel-rewards.reject', 'label' => 'Từ chối phần thưởng'],
            'configure_auto_approval' => ['code' => 'lucky-wheel-rewards.configure-auto-approval', 'label' => 'Cấu hình tự động duyệt'],
        ],
    ],
    'staff' => [
        'label' => 'Quản lý nhân viên',
        'permissions' => [
            'view' => ['code' => 'staff.view', 'label' => 'Xem danh sách'],
            'view_detail' => ['code' => 'staff.view-detail', 'label' => 'Xem chi tiết'],
            'create' => ['code' => 'staff.create', 'label' => 'Tạo tài khoản quản trị'],
            'update' => ['code' => 'staff.update', 'label' => 'Cập nhật tài khoản quản trị'],
            'change_status' => ['code' => 'staff.change-status', 'label' => 'Khóa, mở khóa và đổi trạng thái'],
        ],
    ],
    'staff_permissions' => [
        'label' => 'Phân quyền nhân viên',
        'permissions' => [
            'view' => ['code' => 'staff-permissions.view', 'label' => 'Xem quyền của nhân viên'],
            'assign' => ['code' => 'staff-permissions.assign', 'label' => 'Cấp và thu hồi quyền'],
        ],
    ],
    'ranks' => [
        'label' => 'Quản lý cấp độ',
        'permissions' => [
            'view' => ['code' => 'ranks.view', 'label' => 'Xem danh sách'],
            'view_detail' => ['code' => 'ranks.view-detail', 'label' => 'Xem chi tiết'],
            'create' => ['code' => 'ranks.create', 'label' => 'Tạo cấp độ'],
            'update' => ['code' => 'ranks.update', 'label' => 'Cập nhật cấp độ'],
            'delete' => ['code' => 'ranks.delete', 'label' => 'Xóa cấp độ'],
        ],
    ],
    'banners' => [
        'label' => 'Quản lý banner',
        'permissions' => [
            'view' => ['code' => 'banners.view', 'label' => 'Xem danh sách'],
            'view_detail' => ['code' => 'banners.view-detail', 'label' => 'Xem chi tiết'],
            'create' => ['code' => 'banners.create', 'label' => 'Tạo banner'],
            'update' => ['code' => 'banners.update', 'label' => 'Cập nhật banner'],
            'delete' => ['code' => 'banners.delete', 'label' => 'Xóa banner'],
            'change_status' => ['code' => 'banners.change-status', 'label' => 'Thay đổi trạng thái banner'],
        ],
    ],
    'site_content' => [
        'label' => 'Nội dung website',
        'permissions' => [
            'view' => ['code' => 'site-content.view', 'label' => 'Xem danh sách'],
            'view_detail' => ['code' => 'site-content.view-detail', 'label' => 'Xem chi tiết'],
            'create' => ['code' => 'site-content.create', 'label' => 'Tạo nội dung'],
            'update' => ['code' => 'site-content.update', 'label' => 'Cập nhật nội dung'],
            'change_status' => ['code' => 'site-content.change-status', 'label' => 'Thay đổi trạng thái nội dung'],
        ],
    ],
    'partners' => [
        'label' => 'Quản lý đối tác',
        'permissions' => [
            'view' => ['code' => 'partners.view', 'label' => 'Xem danh sách'],
            'view_detail' => ['code' => 'partners.view-detail', 'label' => 'Xem chi tiết'],
            'create' => ['code' => 'partners.create', 'label' => 'Tạo đối tác'],
            'update' => ['code' => 'partners.update', 'label' => 'Cập nhật đối tác'],
            'delete' => ['code' => 'partners.delete', 'label' => 'Xóa đối tác'],
        ],
    ],
    'languages' => [
        'label' => 'Quản lý ngôn ngữ',
        'permissions' => [
            'view' => ['code' => 'languages.view', 'label' => 'Xem danh sách'],
            'view_detail' => ['code' => 'languages.view-detail', 'label' => 'Xem chi tiết'],
            'create' => ['code' => 'languages.create', 'label' => 'Tạo ngôn ngữ'],
            'update' => ['code' => 'languages.update', 'label' => 'Cập nhật ngôn ngữ'],
        ],
    ],
    'frozen_order_settings' => [
        'label' => 'Cấu hình đơn hàng đóng băng',
        'permissions' => [
            'view' => ['code' => 'frozen-order-settings.view', 'label' => 'Xem cấu hình'],
            'update' => ['code' => 'frozen-order-settings.update', 'label' => 'Cập nhật cấu hình'],
        ],
    ],
    'order_timing' => [
        'label' => 'Thời gian trạng thái đơn hàng',
        'permissions' => [
            'view' => ['code' => 'order-timing.view', 'label' => 'Xem cấu hình'],
            'update' => ['code' => 'order-timing.update', 'label' => 'Cập nhật cấu hình'],
        ],
    ],
    'feature_announcements' => [
        'label' => 'Thông báo tính năng',
        'permissions' => [
            'view' => ['code' => 'feature-announcements.view', 'label' => 'Xem danh sách'],
            'create' => ['code' => 'feature-announcements.create', 'label' => 'Tạo thông báo'],
            'update' => ['code' => 'feature-announcements.update', 'label' => 'Cập nhật thông báo'],
            'toggle' => ['code' => 'feature-announcements.toggle', 'label' => 'Bật hoặc tắt thông báo'],
            'delete' => ['code' => 'feature-announcements.delete', 'label' => 'Xóa thông báo'],
            'view_report' => ['code' => 'feature-announcements.view-report', 'label' => 'Xem báo cáo đã đọc'],
        ],
    ],
    'chats' => [
        'label' => 'Tin nhắn',
        'permissions' => [
            'view_all' => ['code' => 'chats.view-all', 'label' => 'Xem tin nhắn của nhân viên khác'],
        ],
    ],
];

$capabilities = [];
foreach ($modules as $moduleKey => $module) {
    foreach ($module['permissions'] as $actionKey => $permission) {
        $capabilities[$moduleKey . '_' . $actionKey] = $permission['code'];
    }
}

return [
    // Keep historical assignments in the database, but omit retired features from permission screens.
    'retired_permissions' => ['orders.maintenance'],
    'fallback_route' => 'chat-panel',
    'modules' => $modules,
    'capabilities' => $capabilities,
];
