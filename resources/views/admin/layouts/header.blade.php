<nav id="adminHeaderState"
    class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow"
    style="display: flex !important;"
    data-state-url="{{ route('header.state') }}"
    data-notification-read-url="{{ route('header.notifications.read', ['notification' => '__NOTIFICATION__']) }}"
    data-notification-read-all-url="{{ route('header.notifications.read-all') }}"
    data-user-id="{{ Auth::id() }}">

    <button id="adminSidebarOpen" class="btn btn-link d-lg-none rounded-circle mr-3" type="button"
        aria-label="Mở thanh điều hướng" aria-controls="accordionSidebar" aria-expanded="false">
        <i class="fa fa-bars" aria-hidden="true"></i>
    </button>

    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
        <div class="input-group">
            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                aria-label="Search" aria-describedby="basic-addon2">
            <div class="input-group-append">
                <button class="btn btn-primary" type="button">
                    <i class="fas fa-search fa-sm"></i>
                </button>
            </div>
        </div>
    </form>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown no-arrow d-sm-none">
            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-search fa-fw"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in" aria-labelledby="searchDropdown">
                <form class="form-inline mr-auto w-100 navbar-search">
                    <div class="input-group">
                        <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                            aria-label="Search" aria-describedby="basic-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button">
                                <i class="fas fa-search fa-sm"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </li>

        <div class="d-flex align-items-center mr-3 fw-bold admin-header-referral">
            <span>Mã mời của tôi: {{ Auth::user()->referral_code }}</span>
        </div>

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell fa-fw"></i>
                <span id="adminNotificationBadge" class="badge badge-danger badge-counter" hidden></span>
            </a>
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in admin-header-dropdown"
                aria-labelledby="alertsDropdown">
                <div class="dropdown-header admin-header-dropdown__header">
                    <span>Thông báo</span>
                    <button id="adminNotificationReadAll" type="button" class="admin-header-action" hidden>
                        Đánh dấu đã đọc
                    </button>
                </div>
                <div id="adminNotificationList" class="admin-header-list" aria-live="polite">
                    <div class="admin-header-state">Đang tải thông báo...</div>
                </div>
                <button id="adminNotificationLoadMore" type="button"
                    class="dropdown-item text-center small text-gray-500 admin-header-load-more" hidden>
                    Tải thêm
                </button>
            </div>
        </li>

        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-envelope fa-fw"></i>
                <span id="adminMessageBadge" class="badge badge-danger badge-counter" hidden></span>
            </a>
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in admin-header-dropdown"
                aria-labelledby="messagesDropdown">
                <h6 class="dropdown-header">Tin nhắn</h6>
                <div id="adminMessageList" class="admin-header-list" aria-live="polite">
                    <div class="admin-header-state">Đang tải tin nhắn...</div>
                </div>
                <a class="dropdown-item text-center small text-gray-500" href="{{ route('chat-panel') }}">
                    Xem tất cả tin nhắn
                </a>
            </div>
        </li>

        <div class="topbar-divider d-none d-sm-block"></div>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600">{{ Auth::user()->username ?? Auth::user()->full_name }}</span>
                <img class="img-profile rounded-circle" src="{{ asset('theme/admin/img/undraw_profile.svg') }}" alt="Avatar">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#changePasswordModal">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Đổi mật khẩu
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>
    </ul>
</nav>
