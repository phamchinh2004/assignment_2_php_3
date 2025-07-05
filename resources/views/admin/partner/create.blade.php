@extends('admin.layouts.master')
@section('title')
Thêm mới partner
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/partner/create.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/partner/create.js')
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_partner";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="mb-2 ml-3">
    <a href="{{route('partner.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Tạo partner</h6>
            </div>
        </div>
    </div>
    <partner class="container-fluid">
        <form action="{{ route('partner.store') }}" method="post" enctype="multipart/form-data" id="form">
            @csrf
            @method('POST')
            <div class="fw-bold">
                <label for="">Tên đối tác</label>
                <input type="text" name="name" value="{{ old('name','') }}" class="form-control" placeholder="Nhập tên partner (VD: 30/4-1/5)">
                @error('name')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Hình ảnh</label>
                <input type="file" accept="image/*" name="image" class="form-control">
                @error('image')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Đường dẫn tới trang web</label>
                <input type="text" name="link" value="{{ old('link','') }}" class="form-control" placeholder="Nhập đường dẫn cho đối tác VD: https://www.facebook.com/">
                @error('link')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="d-flex mt-3 justify-content-center">
                <button class="btn btn-success" type="button" id="btn_submit">Xong</button>
            </div>
        </form>
    </partner>
</div>

@endsection