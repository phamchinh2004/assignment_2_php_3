@extends('admin.layouts.master')

@section('title')
    Chi tiết ngôn ngữ — {{ $language->name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('language.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách ngôn ngữ
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="entity-thumbnail" style="width: 56px; height: 38px; border-radius: 6px; background: #ffffff;">
                @if($language->image)
                    <img src="{{ Storage::url($language->image) }}" alt="{{ $language->name }}">
                @else
                    <i class="fas fa-flag text-muted" style="font-size: 1.4rem;"></i>
                @endif
            </div>
            <div>
                <h1 class="page-title-main" style="font-size: 1.35rem;">
                    {{ $language->name }}
                    <span class="id-chip">ISO: {{ strtoupper($language->code) }}</span>
                </h1>
                <p class="page-subtitle">
                    Gói ngôn ngữ hỗ trợ giao diện trên ứng dụng
                </p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('language.edit', ['language' => $language->id]) }}" class="btn-create-modern">
                <i class="fas fa-pen mr-1"></i> Chỉnh sửa ngôn ngữ
            </a>
        </div>
    </div>

    {{-- Detail Card --}}
    <div class="row">
        <div class="col-12 col-md-8 mx-auto">
            <div class="detail-card-modern">
                <h5 class="detail-card-title">
                    <i class="fas fa-circle-info"></i> Thông số gói ngôn ngữ
                </h5>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">ID</span>
                    <span class="detail-value-modern">#{{ $language->id }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Tên ngôn ngữ</span>
                    <span class="detail-value-modern font-weight-bold">{{ $language->name }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Mã ISO Code</span>
                    <span class="detail-value-modern">
                        <code style="font-size: 1rem; background: #f1f5f9; padding: 3px 10px; border-radius: 6px; color: #4338ca; font-weight: 700;">
                            {{ $language->code }}
                        </code>
                    </span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Ngày thiết lập</span>
                    <span class="detail-value-modern">{{ $language->created_at ? $language->created_at->format('d/m/Y H:i:s') : '—' }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Cập nhật cuối</span>
                    <span class="detail-value-modern">{{ $language->updated_at ? $language->updated_at->format('d/m/Y H:i:s') : '—' }}</span>
                </div>

                @if($language->image)
                    <div class="mt-4 pt-3 border-top text-center">
                        <label class="form-label-modern mb-2">Biểu tượng quốc kỳ</label>
                        <div class="p-2 d-inline-block border rounded bg-white shadow-sm" style="max-width: 120px;">
                            <img src="{{ Storage::url($language->image) }}" alt="{{ $language->name }}" style="max-width: 100%; height: auto; border-radius: 4px;">
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
