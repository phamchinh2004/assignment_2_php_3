@extends('admin.layouts.master')
@section('title')
    Thêm mới banner
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('banner.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách banner
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon info"><i class="fas fa-panorama"></i></span>
                Tạo bộ banner mới
            </h1>
            <p class="page-subtitle">Thêm tên bộ sưu tập và chọn các hình ảnh slide trình chiếu</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('banner.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-body-modern">
                <div class="row">
                    <div class="col-12 col-md-8 mx-auto">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-tag"></i> Tên bộ banner
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="name">
                                    Tên hiển thị / Mô tả <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                       value="{{ old('name', '') }}"
                                       class="form-control-modern @error('name') is-invalid @enderror"
                                       placeholder="Ví dụ: Banner trang chủ mùa hè, Khuyến mãi Tết..." required>
                                @error('name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-images"></i> Tải lên hình ảnh slide
                            </div>

                            <div class="image-upload-box">
                                <i class="fas fa-cloud-arrow-up text-muted mb-2" style="font-size: 2.2rem;"></i>
                                <p class="mb-1 font-weight-bold" style="font-size: 0.95rem;">Chọn các hình ảnh cho slide</p>
                                <p class="text-muted mb-3" style="font-size: 0.78125rem;">Hỗ trợ chọn cùng lúc nhiều ảnh (JPG, PNG, WEBP, GIF)</p>
                                <input type="file" name="images[]" id="images" accept="image/*" multiple class="form-control-file d-inline-block" style="max-width: 320px;" required>
                                @error('images')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions-bar">
                <a href="{{ route('banner.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button type="submit" class="btn-submit-modern">
                    <i class="fas fa-check"></i> Tạo banner
                </button>
            </div>
        </form>
    </div>

</div>
@endsection