@extends('admin.layouts.master')
@section('title')
    Danh sách chức năng quản lý
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
    $totalSettings = !empty($list_manager_settings) ? $list_manager_settings->count() : 0;
@endphp

<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon teal"><i class="fas fa-shield-halved"></i></span>
                Chức năng quản lý (Permissions)
            </h1>
            <p class="page-subtitle">Danh mục các quyền hạn và module chức năng dùng để phân quyền cho nhân viên</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('manager_setting.create') }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-plus"></i>
                <span>Thêm chức năng mới</span>
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern teal">
            <div class="stat-content">
                <span class="stat-label">Tổng chức năng quản lý</span>
                <span class="stat-number text-teal">{{ number_format($totalSettings) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-key text-teal"></i> Module phân quyền trong hệ thống
                </span>
            </div>
            <div class="stat-icon-wrapper teal">
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-list-check"></i> Danh sách chức năng phân quyền
            </h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Tên chức năng</th>
                            <th>Mã chức năng (Permission Code)</th>
                            <th>Ngày thiết lập</th>
                            <th class="text-center" style="width: 120px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($list_manager_settings))
                            @foreach ($list_manager_settings as $index => $item)
                                <tr>
                                    <td class="text-center">
                                        <span class="id-chip">#{{ $index + 1 }}</span>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-shield text-teal" style="font-size: 13px;"></i>
                                            <a class="entity-title font-weight-bold" href="{{ route('manager_setting.show', ['manager_setting' => $item->id]) }}">
                                                {{ $item->manager_name }}
                                            </a>
                                        </div>
                                    </td>

                                    <td>
                                        <code style="font-size: 0.85rem; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; color: #0d9488; font-weight: 700;">
                                            {{ $item->manager_code }}
                                        </code>
                                    </td>

                                    <td>
                                        <span class="text-muted" style="font-size: 0.8125rem;">
                                            {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '—' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="action-btn-group justify-content-center">
                                            <a href="{{ route('manager_setting.show', ['manager_setting' => $item->id]) }}"
                                               class="btn-action-icon view" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="{{ route('manager_setting.edit', ['manager_setting' => $item->id]) }}"
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
