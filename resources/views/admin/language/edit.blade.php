@extends('admin.layouts.master')
@section('title')
    Chỉnh sửa ngôn ngữ — {{ $language->name }}
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
                <span class="page-title-icon primary"><i class="fas fa-pen-to-square"></i></span>
                Chỉnh sửa ngôn ngữ: {{ $language->name }}
            </h1>
            <p class="page-subtitle">Cập nhật tên hiển thị, mã ISO hoặc hình ảnh biểu tượng</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('language.update', ['language' => $language->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

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
                                       value="{{ old('name', $language->name) }}"
                                       class="form-control-modern @error('name') is-invalid @enderror"
                                       required>
                                @error('name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="code">
                                    Mã quốc tế (ISO Code) <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="code" id="code"
                                       value="{{ old('code', $language->code) }}"
                                       class="form-control-modern @error('code') is-invalid @enderror"
                                       required>
                                @error('code')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-flag"></i> Biểu tượng quốc kỳ
                            </div>

                            <div class="image-upload-box">
                                @if($language->image)
                                    <div class="image-preview-frame" style="width: 80px; height: 50px;">
                                        <img src="{{ Storage::url($language->image) }}" alt="{{ $language->name }}">
                                    </div>
                                    <p class="mb-1 text-muted" style="font-size: 0.78125rem;">Tải ảnh mới nếu muốn thay đổi:</p>
                                @endif
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
                    <i class="fas fa-save"></i> Cập nhật ngôn ngữ
                </button>
            </div>
        </form>
    </div>

</div>
@endsection