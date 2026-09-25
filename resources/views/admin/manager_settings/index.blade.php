@extends('admin.layouts.master')
@section('title')
    Danh sách chức năng quản lý
@endsection

@section('style-libs')
    @include('admin.manager_settings.group-styles')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')


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

        <div class="card-body p-4">
            @forelse($permissionGroups as $group)
                <section class="permission-module">
                    <div class="permission-module__header">
                        <h2 class="permission-module__title"><i class="fas fa-layer-group text-primary"></i> {{ $group['label'] }}</h2>
                        @include('admin.manager_settings.item-actions', ['item' => $group['root']])
                    </div>
                    <div class="px-3 py-2 text-muted"><code>{{ $group['root']->manager_code }}</code> &middot; {{ $group['root']->created_at?->format('d/m/Y H:i') }}</div>
                    @if($group['settings']->count() > 1)
                        <div class="permission-module__body">
                            @foreach($group['settings']->reject(fn ($setting) => $setting->id === $group['root']->id) as $item)
                                <div class="border rounded p-3 d-flex justify-content-between align-items-center" style="gap: 1rem; min-width: 0;">
                                    <div style="min-width: 0; overflow-wrap: anywhere;">
                                        <a href="{{ route('manager_setting.show', $item) }}" class="entity-title font-weight-bold">{{ $item->manager_name }}</a>

                                        <div><code>{{ $item->manager_code }}</code></div>
                                        <small class="text-muted">{{ $item->created_at?->format('d/m/Y H:i') }}</small>
                                    </div>
                                    @include('admin.manager_settings.item-actions', ['item' => $item])
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @empty
                <p class="text-muted text-center p-4">Chưa có chức năng phân quyền.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
