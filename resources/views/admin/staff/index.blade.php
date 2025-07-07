@extends('admin.layouts.master')
@section('title')
Danh sách nhân viên
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
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary fs-5" id="tittle">Danh sách nhân viên</h6>
            </div>
            <div id="div_btn_create" class="mb-2 d-flex justify-content-end">
                <a id="btn_create" href="{{route('staff.create')}}" class="btn btn-success text-decoration-none btn-sm"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped rounded" id="dataTable" width="100%" cellspacing="0">
                    <thead class="position-sticky top-0">
                        <tr class="bg-primary">
                            <th class="tittle_column">#</th>
                            <th class="tittle_column">Thông tin</th>
                            <th class="tittle_column">Tổng doanh số</th>
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
                            <th class="tittle_column">Tổng doanh số</th>
                            <th class="tittle_column">Trạng thái</th>
                            <th class="tittle_column">Ngày tạo</th>
                            <th class="tittle_column">Ngày cập nhật</th>
                            <th class="tittle_column">Thao tác</th>
                        </tr>
                    </tfoot>
                    <tbody id="tbody">

                        @if (!empty($list_staffs))
                        @foreach ($list_staffs as $index =>$item)
                        <tr class="small">
                            <td>{{$index+1}}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span>Họ và tên: <b><a class="cspt" href="{{ route('staff.edit',['staff'=>$item->id]) }}">{{Str::limit( $item->full_name ,30,'...')}}</a></b></span>
                                    <span>Tên đăng nhập: <b>{{ $item->username }}</b></span>
                                    <span>Số điện thoại: <b>{{ $item->phone }}</b></span>
                                    @if (!empty($item->referrer))
                                    <span>Được tạo bởi: <b>{{ $item->referrer->full_name ." (".$item->referrer->username.")" }}</b></span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="text-success fw-bold">{{ format_money($item->total_deposit) }}$</span>
                            </td>
                            <td>
                                @if($item->status=="activated")
                                <span class="text-white badge badge-success">Đã kích hoạt</span>
                                @elseif($item->status=="inactivated")
                                <span class="text-white badge badge-warning">Chưa kích hoạt</span>
                                @else
                                <span class="text-white badge badge-danger">Bị khóa</span>
                                @endif
                            </td>
                            <td>{{$item->created_at}}</td>
                            <td>{{$item->updated_at}}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <div class="d-flex flex-row justify-content-center mt-1">
                                        @if($item->status=="activated")
                                        <a href="{{ route('staff.change.status',['id'=>$item->id]) }}" class="btn btn-danger btn-sm d-flex align-items-center mr-1"><i class="fas fa-lock fa-sm p-2"></i></a>
                                        @elseif($item->status=="inactivated")
                                        <a href="{{ route('staff.change.status',['id'=>$item->id]) }}" class="btn btn-primary btn-sm d-flex align-items-center mr-1"><i class="fas fa-circle-check fa-sm p-2"></i></a>
                                        @else
                                        <a href="{{ route('staff.change.status',['id'=>$item->id]) }}" class="btn btn-success btn-sm d-flex align-items-center mr-1"><i class="fas fa-lock-open fa-sm p-2"></i></a>
                                        @endif

                                    </div>
                                    <div class="d-flex justify-content-center flex-row mt-1">
                                        <a href="{{ route('staff.edit',['staff'=>$item->id]) }}" class="btn btn-warning btn-sm d-flex align-items-center mr-1"><i class="fas fa-pen-to-square fa-sm p-2"></i></a>
                                        <a href="{{ route('staff.edit.permissions',['id'=>$item->id]) }}" class="btn btn-primary btn-sm d-flex align-items-center mr-1"><i class="fas fa-gear fa-sm p-2"></i></a>
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