@extends('admin.layouts.master')
@section('title')
    Thêm mới ngôn ngữ
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('language.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách ngôn ngữ
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon primary"><i class="fas fa-globe"></i></span>
                Thêm mới ngôn ngữ
            </h1>
            <p class="page-subtitle">Thêm gói ngôn ngữ mới để hỗ trợ bản dịch trên ứng dụng</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('language.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-body-modern">
                <div class="row">
                    <div class="col-12 col-md-8 mx-auto">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-language"></i> Thông tin ngôn ngữ
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="name">
                                    Tên hiển thị <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                       value="{{ old('name', '') }}"
                                       class="form-control-modern @error('name') is-invalid @enderror"
                                       placeholder="Ví dụ: Tiếng Việt, English, 简体中文..." required>
                                @error('name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="code">
                                    Mã quốc tế (ISO Code) <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="code" id="code"
                                       value="{{ old('code', '') }}"
                                       class="form-control-modern @error('code') is-invalid @enderror"
                                       placeholder="Ví dụ: vi, en, zh, ja..." required>
                                @error('code')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                                <span class="form-hint-modern">Viết thường, 2-5 ký tự đại diện cho mã locale chuẩn.</span>
                            </div>
                        </div>

                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-flag"></i> Biểu tượng quốc kỳ
                            </div>

                            <div class="image-upload-box">
                                <i class="fas fa-cloud-arrow-up text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-1 font-weight-bold" style="font-size: 0.9rem;">Chọn ảnh cờ quốc gia</p>
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
                <a href="{{ route('language.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button type="submit" class="btn-submit-modern">
                    <i class="fas fa-check"></i> Lưu ngôn ngữ
                </button>
            </div>
        </form>
    </div>

</div>
@endsection