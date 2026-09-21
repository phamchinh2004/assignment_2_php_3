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
        'frozen_order_alert_settings' => 'cau_hinh_moc_canh_bao_frozen_order',
        'order_timing_settings' => 'cau_hinh_thoi_gian_don_hang',
        'order_distributions' => 'quan_ly_phan_phoi_don_hang',
    ],
];
