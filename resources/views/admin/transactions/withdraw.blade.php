@extends('admin.layouts.master')
@section('title')
Danh sách cấp độ
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/transaction/withdraw.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@vite('resources/js/admin/transaction/withdraw.js')
<script>
    window.currentPermissionCode = "quan_ly_tat_ca_giao_dich_nguoi_dung";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary fs-5" id="tittle">Quản lý rút tiền</h6>
            </div>
            <div id="div_btn_create" class="mb-2 d-flex justify-content-end">
                <a id="btn_create" href="{{route('rank.create')}}" class="btn btn-success text-decoration-none btn-sm"><i class="fas fa-plus mr-2"></i>Thêm mới</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped rounded" id="dataTable" width="100%" cellspacing="0">
                    <thead class="position-sticky top-0">
                        <tr class="bg-primary">
                            <th class="tittle_column">#</th>
                            <th class="tittle_column">Thông tin</th>
                            <th class="tittle_column">Số điện thoại</th>
                            <th class="tittle_column">Được thao tác bởi</th>
                            <th class="tittle_column">Biến động số dư</th>
                            <th class="tittle_column">Số dư hiện tại</th>
                            <th class="tittle_column">Trạng thái</th>
                            <th class="tittle_column">Ngày nạp tiền</th>
                            <th class="tittle_column">Thao tác</th>
                        </tr>
                    </thead>
                    <tfoot class="sticky-bottom">
                        <tr>
                            <th class="tittle_column">#</th>
                            <th class="tittle_column">Thông tin</th>
                            <th class="tittle_column">Số điện thoại</th>
                            <th class="tittle_column">Được thao tác bởi</th>
                            <th class="tittle_column">Biến động số dư</th>
                            <th class="tittle_column">Số dư hiện tại</th>
                            <th class="tittle_column">Trạng thái</th>
                            <th class="tittle_column">Ngày nạp tiền</th>
                            <th class="tittle_column">Thao tác</th>
                        </tr>
                    </tfoot>
                    <tbody id="tbody">
                        @if (!empty($list_withdraw_transactions))
                        @foreach ($list_withdraw_transactions as $index =>$item)
                        <tr class="small">
                            <td>{{$index+1}}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span>Tên khách hàng: <b>{{ $item->user->full_name }}</b></span>
                                    <span>Tên tài khoản: <b>{{ $item->user->username }}</b></span>
                                    <span>Tên ngân hàng: <b>{{ $item->username_bank }}</b></span>
                                    <span>Ngân hàng: <b>{{ $item->bank_name }}</b></span>
                                    <span>Số tài khoản: <b>{{ $item->account_number }}</b></span>
                                </div>
                            </td>
                            <td>{{$item->user->phone}}</td>
                            <td>{{ optional($item->byUser)->username ?? "Chờ xử lý!" }}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span>Ban đầu: <b class="text-warning">{{ format_money($item->initial_balance) }}$</b></span>
                                    <span>Rút ra: <b class="text-danger">-{{ format_money($item->value) }}$</b></span>
                                </div>
                            </td>
                            <td>
                                <span class="text-primary fw-bold">{{format_money($item->user->balance)}}$</span>
                            </td>
                            <td>
                                @if($item->status==="processing")
                                <span class="badge badge-warning">Chờ xác nhận!</span>
                                @elseif($item->status==="completed")
                                <span class="badge badge-success">Hoàn thành</span>
                                @else
                                <span class="badge badge-danger">Đã hủy</span>
                                @endif
                            </td>
                            <td>{{$item->created_at}}</td>
                            <td>

                                <div class="mt-1">
                                    @if($item->status==="processing")
                                    <a data-url="{{ route('confirm.withdraw',['transaction'=>$item->id]) }}" class="btn btn-success btn-sm d-flex align-items-center btn_confirm_transaction">Xác nhận <i class="fas fa-check fa-sm p-2"></i></a>
                                    <a data-url="{{ route('cancel.withdraw',['transaction'=>$item->id]) }}" class="btn btn-danger btn-sm d-flex align-items-center mt-2 btn_cancel_transaction">Từ chối <i class="fas fa-xmark fa-sm p-2"></i></a>
                                    @else
                                    <span>&nbsp;</span>
                                    @endif
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