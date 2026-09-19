@extends('admin.layouts.master')
@section('title')
Thống kê doanh thu theo nhân viên
@endsection

@section('style-libs')
@vite('resources/css/admin/common-modern.css')
@vite('resources/css/admin/statistical/doanhThuTheoNhanVien.css')
@endsection

@section('script-libs')
@vite('resources/js/admin/statistical/doanhThuTheoNhanVien.js')
@endsection

@section('content')
<div class="container-fluid py-3">
    <!-- Page Header & Action Bar -->
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon teal"><i class="fas fa-user-tie"></i></span>
                Thống kê doanh thu theo nhân viên
            </h1>
            <p class="page-subtitle">Đánh giá hiệu quả kinh doanh, số khách giới thiệu và doanh số của từng nhân viên</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button id="refreshBtn" class="btn btn-light-modern" title="Tải lại số liệu">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
            <button id="exportBtn" class="btn btn-create-modern" title="Xuất danh sách ra CSV">
                <i class="fas fa-file-export"></i> Xuất CSV
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
            <span class="text-muted small" id="currentFilterText">Khoảng thời gian: Tháng này</span>
        </div>

        <!-- Custom Filters Row -->
        <div class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label for="dateFrom" class="form-label-modern">Từ ngày:</label>
                <div class="input-date-wrapper w-100">
                    <input type="date" id="dateFrom" class="form-control-modern w-100" value="{{ date('Y-m-01') }}">
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="dateTo" class="form-label-modern">Đến ngày:</label>
                <div class="input-date-wrapper w-100">
                    <input type="date" id="dateTo" class="form-control-modern w-100" value="{{ date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-lg-4 col-md-8">
                <label for="staffSelect" class="form-label-modern">Lọc theo nhân viên:</label>
                <select id="staffSelect" class="form-select-modern w-100">
                    <option value="">Tất cả nhân viên</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <button id="filterBtn" class="btn-filter-apply w-100 justify-content-center">
                    <i class="fas fa-filter"></i> Lọc dữ liệu
                </button>
            </div>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="stats-grid mb-4">
        <!-- 1. Tổng số nhân viên -->
        <div class="stat-card-modern info">
            <div class="stat-content">
                <span class="stat-label">Tổng số nhân viên</span>
                <div class="stat-number" id="totalStaff">0</div>
                <div class="stat-subtext">
                    <i class="fas fa-id-badge text-info"></i>
                    <span class="trend-label">Nhân viên hoạt động</span>
                </div>
            </div>
            <div class="stat-icon-wrapper info">
                <i class="fas fa-users-cog"></i>
            </div>
        </div>

        <!-- 2. Tổng doanh thu mang về -->
        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Tổng doanh thu mang về</span>
                <div class="stat-number" id="totalRevenue">0 USD</div>
                <div class="stat-subtext">
                    <i class="fas fa-donate text-success"></i>
                    <span class="trend-label">Từ khách được giới thiệu</span>
                </div>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>

        <!-- 3. Tổng giao dịch -->
        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Tổng giao dịch</span>
                <div class="stat-number" id="totalTransactions">0</div>
                <div class="stat-subtext">
                    <i class="fas fa-receipt text-warning"></i>
                    <span class="trend-label">Lệnh nạp thành công</span>
                </div>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-credit-card"></i>
            </div>
        </div>

        <!-- 4. Doanh thu TB / Nhân viên -->
        <div class="stat-card-modern teal">
            <div class="stat-content">
                <span class="stat-label">Doanh thu TB / Nhân viên</span>
                <div class="stat-number" id="avgRevenue">0 USD</div>
                <div class="stat-subtext">
                    <i class="fas fa-balance-scale text-teal"></i>
                    <span class="trend-label">Năng suất bình quân</span>
                </div>
            </div>
            <div class="stat-icon-wrapper teal">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7 mb-4 mb-lg-0">
            <div class="card-modern h-100">
                <div class="card-header-modern">
                    <h2 class="title-header">
                        <i class="fas fa-chart-bar"></i> Doanh thu của từng nhân viên
                    </h2>
                    <span class="badge badge-light-modern">Biểu đồ so sánh</span>
                </div>
                <div class="card-body p-4">
                    <div class="chart-container-wrapper" style="position: relative; height: 320px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-5">
            <div class="card-modern h-100">
                <div class="card-header-modern">
                    <h2 class="title-header">
                        <i class="fas fa-trophy"></i> Top 5 nhân viên xuất sắc
                    </h2>
                    <span class="badge badge-light-modern">Xếp hạng</span>
                </div>
                <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                    <div class="chart-container-wrapper" style="position: relative; height: 260px; width: 100%;">
                        <canvas id="topStaffChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Ranking & Detail Table -->
    <div class="card-modern">
        <div class="card-header-modern">
            <h2 class="title-header">
                <i class="fas fa-list-ol"></i> Bảng xếp hạng doanh số nhân viên
            </h2>
            <span class="badge badge-light-modern" id="tableCountBadge">0 nhân viên</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0" id="revenueTable">
                    <thead>
                        <tr>
                            <th style="width: 70px;" class="text-center">Hạng</th>
                            <th>Nhân viên</th>
                            <th>Email</th>
                            <th class="text-center">Khách mời</th>
                            <th class="text-center">Tổng GD</th>
                            <th class="text-end">Doanh thu</th>
                            <th style="width: 140px;">Tỷ trọng</th>
                            <th style="width: 110px;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="revenueTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin me-2"></i> Đang tải dữ liệu nhân viên...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết nhân viên -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--radius-lg, 16px); overflow: hidden;">
            <div class="modal-header border-bottom py-3 px-4" style="background: #f8fafc;">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 m-0" id="modalStaffTitle">
                    <i class="fas fa-user-circle text-primary"></i> Chi tiết doanh thu nhân viên
                </h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detailContent">
                <!-- Nội dung được tải động bằng JS -->
            </div>
            <div class="modal-footer border-top py-2 px-4" style="background: #f8fafc;">
                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection