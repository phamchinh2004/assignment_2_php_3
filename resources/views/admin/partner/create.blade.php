@extends('admin.layouts.master')
@section('title')
    Thêm mới đối tác
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('partner.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách đối tác
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon success"><i class="fas fa-handshake"></i></span>
                Thêm mới đối tác
            </h1>
            <p class="page-subtitle">Thêm thông tin thương hiệu đối tác liên kết vào hệ thống</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('partner.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-body-modern">
                <div class="row">
                    <div class="col-12 col-md-8 mx-auto">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-store"></i> Thông tin đối tác
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="name">
                                    Tên đối tác / Thương hiệu <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                       value="{{ old('name', '') }}"
                                       class="form-control-modern @error('name') is-invalid @enderror"
                                       placeholder="Ví dụ: Shopee, Lazada, Tiki, Amazon..." required>
                                @error('name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="link">
                                    Đường dẫn liên kết (Website / URL)
                                </label>
                                <input type="url" name="link" id="link"
                                       value="{{ old('link', '') }}"
                                       class="form-control-modern @error('link') is-invalid @enderror"
                                       placeholder="https://example.com">
                                @error('link')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-image"></i> Logo thương hiệu
                            </div>

                            <div class="image-upload-box">
                                <i class="fas fa-cloud-arrow-up text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-1 font-weight-bold" style="font-size: 0.9rem;">Chọn ảnh logo đối tác</p>
                                <input type="file" name="image" id="image" accept="image/*" class="form-control-file d-inline-block" style="max-width: 300px;">
                                @error('image')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions-bar">
                <a href="{{ route('partner.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button type="submit" class="btn-submit-modern">
                    <i class="fas fa-check"></i> Lưu đối tác
                </button>
            </div>
        </form>
    </div>

</div>
@endsection