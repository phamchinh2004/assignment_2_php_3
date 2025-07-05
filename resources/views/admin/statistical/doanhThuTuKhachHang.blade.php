@extends('admin.layouts.master')
@section('title')
Danh sách section
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/statistical/doanhThuTuKhachHang.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@vite('resources/js/admin/statistical/doanhThuTuKhachHang.js')
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line mr-2"></i>
                        Thống kê doanh thu từ khách hàng
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Bộ lọc -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="filterType">Loại thống kê:</label>
                            <select id="filterType" class="form-control">
                                <option value="daily">Theo ngày</option>
                                <option value="monthly">Theo tháng</option>
                                <option value="yearly">Theo năm</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="startDate">Từ ngày:</label>
                            <input type="date" id="startDate" class="form-control" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="endDate">Đến ngày:</label>
                            <input type="date" id="endDate" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button type="button" id="btnFilter" class="btn btn-primary btn-block">
                                <i class="fas fa-filter"></i> Lọc dữ liệu
                            </button>
                        </div>
                    </div>

                    <!-- Thống kê tổng quan -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3 id="totalRevenue">0</h3>
                                    <p>Tổng doanh thu</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3 id="totalTransactions">0</h3>
                                    <p>Tổng giao dịch</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3 id="totalCustomers">0</h3>
                                    <p>Tổng khách hàng</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3 id="avgTransaction">0</h3>
                                    <p>Trung bình/giao dịch</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calculator"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ doanh thu theo thời gian -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Biểu đồ doanh thu theo thời gian</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ top khách hàng -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Top 10 khách hàng theo doanh thu</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="topCustomersChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Phân bố doanh thu theo khách hàng</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueDistributionChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bảng chi tiết -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Chi tiết doanh thu theo khách hàng</h3>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="customerRevenueTable" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>STT</th>
                                                    <th>Khách hàng</th>
                                                    <th>Số điện thoại</th>
                                                    <th>Số giao dịch</th>
                                                    <th>Tổng nạp tiền</th>
                                                    <th>Lần nạp cuối</th>
                                                </tr>
                                            </thead>
                                            <tbody id="customerRevenueTableBody">
                                                <!-- Dữ liệu sẽ được load bằng JavaScript -->
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