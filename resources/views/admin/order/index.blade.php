@extends('admin.layouts.master')
@section('title')
    Quản lý đơn hàng
@endsection

@section('style-libs')
    <link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    @vite('resources/css/admin/common-modern.css')
    @vite('resources/css/admin/order/index.css')
@endsection

@section('script-libs')
    <script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
    @vite('resources/js/admin/order/index.js')
    <script>
        function handleUpdateStatusHistory(event, element) {
            event.preventDefault();
            const confirmed = confirm('Bạn có chắc chắn muốn cập nhật trạng thái đơn hàng? Hành động này sẽ thêm tất cả các trạng thái từ nhận đơn đến hoàn thành cho các đơn hàng chưa có lịch sử trạng thái.');
            if (confirmed) {
                const spinner = document.getElementById('spinner');
                if (spinner) spinner.hidden = false;
                element.style.pointerEvents = 'none';
                element.style.opacity = '0.6';
                window.location.href = element.href;
            }
            return false;
        }
        function handleUpdateCommissionPaid(event, element) {
            event.preventDefault();
            const confirmed = confirm('Bạn có chắc chắn muốn cập nhật trạng thái đã thanh toán hoa hồng? Hành động này sẽ cập nhật commission_paid = 1 cho tất cả các đơn hàng đã completed trong bảng frozen_orders.');
            if (confirmed) {
                const spinner = document.getElementById('spinner');
                if (spinner) spinner.hidden = false;
                element.style.pointerEvents = 'none';
                element.style.opacity = '0.6';
                window.location.href = element.href;
            }
            return false;
        }
        function handleUpdateFrozenCommissionPercentage(event, element) {
            event.preventDefault();
            const confirmed = confirm('Bạn có chắc chắn muốn cập nhật hoa hồng đơn hàng đóng băng? Hành động này sẽ set commission_percentage = 10 (10%) cho các đơn hàng đóng băng có custom_price != null và commission_percentage = null.');
            if (confirmed) {
                const spinner = document.getElementById('spinner');
                if (spinner) spinner.hidden = false;
                element.style.pointerEvents = 'none';
                element.style.opacity = '0.6';
                window.location.href = element.href;
            }
            return false;
        }
    </script>
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon primary"><i class="fas fa-boxes-packing"></i></span>
                Quản lý đơn hàng
            </h1>
            <p class="page-subtitle">Theo dõi kho đơn hàng mẫu, giá bán, hoa hồng và gán cho các vòng quay thành viên</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a id="btn_create" href="{{ route('order.create') }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-plus"></i>
                <span>Tạo đơn hàng mới</span>
            </a>

            {{-- Bulk actions dropdown --}}
            <div class="dropdown">
                <button class="btn btn-light btn-sm border dropdown-toggle px-3 py-2 font-weight-bold" type="button"
                        id="orderActionsDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                        style="border-radius: 10px; height: 40px;">
                    <i class="fas fa-gear mr-1 text-muted"></i> Thao tác
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow-sm border-0" aria-labelledby="orderActionsDropdown" style="border-radius: 10px; font-size: 13px;">
                    <h6 class="dropdown-header text-uppercase font-weight-bold" style="font-size: 10px; letter-spacing: 0.05em;">Cập nhật hàng loạt</h6>
                    <a class="dropdown-item py-2" href="{{ route('order.add.customer.info') }}" id="add_customer_info">
                        <i class="fas fa-user-plus text-info mr-2"></i> Thêm thông tin khách hàng
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item py-2 text-warning" href="{{ route('order.update.status.history') }}"
                       id="update_status_history" onclick="return handleUpdateStatusHistory(event, this);">
                        <i class="fas fa-history mr-2"></i> Cập nhật lịch sử trạng thái
                    </a>
                    <a class="dropdown-item py-2 text-success" href="{{ route('order.update.commission.paid') }}"
                       id="update_commission_paid" onclick="return handleUpdateCommissionPaid(event, this);">
                        <i class="fas fa-check-circle mr-2"></i> Đánh dấu đã thanh toán hoa hồng
                    </a>
                    <a class="dropdown-item py-2 text-info" href="{{ route('order.update.frozen.commission.percentage') }}"
                       id="update_frozen_commission_percentage" onclick="return handleUpdateFrozenCommissionPercentage(event, this);">
                        <i class="fas fa-snowflake mr-2"></i> Cập nhật hoa hồng đóng băng
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Tổng đơn hàng</span>
                <span class="stat-number text-primary">{{ number_format($total_orders_count ?? 0) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-boxes-stacked text-primary"></i> Đơn hàng trong kho
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-shopping-bag"></i>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Đang hoạt động</span>
                <span class="stat-number text-success">{{ number_format($active_orders_count ?? 0) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-circle-check text-success"></i> Sẵn sàng quay đơn
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-check-double"></i>
            </div>
        </div>

        <div class="stat-card-modern danger">
            <div class="stat-content">
                <span class="stat-label">Tạm ngừng / Khóa</span>
                <span class="stat-number text-danger">{{ number_format($inactive_orders_count ?? 0) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-lock text-danger"></i> Tạm ngưng phân phối
                </span>
            </div>
            <div class="stat-icon-wrapper danger">
                <i class="fas fa-box-archive"></i>
            </div>
        </div>

        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Tổng giá trị đơn</span>
                <span class="stat-number text-warning">{{ format_money($total_orders_value ?? 0, 2) }}$</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-coins text-warning"></i> Giá trị danh mục
                </span>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-hand-holding-dollar"></i>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card user-card">
        {{-- Card Header --}}
        <div class="card-header-modern">
            <h6 class="title-header" id="tittle">
                <i class="fas fa-list-ul"></i>
                Danh sách đơn hàng
            </h6>
        </div>

        {{-- Quick Filter Tabs theo trạng thái --}}
        <div class="filter-tabs-bar" id="statusFilterBar">
            <button type="button" class="filter-tab-btn active" id="btn_all_status">
                <i class="fas fa-layer-group"></i> Tất cả
                <span class="filter-tab-count">{{ $total_orders_count ?? 0 }}</span>
            </button>
            <button type="button" class="filter-tab-btn" id="btn_active_top">
                <i class="fas fa-check-circle text-success"></i> Đang hoạt động
                <span class="filter-tab-count">{{ $active_orders_count ?? 0 }}</span>
            </button>
            <button type="button" class="filter-tab-btn" id="btn_inactive_top">
                <i class="fas fa-lock text-danger"></i> Tạm dừng / Khóa
                <span class="filter-tab-count">{{ $inactive_orders_count ?? 0 }}</span>
            </button>
        </div>

        {{-- Rank Filter Bar --}}
        <div class="order-filter-bar px-4 py-2 border-bottom d-flex align-items-center gap-2 flex-wrap" style="background:#f8fafc;">
            <span class="filter-label text-muted font-weight-bold text-uppercase mr-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                <i class="fas fa-crown text-warning mr-1"></i> Cấp độ:
            </span>
            <button id="all_ranks" class="rank-chip selected">
                Tất cả <span class="rank-count">{{ $total_orders_count ?? 0 }}</span>
            </button>
            @if (!empty($list_ranks))
                @foreach ($list_ranks as $rank)
                    <button id="{{ $rank->id }}" class="rank-chip filter_rank">
                        {{ $rank->name }} <span class="rank-count">{{ $rank->orders_count }}</span>
                    </button>
                @endforeach
            @endif
        </div>

        {{-- Card Body with Modern Responsive Table --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable_list_orders" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">#</th>
                            <th>Sản phẩm & Đơn hàng</th>
                            <th>Giá bán & Hoa hồng</th>
                            <th>Khách hàng & Giao hàng</th>
                            <th style="width: 130px;">Trạng thái</th>
                            <th style="width: 140px;">Lịch sử</th>
                            <th style="width: 130px;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        {{-- Dữ liệu render qua JavaScript DataTable --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
