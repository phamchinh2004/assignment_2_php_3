@extends('admin.layouts.master')
@section('title')
    Danh sách ngôn ngữ
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
        window.currentPermissionCode = "quan_ly_ngon_ngu";
    </script>
@endsection

@section('content')
@php
    $totalLanguages = !empty($list_languages) ? $list_languages->count() : 0;
@endphp

<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon primary"><i class="fas fa-globe"></i></span>
                Quản lý ngôn ngữ
            </h1>
            <p class="page-subtitle">Thiết lập các gói ngôn ngữ và đa ngữ hóa trên nền tảng</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('language.create') }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-plus"></i>
                <span>Thêm ngôn ngữ mới</span>
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Tổng ngôn ngữ hỗ trợ</span>
                <span class="stat-number text-primary">{{ number_format($totalLanguages) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-language text-primary"></i> Gói ngôn ngữ khả dụng
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-earth-americas"></i>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-language"></i> Danh mục ngôn ngữ
            </h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Quốc kỳ / Biểu tượng</th>
                            <th>Tên ngôn ngữ</th>
                            <th>Mã định danh (ISO Code)</th>
                            <th>Ngày thiết lập</th>
                            <th class="text-center" style="width: 120px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($list_languages))
                            @foreach ($list_languages as $index => $item)
                                <tr>
                                    <td class="text-center">
                                        <span class="id-chip">#{{ $index + 1 }}</span>
                                    </td>

                                    <td>
                                        <div class="entity-thumbnail" style="width: 42px; height: 28px; border-radius: 4px; background: #ffffff;">
                                            @if($item->image)
                                                <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}" style="object-fit: cover;">
                                            @else
                                                <i class="fas fa-flag text-muted"></i>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <a class="entity-title font-weight-bold" href="{{ route('language.show', ['language' => $item->id]) }}">
                                            {{ $item->name }}
                                        </a>
                                    </td>

                                    <td>
                                        <code style="font-size: 0.9rem; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; color: #4338ca; font-weight: 700;">
                                            {{ strtoupper($item->code) }}
                                        </code>
                                    </td>

                                    <td>
                                        <span class="text-muted" style="font-size: 0.8125rem;">
                                            {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '—' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="action-btn-group justify-content-center">
                                            <a href="{{ route('language.show', ['language' => $item->id]) }}"
                                               class="btn-action-icon view" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="{{ route('language.edit', ['language' => $item->id]) }}"
                                               class="btn-action-icon edit" title="Chỉnh sửa">
                                                <i class="fas fa-pen"></i>
                                            </a>
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