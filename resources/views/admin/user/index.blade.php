@extends('admin.layouts.master')
@section('title')
Danh sách người dùng
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@vite('resources/js/admin/user/index.js')
<script>
    window.currentPermissionCode = "quan_ly_tat_ca_nguoi_dung";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary fs-5" id="tittle">Danh sách người dùng</h6>
            </div>
            <div id="div_btn_create" class="mb-2 d-flex justify-content-end">
                <a id="btn_create" href="{{route('user.create')}}" class="btn btn-success text-decoration-none btn-sm"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped rounded" id="dataTable" width="100%" cellspacing="0">
                    <thead class="position-sticky top-0">
                        <tr class="bg-primary">
                            <th class="tittle_column">#</th>
                            <th class="tittle_column">Thông tin</th>
                            <th class="tittle_column">Trạng thái</th>
                            <th class="tittle_column">Ngày tạo</th>
                            <th class="tittle_column">Ngày cập nhật</th>
                            <th class="tittle_column">Thao tác</th>
                        </tr>
                    </thead>
                    <tfoot class="sticky-bottom">
                        <tr>
                            <th class="tittle_column">#</th>
                            <th class="tittle_column">Thông tin</th>
                            <th class="tittle_column">Trạng thái</th>
                            <th class="tittle_column">Ngày tạo</th>
                            <th class="tittle_column">Ngày cập nhật</th>
                            <th class="tittle_column">Thao tác</th>
                        </tr>
                    </tfoot>
                    <tbody id="tbody">

                        @if (!empty($users))
                        @foreach ($users as $index =>$item)
                        @php
                        $should_show_button = true;
                        $frozen_order_id = null;
                        if (!empty($item->frozen_orders)) {
                        foreach ($item->frozen_orders as $frozen_order) {
                        if ($frozen_order->custom_price !== null && $frozen_order->is_frozen == true) {
                        $should_show_button = false;
                        $frozen_order_id=$frozen_order->id;
                        break;
                        }
                        }
                        }
                        @endphp
                        <tr class="small">
                            <td>{{$index+1}}</td>
                            <td>
                                <div class="d-flex flex-column nowrap">
                                    <span>ID: {{ $item->id }}</span>
                                    <span>Họ và tên: <b><a class="cspt" href="{{ route('user.edit',['user'=>$item->id]) }}">{{Str::limit( $item->full_name ,30,'...')}}</a></b></span>
                                    <span>Tên đăng nhập: <b>{{ $item->username }}</b></span>
                                    <span>Số điện thoại: <b>{{ $item->phone }}</b></span>
                                    <span>Email: <b>{{ $item->email?:"Chưa có" }}</b></span>
                                    <span>Số dư: <b>{{ format_money($item->balance) }}$</b></span>
                                    @if (!empty($item->referrer))
                                    <span>Được giới thiệu bởi: <b>{{ $item->referrer->full_name ." (".$item->referrer->username.")" }}</b></span>
                                    @endif
                                    <span>Cấp bậc: <b>{!! optional($item->rank)->name ?? '<i class="text-secondary">Chưa có cấp bậc</i>' !!}</b></span>
                                </div>
                            </td>
                            <td>
                                @if($item->status=="activated")
                                <span class="text-white badge badge-success">Đã kích hoạt</span>
                                @elseif($item->status=="inactivated")
                                <span class="text-white badge badge-warning">Chưa kích hoạt</span>
                                @else
                                <span class="text-white badge badge-danger">Bị khóa</span>
                                @endif
                                @if (!$should_show_button)
                                <span class="text-white badge badge-danger">Đã đóng băng đơn hàng</span>
                                @endif
                                @if ($item->clone_account)
                                <span class="text-white badge badge-primary">Đây là tài khoản clone</span>
                                @endif
                            </td>
                            <td>{{$item->created_at}}</td>
                            <td>{{$item->updated_at}}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <div class="d-flex flex-row justify-content-center mt-1">
                                        @if($item->status=="activated")
                                        <a href="{{ route('user.change.status',['user'=>$item->id]) }}" class="btn btn-danger btn-sm d-flex align-items-center mr-1"><i class="fas fa-lock fa-sm p-2"></i></a>
                                        @elseif($item->status=="inactivated")
                                        <a href="{{ route('user.change.status',['user'=>$item->id]) }}" class="btn btn-primary btn-sm d-flex align-items-center mr-1"><i class="fas fa-circle-check fa-sm p-2"></i></a>
                                        @else
                                        <a href="{{ route('user.change.status',['user'=>$item->id]) }}" class="btn btn-success btn-sm d-flex align-items-center mr-1"><i class="fas fa-lock-open fa-sm p-2"></i></a>
                                        @endif


                                       
                                        <a href="{{ route('user.frozen.order.interface',['user'=>$item->id]) }}"
                                            class="btn btn-dark btn-sm d-flex align-items-center mr-1">
                                            <i class="fas fa-snowflake fa-sm p-2 text-white"></i>
                                        </a>
                                    
                                    </div>
                                    <div class="d-flex justify-content-center flex-row mt-1">
                                        <a href="{{ route('user.edit',['user'=>$item->id]) }}" class="btn btn-warning btn-sm d-flex align-items-center mr-1"><i class="fas fa-pen-to-square fa-sm p-2"></i></a>
                                        <span class="btn btn-success btn-sm btn_plus_money" id="{{ $item->id }}"><i class="fas fa-plus fa-sm"></i> $</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="9" align="center">Chưa có người dùng nào!</td>
                        </tr>
                        @endif


                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->
@endsection