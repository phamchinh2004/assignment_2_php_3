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
@vite('resources/js/admin/order/index.js')
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
                <a id="btn_create" href="{{route('order.create')}}" class="btn btn-success text-decoration-none btn-sm"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
            </div>
        </div>
        <section class="filter_vip d-flex justify-content-between">
            <div>
                <button id="all_ranks" class="btn btn-outline-primary btn-sm">Tất cả ({{ count($list_orders) }})</button>
                @if (!empty($list_ranks))
                @foreach ($list_ranks as $rank)
                <button id="{{ $rank->id }}" class="btn btn-outline-primary btn-sm filter_rank">{{ $rank->name. " (".$rank->orders_count.")" }}</button>
                @endforeach
                @endif
            </div>
            <a class="btn btn-primary btn-sm" href="{{ route('order.update.commission.percentage') }}" id="update_order_rose">Cập nhật hoa hồng đơn hàng</a>
        </section>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped rounded" id="dataTable_list_orders" width="100%" cellspacing="0">
                    <thead class="position-sticky top-0">
                        <tr class="bg-primary">
                            <th class="tittle_column">#</th>
                            <th class="tittle_column">ID</th>
                            <th class="tittle_column">Mã</th>
                            <th class="tittle_column">Ảnh</th>
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
                            <th class="tittle_column">ID</th>
                            <th class="tittle_column">Mã</th>
                            <th class="tittle_column">Ảnh</th>
                            <th class="tittle_column">Thông tin</th>
                            <th class="tittle_column">Trạng thái</th>
                            <th class="tittle_column">Ngày tạo</th>
                            <th class="tittle_column">Ngày cập nhật</th>
                            <th class="tittle_column">Thao tác</th>
                        </tr>
                    </tfoot>
                    <tbody id="tbody">
                        @if (!empty($list_orders))
                        @foreach ($list_orders as $index =>$item)
                        <tr class="small">
                            <td>{{$index+1}}</td>
                            <td>{{$item->id}}</td>
                            <td>{{$item->order_code}}</td>
                            <td>
                                <div class="d-flex justify-content-center align-items-center">
                                    <img class="index_image" src="{{ asset('uploads/orders/images/'.$item->image) }}" alt="">
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span>Tên: <b><a class="cspt" href="">{{Str::limit( $item->name ,30,'...')}}</a></b></span>
                                    <span>Giá: <b>{{ $item->price }}$</b></span>
                                    <span>Số lượng: <b>{{ $item->quantity }}</b></span>
                                    <span>Hoa hồng: <b>{{ $item->commission_percentage }}%</b></span>
                                </div>
                            </td>
                            <td>
                                @if($item->status==1)
                                <span class="text-white badge badge-success">Đang hoạt động</span>
                                @else
                                <span class="text-white badge badge-danger">Ngừng họạt động</span>
                                @endif
                            </td>
                            <td>{{$item->created_at}}</td>
                            <td>{{$item->updated_at}}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <div class="d-flex flex-row justify-content-center">
                                        <a href="" class="btn btn-secondary btn-sm d-flex align-items-center mr-1"><i class="fas fa-eye fa-sm p-2"></i></a>
                                        @if($item->status==1)
                                        <a href="{{ route('order.change.status',['order'=>$item->id]) }}" class="btn btn-danger btn-sm d-flex align-items-center"><i class="fas fa-lock fa-sm p-2"></i></a>
                                        @else
                                        <a href="{{ route('order.change.status',['order'=>$item->id]) }}" class="btn btn-success btn-sm d-flex align-items-center"><i class="fas fa-lock-open fa-sm p-2"></i></a>
                                        @endif
                                    </div>
                                    <div class="d-flex flex-row justify-content-center mt-1">
                                        <a href="{{ route('order.edit',['order'>$item->id]) }}" class="btn btn-warning btn-sm d-flex align-items-center mr-1"><i class="fas fa-pen-to-square fa-sm p-2"></i></a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="9" align="center">Không có đơn hàng nào có sẵn!</td>
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