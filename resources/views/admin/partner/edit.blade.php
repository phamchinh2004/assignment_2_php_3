@extends('admin.layouts.master')
@section('title')
    Chỉnh sửa đối tác — {{ $partner->name }}
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
                <span class="page-title-icon success"><i class="fas fa-pen-to-square"></i></span>
                Chỉnh sửa đối tác: {{ $partner->name }}
            </h1>
            <p class="page-subtitle">Cập nhật tên, liên kết website hoặc thay đổi logo đối tác</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('partner.update', ['partner' => $partner->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

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
                                       value="{{ old('name', $partner->name) }}"
                                       class="form-control-modern @error('name') is-invalid @enderror"
                                       required>
                                @error('name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="link">
                                    Đường dẫn liên kết (Website / URL)
                                </label>
                                <input type="url" name="link" id="link"
                                       value="{{ old('link', $partner->link) }}"
                                       class="form-control-modern @error('link') is-invalid @enderror">
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
                                @if($partner->image)
                                    <div class="image-preview-frame">
                                        <img src="{{ Storage::url($partner->image) }}" alt="{{ $partner->name }}">
                                    </div>
                                    <p class="mb-1 text-muted" style="font-size: 0.78125rem;">Tải ảnh mới nếu muốn thay đổi logo:</p>
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
                <a href="{{ route('partner.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button type="submit" class="btn-submit-modern">
                    <i class="fas fa-save"></i> Cập nhật đối tác
                </button>
            </div>
        </form>
    </div>

</div>
@endsection