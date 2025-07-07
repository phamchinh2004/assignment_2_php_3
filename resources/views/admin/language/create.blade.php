@extends('admin.layouts.master')
@section('title')
Thêm mới language
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/language/create.js')
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_ngon_ngu";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="mb-2 ml-3">
    <a href="{{route('language.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Tạo ngôn ngữ</h6>
            </div>
        </div>
    </div>
    <language class="container-fluid">
        <form action="{{ route('language.store') }}" method="post" enctype="multipart/form-data" id="form">
            @csrf
            @method('POST')
            <div class="mt-2 fw-bold">
                <label for="">Tên ngôn ngữ</label>
                <input type="text" name="name" value="{{ old('name','') }}" class="form-control" placeholder="Nhập tên ngôn ngữ, ví dụ: Việt Nam, English, Japan,...">
                @error('name')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Mã ngôn ngữ (vui lòng tra google!) <a href="https://google.com">Click vào đây!</a></label>
                <input type="text" name="code" value="{{ old('code','') }}" class="form-control" placeholder="Nhập tên language (VD: 30/4-1/5)">
                @error('code')
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
            <div class="d-flex mt-3 justify-content-center">
                <button class="btn btn-success" type="button" id="btn_submit">Xong</button>
            </div>
        </form>
    </language>
</div>

@endsection