@extends('admin.layouts.master')
@section('title')
Danh sách section
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/statistical/doanhThuTheoNhanVien.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@vite('resources/js/admin/statistical/doanhThuTheoNhanVien.js')
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Thống kê doanh thu theo nhân viên</h3>
                </div>
                <div class="card-body">
                    <!-- Bộ lọc -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="dateFrom">Từ ngày:</label>
                            <input type="date" id="dateFrom" class="form-control" value="{{ date('Y-m-01') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="dateTo">Đến ngày:</label>
                            <input type="date" id="dateTo" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="staffSelect">Nhân viên:</label>
                            <select id="staffSelect" class="form-control">
                                <option value="">Tất cả nhân viên</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button id="filterBtn" class="btn btn-primary form-control">
                                <i class="fas fa-filter"></i> Lọc dữ liệu
                            </button>
                        </div>
                    </div>

                    <!-- Tổng quan -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-users"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tổng số nhân viên</span>
                                    <span class="info-box-number" id="totalStaff">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fas fa-dollar-sign"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tổng doanh thu</span>
                                    <span class="info-box-number" id="totalRevenue">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fas fa-credit-card"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tổng giao dịch</span>
                                    <span class="info-box-number" id="totalTransactions">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger">
                                    <i class="fas fa-chart-line"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Doanh thu TB/NV</span>
                                    <span class="info-box-number" id="avgRevenue">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Biểu đồ doanh thu theo nhân viên</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart" height="100"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Top 5 nhân viên</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="topStaffChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bảng chi tiết -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Chi tiết doanh thu theo nhân viên</h4>
                                    <div class="card-tools">
                                        <button id="" class="btn btn-success btn-sm">
                                            <i class="fas fa-download"></i> Xuất Excel
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="revenueTable" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>STT</th>
                                                    <th>Nhân viên</th>
                                                    <th>Email</th>
                                                    <th>Số người mời</th>
                                                    <th>Tổng giao dịch</th>
                                                    <th>Doanh thu</th>
                                                    <th>Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody id="revenueTableBody">
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

<!-- Modal chi tiết -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Chi tiết doanh thu nhân viên</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="detailContent">
                    <!-- Nội dung chi tiết sẽ được load bằng JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->
@endsection