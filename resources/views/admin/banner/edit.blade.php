@extends('admin.layouts.master')
@section('title')
Sửa banner
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
<style>
    .image-preview {
        position: relative;
        display: inline-block;
    }

    .image-preview .btn-delete {
        position: absolute;
        top: -10px;
        right: -10px;
        width: 30px;
        height: 30px;
        padding: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .image-preview img {
        object-fit: cover;
        width: 100%;
        height: 100%;
    }
</style>
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/banner/edit.js')
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
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Sửa banner</h6>
            </div>
        </div>
    </div>
    <section class="container-fluid">
        <form action="{{ route('banner.update', $banner->id) }}" method="post" enctype="multipart/form-data" id="form">
            @csrf
            @method('PUT')

            <div class="mt-2 fw-bold">
                <label for="">Tên banner</label>
                <input type="text" name="name" value="{{ old('name', $banner->name) }}" class="form-control" placeholder="Nhập tên banner (VD: 30/4-1/5)">
                @error('name')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="mt-2 fw-bold">
                <label for="">Hình ảnh hiện tại</label>
                <div class="d-flex flex-wrap gap-2" id="current-images">
                    @foreach ($banner->banner_images as $banner_image)
                    <div class="border rounded p-1 image-preview" style="width: 120px; height: 120px;" data-image-id="{{ $banner_image->id }}">
                        <img src="{{ Storage::url($banner_image->path) }}" alt="" class="img-fluid rounded">
                        <button type="button" class="btn btn-danger btn-sm btn-delete" data-image-id="{{ $banner_image->id }}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
                <!-- Hidden inputs để lưu các ID ảnh cần xóa -->
                <input type="hidden" name="deleted_images" id="deleted_images" value="">
            </div>

            <div class="mt-3 fw-bold">
                <label for="">Thêm hình ảnh mới (chọn được nhiều)</label>
                <input type="file" accept="image/*" name="images[]" class="form-control" multiple>
                @error('images')
                <small class="text-danger">{{ $message }}</small>
                @enderror
                @error('images.*')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="d-flex mt-3 justify-content-center">
                <button class="btn btn-primary" type="button" id="btn_submit">Cập nhật</button>
            </div>
        </form>
    </section>
</div>

@endsection