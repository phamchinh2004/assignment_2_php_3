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

    @if (app(\App\Services\AuthorizationService::class)->can(Auth::user(), config('authorization.capabilities.statistics_view_overview')))
        <div class="admin-nav-item {{ $isDashboardActive ? 'active' : '' }}">
            <a class="admin-menu-link" href="{{ route('tong.doanh.thu') }}" data-sidebar-tooltip="Dashboard"
                @if($isDashboardActive) aria-current="page" @endif>
                <span class="admin-menu-icon"><i class="fas fa-chart-pie" aria-hidden="true"></i></span>
                <span class="admin-menu-label">Dashboard</span>
            </a>
        </div>

    @endif
    @if(app(\App\Services\AuthorizationService::class)->canAny(Auth::user(), ['statistics.view-staff', 'statistics.view-customers', 'statistics.view-personal']))
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
                    @if(app(\App\Services\AuthorizationService::class)->can(Auth::user(), config('authorization.capabilities.statistics_view_staff')))
<a class="admin-submenu-link {{ request()->routeIs('doanh.thu.theo.nhan.vien') ? 'active' : '' }}"
                        href="{{ route('doanh.thu.theo.nhan.vien') }}">Doanh thu nhân viên</a>
@endif
                    @if(app(\App\Services\AuthorizationService::class)->can(Auth::user(), config('authorization.capabilities.statistics_view_customers')))
<a class="admin-submenu-link {{ request()->routeIs('doanh.thu.tu.khach.hang') ? 'active' : '' }}"
                        href="{{ route('doanh.thu.tu.khach.hang') }}">Doanh thu từ khách hàng</a>
@endif
                    @if(app(\App\Services\AuthorizationService::class)->can(Auth::user(), config('authorization.capabilities.statistics_view_personal')))
<a class="admin-submenu-link {{ request()->routeIs('doanh.thu.ban.than') ? 'active' : '' }}"
                        href="{{ route('doanh.thu.ban.than') }}">Doanh thu bản thân</a>
@endif
                </div>
            </div>
        </div>
    @endif
</div>
