@extends('admin.layouts.master')
@section('title')
    Danh sách đơn hàng
@endsection

@section('style-libs')
    <!-- Custom styles for this page -->
    <link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    @vite('resources/css/admin/order/index.css')
@endsection

@section('script-libs')
    <!-- Page level plugins -->
    <script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <!-- Page level custom scripts -->
    <script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
    <script>
        window.currentPermissionCode = "quan_ly_don_hang";
    </script>
    @vite('resources/js/admin/order/index.js')
    <script>
        function handleUpdateStatusHistory(event, element) {
            event.preventDefault();
            
            const confirmed = confirm('Bạn có chắc chắn muốn cập nhật trạng thái đơn hàng? Hành động này sẽ thêm tất cả các trạng thái từ nhận đơn đến hoàn thành cho các đơn hàng chưa có lịch sử trạng thái.');
            
            if (confirmed) {
                // Hiển thị spinner
                const spinner = document.getElementById('spinner');
                if (spinner) {
                    spinner.hidden = false;
                }
                
                // Disable link để tránh click nhiều lần
                element.style.pointerEvents = 'none';
                element.style.opacity = '0.6';
                
                // Redirect đến route
                window.location.href = element.href;
            }
            
            return false;
        }

        function handleUpdateCommissionPaid(event, element) {
            event.preventDefault();
            
            const confirmed = confirm('Bạn có chắc chắn muốn cập nhật trạng thái đã thanh toán hoa hồng? Hành động này sẽ cập nhật commission_paid = 1 cho tất cả các đơn hàng đã completed trong bảng frozen_orders.');
            
            if (confirmed) {
                // Hiển thị spinner
                const spinner = document.getElementById('spinner');
                if (spinner) {
                    spinner.hidden = false;
                }
                
                // Disable link để tránh click nhiều lần
                element.style.pointerEvents = 'none';
                element.style.opacity = '0.6';
                
                // Redirect đến route
                window.location.href = element.href;
            }
            
            return false;
        }

        function handleUpdateFrozenCommissionPercentage(event, element) {
            event.preventDefault();
            
            const confirmed = confirm('Bạn có chắc chắn muốn cập nhật hoa hồng đơn hàng đóng băng? Hành động này sẽ set commission_percentage = 10 cho các đơn hàng đóng băng có custom_price != null và commission_percentage = null.');
            
            if (confirmed) {
                // Hiển thị spinner
                const spinner = document.getElementById('spinner');
                if (spinner) {
                    spinner.hidden = false;
                }
                
                // Disable link để tránh click nhiều lần
                element.style.pointerEvents = 'none';
                element.style.opacity = '0.6';
                
                // Redirect đến route
                window.location.href = element.href;
            }
            
            return false;
        }
    </script>
@endsection

@section('content')
    <!-- Begin Page Content -->
    <div class="container-fluid">
        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <div class="d-flex flex-column">
                    <h6 class="m-0 font-weight-bold text-primary" id="tittle">Danh sách đơn hàng</h6>
                    <div class="d-flex flex-row mt-2">
                        <div id="btn_active" href="{{route('order.index')}}">
                            <span class="btn btn-outline-primary btn-sm" id="active">Đang hoạt động</span>
                        </div>
                        <div id="btn_inactive" href="">
                            <span class="ml-3 btn btn-outline-danger btn-sm" id="inactive">Ngừng hoạt động</span>
                        </div>
                    </div>
                </div>
                <div id="div_btn_create" class="mb-2 d-flex justify-content-end">
                    <a id="btn_create" href="{{route('order.create')}}"
                        class="btn btn-success text-decoration-none btn-sm"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
                </div>
            </div>
            <section class="filter_vip d-flex justify-content-between">
                <div>
                    <button id="all_ranks" class="btn btn-outline-primary btn-sm">Tất cả
                        ({{ $total_orders_count ?? 0 }})</button>
                    @if (!empty($list_ranks))
                        @foreach ($list_ranks as $rank)
                            <button id="{{ $rank->id }}"
                                class="btn btn-outline-primary btn-sm filter_rank">{{ $rank->name . " (" . $rank->orders_count . ")" }}</button>
                        @endforeach
                    @endif
                </div>
                <div class="dropdown">
                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="orderActionsDropdown"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="orderActionsDropdown">
                        <a class="dropdown-item" href="{{ route('order.update.commission.percentage') }}"
                            id="update_order_rose">
                            <i class="fas fa-percent mr-2"></i>Cập nhật hoa hồng đơn hàng
                        </a>
                        <a class="dropdown-item" href="{{ route('order.add.customer.info') }}"
                            id="add_customer_info">
                            <i class="fas fa-user-plus mr-2"></i>Thêm thông tin khách hàng
                        </a>
                        <a class="dropdown-item" href="{{ route('order.update.status.history') }}"
                            id="update_status_history"
                            onclick="return handleUpdateStatusHistory(event, this);">
                            <i class="fas fa-history mr-2"></i>Cập nhật trạng thái đơn hàng
                        </a>
                        <a class="dropdown-item" href="{{ route('order.update.commission.paid') }}"
                            id="update_commission_paid"
                            onclick="return handleUpdateCommissionPaid(event, this);">
                            <i class="fas fa-check-circle mr-2"></i>Cập nhật trạng thái đã thanh toán hoa hồng
                        </a>
                        <a class="dropdown-item" href="{{ route('order.update.frozen.commission.percentage') }}"
                            id="update_frozen_commission_percentage"
                            onclick="return handleUpdateFrozenCommissionPercentage(event, this);">
                            <i class="fas fa-snowflake mr-2"></i>Cập nhật hoa hồng đơn hàng đóng băng
                        </a>
                    </div>
                </div>
            </section>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped rounded" id="dataTable_list_orders" width="100%"
                        cellspacing="0">
                        <thead class="position-sticky top-0">
                            <tr class="bg-primary">
                                <th class="tittle_column">#</th>
                                <th class="tittle_column">ID</th>
                                <th class="tittle_column">Mã/Ảnh</th>
                                <th class="tittle_column">Thông tin cơ bản</th>
                                <th class="tittle_column">Thông tin đơn hàng</th>
                                <th class="tittle_column">Trạng thái</th>
                                <th class="tittle_column">Ngày tạo / Cập nhật</th>
                                <th class="tittle_column">Thao tác</th>
                            </tr>
                        </thead>
                        <tfoot class="sticky-bottom">
                            <tr>
                                <th class="tittle_column">#</th>
                                <th class="tittle_column">ID</th>
                                <th class="tittle_column">Mã/Ảnh</th>
                                <th class="tittle_column">Thông tin cơ bản</th>
                                <th class="tittle_column">Thông tin đơn hàng</th>
                                <th class="tittle_column">Trạng thái</th>
                                <th class="tittle_column">Ngày tạo / Cập nhật</th>
                                <th class="tittle_column">Thao tác</th>
                            </tr>
                        </tfoot>
                        <tbody id="tbody">
                            {{-- Dữ liệu sẽ được load tự động qua JavaScript theo filter rank --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- /.container-fluid -->
@endsection