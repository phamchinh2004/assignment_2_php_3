<div class="admin-sidebar-section">
    <div class="admin-sidebar-section-label">Vận hành</div>

    <div class="admin-nav-item {{ request()->routeIs('chat-panel') ? 'active' : '' }}">
        <a class="admin-menu-link" href="{{ route('chat-panel') }}" data-sidebar-tooltip="Quản lý tin nhắn"
            @if(request()->routeIs('chat-panel')) aria-current="page" @endif>
            <span class="admin-menu-icon"><i class="fas fa-message" aria-hidden="true"></i></span>
            <span class="admin-menu-label">Quản lý tin nhắn</span>
        </a>
    </div>

    <div class="admin-nav-item {{ request()->routeIs('user.*') ? 'active' : '' }}">
        <a class="admin-menu-link" href="{{ route('user.index') }}" data-sidebar-tooltip="Quản lý khách hàng"
            @if(request()->routeIs('user.*')) aria-current="page" @endif>
            <span class="admin-menu-icon"><i class="fas fa-users" aria-hidden="true"></i></span>
            <span class="admin-menu-label">Quản lý khách hàng</span>
        </a>
    </div>

    @include('admin.layouts.sidebar-transactions')
    @include('admin.layouts.sidebar-order-links')
</div>
