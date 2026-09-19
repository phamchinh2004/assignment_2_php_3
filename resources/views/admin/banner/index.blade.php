@extends('admin.layouts.master')
@section('title')
    Danh sách banner
@endsection

@section('style-libs')
    <link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('script-libs')
    <script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
    <script>
        window.currentPermissionCode = "quan_ly_banner";
    </script>
@endsection

@section('content')
@php
    $totalBanners = !empty($list_banners) ? $list_banners->count() : 0;
    $activeBanners = !empty($list_banners) ? $list_banners->where('status', 1)->count() : 0;
    $totalImages = 0;
    if(!empty($list_banners)) {
        foreach($list_banners as $b) {
            $totalImages += $b->banner_images ? $b->banner_images->count() : 0;
        }
    }
@endphp

<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon info"><i class="fas fa-images"></i></span>
                Quản lý banner quảng cáo
            </h1>
            <p class="page-subtitle">Quản lý các slide banner hiển thị tại trang chủ và ứng dụng thành viên</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('banner.create') }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-plus"></i>
                <span>Thêm banner mới</span>
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern info">
            <div class="stat-content">
                <span class="stat-label">Tổng bộ banner</span>
                <span class="stat-number text-info">{{ number_format($totalBanners) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-layer-group text-info"></i> Bộ sưu tập banner
                </span>
            </div>
            <div class="stat-icon-wrapper info">
                <i class="fas fa-panorama"></i>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Đang hiển thị trên Web</span>
                <span class="stat-number text-success">{{ number_format($activeBanners) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-circle-check text-success"></i> Đang chạy ngoài trang chủ
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-display"></i>
            </div>
        </div>

        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Tổng hình ảnh slide</span>
                <span class="stat-number text-primary">{{ number_format($totalImages) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-photo-film text-primary"></i> Tấm hình trong hệ thống
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-image"></i>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-table-cells"></i> Danh sách các bộ banner
            </h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Tên banner</th>
                            <th>Ảnh xem trước (Slides)</th>
                            <th class="text-center">Số lượng ảnh</th>
                            <th class="text-center">Trạng thái</th>
                            <th>Ngày cập nhật</th>
                            <th class="text-center" style="width: 160px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($list_banners))
                            @foreach ($list_banners as $index => $item)
                                <tr>
                                    <td class="text-center">
                                        <span class="id-chip">#{{ $index + 1 }}</span>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-column">
                                            <a class="entity-title font-weight-bold" href="{{ route('banner.show', ['banner' => $item->id]) }}">
                                                {{ $item->name }}
                                            </a>
                                            <span class="text-muted" style="font-size: 0.75rem;">Mã banner: #{{ $item->id }}</span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center" style="gap: 6px; overflow-x: auto; max-width: 320px; padding: 2px 0;">
                                            @forelse($item->banner_images as $img)
                                                <div class="entity-thumbnail wide" style="flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                                    <img src="{{ Storage::url($img->path) }}" alt="Banner slide">
                                                </div>
                                            @empty
                                                <span class="text-muted font-italic" style="font-size: 0.8125rem;">Chưa có ảnh</span>
                                            @endforelse
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span class="id-chip font-weight-bold">{{ $item->banner_images ? $item->banner_images->count() : 0 }} ảnh</span>
                                    </td>

                                    <td class="text-center">
                                        @if($item->status == 1)
                                            <span class="badge-status-modern success"><span class="status-dot"></span> Đang hiển thị</span>
                                        @else
                                            <span class="badge-status-modern secondary"><span class="status-dot"></span> Đang ẩn</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="text-muted" style="font-size: 0.8125rem;">
                                            {{ $item->updated_at ? $item->updated_at->format('d/m/Y H:i') : '—' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="action-btn-group justify-content-center">
                                            <a href="{{ route('banner.show', ['banner' => $item->id]) }}"
                                               class="btn-action-icon view" title="Xem slide preview">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="{{ route('banner.edit', ['banner' => $item->id]) }}"
                                               class="btn-action-icon edit" title="Chỉnh sửa banner">
                                                <i class="fas fa-pen"></i>
                                            </a>

                                            @if($item->status == 1)
                                                <a href="{{ route('banner.change.status', ['banner' => $item->id]) }}"
                                                   class="btn-action-icon lock" title="Tắt hiển thị"
                                                   onclick="return confirm('Tắt hiển thị banner này trên trang chủ?');">
                                                    <i class="fas fa-eye-slash"></i>
                                                </a>
                                            @else
                                                <a href="{{ route('banner.change.status', ['banner' => $item->id]) }}"
                                                   class="btn-action-icon view" title="Kích hoạt hiển thị"
                                                   onclick="return confirm('Kích hoạt banner này hiển thị trên trang chủ?');">
                                                    <i class="fas fa-circle-check"></i>
                                                </a>
                                            @endif

                                            <form action="{{ route('banner.destroy', ['banner' => $item->id]) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa bộ banner này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action-icon delete" title="Xóa bộ banner">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection