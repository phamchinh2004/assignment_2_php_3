@extends('admin.layouts.master')
@section('title')
    Chỉnh sửa banner — {{ $banner->name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
    <style>
        .image-preview-thumb {
            position: relative;
            width: 140px;
            height: 90px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 2px solid var(--border-color);
            background: #ffffff;
            box-shadow: var(--shadow-subtle);
            transition: all 0.2s ease;
        }
        .image-preview-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-preview-thumb .btn-delete {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 26px;
            height: 26px;
            padding: 0;
            border-radius: 50%;
            background: rgba(220, 38, 38, 0.9);
            color: #ffffff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            cursor: pointer;
            transition: transform 0.15s ease;
        }
        .image-preview-thumb .btn-delete:hover {
            transform: scale(1.15);
            background: #dc2626;
        }
    </style>
@endsection

@section('script-libs')
    @vite('resources/js/admin/banner/edit.js')
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
                <span class="page-title-icon info"><i class="fas fa-pen-to-square"></i></span>
                Chỉnh sửa bộ banner: {{ $banner->name }}
            </h1>
            <p class="page-subtitle">Quản lý các slide hình ảnh và cập nhật tên bộ sưu tập</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('banner.update', $banner->id) }}" method="POST" enctype="multipart/form-data" id="form">
            @csrf
            @method('PUT')

            <div class="form-body-modern">
                <div class="row">
                    <div class="col-12 col-md-8 mx-auto">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-tag"></i> Thông tin banner
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="name">
                                    Tên bộ banner <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                       value="{{ old('name', $banner->name) }}"
                                       class="form-control-modern @error('name') is-invalid @enderror"
                                       required>
                                @error('name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Current Images --}}
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-photo-film"></i> Hình ảnh hiện tại trong bộ
                                <span class="text-muted font-weight-normal" style="font-size: 0.8rem; margin-left: auto;">
                                    Bấm vào nút <i class="fas fa-times text-danger"></i> để xóa ảnh
                                </span>
                            </div>

                            <div class="d-flex flex-wrap gap-3" id="current-images" style="gap: 12px;">
                                @forelse ($banner->banner_images as $banner_image)
                                    <div class="image-preview image-preview-thumb" data-image-id="{{ $banner_image->id }}">
                                        <img src="{{ Storage::url($banner_image->path) }}" alt="Banner slide">
                                        <button type="button" class="btn-delete" data-image-id="{{ $banner_image->id }}" title="Xóa ảnh này">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @empty
                                    <span class="text-muted font-italic">Chưa có ảnh nào trong bộ này.</span>
                                @endforelse
                            </div>
                            <input type="hidden" name="deleted_images" id="deleted_images" value="">
                        </div>

                        {{-- Upload More Images --}}
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-cloud-arrow-up"></i> Tải thêm hình ảnh mới
                            </div>

                            <div class="image-upload-box">
                                <i class="fas fa-images text-muted mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-1 font-weight-bold" style="font-size: 0.9rem;">Thêm ảnh vào slide (tùy chọn)</p>
                                <input type="file" accept="image/*" name="images[]" multiple class="form-control-file d-inline-block" style="max-width: 300px;">
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
                <button type="submit" class="btn-submit-modern" id="btn_submit">
                    <i class="fas fa-save"></i> Cập nhật banner
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
