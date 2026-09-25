@extends('admin.layouts.master')
@section('title')
    Danh sách đối tác
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
    $authorization = app(\App\Services\AuthorizationService::class);
    $canCreatePartner = $authorization->can(auth()->user(), config('authorization.capabilities.partners_create'));
    $canViewPartnerDetail = $authorization->can(auth()->user(), config('authorization.capabilities.partners_view_detail'));
    $canUpdatePartner = $authorization->can(auth()->user(), config('authorization.capabilities.partners_update'));
    $canDeletePartner = $authorization->can(auth()->user(), config('authorization.capabilities.partners_delete'));
@endphp
@php
    $totalPartners = !empty($list_partners) ? $list_partners->count() : 0;
@endphp

<div class="container-fluid px-4 pb-5">

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon success"><i class="fas fa-handshake"></i></span>
                Quản lý đối tác (Partner)
            </h1>
            <p class="page-subtitle">Quản lý các thương hiệu, sàn thương mại điện tử và đối tác liên kết</p>
        </div>
        @if ($canCreatePartner)
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('partner.create') }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-plus"></i>
                <span>Thêm đối tác mới</span>
            </a>
        </div>
        @endif
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Tổng đối tác liên kết</span>
                <span class="stat-number text-success">{{ number_format($totalPartners) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-handshake text-success"></i> Thương hiệu trên hệ thống
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-store"></i>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-building"></i> Danh mục đối tác
            </h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>Đối tác</th>
                            <th>Đường dẫn liên kết</th>
                            <th>Ngày hợp tác</th>
                            <th class="text-center" style="width: 140px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($list_partners))
                            @foreach ($list_partners as $index => $item)
                                <tr>
                                    <td class="text-center">
                                        <span class="id-chip">#{{ $index + 1 }}</span>
                                    </td>

                                    <td>
                                        <div class="entity-identity-cell">
                                            <div class="entity-thumbnail" style="width: 44px; height: 44px; background: #ffffff;">
                                                @if($item->image)
                                                    <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}">
                                                @else
                                                    <i class="fas fa-store text-muted"></i>
                                                @endif
                                            </div>
                                            <div class="entity-details">
                                                @if ($canViewPartnerDetail)
                                                <a class="entity-title font-weight-bold" href="{{ route('partner.show', ['partner' => $item->id]) }}">
                                                    {{ $item->name }}
                                                </a>
                                                @else
                                                    <span class="entity-title font-weight-bold">{{ $item->name }}</span>
                                                @endif
                                                <span class="entity-subtitle">Mã: #{{ $item->id }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        @if($item->link)
                                            <a href="{{ $item->link }}" target="_blank" class="text-primary font-weight-bold text-decoration-none" style="font-size: 0.85rem;">
                                                <i class="fas fa-arrow-up-right-from-square mr-1"></i> {{ Str::limit($item->link, 40) }}
                                            </a>
                                        @else
                                            <span class="text-muted font-italic">Không có liên kết</span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="text-muted" style="font-size: 0.8125rem;">
                                            {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : '—' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="action-btn-group justify-content-center">
                                            @if ($canViewPartnerDetail)
                                            <a href="{{ route('partner.show', ['partner' => $item->id]) }}"
                                               class="btn-action-icon view" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @endif

                                            @if ($canUpdatePartner)
                                            <a href="{{ route('partner.edit', ['partner' => $item->id]) }}"
                                               class="btn-action-icon edit" title="Chỉnh sửa">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            @endif

                                            @if ($canDeletePartner)
                                            <form action="{{ route('partner.destroy', ['partner' => $item->id]) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa đối tác này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-action-icon delete" title="Xóa đối tác">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
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
