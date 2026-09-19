@extends('admin.layouts.master')
@section('title')
Thống kê doanh thu cá nhân
@endsection

@section('style-libs')
@vite('resources/css/admin/common-modern.css')
@vite('resources/css/admin/statistical/doanhThuBanThan.css')
@endsection

@section('script-libs')
@vite('resources/js/admin/statistical/doanhThuBanThan.js')
@endsection

@section('content')
<div class="container-fluid py-3">
    <!-- Page Header & Action Bar -->
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon"><i class="fas fa-user-shield"></i></span>
                Thống kê doanh thu cá nhân
            </h1>
            <p class="page-subtitle">Theo dõi doanh số, dòng tiền nạp - rút và các giao dịch từ khách hàng do bạn quản lý</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button id="btnRefresh" class="btn btn-light-modern" title="Tải lại số liệu">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
        </div>
    </div>

    <!-- Filter Card with Quick Presets -->
    <div class="filter-card-modern mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="quick-preset-group d-flex flex-wrap gap-1">
                <button type="button" class="preset-btn active" data-range="7_days">7 ngày qua</button>
                <button type="button" class="preset-btn" data-range="30_days">30 ngày qua</button>
                <button type="button" class="preset-btn" data-range="3_months">3 tháng qua</button>
                <button type="button" class="preset-btn" data-range="6_months">6 tháng qua</button>
                <button type="button" class="preset-btn" data-range="1_year">1 năm qua</button>
            </div>
            <span class="text-muted small" id="currentRangeText">Khoảng thời gian: 7 ngày qua</span>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="stats-grid mb-4">
        <!-- 1. Doanh thu nạp -->
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Doanh thu nạp từ khách</span>
                <div class="stat-number" id="totalRevenue">0 USD</div>
                <div class="stat-subtext">
                    <span id="growthRateBadge" class="trend-badge">--</span>
                    <span class="trend-label">so với kỳ trước</span>
                </div>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
        </div>

        <!-- 2. Khách rút tiền -->
        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Tổng khách rút tiền</span>
                <div class="stat-number" id="totalWithdraw">0 USD</div>
                <div class="stat-subtext">
                    <i class="fas fa-arrow-circle-up text-warning"></i>
                    <span class="trend-label" id="withdrawCountLabel">0 lệnh rút</span>
                </div>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- 3. Lợi nhuận ròng -->
        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Lợi nhuận ròng (Nạp - Rút)</span>
                <div class="stat-number" id="netRevenue">0 USD</div>
                <div class="stat-subtext">
                    <i class="fas fa-coins text-success"></i>
                    <span class="trend-label">Dòng tiền thực thu</span>
                </div>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>

        <!-- 4. TB mỗi giao dịch nạp -->
        <div class="stat-card-modern teal">
            <div class="stat-content">
                <span class="stat-label">TB / Giao dịch nạp</span>
                <div class="stat-number" id="avgTransaction">0 USD</div>
                <div class="stat-subtext">
                    <i class="fas fa-receipt text-teal"></i>
                    <span class="trend-label" id="totalTransactionsLabel">0 giao dịch</span>
                </div>
            </div>
            <div class="stat-icon-wrapper teal">
                <i class="fas fa-calculator"></i>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Biểu đồ biến động doanh thu & số giao dịch -->
        <div class="col-xl-8 col-lg-7 mb-4 mb-lg-0">
            <div class="card-modern h-100">
                <div class="card-header-modern">
                    <h2 class="title-header">
                        <i class="fas fa-chart-line"></i> Biến động Doanh thu & Lượt giao dịch
                    </h2>
                    <span class="badge badge-light-modern">Hai trục chỉ số</span>
                </div>
                <div class="card-body p-4">
                    <div class="chart-container-wrapper" style="position: relative; height: 320px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ cơ cấu giao dịch -->
        <div class="col-xl-4 col-lg-5">
            <div class="card-modern h-100">
                <div class="card-header-modern">
                    <h2 class="title-header">
                        <i class="fas fa-pie-chart"></i> Phân bổ Nạp vs Rút
                    </h2>
                    <span class="badge badge-light-modern">Cơ cấu</span>
                </div>
                <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                    <div class="chart-container-wrapper" style="position: relative; height: 260px; width: 100%;">
                        <canvas id="transactionTypeChart"></canvas>
                    </div>
                    <div id="personalTypeSummary" class="mt-3 text-center text-muted small"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Chart (hiển thị khi chọn kỳ xem dài hạn) -->
    <div class="card-modern mb-4" id="monthlyChartContainer" style="display: none;">
        <div class="card-header-modern">
            <h2 class="title-header">
                <i class="fas fa-calendar-alt"></i> Xu hướng doanh thu tổng hợp theo tháng
            </h2>
            <span class="badge badge-light-modern">Dài hạn</span>
        </div>
        <div class="card-body p-4">
            <div class="chart-container-wrapper" style="position: relative; height: 280px;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bảng giao dịch cá nhân gần đây -->
    <div class="card-modern">
        <div class="card-header-modern">
            <h2 class="title-header">
                <i class="fas fa-history"></i> Lịch sử giao dịch từ khách hàng quản lý
            </h2>
            <span class="badge badge-light-modern">Giao dịch mới nhất</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0" id="transactionsTable">
                    <thead>
                        <tr>
                            <th style="width: 90px;">Mã GD</th>
                            <th>Thời gian</th>
                            <th>Khách hàng</th>
                            <th>Tài khoản</th>
                            <th>Loại giao dịch</th>
                            <th class="text-end">Số tiền</th>
                            <th class="text-center">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin me-2"></i> Đang tải dữ liệu giao dịch...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection