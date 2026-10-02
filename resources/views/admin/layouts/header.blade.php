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
        ['patterns' => ['bug_reports.*'], 'section' => 'Vận hành', 'page' => 'Báo lỗi hệ thống'],
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
    $authorization = app(\App\Services\AuthorizationService::class);
    $isBugReporterRole = in_array($currentRole, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_STAFF], true);
    $canSubmitBugReport = $isBugReporterRole
        && $authorization->can(Auth::user(), config('authorization.capabilities.bug_reports_create'));
    $canReceiveBugReports = $currentRole === \App\Models\User::ROLE_OWNER;
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
        <small class="text-muted ms-2">Giờ Việt Nam (UTC+7)</small>
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

        @if ($canReceiveBugReports)
            <a class="admin-topbar__report-button" href="{{ route('bug_reports.index') }}" aria-label="Mở danh sách báo lỗi hệ thống">
                <i class="fas fa-bug" aria-hidden="true"></i>
                <span>Báo lỗi</span>
            </a>
        @elseif ($isBugReporterRole)
            <button class="admin-topbar__report-button" type="button" data-toggle="modal" data-target="#bugReportModal"
                aria-label="Báo lỗi hệ thống"
                @unless($canSubmitBugReport) disabled title="Tài khoản chưa được cấp quyền Gửi báo lỗi" @endunless>
                <i class="fas {{ $canSubmitBugReport ? 'fa-bug' : 'fa-lock' }}" aria-hidden="true"></i>
                <span>Báo lỗi</span>
            </button>
        @endif

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

@if ($canSubmitBugReport)
    <div class="modal fade" id="bugReportModal" tabindex="-1" role="dialog" aria-labelledby="bugReportModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content admin-bug-report-modal">
                <form id="adminBugReportForm" action="{{ route('bug_reports.store') }}" method="POST" novalidate>
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="bugReportModalTitle">Báo lỗi hệ thống</h5>
                            <p class="admin-bug-report-modal__subtitle">Mô tả lỗi bạn đang gặp. Báo cáo sẽ được gửi trực tiếp tới chủ hệ thống.</p>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="adminBugReportStatus" class="alert d-none" role="status"></div>
                        <div class="form-group">
                            <label for="adminBugReportTitle">Tiêu đề lỗi</label>
                            <input id="adminBugReportTitle" class="form-control" type="text" name="title" maxlength="160"
                                placeholder="Ví dụ: Không thể xác nhận giao dịch">
                            <div id="adminBugReportTitleError" class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="adminBugReportDescription">Mô tả chi tiết</label>
                            <textarea id="adminBugReportDescription" class="form-control" name="description" rows="5" maxlength="5000"
                                placeholder="Bạn thao tác gì, lỗi xảy ra ở bước nào, kết quả mong đợi là gì..."></textarea>
                            <div id="adminBugReportDescriptionError" class="invalid-feedback"></div>
                        </div>
                        <div class="form-group mt-3 mb-0">
                            <label for="adminBugReportImages">Ảnh đính kèm <span class="text-muted font-weight-normal">(không bắt buộc)</span></label>
                            <input id="adminBugReportImages" class="form-control-file" type="file" name="images[]" multiple
                                accept="image/jpeg,image/png,image/webp">
                            <small class="form-text text-muted">Tối đa 5 ảnh JPG/PNG/WEBP, mỗi ảnh tối đa 5MB.</small>
                            <div id="adminBugReportImagesError" class="invalid-feedback d-block"></div>
                            <div id="adminBugReportImagePreview" class="admin-bug-report-images" aria-live="polite"></div>
                        </div>
                        <input id="adminBugReportPageUrl" type="hidden" name="page_url">
                        <input id="adminBugReportUserAgent" type="hidden" name="user_agent">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                        <button id="adminBugReportSubmit" type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane mr-1" aria-hidden="true"></i> Gửi báo lỗi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
