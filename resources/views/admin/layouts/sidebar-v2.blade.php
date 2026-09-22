<aside class="admin-sidebar sidebar sidebar-dark" id="accordionSidebar" aria-label="Điều hướng quản trị">
    <div class="admin-sidebar-header">
        <a class="admin-sidebar-brand" href="{{ app(\App\Services\AuthorizationService::class)->can(Auth::user(), config('authorization.capabilities.system_statistics')) ? route('tong.doanh.thu') : route('chat-panel') }}">
            <span class="admin-sidebar-brand-mark" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
            <span class="admin-sidebar-brand-copy">
                <strong>Hệ thống</strong>
                <small>Quản trị vận hành</small>
            </span>
        </a>

        <button type="button" class="admin-sidebar-collapse-btn" id="sidebarToggle"
            aria-label="Thu gọn thanh điều hướng" aria-controls="accordionSidebar" aria-expanded="true">
            <i class="fas fa-angles-left" aria-hidden="true"></i>
        </button>

        <button type="button" class="admin-sidebar-close-btn" id="adminSidebarClose" aria-label="Đóng thanh điều hướng">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <div class="admin-sidebar-scroll">
        <nav class="admin-sidebar-nav" aria-label="Menu chính">
            @include('admin.layouts.sidebar-overview')
            @include('admin.layouts.sidebar-operations')
            @include('admin.layouts.sidebar-settings')
        </nav>
    </div>
</aside>

<button type="button" class="admin-sidebar-overlay" id="adminSidebarOverlay"
    aria-label="Đóng thanh điều hướng" tabindex="-1"></button>
