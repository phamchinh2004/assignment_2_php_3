@extends('admin.layouts.master')
@section('title')
Danh sách người dùng
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/user/index.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@vite('resources/js/admin/user/index.js')
<script>
    window.currentPermissionCode = "quan_ly_tat_ca_nguoi_dung";
</script>
@endsection

@section('content')
@php
    $totalUsers = !empty($users) ? $users->count() : 0;
    $activeUsers = !empty($users) ? $users->where('status', 'activated')->count() : 0;
    $inactivatedUsers = !empty($users) ? $users->where('status', 'inactivated')->count() : 0;
    $lockedUsers = !empty($users) ? $users->whereNotIn('status', ['activated', 'inactivated'])->count() : 0;
    $totalBalance = !empty($users) ? $users->sum('balance') : 0;
    $totalFrozen = !empty($users) ? $users->sum('frozen_balance') : 0;
    $frozenUsersCount = 0;
    $cloneUsersCount = 0;

    if (!empty($users)) {
        foreach ($users as $u) {
            if ($u->clone_account) {
                $cloneUsersCount++;
            }
            if (!empty($u->frozen_orders)) {
                foreach ($u->frozen_orders as $fo) {
                    if ($fo->custom_price !== null && $fo->is_frozen == true) {
                        $frozenUsersCount++;
                        break;
                    }
                }
            }
        }
    }
@endphp

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Header & Action Bar -->
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon"><i class="fas fa-users"></i></span>
                Quản lý người dùng
            </h1>
            <p class="page-subtitle">Theo dõi tài khoản thành viên, số dư, định vị và quyền hạn hệ thống</p>
        </div>
        <div class="d-flex align-items-center gap-2" id="div_btn_create">
            <a id="btn_create" href="{{ route('user.create') }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-user-plus"></i>
                <span>Thêm thành viên mới</span>
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="stats-grid">
        <!-- Stat 1: Tổng người dùng -->
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Tổng thành viên</span>
                <span class="stat-number">{{ number_format($totalUsers) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-shield-alt text-primary"></i> Tài khoản trong hệ thống
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- Stat 2: Đang hoạt động -->
        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Đang hoạt động</span>
                <span class="stat-number text-success">{{ number_format($activeUsers) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-circle-check text-success"></i> Đã kích hoạt
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        <!-- Stat 3: Cần lưu ý / Tạm khóa -->
        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Chưa kích hoạt / Khóa</span>
                <span class="stat-number text-warning">{{ number_format($inactivatedUsers + $lockedUsers) }}</span>
                <span class="stat-subtext text-muted">
                    <span>{{ $inactivatedUsers }} chờ kích hoạt</span> • <span>{{ $lockedUsers }} bị khóa</span>
                </span>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-user-lock"></i>
            </div>
        </div>

        <!-- Stat 4: Tổng số dư lưu hành -->
        <div class="stat-card-modern info">
            <div class="stat-content">
                <span class="stat-label">Tổng số dư thành viên</span>
                <span class="stat-number text-primary">{{ format_money($totalBalance, 2) }}$</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-snowflake text-info"></i> {{ format_money($totalFrozen, 2) }}$ đóng băng
                </span>
            </div>
            <div class="stat-icon-wrapper info">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card user-card">
        <!-- Card Header -->
        <div class="card-header-modern">
            <h6 class="title-header" id="tittle">
                <i class="fas fa-list-ul"></i>
                Danh sách người dùng
            </h6>
        </div>

        <!-- Quick Filter Tabs -->
        <div class="filter-tabs-bar" id="statusFilterBar">
            <button type="button" class="filter-tab-btn active" data-filter="">
                <i class="fas fa-layer-group"></i> Tất cả
                <span class="filter-tab-count">{{ $totalUsers }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Đã kích hoạt">
                <i class="fas fa-check-circle text-success"></i> Hoạt động
                <span class="filter-tab-count">{{ $activeUsers }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Chưa kích hoạt">
                <i class="fas fa-clock text-warning"></i> Chưa kích hoạt
                <span class="filter-tab-count">{{ $inactivatedUsers }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Bị khóa">
                <i class="fas fa-lock text-danger"></i> Bị khóa
                <span class="filter-tab-count">{{ $lockedUsers }}</span>
            </button>
            @if($frozenUsersCount > 0)
            <button type="button" class="filter-tab-btn" data-filter="Đóng băng">
                <i class="fas fa-snowflake text-info"></i> Đóng băng
                <span class="filter-tab-count">{{ $frozenUsersCount }}</span>
            </button>
            @endif
            @if($cloneUsersCount > 0)
            <button type="button" class="filter-tab-btn" data-filter="Clone">
                <i class="fas fa-clone text-secondary"></i> Clone
                <span class="filter-tab-count">{{ $cloneUsersCount }}</span>
            </button>
            @endif
        </div>

        <!-- Card Body with Modern Responsive Table -->
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">#</th>
                            <th>Khách hàng</th>
                            <th>Số dư & Tài chính</th>
                            <th>Vị trí & Khu vực</th>
                            <th>Trạng thái</th>
                            <th>Lịch sử</th>
                            <th style="width: 170px;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        @if (!empty($users) && $users->count() > 0)
                        @foreach ($users as $index => $item)
                        @php
                        $should_show_button = true;
                        $frozen_order_id = null;
                        if (!empty($item->frozen_orders)) {
                            foreach ($item->frozen_orders as $frozen_order) {
                                if ($frozen_order->custom_price !== null && $frozen_order->is_frozen == true) {
                                    $should_show_button = false;
                                    $frozen_order_id = $frozen_order->id;
                                    break;
                                }
                            }
                        }
                        $initials = mb_strtoupper(mb_substr($item->full_name ?: ($item->username ?: 'U'), 0, 2));
                        @endphp
                        <tr id="user-{{ $item->id }}" class="user-row">
                            <!-- Col 1: STT -->
                            <td class="text-center">
                                <span class="user-id-chip">#{{ $index + 1 }}</span>
                            </td>

                            <!-- Col 2: Khách hàng (User Profile) -->
                            <td>
                                <div class="user-identity-cell">
                                    <div class="user-avatar-circle" title="{{ $item->full_name ?: $item->username }}">
                                        @if(!empty($item->avatar))
                                            <img src="{{ get_user_avatar($item) }}" alt="{{ $item->username }}" class="user-avatar-img">
                                        @else
                                            <span>{{ $initials }}</span>
                                        @endif
                                    </div>
                                    <div class="user-details">
                                        <div class="d-flex align-items-center gap-2">
                                            <a class="user-link" href="{{ route('user.edit', ['user' => $item->id]) }}" title="{{ $item->full_name }}">
                                                {{ Str::limit($item->full_name ?: 'Chưa đặt tên', 26, '...') }}
                                            </a>
                                            <span class="user-id-chip">ID: {{ $item->id }}</span>
                                        </div>
                                        <div class="user-meta-row">
                                            <span class="copy-badge btn-copy-text" data-copy="{{ $item->username }}" title="Nhấp để sao chép username">
                                                <span>@<span>{{ $item->username }}</span></span>
                                                <i class="fas fa-copy ms-1"></i>
                                            </span>
                                            @if($item->rank)
                                                <span class="rank-pill">
                                                    <i class="fas fa-crown"></i> {{ $item->rank->name }}
                                                </span>
                                            @else
                                                <span class="rank-pill no-rank">
                                                    <i class="fas fa-minus"></i> Chưa có cấp
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <!-- Hidden fallback for backward compatibility -->
                                <div style="display: none;" class="info-box">
                                    <span class="info-value">{{ $item->id }}</span>
                                    <span class="info-value">{{ $item->username }}</span>
                                </div>
                            </td>

                            <!-- Col 3: Số dư & Tài chính -->
                            <td>
                                <div class="finance-box">
                                    <div>
                                        <span class="balance-highlight" title="Số dư khả dụng">
                                            <i class="fas fa-wallet text-success"></i>
                                            {{ format_money($item->balance ?? 0, 5) }}$
                                        </span>
                                    </div>
                                    <div>
                                        <span class="frozen-balance-chip" title="Số dư đang đóng băng">
                                            <i class="fas fa-snowflake"></i>
                                            {{ format_money($item->frozen_balance ?? 0, 5) }}$
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 4: Vị trí & Khu vực -->
                            <td>
                                <div class="location-box">
                                    <div class="location-place">
                                        @if($item->location_country_code)
                                            <span class="fs-6">{{ country_flag($item->location_country_code) }}</span>
                                        @endif
                                        <span>
                                            {{ $item->location_city ?: 'Chưa rõ' }}@if($item->location_country), {{ $item->location_country }}@endif
                                        </span>
                                    </div>
                                    @if($item->location_latitude !== null && $item->location_longitude !== null)
                                        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $item->location_latitude }},{{ $item->location_longitude }}"
                                           class="btn-map-modern"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           title="Xem vị trí tọa độ trên Google Maps">
                                            <i class="fas fa-location-dot text-danger"></i>
                                            <span>Bản đồ</span>
                                        </a>
                                    @else
                                        <small class="text-muted">
                                            <i class="fas fa-location-crosshairs mr-1"></i>Chưa định vị
                                        </small>
                                    @endif
                                </div>
                            </td>

                            <!-- Col 5: Trạng thái -->
                            <td>
                                <div class="status-container">
                                    @if($item->status == "activated")
                                        <span class="status-badge active">
                                            <span class="status-dot"></span> Đã kích hoạt
                                        </span>
                                    @elseif($item->status == "inactivated")
                                        <span class="status-badge warning">
                                            <span class="status-dot"></span> Chưa kích hoạt
                                        </span>
                                    @else
                                        <span class="status-badge danger">
                                            <span class="status-dot"></span> Bị khóa
                                        </span>
                                    @endif

                                    @if (!$should_show_button)
                                        <span class="tag-pill tag-frozen" title="Đang có đơn hàng đóng băng">
                                            <i class="fas fa-snowflake"></i> Đóng băng
                                        </span>
                                    @endif

                                    @if ($item->clone_account)
                                        <span class="tag-pill tag-clone" title="Tài khoản nhân bản">
                                            <i class="fas fa-clone"></i> Clone
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Col 6: Lịch sử -->
                            <td>
                                <div class="time-box">
                                    <div class="time-item" title="Ngày đăng ký: {{ $item->created_at->format('d/m/Y H:i:s') }}">
                                        <i class="fas fa-user-plus"></i>
                                        <span>Tạo: {{ $item->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="time-item" title="Cập nhật lần cuối: {{ $item->updated_at->format('d/m/Y H:i:s') }}">
                                        <i class="fas fa-clock-rotate-left"></i>
                                        <span>Sửa: {{ $item->updated_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Col 7: Thao tác -->
                            <td>
                                <div class="actions-wrapper">
                                    <!-- Nút Nạp tiền chính -->
                                    <button type="button" 
                                            class="btn-action-deposit btn_plus_money"
                                            id="{{ $item->id }}"
                                            data-user-id="{{ $item->id }}"
                                            data-user-name="{{ $item->full_name }}"
                                            data-user-username="{{ $item->username }}"
                                            data-user-balance="{{ format_money($item->balance ?? 0, 5) }}"
                                            title="Nạp tiền vào tài khoản người dùng">
                                        <i class="fas fa-circle-plus"></i>
                                        <span>Nạp tiền</span>
                                    </button>

                                    <!-- Cụm nút icon hành động -->
                                    <div class="action-icon-group">
                                        <a href="{{ route('user.show', ['user' => $item->id]) }}"
                                           class="btn-icon-modern view"
                                           title="Xem chi tiết người dùng">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('chat-panel') }}#user-{{ $item->id }}" 
                                           class="btn-icon-modern chat"
                                           title="Nhắn tin với người dùng">
                                            <i class="fas fa-comment-dots"></i>
                                        </a>

                                        @if($item->status == "activated")
                                        <a href="{{ route('user.change.status', ['user' => $item->id]) }}" 
                                           class="btn-icon-modern lock" 
                                           title="Khóa tài khoản này">
                                            <i class="fas fa-lock"></i>
                                        </a>
                                        @elseif($item->status == "inactivated")
                                        <a href="{{ route('user.change.status', ['user' => $item->id]) }}" 
                                           class="btn-icon-modern unlock"
                                           title="Kích hoạt tài khoản">
                                            <i class="fas fa-circle-check"></i>
                                        </a>
                                        @else
                                        <a href="{{ route('user.change.status', ['user' => $item->id]) }}" 
                                           class="btn-icon-modern unlock"
                                           title="Mở khóa tài khoản">
                                            <i class="fas fa-lock-open"></i>
                                        </a>
                                        @endif

                                        <a href="{{ route('user.frozen.order.interface', ['user' => $item->id]) }}"
                                           class="btn-icon-modern freeze"
                                           title="Quản lý đóng băng đơn hàng">
                                            <i class="fas fa-snowflake"></i>
                                        </a>

                                        <a href="{{ route('user.edit', ['user' => $item->id]) }}" 
                                           class="btn-icon-modern edit"
                                           title="Chỉnh sửa thông tin">
                                            <i class="fas fa-pen-to-square"></i>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                <p class="text-muted fw-semibold">Không tìm thấy người dùng nào trong hệ thống!</p>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<!-- Modal Cộng / Nạp Tiền Hiện Đại -->
<div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content deposit-modal">
            <div class="modal-header-gradient">
                <h5 class="modal-title" id="depositModalLabel">
                    <i class="fas fa-wallet"></i> Nạp tiền cho khách hàng
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- User Info Card -->
                <div class="user-info-card mb-3">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1 user-name" id="modalUserName">---</h6>
                            <p class="mb-0 text-muted small">
                                <span id="modalUserUsername" class="fw-semibold">---</span> • 
                                Số dư hiện tại: <strong class="text-success fw-bold" id="modalUserBalance">0$</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Amount Input with Quick Presets -->
                <div class="mb-3">
                    <label for="depositAmount" class="form-label fw-bold text-dark mb-1">
                        <i class="fas fa-dollar-sign text-success me-1"></i> Số tiền nạp (USD)
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-money-bill-wave text-success"></i>
                        </span>
                        <input type="number" 
                               class="form-control form-control-lg fw-bold text-center" 
                               id="depositAmount" 
                               placeholder="Nhập số tiền..." 
                               min="0" 
                               step="0.01"
                               autocomplete="off">
                        <span class="input-group-text bg-light">$</span>
                    </div>
                    
                    <!-- Quick Amount Buttons -->
                    <div class="quick-amount-grid mt-2">
                        <button type="button" class="quick-amount-btn" data-add="10">+10$</button>
                        <button type="button" class="quick-amount-btn" data-add="50">+50$</button>
                        <button type="button" class="quick-amount-btn" data-add="100">+100$</button>
                        <button type="button" class="quick-amount-btn" data-add="500">+500$</button>
                        <button type="button" class="quick-amount-btn" data-add="1000">+1,000$</button>
                        <button type="button" class="quick-amount-btn" data-add="5000">+5,000$</button>
                    </div>

                    <div class="amount-preview mt-2" id="amountPreview"></div>
                </div>

                <!-- Deposit Type Toggle Segment -->
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark mb-2">
                        <i class="fas fa-tags text-info me-1"></i> Loại tiền nạp
                    </label>
                    <div class="deposit-type-toggle">
                        <input type="radio" class="btn-check" name="depositType" id="depositTypeReal" value="real" checked>
                        <label class="btn btn-outline-success" for="depositTypeReal">
                            <i class="fas fa-credit-card fa-lg text-success"></i>
                            <div>
                                <div class="fw-bold">Tiền nạp thực</div>
                                <small class="text-muted d-block">Khách hàng nạp</small>
                            </div>
                        </label>

                        <input type="radio" class="btn-check" name="depositType" id="depositTypeBonus" value="bonus">
                        <label class="btn btn-outline-warning" for="depositTypeBonus">
                            <i class="fas fa-gift fa-lg text-warning"></i>
                            <div>
                                <div class="fw-bold">Tiền thưởng</div>
                                <small class="text-muted d-block">Hệ thống khuyến mại</small>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Summary Breakdown Card -->
                <div class="summary-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Số dư hiện tại:</span>
                        <strong class="text-dark" id="summaryCurrentBalance">0$</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Số tiền nạp:</span>
                        <strong class="text-success" id="summaryDepositAmount">+0$</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Số dư sau nạp:</span>
                        <strong class="text-primary fs-5 fw-extrabold" id="summaryNewBalance">0$</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 gap-2">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Hủy
                </button>
                <button type="button" class="btn btn-success-gradient px-4" id="confirmDepositBtn">
                    <i class="fas fa-check me-1"></i> Tiếp tục xác nhận
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xác nhận -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content confirm-modal">
            <div class="modal-body text-center p-4">
                <div class="confirm-icon mb-3">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h5 class="mb-2 fw-bold text-dark" id="confirmTitle">Xác nhận nạp tiền</h5>
                <p class="text-muted small mb-4" id="confirmMessage">Bạn chắc chắn muốn thực hiện giao dịch này?</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light border px-3" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Hủy
                    </button>
                    <button type="button" class="btn btn-primary-gradient px-4" id="confirmYesBtn">
                        <i class="fas fa-check me-1"></i> Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thành công -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content success-modal">
            <div class="modal-body text-center p-4">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h5 class="mb-2 fw-bold text-success">Thành công!</h5>
                <p class="text-muted small mb-4" id="successMessage">Nạp tiền thành công</p>
                <button type="button" class="btn btn-success-gradient px-4 w-100" id="successOkBtn">
                    <i class="fas fa-check me-1"></i> Hoàn tất
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lỗi -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content error-modal">
            <div class="modal-body text-center p-4">
                <div class="error-icon mb-3">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <h5 class="mb-2 fw-bold text-danger">Có lỗi xảy ra!</h5>
                <p class="text-muted small mb-4" id="errorMessage">Đã có lỗi xảy ra trong quá trình nạp tiền</p>
                <button type="button" class="btn btn-danger-gradient px-4 w-100" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Đóng
                </button>
            </div>
        </div>
    </div>
</div>

@endsection