@extends('admin.layouts.master')
@section('title')
    Danh sách nhân viên
@endsection

@section('style-libs')
    <link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('script-libs')
    <script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = $('#dataTable').DataTable();
            const filterBtns = document.querySelectorAll('.filter-tab-btn');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const filter = this.getAttribute('data-filter') || '';
                    table.search(filter).draw();
                });
            });

            // Polling nhẹ cập nhật trạng thái Online / Offline mỗi 60 giây
            function refreshOnlineStatuses() {
                fetch("{{ route('staff.online.statuses') }}", {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.success) {
                        // Cập nhật số đếm trên KPI và Tabs
                        const onlineCountEl = document.getElementById('kpiOnlineCount');
                        const onlineSubtextEl = document.getElementById('kpiOnlineSubtext');
                        const tabOnlineCountEl = document.getElementById('tabOnlineCount');
                        const tabOfflineCountEl = document.getElementById('tabOfflineCount');

                        if (onlineCountEl) onlineCountEl.textContent = data.online_count;
                        if (onlineSubtextEl) onlineSubtextEl.textContent = `${data.offline_count} đang ngoại tuyến`;
                        if (tabOnlineCountEl) tabOnlineCountEl.textContent = data.online_count;
                        if (tabOfflineCountEl) tabOfflineCountEl.textContent = data.offline_count;

                        // Cập nhật từng dòng nhân viên
                        if (data.staffs && Array.isArray(data.staffs)) {
                            data.staffs.forEach(staff => {
                                const cell = document.querySelector(`.staff-presence-cell[data-staff-id="${staff.id}"]`);
                                if (cell) {
                                    if (staff.is_online) {
                                        cell.setAttribute('data-presence', 'online');
                                        cell.innerHTML = `
                                            <span class="badge-presence online" title="Lần cuối: ${staff.last_seen_formatted}">
                                                <span class="presence-dot"></span> Online
                                            </span>
                                        `;
                                    } else {
                                        cell.setAttribute('data-presence', 'offline');
                                        cell.innerHTML = `
                                            <span class="badge-presence offline" title="Lần cuối: ${staff.last_seen_formatted}">
                                                <span class="presence-dot"></span> ${staff.last_seen_diff}
                                            </span>
                                        `;
                                    }
                                }
                            });
                        }
                    }
                })
                .catch(err => console.debug('Không thể làm mới trạng thái nhân viên:', err));
            }

            // Chạy polling sau mỗi 60 giây và khi quay lại tab
            const presenceInterval = setInterval(refreshOnlineStatuses, 60000);
            window.addEventListener('focus', refreshOnlineStatuses);
        });
    </script>
@endsection

@section('content')
@php
    $totalStaff = !empty($list_staffs) ? $list_staffs->count() : 0;
    $onlineStaff = $onlineStaffCount ?? 0;
    $offlineStaff = $offlineStaffCount ?? ($totalStaff - $onlineStaff);
    $activeStaff = !empty($list_staffs) ? $list_staffs->where('status', 'activated')->count() : 0;
    $bannedStaff = !empty($list_staffs) ? $list_staffs->where('status', 'banned')->count() : 0;
    $totalRevenue = !empty($list_staffs) ? $list_staffs->sum('total_deposit') : 0;
@endphp

<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon teal"><i class="fas fa-user-tie"></i></span>
                Quản lý nhân viên
            </h1>
            <p class="page-subtitle">Quản lý đội ngũ nhân viên, theo dõi trạng thái hoạt động trực tuyến và doanh số đóng góp</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('staff.create') }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-user-plus"></i>
                <span>Thêm nhân viên mới</span>
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        {{-- 1. Tổng nhân viên --}}
        <div class="stat-card-modern teal">
            <div class="stat-content">
                <span class="stat-label">Tổng nhân viên</span>
                <span class="stat-number">{{ number_format($totalStaff) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-users text-teal"></i> Tài khoản nội bộ
                </span>
            </div>
            <div class="stat-icon-wrapper teal">
                <i class="fas fa-user-group"></i>
            </div>
        </div>

        {{-- 2. Đang Online (Trực tuyến) --}}
        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Trực tuyến (Online)</span>
                <span class="stat-number text-success" id="kpiOnlineCount">{{ number_format($onlineStaff) }}</span>
                <span class="stat-subtext text-muted">
                    <span class="presence-dot" style="background:#10b981; width:7px; height:7px;"></span>
                    <span id="kpiOnlineSubtext">{{ number_format($offlineStaff) }} đang ngoại tuyến</span>
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-wifi"></i>
            </div>
        </div>

        {{-- 3. Đã kích hoạt --}}
        <div class="stat-card-modern info">
            <div class="stat-content">
                <span class="stat-label">Tài khoản kích hoạt</span>
                <span class="stat-number text-info">{{ number_format($activeStaff) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-check-circle text-info"></i> Có quyền truy cập
                </span>
            </div>
            <div class="stat-icon-wrapper info">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

        {{-- 4. Tài khoản bị khóa --}}
        <div class="stat-card-modern danger">
            <div class="stat-content">
                <span class="stat-label">Tài khoản bị khóa</span>
                <span class="stat-number text-danger">{{ number_format($bannedStaff) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-lock text-danger"></i> Tạm ngưng quyền
                </span>
            </div>
            <div class="stat-icon-wrapper danger">
                <i class="fas fa-user-lock"></i>
            </div>
        </div>

        {{-- 5. Tổng doanh số nạp --}}
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Tổng doanh số nạp</span>
                <span class="stat-number text-primary">{{ format_money($totalRevenue, 2) }}$</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-chart-line text-primary"></i> Đóng góp nạp tiền
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-sack-dollar"></i>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-address-book"></i> Danh sách tài khoản nhân viên
            </h6>
        </div>

        {{-- Filter Tabs --}}
        <div class="filter-tabs-bar">
            <button type="button" class="filter-tab-btn active" data-filter="">
                <i class="fas fa-layer-group"></i> Tất cả
                <span class="filter-tab-count">{{ $totalStaff }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Online">
                <span class="presence-dot" style="background:#10b981; width:7px; height:7px;"></span> Đang Online
                <span class="filter-tab-count" id="tabOnlineCount">{{ $onlineStaff }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Offline">
                <span class="presence-dot" style="background:#94a3b8; width:7px; height:7px;"></span> Ngoại tuyến
                <span class="filter-tab-count" id="tabOfflineCount">{{ $offlineStaff }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Đã kích hoạt">
                <i class="fas fa-check-circle text-success"></i> Đã kích hoạt
                <span class="filter-tab-count">{{ $activeStaff }}</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="Bị khóa">
                <i class="fas fa-lock text-danger"></i> Bị khóa
                <span class="filter-tab-count">{{ $bannedStaff }}</span>
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Nhân viên</th>
                            <th class="text-center" style="width: 140px;">Hoạt động</th>
                            <th>Người tạo / Giới thiệu</th>
                            <th>Tổng doanh số nạp</th>
                            <th class="text-center">Trạng thái</th>
                            <th>Ngày tham gia</th>
                            <th class="text-center" style="width: 160px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($list_staffs))
                            @foreach ($list_staffs as $index => $item)
                                @php
                                    $isOnline = $item->isOnline();
                                    $presenceText = $isOnline ? 'Online' : 'Offline';
                                @endphp
                                <tr>
                                    <td class="text-center">
                                        <span class="id-chip">#{{ $index + 1 }}</span>
                                    </td>

                                    <td>
                                        <div class="entity-identity-cell">
                                            <div class="user-avatar-circle" style="width: 40px; height: 40px;">
                                                <span>{{ mb_strtoupper(mb_substr($item->full_name ?: ($item->username ?: 'S'), 0, 2)) }}</span>
                                            </div>
                                            <div class="entity-details">
                                                <a class="entity-title" href="{{ route('staff.show', ['staff' => $item->id]) }}">
                                                    {{ $item->full_name ?: 'Chưa đặt tên' }}
                                                </a>
                                                <span class="entity-subtitle">
                                                    <span>@<span>{{ $item->username }}</span></span> • {{ $item->phone ?: 'Chưa có SĐT' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Cột trạng thái hoạt động Online / Offline --}}
                                    <td class="text-center staff-presence-cell" data-staff-id="{{ $item->id }}" data-presence="{{ $isOnline ? 'online' : 'offline' }}">
                                        @if($isOnline)
                                            <span class="badge-presence online" title="Lần cuối: {{ $item->last_seen_formatted }}">
                                                <span class="presence-dot"></span> Online
                                            </span>
                                        @else
                                            <span class="badge-presence offline" title="Lần cuối: {{ $item->last_seen_formatted }}">
                                                <span class="presence-dot"></span> {{ $item->last_seen ? $item->last_seen->diffForHumans() : 'Chưa từng online' }}
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if (!empty($item->referrer))
                                            <div class="d-flex flex-column" style="font-size: 0.8125rem;">
                                                <span class="font-weight-bold text-dark">{{ $item->referrer->full_name }}</span>
                                                <span class="text-muted">@<span>{{ $item->referrer->username }}</span></span>
                                            </div>
                                        @else
                                            <span class="text-muted font-italic">Quản trị viên cấp cao</span>
                                        @endif
                                    </td>

                                    <td>
                                        <strong class="text-success" style="font-size: 0.95rem;">
                                            {{ format_money($item->total_deposit ?? 0, 2) }}$
                                        </strong>
                                    </td>

                                    <td class="text-center">
                                        @if($item->status === 'activated')
                                            <span class="badge-status-modern success"><span class="status-dot"></span> Đã kích hoạt</span>
                                        @elseif($item->status === 'inactivated')
                                            <span class="badge-status-modern warning"><span class="status-dot"></span> Chưa kích hoạt</span>
                                        @else
                                            <span class="badge-status-modern danger"><span class="status-dot"></span> Bị khóa</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="text-muted" style="font-size: 0.8125rem;">
                                            {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '—' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="action-btn-group justify-content-center">
                                            {{-- Xem chi tiết --}}
                                            <a href="{{ route('staff.show', ['staff' => $item->id]) }}"
                                               class="btn-action-icon view" title="Xem chi tiết nhân viên">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            {{-- Phân quyền --}}
                                            <a href="{{ route('staff.edit.permissions', ['id' => $item->id]) }}"
                                               class="btn-action-icon edit" title="Chỉnh sửa quyền hạn" style="color: #4f46e5;">
                                                <i class="fas fa-shield-halved"></i>
                                            </a>

                                            {{-- Sửa tài khoản --}}
                                            <a href="{{ route('staff.edit', ['staff' => $item->id]) }}"
                                               class="btn-action-icon edit" title="Sửa thông tin">
                                                <i class="fas fa-pen"></i>
                                            </a>

                                            {{-- Khóa / Mở khóa --}}
                                            @if($item->status === 'activated')
                                                <a href="{{ route('staff.change.status', ['id' => $item->id]) }}"
                                                   class="btn-action-icon delete" title="Khóa tài khoản"
                                                   onclick="return confirm('Bạn có chắc chắn muốn khóa tài khoản nhân viên này?');">
                                                    <i class="fas fa-lock"></i>
                                                </a>
                                            @else
                                                <a href="{{ route('staff.change.status', ['id' => $item->id]) }}"
                                                   class="btn-action-icon view" title="Kích hoạt / Mở khóa"
                                                   onclick="return confirm('Mở khóa tài khoản nhân viên này?');">
                                                    <i class="fas fa-lock-open"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection