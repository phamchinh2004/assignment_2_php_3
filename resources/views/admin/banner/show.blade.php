@extends('admin.layouts.master')

@section('title')
    Chi tiết banner — {{ $banner->name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
    <style>
        .banner-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
        }
        .banner-gallery-item {
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-subtle);
            background: #ffffff;
            transition: all 0.25s ease;
        }
        .banner-gallery-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-card);
        }
        .banner-gallery-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
        }
        .banner-gallery-info {
            padding: 0.75rem 1rem;
            background: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78125rem;
            color: var(--text-muted);
            border-top: 1px solid #f1f5f9;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('banner.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách banner
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon info"><i class="fas fa-images"></i></span>
                {{ $banner->name }}
                <span class="id-chip">Mã: #{{ $banner->id }}</span>
                @if($banner->status == 1)
                    <span class="badge-status-modern success"><span class="status-dot"></span> Đang hiển thị</span>
                @else
                    <span class="badge-status-modern secondary"><span class="status-dot"></span> Đang ẩn</span>
                @endif
            </h1>
            <p class="page-subtitle">
                <span>Số lượng ảnh: <b>{{ $banner->banner_images ? $banner->banner_images->count() : 0 }} slides</b></span> •
                <span>Ngày tạo: {{ $banner->created_at ? $banner->created_at->format('d/m/Y H:i') : '—' }}</span>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('banner.edit', ['banner' => $banner->id]) }}" class="btn-create-modern">
                <i class="fas fa-pen mr-1"></i> Chỉnh sửa bộ banner
            </a>
        </div>
    </div>

    {{-- Banner Gallery Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-photo-film"></i> Tất cả các slide hình ảnh trong bộ
            </h6>
        </div>

        <div class="card-body p-4">
            @if($banner->banner_images && $banner->banner_images->count() > 0)
                <div class="banner-gallery-grid">
                    @foreach($banner->banner_images as $index => $img)
                        <div class="banner-gallery-item">
                            <img src="{{ Storage::url($img->path) }}" alt="Banner slide {{ $index + 1 }}" class="banner-gallery-img">
                            <div class="banner-gallery-info">
                                <span class="font-weight-bold">Slide #{{ $index + 1 }}</span>
                                <a href="{{ Storage::url($img->path) }}" target="_blank" class="text-primary font-weight-bold text-decoration-none">
                                    <i class="fas fa-arrow-up-right-from-square mr-1"></i> Xem ảnh gốc
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state-modern">
                    <div class="empty-state-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    <h5 class="empty-state-title">Chưa có ảnh nào trong bộ này</h5>
                    <p class="empty-state-text">Hãy chỉnh sửa bộ banner để tải thêm hình ảnh slide.</p>
                    <a href="{{ route('banner.edit', ['banner' => $banner->id]) }}" class="btn btn-primary btn-sm px-3" style="border-radius: 8px;">
                        <i class="fas fa-plus mr-1"></i> Thêm ảnh ngay
                    </a>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
