@extends('admin.layouts.master')
@section('title')
Thêm mới đơn hàng
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/order/create.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/order/create.js')
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
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
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Tạo đơn hàng</h6>
            </div>
        </div>
        <div class="card-body section_1_content">
            <select name="" id="rank" class="form-select form-select-sm">
                <option value="">--- Chọn cấp độ ---</option>
                @if (!empty($list_ranks))
                @foreach ($list_ranks as $rank)
                <option value="{{ $rank['id'] }}"
                    data-quantity="{{ $rank['quantity'] }}"
                    data-value="{{ $rank['value'] }}"
                    data-start="{{ $rank['start'] }}"
                    data-spin_count="{{ $rank['spin_count'] }}"
                    data-commission_percentage="{{ $rank['commission_percentage'] }}"
                    {{ $rank['quantity']==0? "disabled":"" }}>
                    {{ $rank['name']}} cần
                    {{ $rank['quantity']}} hình ảnh
                </option>
                @endforeach
                @endif
            </select>
            <div class="mt-2">
                <label for="">Vui lòng chọn hình ảnh đơn hàng để tạo tự động đơn hàng, có thể chọn nhiều hình ảnh, mỗi
                    hình ảnh là một đơn hàng!</label>
                <br>
                <label for="" class="text-danger fw-bold">Mỗi lần có thể tạo tối đa 20 đơn hàng, chọn tối đa 20 ảnh thôi nhé!</label>
                <input id="images" accept="image/*" type="file" class="form-control" multiple>
            </div>
            <div class="d-flex justify-content-center mt-3">
                <button class="btn btn-sm btn-success" id="btn_generate_auto">Tạo tự động</button>
            </div>
        </div>
    </div>
    <section class="order_items container-fluid">
        <div class="row d-flex justify-content-between align-items-center" id="list_orders">
            <!-- <div class="order_item position-relative">
                <div class="div_img">
                    <img src="{{ asset('images/orders/syglp5via6r7rxqjc1k8.jpg') }}" alt="">
                </div>
                <div class="form-floating div_name">
                    <input type="text" class="form-control input_name" id="input_name_test" placeholder="Nhập tên đơn hàng">
                    <label class="label_name" for="input_name_test">Nhập tên đơn hàng</label>
                </div>
                <span class="badge bg-primary index">1</span>
                <span class="price">123$</span>
            </div> -->
        </div>
    </section>
    <div id="submit" hidden>
        <div class="d-flex mt-3 justify-content-center">
            <button class="btn btn-secondary csdf" id="btn_submit">Xong</button>
        </div>
    </div>
</div>

@endsection