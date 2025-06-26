@extends('admin.layouts.master')
@section('title')
Cập nhật đóng băng đơn hàng
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<!-- @vite('resources/css/admin/user/create.css') -->
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/user/frozen_order.js')
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_tat_ca_nguoi_dung";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="mb-2 ml-3">
    <a href="{{route('user.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary fs-5" id="tittle">Chỉnh sửa đóng băng đơn hàng của người dùng <i class="text-danger">{{ $user->full_name }}</i></h6>
                <h6 class="mt-2 font-weight-bold text-danger fs-6" id="tittle">Số dư hiện tại: {{ format_money($user->balance?$user->balance:0) }}$</h6>
            </div>
        </div>
    </div>
    <section class="container-fluid">
        <form action="{{ route('user.update.frozen.order',['user'=>$user->id,'id'=>$frozen_order_old->id]) }}" method="post" id="form">
            @csrf
            @method('PUT')
            <div class="mt-2 fw-bold">
                <label for="">Chọn đơn hàng muốn đóng băng</label>
                <select name="order" id="" class="form-select">
                    @if (!empty($list_orders))
                    @foreach ($list_orders as $order)
                    <option
                        value="{{ $order['id'] }}"
                        class="{{ $order->index===$progress->current_spin?'text-success fw-bold':'' }}"
                        {{ $order->index<=$progress->current_spin?"disabled":"" }}
                        {{ $order->id<=$frozen_order_old->order_id?"selected":"" }}>
                        {{$order->index}} -
                        {{Str::limit( $order->name ,30,'...')}}
                        {{ $order->index===$progress->current_spin?" - Hiện tại":"" }}
                    </option>
                    @endforeach
                    @endif
                </select>
                @error('order')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Giá giả ($)</label>
                <input type="text" name="custom_price" id="custom_price" value="{{ old('custom_price',$frozen_order_old->custom_price) }}" class="form-control" placeholder="Nhập giá giả">
                @error('custom_price')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="d-flex mt-3 justify-content-center">
                <button class="btn btn-success" type="button" id="btn_submit">Xong</button>
            </div>
        </form>
    </section>
</div>

@endsection