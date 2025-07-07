@extends('admin.layouts.master')
@section('title')
Thêm mới banner
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">

@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/banner/create.js')
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_banner";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="mb-2 ml-3">
    <a href="{{route('banner.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Tạo banner</h6>
            </div>
        </div>
    </div>
    <section class="container-fluid">
        <form action="{{ route('banner.store') }}" method="post" enctype="multipart/form-data" id="form">
            @csrf
            @method('POST')
            <div class="mt-2 fw-bold">
                <label for="">Tên banner</label>
                <input type="text" name="name" value="{{ old('name','') }}" class="form-control" placeholder="Nhập tên banner (VD: 30/4-1/5)">
                @error('name')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Hình ảnh (chọn được nhiều)</label>
                <input type="file" accept="image/*" name="images[]" class="form-control" multiple>
                @error('images')
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