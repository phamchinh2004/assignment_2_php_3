<?php

// Historical conversion only; never used to authorize requests.
return [
        'quan_ly_don_hang' => [
            'orders.view', 'orders.view-detail', 'orders.create', 'orders.update', 'orders.change-status',
            'order-reports.view', 'order-reports.view-detail', 'order-reports.confirm', 'order-reports.cancel',
        ],
        'quan_ly_cap_do' => ['ranks.view', 'ranks.view-detail', 'ranks.create', 'ranks.update', 'ranks.delete'],
        'quan_ly_banner' => ['banners.view', 'banners.view-detail', 'banners.create', 'banners.update', 'banners.delete', 'banners.change-status'],
        'quan_ly_thong_tin_trang_web' => ['site-content.view', 'site-content.view-detail', 'site-content.create', 'site-content.update', 'site-content.change-status'],
        'quan_ly_doi_tac' => ['partners.view', 'partners.view-detail', 'partners.create', 'partners.update', 'partners.delete'],
        'quan_ly_ngon_ngu' => ['languages.view', 'languages.view-detail', 'languages.create', 'languages.update'],
        'quan_ly_tat_ca_nguoi_dung' => [
            'customers.view', 'customers.view-all', 'customers.view-detail', 'customers.view-financials',
            'customers.create', 'customers.update', 'customers.change-status', 'customers.manage-location',
            'customers.manage-frozen-orders', 'customers.adjust-balance', 'customers.manage-spin',
        ],
        'quan_ly_tat_ca_giao_dich_nguoi_dung' => [
            'withdrawals.view', 'withdrawals.view-all', 'withdrawals.confirm', 'withdrawals.cancel', 'withdrawals.change-type',
            'deposits.view', 'deposits.view-all', 'deposits.delete', 'deposits.change-type',
        ],
        'cau_hinh_thoi_gian_hoan_thanh_don_hang' => ['frozen-order-settings.view', 'frozen-order-settings.update'],
        'cau_hinh_thoi_gian_don_hang' => ['order-timing.view', 'order-timing.update'],
        'quan_ly_phan_phoi_don_hang' => ['order-distributions.view', 'order-distributions.view-detail'],
        'quan_ly_nhan_vien' => ['staff.view', 'staff.view-detail', 'staff.create', 'staff.update', 'staff.change-status'],
        'phan_quyen_nhan_vien' => ['staff-permissions.view', 'staff-permissions.assign'],
        'quan_ly_tat_ca_tin_nhan' => ['chats.view-all'],
        'xem_thong_bao_tinh_nang' => ['feature-announcements.view'],
        'tao_thong_bao_tinh_nang' => ['feature-announcements.create'],
        'cap_nhat_thong_bao_tinh_nang' => ['feature-announcements.update', 'feature-announcements.toggle'],
        'xoa_thong_bao_tinh_nang' => ['feature-announcements.delete'],
        'xem_bao_cao_thong_bao_tinh_nang' => ['feature-announcements.view-report'],
    ];
