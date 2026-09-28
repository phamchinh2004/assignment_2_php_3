@php
    $dashboardUrl = route('admin.dashboard');

    $routeGroups = [
        ['patterns' => ['admin.dashboard'], 'section' => 'Tổng quan', 'page' => 'Dashboard'],
        ['patterns' => ['tong.doanh.thu', 'admin.statistical.*', 'doanh.thu.*', 'admin.revenue.*'], 'section' => 'Tổng quan', 'page' => 'Thống kê'],
        ['patterns' => ['chat-panel'], 'section' => 'Vận hành', 'page' => 'Quản lý tin nhắn'],
        ['patterns' => ['user.*'], 'section' => 'Khách hàng', 'page' => 'Quản lý khách hàng'],
        ['patterns' => ['withdraw_transaction', 'deposit_transaction', 'confirm.withdraw', 'cancel.withdraw', 'change.*.transaction.type', 'lucky_wheel_rewards.*'], 'section' => 'Giao dịch', 'page' => 'Quản lý GDKH'],
        ['patterns' => ['staff.*', 'staffs.*'], 'section' => 'Nhân sự', 'page' => 'Quản lý nhân viên'],
        ['patterns' => ['order.*'], 'section' => 'Đơn hàng', 'page' => 'Quản lý đơn hàng'],
        ['patterns' => ['order_distributions.*'], 'section' => 'Đơn hàng', 'page' => 'Phân phối đơn hàng'],
        ['patterns' => ['order_reports.*'], 'section' => 'Đơn hàng', 'page' => 'Đơn hàng bị báo cáo'],
        ['patterns' => ['feature_announcements.*'], 'section' => 'Cấu hình', 'page' => 'Thông báo tính năng'],
        ['patterns' => ['admin.order_status_timing.*'], 'section' => 'Cấu hình', 'page' => 'Thời gian đơn hàng'],
        ['patterns' => ['frozen_order_settings.*'], 'section' => 'Cấu hình', 'page' => 'Thời gian xử lý đơn hàng'],
        ['patterns' => ['rank.*'], 'section' => 'Cấu hình', 'page' => 'Quản lý cấp độ'],
        ['patterns' => ['banner.*'], 'section' => 'Nội dung', 'page' => 'Quản lý banner'],
        ['patterns' => ['section.*'], 'section' => 'Nội dung', 'page' => 'Nội dung website'],
        ['patterns' => ['partner.*'], 'section' => 'Nội dung', 'page' => 'Quản lý đối tác'],
        ['patterns' => ['language.*'], 'section' => 'Nội dung', 'page' => 'Quản lý ngôn ngữ'],
        ['patterns' => ['manager_setting.*'], 'section' => 'Cấu hình', 'page' => 'Quản lý chức năng'],
    ];

    $breadcrumbSection = 'Tổng quan';
    $breadcrumbPage = 'Dashboard';
    foreach ($routeGroups as $routeGroup) {
        if (request()->routeIs(...$routeGroup['patterns'])) {
            $breadcrumbSection = $routeGroup['section'];
            $breadcrumbPage = $routeGroup['page'];
            break;
        }
    }

    $roleLabels = [
        'own' => 'Chủ hệ thống',
        'admin' => 'Quản trị viên',
        'staff' => 'Nhân viên',
    ];
    $currentRole = Auth::user()->role ?? 'admin';
    $roleLabel = $roleLabels[$currentRole] ?? ucfirst($currentRole);
    $displayName = Auth::user()->username ?? Auth::user()->full_name ?? 'Admin';
    $avatarInitial = strtoupper(substr($displayName, 0, 1));
@endphp

<nav id="adminHeaderState"
    class="admin-topbar"
    data-state-url="{{ route('header.state') }}"
    data-notification-read-url="{{ route('header.notifications.read', ['notification' => '__NOTIFICATION__']) }}"
    data-notification-read-all-url="{{ route('header.notifications.read-all') }}"
    data-user-id="{{ Auth::id() }}">
    <div class="admin-topbar__context">
        <button id="adminSidebarOpen" class="admin-topbar__mobile-menu d-lg-none" type="button"
            aria-label="Mở thanh điều hướng" aria-controls="accordionSidebar" aria-expanded="false">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>

        <nav class="admin-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ $dashboardUrl }}" class="admin-breadcrumb__home" aria-label="Trang tổng quan">
                <i class="fas fa-house" aria-hidden="true"></i>
            </a>
            <span class="admin-breadcrumb__separator" aria-hidden="true">/</span>
            <span class="admin-breadcrumb__section">{{ $breadcrumbSection }}</span>
            <span class="admin-breadcrumb__separator" aria-hidden="true">/</span>
            <strong class="admin-breadcrumb__current">{{ $breadcrumbPage }}</strong>
        </nav>
    </div>

    <div class="admin-topbar__actions">
        <div class="admin-quick-search" id="adminQuickSearch">
            <div class="admin-quick-search__field">
                <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                <input id="adminQuickSearchInput" type="search" placeholder="Tìm kiếm nhanh..."
                    aria-label="Tìm nhanh chức năng quản trị" autocomplete="off" spellcheck="false">
                <kbd class="admin-quick-search__shortcut">Ctrl K</kbd>
            </div>
            <div id="adminQuickSearchResults" class="admin-quick-search__results" role="listbox" hidden></div>
        </div>

        <div class="dropdown admin-topbar__dropdown">
            <button class="admin-topbar__icon-button dropdown-toggle" type="button" id="alertsDropdown"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Thông báo">
                <i class="far fa-bell" aria-hidden="true"></i>
                <span id="adminNotificationBadge" class="admin-topbar__badge" hidden></span>
            </button>
            <div class="dropdown-menu dropdown-menu-right admin-header-dropdown" aria-labelledby="alertsDropdown">
                <div class="admin-header-dropdown__header">
                    <div>
                        <strong>Thông báo</strong>
                        <span>Cập nhật mới trong hệ thống</span>
                    </div>
                    <button id="adminNotificationReadAll" type="button" class="admin-header-action" hidden>
                        Đánh dấu đã đọc
                    </button>
                </div>
                <div id="adminNotificationList" class="admin-header-list" aria-live="polite">
                    <div class="admin-header-state">Đang tải thông báo...</div>
                </div>
                <button id="adminNotificationLoadMore" type="button" class="admin-header-load-more" hidden>
                    Tải thêm
                </button>
            </div>
        </div>

        <div class="dropdown admin-topbar__dropdown">
            <button class="admin-topbar__icon-button dropdown-toggle" type="button" id="messagesDropdown"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Tin nhắn">
                <i class="far fa-envelope" aria-hidden="true"></i>
                <span id="adminMessageBadge" class="admin-topbar__badge" hidden></span>
            </button>
            <div class="dropdown-menu dropdown-menu-right admin-header-dropdown" aria-labelledby="messagesDropdown">
                <div class="admin-header-dropdown__header">
                    <div>
                        <strong>Tin nhắn</strong>
                        <span>Hội thoại cần theo dõi</span>
                    </div>
                </div>
                <div id="adminMessageList" class="admin-header-list" aria-live="polite">
                    <div class="admin-header-state">Đang tải tin nhắn...</div>
                </div>
                <a class="admin-header-load-more" href="{{ route('chat-panel') }}">Xem tất cả tin nhắn</a>
            </div>
        </div>

        <span class="admin-topbar__divider" aria-hidden="true"></span>

        <div class="dropdown admin-profile-menu">
            <button class="admin-profile-menu__trigger dropdown-toggle" type="button" id="userDropdown"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="admin-profile-menu__avatar" aria-hidden="true">{{ $avatarInitial }}</span>
                <span class="admin-profile-menu__copy">
                    <strong>{{ $displayName }}</strong>
                    <small>{{ $roleLabel }}</small>
                </span>
                <i class="fas fa-chevron-down admin-profile-menu__chevron" aria-hidden="true"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right admin-profile-dropdown" aria-labelledby="userDropdown">
                <div class="admin-profile-dropdown__identity">
                    <strong>{{ $displayName }}</strong>
                    <span>{{ $roleLabel }}</span>
                    @if(Auth::user()->referral_code)
                        <small>Mã mời: {{ Auth::user()->referral_code }}</small>
                    @endif
                </div>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#changePasswordModal">
                    <i class="fas fa-key" aria-hidden="true"></i>
                    Đổi mật khẩu
                </a>
                <a class="dropdown-item text-danger" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i>
                    Đăng xuất
                </a>
            </div>
        </div>
    </div>
</nav>
