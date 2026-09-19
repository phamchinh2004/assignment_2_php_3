@extends('admin.layouts.master')
@section('title')
Thống kê tổng doanh thu
@endsection

@section('style-libs')
@vite('resources/css/admin/common-modern.css')
@vite('resources/css/admin/statistical/tongDoanhThu.css')
@endsection

@section('script-libs')
@vite('resources/js/admin/statistical/tongDoanhThu.js')
@endsection

@section('content')
<div class="container-fluid py-3">
    <!-- Page Header & Action Bar -->
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon"><i class="fas fa-chart-line"></i></span>
                Thống kê tổng doanh thu
            </h1>
            <p class="page-subtitle">Theo dõi dòng tiền nạp, rút, doanh thu ròng và biến động giao dịch toàn sàn</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button id="refreshBtn" class="btn btn-light-modern" title="Tải lại số liệu">
                <i class="fas fa-sync-alt"></i> Làm mới
            </button>
            <button id="exportBtn" class="btn btn-create-modern" title="Xuất báo cáo CSV">
                <i class="fas fa-file-export"></i> Xuất CSV
            </button>
        </div>
    </div>

    <!-- Filter Control Card -->
    <div class="filter-card-modern mb-4">
        <div class="filter-header-bar d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <!-- Quick Preset Tabs -->
            <div class="quick-preset-group d-flex flex-wrap gap-1">
                <button type="button" class="preset-btn" data-days="0">Hôm nay</button>
                <button type="button" class="preset-btn" data-days="7">7 ngày qua</button>
                <button type="button" class="preset-btn active" data-days="30">30 ngày qua</button>
                <button type="button" class="preset-btn" data-days="this_month">Tháng này</button>
                <button type="button" class="preset-btn" data-days="last_month">Tháng trước</button>
                <button type="button" class="preset-btn" data-days="365">1 năm qua</button>
            </div>

            <!-- Custom Date Range -->
            <div class="custom-range-inputs d-flex align-items-center gap-2 flex-wrap">
                <div class="input-date-wrapper">
                    <span class="input-date-label">Từ</span>
                    <input type="date" id="startDate" class="form-control-modern">
                </div>
                <span class="text-muted"><i class="fas fa-arrow-right"></i></span>
                <div class="input-date-wrapper">
                    <span class="input-date-label">Đến</span>
                    <input type="date" id="endDate" class="form-control-modern">
                </div>
                <button type="button" id="applyFilterBtn" class="btn-filter-apply">
                    <i class="fas fa-filter"></i> Áp dụng
                </button>
            </div>
        </div>
    </div>

    <!-- KPI Summary Cards Grid -->
    <div class="stats-grid mb-4">
        <!-- 1. Doanh thu ròng -->
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Doanh thu ròng (Nạp - Rút)</span>
                <div class="stat-number" id="totalRevenue">
                    <i class="fas fa-spinner fa-spin text-primary"></i>
                </div>
                <div class="stat-subtext">
                    <span id="revenueTrendBadge" class="trend-badge">--</span>
                    <span class="trend-label">so với kỳ trước</span>
                </div>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-wallet"></i>
            </div>
        </div>

        <!-- 2. Tổng nạp tiền -->
        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Tổng nạp tiền</span>
                <div class="stat-number" id="totalDeposit">
                    <i class="fas fa-spinner fa-spin text-success"></i>
                </div>
                <div class="stat-subtext">
                    <span id="depositTrendBadge" class="trend-badge">--</span>
                    <span class="trend-label" id="depositCountLabel">0 lệnh nạp</span>
                </div>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-arrow-circle-down"></i>
            </div>
        </div>

        <!-- 3. Tổng rút tiền -->
        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Tổng rút tiền</span>
                <div class="stat-number" id="totalWithdraw">
                    <i class="fas fa-spinner fa-spin text-warning"></i>
                </div>
                <div class="stat-subtext">
                    <span id="withdrawTrendBadge" class="trend-badge">--</span>
                    <span class="trend-label" id="withdrawCountLabel">0 lệnh rút</span>
                </div>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-arrow-circle-up"></i>
            </div>
        </div>

        <!-- 4. Số giao dịch -->
        <div class="stat-card-modern info">
            <div class="stat-content">
                <span class="stat-label">Tổng giao dịch</span>
                <div class="stat-number" id="totalTransactions">
                    <i class="fas fa-spinner fa-spin text-info"></i>
                </div>
                <div class="stat-subtext">
                    <i class="fas fa-layer-group text-info"></i>
                    <span class="trend-label">Tất cả trạng thái</span>
                </div>
            </div>
            <div class="stat-icon-wrapper info">
                <i class="fas fa-exchange-alt"></i>
            </div>
        </div>

        <!-- 5. Giá trị nạp trung bình -->
        <div class="stat-card-modern teal">
            <div class="stat-content">
                <span class="stat-label">TB mỗi lệnh nạp</span>
                <div class="stat-number" id="avgDeposit">
                    <i class="fas fa-spinner fa-spin text-teal"></i>
                </div>
                <div class="stat-subtext">
                    <i class="fas fa-chart-bar text-teal"></i>
                    <span class="trend-label">Hiệu suất mỗi giao dịch</span>
                </div>
            </div>
            <div class="stat-icon-wrapper teal">
                <i class="fas fa-receipt"></i>
            </div>
        </div>

        <!-- 6. Khách hàng nạp tiền -->
        <div class="stat-card-modern dark">
            <div class="stat-content">
                <span class="stat-label">Khách nạp tiền</span>
                <div class="stat-number" id="uniqueCustomers">
                    <i class="fas fa-spinner fa-spin text-secondary"></i>
                </div>
                <div class="stat-subtext">
                    <i class="fas fa-user-check text-success"></i>
                    <span class="trend-label">Khách phát sinh giao dịch</span>
                </div>
            </div>
            <div class="stat-icon-wrapper dark">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <!-- Charts Section - Row 1 -->
    <div class="row mb-4">
        <!-- Biểu đồ doanh thu theo thời gian -->
        <div class="col-xl-8 col-lg-7 mb-4 mb-lg-0">
            <div class="card-modern h-100">
                <div class="card-header-modern">
                    <h2 class="title-header">
                        <i class="fas fa-chart-area"></i> Xu hướng doanh thu theo thời gian
                    </h2>
                    <span class="badge badge-light-modern" id="chartPeriodBadge">30 ngày qua</span>
                </div>
                <div class="card-body p-4">
                    <div class="chart-container-wrapper" style="position: relative; height: 320px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ cơ cấu dòng tiền -->
        <div class="col-xl-4 col-lg-5">
            <div class="card-modern h-100">
                <div class="card-header-modern">
                    <h2 class="title-header">
                        <i class="fas fa-pie-chart"></i> Cơ cấu Nạp vs Rút
                    </h2>
                    <span class="badge badge-light-modern">Tỷ trọng</span>
                </div>
                <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                    <div class="chart-container-wrapper" style="position: relative; height: 250px; width: 100%;">
                        <canvas id="pieChart"></canvas>
                    </div>
                    <div id="pieSummaryText" class="mt-3 text-center text-muted small"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section - Row 2 -->
    <div class="row mb-4">
        <!-- Biểu đồ cột so sánh Nạp vs Rút -->
        <div class="col-xl-8 col-lg-7 mb-4 mb-lg-0">
            <div class="card-modern h-100">
                <div class="card-header-modern">
                    <h2 class="title-header">
                        <i class="fas fa-chart-bar"></i> Đối chiếu Nạp và Rút tiền theo mốc thời gian
                    </h2>
                    <span class="badge badge-light-modern">So sánh song song</span>
                </div>
                <div class="card-body p-4">
                    <div class="chart-container-wrapper" style="position: relative; height: 300px;">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Biểu đồ phân bố trạng thái giao dịch -->
        <div class="col-xl-4 col-lg-5">
            <div class="card-modern h-100">
                <div class="card-header-modern">
                    <h2 class="title-header">
                        <i class="fas fa-tasks"></i> Phân bố trạng thái giao dịch
                    </h2>
                    <span class="badge badge-light-modern">Trạng thái</span>
                </div>
                <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                    <div class="chart-container-wrapper" style="position: relative; height: 250px; width: 100%;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div id="statusSummaryList" class="w-100 mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng giao dịch gần đây -->
    <div class="card-modern">
        <div class="card-header-modern">
            <h2 class="title-header">
                <i class="fas fa-history"></i> Giao dịch gần đây nhất
            </h2>
            <span class="badge badge-light-modern">10 giao dịch mới nhất</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0" id="recentTransactionsTable">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Mã GD</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Loại giao dịch</th>
                            <th class="text-end">Số tiền</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody id="recentTransactionsBody">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin me-2"></i> Đang tải danh sách giao dịch...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection