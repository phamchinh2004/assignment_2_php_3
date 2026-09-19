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
        });
    </script>
@endsection

@section('content')
@php
    $totalStaff = !empty($list_staffs) ? $list_staffs->count() : 0;
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
            <p class="page-subtitle">Quản lý đội ngũ nhân viên, phân quyền hạn và theo dõi doanh số đóng góp</p>
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

        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Đang hoạt động</span>
                <span class="stat-number text-success">{{ number_format($activeStaff) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-check-circle text-success"></i> Có quyền truy cập
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-user-check"></i>
            </div>
        </div>

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
            <button type="button" class="filter-tab-btn" data-filter="Đã kích hoạt">
                <i class="fas fa-check-circle text-success"></i> Đang hoạt động
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