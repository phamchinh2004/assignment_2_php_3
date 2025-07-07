@extends('admin.layouts.master')
@section('title')
Thêm mới section
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/section/create.js')
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_section";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="mb-2 ml-3">
    <a href="{{route('section.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Tạo section</h6>
            </div>
        </div>
    </div>
    <section class="container-fluid">
        <form action="{{ route('section.store') }}" method="post" enctype="multipart/form-data" id="form">
            @csrf
            @method('POST')
            <div class="mt-2 fw-bold">
                <label for="">Tên section</label>
                <input type="text" name="name" value="{{ old('name','') }}" class="form-control" placeholder="Nhập tên section (VD: 30/4-1/5)">
                @error('name')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            @if (!empty($languages))
            <label class="mt-2 fw-bold text-secondary">Nhập các phiên bản ngôn ngữ</label>
            @foreach ($languages as $language)
            <div class="mt-2 fw-bold">
                <label for="">
                    <div class="d-flex flex-row align-items-center">
                        <span class="mr-2">{{$language->name}}</span>
                        <img width="20px" src="{{ asset('uploads/language/images/'.$language->image) }}" alt="">
                    </div>
                </label>
                <textarea name="content[{{ $language->id }}]" id="sectionContent"></textarea>
                @error('content')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            @endforeach
            @endif
            <div class="d-flex mt-3 justify-content-center">
                <button class="btn btn-success" type="button" id="btn_submit">Xong</button>
            </div>
        </form>
    </section>
</div>

@endsection