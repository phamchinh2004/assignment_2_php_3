@extends('admin.layouts.master')

@section('title')
    Chi tiết đối tác — {{ $partner->name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
@php
    $canUpdatePartner = app(\App\Services\AuthorizationService::class)->can(auth()->user(), config('authorization.capabilities.partners_update'));
@endphp
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('partner.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách đối tác
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="entity-thumbnail" style="width: 56px; height: 56px; background: #ffffff;">
                @if($partner->image)
                    <img src="{{ Storage::url($partner->image) }}" alt="{{ $partner->name }}">
                @else
                    <i class="fas fa-store text-muted" style="font-size: 1.5rem;"></i>
                @endif
            </div>
            <div>
                <h1 class="page-title-main" style="font-size: 1.35rem;">
                    {{ $partner->name }}
                    <span class="id-chip">Mã: #{{ $partner->id }}</span>
                </h1>
                <p class="page-subtitle">
                    Ngày liên kết: {{ $partner->created_at ? $partner->created_at->format('d/m/Y H:i') : '—' }}
                </p>
            </div>
        </div>

        @if ($canUpdatePartner)
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('partner.edit', ['partner' => $partner->id]) }}" class="btn-create-modern">
                <i class="fas fa-pen mr-1"></i> Chỉnh sửa đối tác
            </a>
        </div>
        @endif
    </div>

    {{-- Detail Card --}}
    <div class="row">
        <div class="col-12 col-md-8 mx-auto">
            <div class="detail-card-modern">
                <h5 class="detail-card-title">
                    <i class="fas fa-circle-info"></i> Thông tin đối tác liên kết
                </h5>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">ID đối tác</span>
                    <span class="detail-value-modern">#{{ $partner->id }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Tên thương hiệu</span>
                    <span class="detail-value-modern">{{ $partner->name }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Đường dẫn Website</span>
                    <span class="detail-value-modern">
                        @if($partner->link)
                            <a href="{{ $partner->link }}" target="_blank" class="text-primary font-weight-bold text-decoration-none">
                                <i class="fas fa-arrow-up-right-from-square mr-1"></i> {{ $partner->link }}
                            </a>
                        @else
                            <span class="text-muted font-italic">Chưa có đường dẫn</span>
                        @endif
                    </span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Ngày hợp tác</span>
                    <span class="detail-value-modern">{{ $partner->created_at ? $partner->created_at->format('d/m/Y H:i:s') : '—' }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Cập nhật cuối</span>
                    <span class="detail-value-modern">{{ $partner->updated_at ? $partner->updated_at->format('d/m/Y H:i:s') : '—' }}</span>
                </div>

                @if($partner->image)
                    <div class="mt-4 pt-3 border-top text-center">
                        <label class="form-label-modern mb-2">Logo thương hiệu</label>
                        <div class="p-3 d-inline-block border rounded bg-white shadow-sm" style="max-width: 240px;">
                            <img src="{{ Storage::url($partner->image) }}" alt="{{ $partner->name }}" style="max-width: 100%; height: auto;">
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
