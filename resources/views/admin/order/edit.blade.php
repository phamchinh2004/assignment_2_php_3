@extends('admin.layouts.master')
@section('title')
Chỉnh sửa đơn hàng
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/order/edit.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/order/edit.js')
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_don_hang";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="mb-2 ml-3">
    <a href="{{route('order.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Sửa đơn hàng <i class="text-dark">{{ $order->order_code }}</i></h6>
            </div>
        </div>
    </div>
    <section class="container-fluid">
        <form id="form" action="{{ route('order.update',['order'=>$order->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mt-2">
                <label for="">Tên đơn hàng</label>
                <input type="text" name="name" value="{{ old('name',$order->name) }}" class="form-control">
                @error('name')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2">
                <label for="">Mã đơn hàng</label>
                <input type="text" name="order_code" value="{{ old('order_code',$order->order_code) }}" class="form-control">
                @error('order_code')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="d-flex flex-row">
                <div class="mt-2 mr-3 w-50">
                    <label for="">Hình ảnh cũ</label>
                    <div class="div_image_old">
                        <img src="{{ asset('uploads/orders/images/'.$order->image) }}" alt="">
                    </div>
                </div>
                <div class="mt-2 w-50">
                    <label for="">Hình ảnh mới</label>
                    <input type="file" accept="image/*" name="image" class="form-control">
                    @error('image')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <div class="mt-2">
                <label for="">Giá</label>
                <input type="number" name="price" class="form-control" value="{{ old('price',$order->price) }}">
                @error('price')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2">
                <label for="">Số lượng</label>
                <input type="number" name="quantity" class="form-control" value="{{ old('quantity',$order->quantity) }}">
                @error('quantity')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="d-flex mt-3 justify-content-center">
                <button class="btn btn-warning" type="button" id="btn_submit">Xong</button>
            </div>
        </form>
    </section>
</div>

@endsection