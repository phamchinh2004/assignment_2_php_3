@extends('admin.layouts.master')
@section('title')
    Quản lý nội dung website (Section)
@endsection

@section('style-libs')
    <link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('script-libs')
    <script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
@endsection

@section('content')
@php
    $totalSections = !empty($list_sections) ? $list_sections->count() : 0;
    $activeSections = !empty($list_sections) ? $list_sections->where('status', 1)->count() : 0;
    $inactiveSections = $totalSections - $activeSections;
@endphp

<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon primary"><i class="fas fa-newspaper"></i></span>
                Nội dung website (Section)
            </h1>
            <p class="page-subtitle">Quản lý các khối nội dung tĩnh, quy chế, điều khoản và thông tin trang web</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('section.create') }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-plus"></i>
                <span>Thêm section mới</span>
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Tổng khối nội dung</span>
                <span class="stat-number text-primary">{{ number_format($totalSections) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-file-lines text-primary"></i> Khối section trên hệ thống
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-folder-open"></i>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Đang hiển thị</span>
                <span class="stat-number text-success">{{ number_format($activeSections) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-circle-check text-success"></i> Đã bật hiển thị
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-eye"></i>
            </div>
        </div>

        <div class="stat-card-modern danger">
            <div class="stat-content">
                <span class="stat-label">Đang tạm ngưng</span>
                <span class="stat-number text-danger">{{ number_format($inactiveSections) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-pause-circle text-danger"></i> Đang tắt hiển thị
                </span>
            </div>
            <div class="stat-icon-wrapper danger">
                <i class="fas fa-eye-slash"></i>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-list"></i> Danh sách các khối section
            </h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Tên Section</th>
                            <th>Mã định danh (Code)</th>
                            <th class="text-center">Số ngôn ngữ dịch</th>
                            <th class="text-center">Trạng thái</th>
                            <th>Cập nhật lần cuối</th>
                            <th class="text-center" style="width: 140px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($list_sections))
                            @foreach ($list_sections as $index => $item)
                                <tr>
                                    <td class="text-center">
                                        <span class="id-chip">#{{ $index + 1 }}</span>
                                    </td>

                                    <td>
                                        <a class="entity-title font-weight-bold" href="{{ route('section.show', ['section' => $item->id]) }}">
                                            {{ $item->name }}
                                        </a>
                                    </td>

                                    <td>
                                        <code style="font-size: 0.85rem; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #4338ca;">
                                            {{ $item->code }}
                                        </code>
                                    </td>

                                    <td class="text-center">
                                        <span class="id-chip">{{ $item->sectionLanguages ? $item->sectionLanguages->count() : 0 }} ngôn ngữ</span>
                                    </td>

                                    <td class="text-center">
                                        @if($item->status)
                                            <span class="badge-status-modern success"><span class="status-dot"></span> Kích hoạt</span>
                                        @else
                                            <span class="badge-status-modern danger"><span class="status-dot"></span> Đã dừng</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="text-muted" style="font-size: 0.8125rem;">
                                            {{ $item->updated_at ? $item->updated_at->format('d/m/Y H:i') : '—' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="action-btn-group justify-content-center">
                                            <a href="{{ route('section.show', ['section' => $item->id]) }}"
                                               class="btn-action-icon view" title="Xem nội dung chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="{{ route('section.edit', ['section' => $item->id]) }}"
                                               class="btn-action-icon edit" title="Chỉnh sửa">
                                                <i class="fas fa-pen"></i>
                                            </a>

                                            @if($item->status)
                                                <a href="{{ route('section.change.status', ['section' => $item->id]) }}"
                                                   class="btn-action-icon lock" title="Tắt kích hoạt"
                                                   onclick="return confirm('Dừng hiển thị section này?');">
                                                    <i class="fas fa-lock"></i>
                                                </a>
                                            @else
                                                <a href="{{ route('section.change.status', ['section' => $item->id]) }}"
                                                   class="btn-action-icon view" title="Bật kích hoạt"
                                                   onclick="return confirm('Kích hoạt lại section này?');">
                                                    <i class="fas fa-lock-open"></i>
                                                </a>
                                            @endif
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
