<?php

return [
    'fallback_route' => 'chat-panel',

    /*
    |--------------------------------------------------------------------------
    | Canonical capabilities
    |--------------------------------------------------------------------------
    |
    | Keep the existing permission codes stable. Routes, UI metadata and
    | business scoping reference these keys so the raw codes have one home.
    |
    */
    'capabilities' => [
        'orders' => 'quan_ly_don_hang',
        'ranks' => 'quan_ly_cap_do',
        'banners' => 'quan_ly_banner',
        'site_content' => 'quan_ly_thong_tin_trang_web',
        'partners' => 'quan_ly_doi_tac',
        'languages' => 'quan_ly_ngon_ngu',
        'manage_all_users' => 'quan_ly_tat_ca_nguoi_dung',
        'manage_all_user_transactions' => 'quan_ly_tat_ca_giao_dich_nguoi_dung',
        'order_processing_time_alert_settings' => 'cau_hinh_thoi_gian_hoan_thanh_don_hang',
        'order_timing_settings' => 'cau_hinh_thoi_gian_don_hang',
        'order_distributions' => 'quan_ly_phan_phoi_don_hang',
        'manage_staff' => 'quan_ly_nhan_vien',
        'manage_staff_permissions' => 'phan_quyen_nhan_vien',
        'system_statistics' => 'xem_thong_ke_he_thong',
        'manage_all_chats' => 'quan_ly_tat_ca_tin_nhan',
        'feature_announcements_view' => 'xem_thong_bao_tinh_nang',
        'feature_announcements_create' => 'tao_thong_bao_tinh_nang',
        'feature_announcements_update' => 'cap_nhat_thong_bao_tinh_nang',
        'feature_announcements_delete' => 'xoa_thong_bao_tinh_nang',
        'feature_announcements_view_report' => 'xem_bao_cao_thong_bao_tinh_nang',
    ],
];
