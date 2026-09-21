@php
    $isDashboardActive = request()->routeIs('admin.dashboard', 'tong.doanh.thu');
    $isStatisticsActive = request()->routeIs(
        'doanh.thu.theo.nhan.vien',
        'doanh.thu.tu.khach.hang',
        'doanh.thu.ban.than',
        'admin.statistical.*',
        'admin.revenue.*'
    );
    $isWithdrawActive = request()->routeIs(
        'withdraw_transaction',
        'confirm.withdraw',
        'cancel.withdraw',
        'change.withdraw.transaction.type'
    );
    $isDepositActive = request()->routeIs(
        'deposit_transaction',
        'destroy.deposit',
        'change.deposit.transaction.type'
    );
    $isTransactionActive = $isWithdrawActive || $isDepositActive;
@endphp

<aside class="admin-sidebar" id="accordionSidebar" aria-label="Điều hướng quản trị">
    <div class="admin-sidebar__header">
        <a class="admin-sidebar__brand"
            href="{{ Auth::user()->role === 'admin' ? route('tong.doanh.thu') : route('chat-panel') }}">
            <span class="admin-sidebar__brand-mark" aria-hidden="true">
                <i class="fas fa-layer-group"></i>
            </span>
            <span class="admin-sidebar__brand-copy">
                <strong>Hệ thống</strong>
                <small>Quản trị vận hành</small>
            </span>
        </a>

        <button type="button" class="admin-sidebar__collapse" id="adminSidebarCollapse"
            aria-label="Thu gọn thanh điều hướng" aria-controls="accordionSidebar" aria-expanded="true">
            <i class="fas fa-angles-left" aria-hidden="true"></i>
        </button>

        <button type="button" class="admin-sidebar__close" id="adminSidebarClose"
            aria-label="Đóng thanh điều hướng">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <div class="admin-sidebar__scroll">
        <nav class="admin-sidebar__nav" aria-label="Menu chính">
            <section class="admin-sidebar__section" aria-labelledby="sidebar-overview-title">
                <h2 class="admin-sidebar__section-title" id="sidebar-overview-title">Tổng quan</h2>

                @if (Auth::user()->role === 'admin')
                    <a class="admin-sidebar__link {{ $isDashboardActive ? 'is-active' : '' }}"
                        href="{{ route('tong.doanh.thu') }}" data-sidebar-tooltip="Dashboard"
                        @if($isDashboardActive) aria-current="page" @endif>
                        <span class="admin-sidebar__icon"><i class="fas fa-chart-pie" aria-hidden="true"></i></span>
                        <span class="admin-sidebar__label">Dashboard</span>
                    </a>

                    <div class="admin-sidebar__item {{ $isStatisticsActive ? 'is-active' : '' }}">
                        <button type="button"
                            class="admin-sidebar__link admin-sidebar__submenu-trigger {{ $isStatisticsActive ? '' : 'collapsed' }}"
                            data-toggle="collapse" data-target="#collapseStatistics"
                            aria-expanded="{{ $isStatisticsActive ? 'true' : 'false' }}"
                            aria-controls="collapseStatistics" data-sidebar-tooltip="Thống kê">
                            <span class="admin-sidebar__icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                            <span class="admin-sidebar__label">Thống kê</span>
                            <span class="admin-sidebar__chevron"><i class="fas fa-chevron-down" aria-hidden="true"></i></span>
                        </button>
                        <div id="collapseStatistics" class="collapse admin-sidebar__submenu {{ $isStatisticsActive ? 'show' : '' }}"
                            data-parent="#accordionSidebar">
                            <div class="admin-sidebar__submenu-panel">
                                <span class="admin-sidebar__submenu-heading">Thống kê</span>
                                <a class="admin-sidebar__submenu-link {{ request()->routeIs('doanh.thu.theo.nhan.vien') ? 'is-active' : '' }}"
                                    href="{{ route('doanh.thu.theo.nhan.vien') }}">Doanh thu nhân viên</a>
                                <a class="admin-sidebar__submenu-link {{ request()->routeIs('doanh.thu.tu.khach.hang') ? 'is-active' : '' }}"
                                    href="{{ route('doanh.thu.tu.khach.hang') }}">Doanh thu từ khách hàng</a>
                                <a class="admin-sidebar__submenu-link {{ request()->routeIs('doanh.thu.ban.than') ? 'is-active' : '' }}"
                                    href="{{ route('doanh.thu.ban.than') }}">Doanh thu bản thân</a>
                            </div>
                        </div>
                    </div>
                @elseif (Auth::user()->role === 'staff')
                    <a class="admin-sidebar__link {{ request()->routeIs('doanh.thu.ban.than') ? 'is-active' : '' }}"
                        href="{{ route('doanh.thu.ban.than') }}" data-sidebar-tooltip="Thống kê"
                        @if(request()->routeIs('doanh.thu.ban.than')) aria-current="page" @endif>
                        <span class="admin-sidebar__icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                        <span class="admin-sidebar__label">Thống kê</span>
                    </a>
                @endif
            </section>

            <section class="admin-sidebar__section" aria-labelledby="sidebar-operations-title">
                <h2 class="admin-sidebar__section-title" id="sidebar-operations-title">Vận hành</h2>

                <a class="admin-sidebar__link {{ request()->routeIs('chat-panel') ? 'is-active' : '' }}"
                    href="{{ route('chat-panel') }}" data-sidebar-tooltip="Quản lý tin nhắn"
                    @if(request()->routeIs('chat-panel')) aria-current="page" @endif>
                    <span class="admin-sidebar__icon"><i class="fas fa-message" aria-hidden="true"></i></span>
                    <span class="admin-sidebar__label">Quản lý tin nhắn</span>
                </a>

                <a class="admin-sidebar__link {{ request()->routeIs('user.*') ? 'is-active' : '' }}"
                    href="{{ route('user.index') }}" data-sidebar-tooltip="Quản lý khách hàng"
                    @if(request()->routeIs('user.*')) aria-current="page" @endif>
                    <span class="admin-sidebar__icon"><i class="fas fa-users" aria-hidden="true"></i></span>
                    <span class="admin-sidebar__label">Quản lý khách hàng</span>
                </a>

                <div class="admin-sidebar__item {{ $isTransactionActive ? 'is-active' : '' }}">
                    <button type="button"
                        class="admin-sidebar__link admin-sidebar__submenu-trigger {{ $isTransactionActive ? '' : 'collapsed' }}"
                        data-toggle="collapse" data-target="#collapseOrder"
                        aria-expanded="{{ $isTransactionActive ? 'true' : 'false' }}"
                        aria-controls="collapseOrder" data-sidebar-tooltip="Quản lý GDKH">
                        <span class="admin-sidebar__icon"><i class="fas fa-arrow-right-arrow-left" aria-hidden="true"></i></span>
                        <span class="admin-sidebar__label">Quản lý GDKH</span>
                        <span class="admin-sidebar__chevron"><i class="fas fa-chevron-down" aria-hidden="true"></i></span>
                    </button>
                    <div id="collapseOrder" class="collapse admin-sidebar__submenu {{ $isTransactionActive ? 'show' : '' }}"
                        data-parent="#accordionSidebar">
                        <div class="admin-sidebar__submenu-panel">
                            <span class="admin-sidebar__submenu-heading">Giao dịch khách hàng</span>
                            <a class="admin-sidebar__submenu-link {{ $isWithdrawActive ? 'is-active' : '' }}"
                                href="{{ route('withdraw_transaction') }}">Rút tiền</a>
                            <a class="admin-sidebar__submenu-link {{ $isDepositActive ? 'is-active' : '' }}"
                                href="{{ route('deposit_transaction') }}">Nạp tiền</a>
                        </div>
                    </div>
                </div>

                @if (Auth::user()->role === 'admin')
                    <a class="admin-sidebar__link {{ request()->routeIs('staff.*') ? 'is-active' : '' }}"
                        href="{{ route('staff.index') }}" data-sidebar-tooltip="Quản lý nhân viên"
                        @if(request()->routeIs('staff.*')) aria-current="page" @endif>
                        <span class="admin-sidebar__icon"><i class="fas fa-user-tie" aria-hidden="true"></i></span>
                        <span class="admin-sidebar__label">Quản lý nhân viên</span>
                    </a>
                @endif

                <a class="admin-sidebar__link {{ request()->routeIs('order.*') ? 'is-active' : '' }}"
                    href="{{ route('order.index') }}" data-sidebar-tooltip="Quản lý đơn hàng"
                    data-permission="{{ config('authorization.capabilities.orders') }}" hidden
                    @if(request()->routeIs('order.*')) aria-current="page" @endif>
                    <span class="admin-sidebar__icon"><i class="fas fa-box-open" aria-hidden="true"></i></span>
                    <span class="admin-sidebar__label">Quản lý đơn hàng</span>
                </a>

                @if (Auth::user()->role === 'admin')
                    <a class="admin-sidebar__link {{ request()->routeIs('order_distributions.*') ? 'is-active' : '' }}"
                        href="{{ route('order_distributions.index') }}" data-sidebar-tooltip="Phân phối đơn hàng"
                        @if(request()->routeIs('order_distributions.*')) aria-current="page" @endif>
                        <span class="admin-sidebar__icon"><i class="fas fa-route" aria-hidden="true"></i></span>
                        <span class="admin-sidebar__label">Phân phối đơn hàng</span>
                    </a>
                @endif

                <a class="admin-sidebar__link {{ request()->routeIs('order_reports.*') ? 'is-active' : '' }}"
                    href="{{ route('order_reports.index') }}" data-sidebar-tooltip="Đơn hàng bị báo cáo"
                    data-permission="{{ config('authorization.capabilities.orders') }}" hidden
                    @if(request()->routeIs('order_reports.*')) aria-current="page" @endif>
                    <span class="admin-sidebar__icon"><i class="fas fa-flag" aria-hidden="true"></i></span>
                    <span class="admin-sidebar__label">Đơn hàng bị báo cáo</span>
                </a>
            </section>

            <section class="admin-sidebar__section" aria-labelledby="sidebar-settings-title">
                <h2 class="admin-sidebar__section-title" id="sidebar-settings-title">Cấu hình</h2>

                @if (Auth::user()->role === 'admin')
                    <a class="admin-sidebar__link {{ request()->routeIs('admin.order_status_timing.*') ? 'is-active' : '' }}"
                        href="{{ route('admin.order_status_timing.index') }}" data-sidebar-tooltip="Thời gian đơn hàng"
                        @if(request()->routeIs('admin.order_status_timing.*')) aria-current="page" @endif>
                        <span class="admin-sidebar__icon"><i class="fas fa-clock" aria-hidden="true"></i></span>
                        <span class="admin-sidebar__label">Thời gian đơn hàng</span>
                    </a>
                @endif

                <a class="admin-sidebar__link {{ request()->routeIs('frozen_order_settings.*') ? 'is-active' : '' }}"
                    href="{{ route('frozen_order_settings.index') }}" data-sidebar-tooltip="Frozen Order mặc định"
                    data-permission="{{ config('authorization.capabilities.order_processing_time_alert_settings') }}" hidden
                    @if(request()->routeIs('frozen_order_settings.*')) aria-current="page" @endif>
                    <span class="admin-sidebar__icon"><i class="fas fa-snowflake" aria-hidden="true"></i></span>
                    <span class="admin-sidebar__label">Frozen Order mặc định</span>
                </a>

                <a class="admin-sidebar__link {{ request()->routeIs('rank.*') ? 'is-active' : '' }}"
                    href="{{ route('rank.index') }}" data-sidebar-tooltip="Quản lý cấp độ"
                    data-permission="{{ config('authorization.capabilities.ranks') }}" hidden
                    @if(request()->routeIs('rank.*')) aria-current="page" @endif>
                    <span class="admin-sidebar__icon"><i class="fas fa-ranking-star" aria-hidden="true"></i></span>
                    <span class="admin-sidebar__label">Quản lý cấp độ</span>
                </a>

                <a class="admin-sidebar__link {{ request()->routeIs('banner.*') ? 'is-active' : '' }}"
                    href="{{ route("banner.index") }}" data-sidebar-tooltip="Quản lý banner"
                    data-permission="{{ config('authorization.capabilities.banners') }}" hidden
                    @if(request()->routeIs('banner.*')) aria-current="page" @endif>
                    <span class="admin-sidebar__icon"><i class="fas fa-images" aria-hidden="true"></i></span>
                    <span class="admin-sidebar__label">Quản lý banner</span>
                </a>

                <a class="admin-sidebar__link {{ request()->routeIs('section.*') ? 'is-active' : '' }}"
                    href="{{ route('section.index') }}" data-sidebar-tooltip="Nội dung website"
                    data-permission="{{ config('authorization.capabilities.site_content') }}" hidden
                    @if(request()->routeIs('section.*')) aria-current="page" @endif>
                    <span class="admin-sidebar__icon"><i class="fas fa-table-columns" aria-hidden="true"></i></span>
                    <span class="admin-sidebar__label">Nội dung website</span>
                </a>

                <a class="admin-sidebar__link {{ request()->routeIs('partner.*') ? 'is-active' : '' }}"
                    href="{{ route('partner.index') }}" data-sidebar-tooltip="Quản lý đối tác"
                    data-permission="{{ config('authorization.capabilities.partners') }}" hidden
                    @if(request()->routeIs('partner.*')) aria-current="page" @endif>
                    <span class="admin-sidebar__icon"><i class="fas fa-handshake" aria-hidden="true"></i></span>
                    <span class="admin-sidebar__label">Quản lý đối tác</span>
                </a>

                <a class="admin-sidebar__link {{ request()->routeIs('language.*') ? 'is-active' : '' }}"
                    href="{{ route('language.index') }}" data-sidebar-tooltip="Quản lý ngôn ngữ"
                    data-permission="{{ config('authorization.capabilities.languages') }}" hidden
                    @if(request()->routeIs('language.*')) aria-current="page" @endif>
                    <span class="admin-sidebar__icon"><i class="fas fa-language" aria-hidden="true"></i></span>
                    <span class="admin-sidebar__label">Quản lý ngôn ngữ</span>
                </a>

                @if (Auth::user()->role === 'admin')
                    <a class="admin-sidebar__link {{ request()->routeIs('manager_setting.*') ? 'is-active' : '' }}"
                        href="{{ route('manager_setting.index') }}" data-sidebar-tooltip="Quản lý chức năng"
                        @if(request()->routeIs('manager_setting.*')) aria-current="page" @endif>
                        <span class="admin-sidebar__icon"><i class="fas fa-sliders" aria-hidden="true"></i></span>
                        <span class="admin-sidebar__label">Quản lý chức năng</span>
                    </a>
                @endif
            </section>
        </nav>
    </div>
</aside>

<button type="button" class="admin-sidebar-overlay" id="adminSidebarOverlay"
    aria-label="Đóng thanh điều hướng" tabindex="-1"></button>
