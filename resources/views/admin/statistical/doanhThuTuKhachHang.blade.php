@extends('admin.layouts.master')
@section('title')
Thống kê doanh thu từ khách hàng
@endsection

@section('style-libs')
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/common-modern.css')
@vite('resources/css/admin/statistical/doanhThuTuKhachHang.css')
@endsection

@section('script-libs')
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/statistical/doanhThuTuKhachHang.js')
@endsection

@section('content')
<div class="container-fluid py-3">
    <!-- Page Header & Action Bar -->
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon info"><i class="fas fa-users"></i></span>
                Thống kê doanh thu từ khách hàng
            </h1>
            <p class="page-subtitle">Phân tích dòng tiền nạp từ khách, nhận diện khách hàng VIP và phân bổ doanh số</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button id="btnRefresh" class="btn btn-light-modern" title="Tải lại số liệu">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card-modern mb-4">
        <!-- Quick Preset Tabs -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-3 border-bottom">
            <div class="quick-preset-group d-flex flex-wrap gap-1">
                <button type="button" class="preset-btn" data-preset="today">Hôm nay</button>
                <button type="button" class="preset-btn" data-preset="7_days">7 ngày qua</button>
                <button type="button" class="preset-btn active" data-preset="this_month">Tháng này</button>
                <button type="button" class="preset-btn" data-preset="last_month">Tháng trước</button>
                <button type="button" class="preset-btn" data-preset="year">Năm nay</button>
            </div>
            <span class="text-muted small" id="filterStatusLabel">Thống kê theo: Tháng này</span>
        </div>

        <!-- Custom Controls Row -->
        <div class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-4">
                <label for="filterType" class="form-label-modern">Chế độ xem:</label>
                <select id="filterType" class="form-select-modern w-100">
                    <option value="daily" selected>Theo ngày</option>
                    <option value="monthly">Theo tháng</option>
                    <option value="yearly">Theo năm</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-4">
                <label for="startDate" class="form-label-modern">Từ ngày:</label>
                <div class="input-date-wrapper w-100">
                    <input type="date" id="startDate" class="form-control-modern w-100" value="{{ date('Y-m-01') }}">
                </div>
            </div>
            <div class="col-lg-3 col-md-4">
                <label for="endDate" class="form-label-modern">Đến ngày:</label>
                <div class="input-date-wrapper w-100">
                    <input type="date" id="endDate" class="form-control-modern w-100" value="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-lg-3 col-md-12">
                <button type="button" id="btnFilter" class="btn-filter-apply w-100 justify-content-center">
                    <i class="fas fa-filter"></i> Lọc dữ liệu
                </button>
            </div>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="stats-grid mb-4">
        <!-- 1. Tổng doanh thu nạp -->
        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Tổng doanh thu nạp</span>
                <div class="stat-number" id="totalRevenue">0 USD</div>
                <div class="stat-subtext">
                    <span id="revTrendBadge" class="trend-badge">--</span>
                    <span class="trend-label">so với kỳ trước</span>
                </div>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>

        <!-- 2. Tổng giao dịch -->
        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Tổng lượt nạp</span>
                <div class="stat-number" id="totalTransactions">0</div>
                <div class="stat-subtext">
                    <i class="fas fa-check-circle text-success"></i>
                    <span class="trend-label">Giao dịch thành công</span>
                </div>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-exchange-alt"></i>
            </div>
        </div>

        <!-- 3. Tổng khách nạp -->
        <div class="stat-card-modern info">
            <div class="stat-content">
                <span class="stat-label">Khách nạp tiền</span>
                <div class="stat-number" id="totalCustomers">0</div>
                <div class="stat-subtext">
                    <span id="custTrendBadge" class="trend-badge">--</span>
                    <span class="trend-label">khách phát sinh nạp</span>
                </div>
            </div>
            <div class="stat-icon-wrapper info">
                <i class="fas fa-users"></i>
            </div>
        </div>

        <!-- 4. Trung bình mỗi lượt nạp -->
        <div class="stat-card-modern teal">
            <div class="stat-content">
                <span class="stat-label">TB / Lượt nạp</span>
                <div class="stat-number" id="avgTransaction">0 USD</div>
                <div class="stat-subtext">
                    <i class="fas fa-calculator text-teal"></i>
                    <span class="trend-label">Quy mô mỗi lệnh nạp</span>
                </div>
            </div>
            <div class="stat-icon-wrapper teal">
                <i class="fas fa-chart-bar"></i>
            </div>
        </div>

        <!-- 5. Khách hàng nạp cao nhất -->
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Khách nạp cao nhất</span>
                <div class="stat-number fs-4 text-truncate" style="max-width: 200px;" id="topCustomerAmount">0 USD</div>
                <div class="stat-subtext text-truncate" style="max-width: 200px;">
                    <i class="fas fa-crown text-warning"></i>
                    <span class="fw-bold text-dark" id="topCustomerName">Chưa có</span>
                </div>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-star"></i>
            </div>
        </div>
    </div>

    <!-- Biểu đồ doanh thu theo thời gian -->
    <div class="card-modern mb-4">
        <div class="card-header-modern">
            <h2 class="title-header">
                <i class="fas fa-chart-line"></i> Xu hướng doanh thu từ khách hàng
            </h2>
            <span class="badge badge-light-modern" id="chartTimeBadge">Dòng tiền nạp</span>
        </div>
        <div class="card-body p-4">
            <div class="chart-container-wrapper" style="position: relative; height: 320px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 2 Biểu đồ: Top khách hàng & Phân bổ doanh thu -->
    <div class="row mb-4">
        <div class="col-xl-7 col-lg-6 mb-4 mb-lg-0">
            <div class="card-modern h-100">
                <div class="card-header-modern">
                    <h2 class="title-header">
                        <i class="fas fa-medal"></i> Top 10 khách hàng nạp nhiều nhất
                    </h2>
                    <span class="badge badge-light-modern">Xếp hạng VIP</span>
                </div>
                <div class="card-body p-4">
                    <div class="chart-container-wrapper" style="position: relative; height: 320px;">
                        <canvas id="topCustomersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5 col-lg-6">
            <div class="card-modern h-100">
                <div class="card-header-modern">
                    <h2 class="title-header">
                        <i class="fas fa-chart-pie"></i> Cơ cấu phân bổ doanh thu
                    </h2>
                    <span class="badge badge-light-modern">Top 5 vs Khác</span>
                </div>
                <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                    <div class="chart-container-wrapper" style="position: relative; height: 260px; width: 100%;">
                        <canvas id="revenueDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng chi tiết doanh thu theo khách hàng -->
    <div class="card-modern">
        <div class="card-header-modern">
            <h2 class="title-header">
                <i class="fas fa-table"></i> Danh sách khách hàng nạp tiền chi tiết
            </h2>
            <span class="badge badge-light-modern" id="tableCustomerCountBadge">0 khách hàng</span>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="customerRevenueTable" class="table table-modern align-middle w-100">
                    <thead>
                        <tr>
                            <th style="width: 60px;" class="text-center">STT</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th class="text-center">Số giao dịch</th>
                            <th class="text-end">Tổng nạp tiền</th>
                            <th class="text-end">Lần nạp cuối</th>
                        </tr>
                    </thead>
                    <tbody id="customerRevenueTableBody">
                        <!-- Render bằng DataTable -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection