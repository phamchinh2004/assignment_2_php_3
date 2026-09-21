@php
    $isDashboardActive = request()->routeIs('tong.doanh.thu', 'admin.dashboard');
    $isStatisticsActive = request()->routeIs(
        'doanh.thu.theo.nhan.vien',
        'doanh.thu.tu.khach.hang',
        'doanh.thu.ban.than',
        'admin.statistical.*',
        'admin.revenue.*'
    );
@endphp

<div class="admin-sidebar-section">
    <div class="admin-sidebar-section-label">Tổng quan</div>

    @if (Auth::user()->role === 'admin')
        <div class="admin-nav-item {{ $isDashboardActive ? 'active' : '' }}">
            <a class="admin-menu-link" href="{{ route('tong.doanh.thu') }}" data-sidebar-tooltip="Dashboard"
                @if($isDashboardActive) aria-current="page" @endif>
                <span class="admin-menu-icon"><i class="fas fa-chart-pie" aria-hidden="true"></i></span>
                <span class="admin-menu-label">Dashboard</span>
            </a>
        </div>

        <div class="admin-nav-item has-submenu {{ $isStatisticsActive ? 'active' : '' }}">
            <a class="admin-menu-link {{ $isStatisticsActive ? '' : 'collapsed' }}" href="#collapseStatistics"
                data-toggle="collapse" role="button" aria-controls="collapseStatistics"
                aria-expanded="{{ $isStatisticsActive ? 'true' : 'false' }}" data-sidebar-tooltip="Thống kê">
                <span class="admin-menu-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                <span class="admin-menu-label">Thống kê</span>
                <span class="admin-menu-chevron"><i class="fas fa-chevron-down" aria-hidden="true"></i></span>
            </a>
            <div id="collapseStatistics" class="collapse admin-submenu {{ $isStatisticsActive ? 'show' : '' }}"
                data-parent="#accordionSidebar">
                <div class="admin-submenu-panel">
                    <div class="admin-submenu-title">Thống kê</div>
                    <a class="admin-submenu-link {{ request()->routeIs('doanh.thu.theo.nhan.vien') ? 'active' : '' }}"
                        href="{{ route('doanh.thu.theo.nhan.vien') }}">Doanh thu nhân viên</a>
                    <a class="admin-submenu-link {{ request()->routeIs('doanh.thu.tu.khach.hang') ? 'active' : '' }}"
                        href="{{ route('doanh.thu.tu.khach.hang') }}">Doanh thu từ khách hàng</a>
                    <a class="admin-submenu-link {{ request()->routeIs('doanh.thu.ban.than') ? 'active' : '' }}"
                        href="{{ route('doanh.thu.ban.than') }}">Doanh thu bản thân</a>
                </div>
            </div>
        </div>
    @elseif (Auth::user()->role === 'staff')
        <div class="admin-nav-item {{ request()->routeIs('doanh.thu.ban.than') ? 'active' : '' }}">
            <a class="admin-menu-link" href="{{ route('doanh.thu.ban.than') }}" data-sidebar-tooltip="Thống kê"
                @if(request()->routeIs('doanh.thu.ban.than')) aria-current="page" @endif>
                <span class="admin-menu-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span>
                <span class="admin-menu-label">Thống kê</span>
            </a>
        </div>
    @endif
</div>
