@extends('admin.layouts.master')
@section('title')
Danh sách section
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/statistical/doanhThuBanThan.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@vite('resources/js/admin/statistical/doanhThuBanThan.js')
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i>
                        Thống kê doanh thu cá nhân
                    </h3>
                    <div class="card-tools">
                        <select id="timeRange" class="form-control form-control-sm" style="width: 200px;">
                            <option value="7_days">7 ngày qua</option>
                            <option value="30_days">30 ngày qua</option>
                            <option value="3_months">3 tháng qua</option>
                            <option value="6_months">6 tháng qua</option>
                            <option value="1_year">1 năm qua</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Loading indicator -->
                    <div id="loading" class="text-center" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Đang tải dữ liệu...</p>
                    </div>

                    <!-- Statistics Cards -->
                    <div id="statsCards" class="row mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-white mb-0">Tổng doanh thu</h6>
                                            <h4 class="text-white mb-0" id="totalRevenue">0 VND</h4>
                                        </div>
                                        <div class="text-white">
                                            <i class="fas fa-dollar-sign fa-2x"></i>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <span class="text-white-50" id="growthRate">0%</span>
                                        <small class="text-white-50"> so với kỳ trước</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-success">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-white mb-0">Tổng giao dịch</h6>
                                            <h4 class="text-white mb-0" id="totalTransactions">0</h4>
                                        </div>
                                        <div class="text-white">
                                            <i class="fas fa-exchange-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-white mb-0">Trung bình/giao dịch</h6>
                                            <h4 class="text-white mb-0" id="avgTransaction">0 VND</h4>
                                        </div>
                                        <div class="text-white">
                                            <i class="fas fa-calculator fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card bg-info">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-white mb-0">Lợi nhuận ròng</h6>
                                            <h4 class="text-white mb-0" id="netRevenue">0 VND</h4>
                                        </div>
                                        <div class="text-white">
                                            <i class="fas fa-chart-pie fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Biểu đồ doanh thu theo thời gian</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Phân bổ giao dịch</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="transactionTypeChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Chart (for longer periods) -->
                    <div class="row mt-4" id="monthlyChartContainer" style="display: none;">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Biểu đồ doanh thu theo tháng</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="monthlyChart" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Giao dịch gần đây</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="transactionsTable">
                                            <thead>
                                                <tr>
                                                    <th>Ngày</th>
                                                    <th>Khách hàng</th>
                                                    <th>Loại</th>
                                                    <th>Số tiền</th>
                                                    <th>Trạng thái</th>
                                                </tr>
                                            </thead>
                                            <tbody id="transactionsTableBody">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>
@endsection