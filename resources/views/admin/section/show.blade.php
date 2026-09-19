@extends('admin.layouts.master')
@section('title')
    Chi tiết Section — {{ $section->name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
    <style>
        .section-content-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .rendered-html-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-sm);
            padding: 1.25rem;
            min-height: 120px;
            font-size: 0.95rem;
            line-height: 1.6;
            color: #1e293b;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('section.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách section
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon primary"><i class="fas fa-file-lines"></i></span>
                {{ $section->name }}
                <span class="id-chip">Mã: <code>{{ $section->code }}</code></span>
                @if($section->status)
                    <span class="badge-status-modern success"><span class="status-dot"></span> Kích hoạt</span>
                @else
                    <span class="badge-status-modern danger"><span class="status-dot"></span> Đã dừng</span>
                @endif
            </h1>
            <p class="page-subtitle">
                <span>Số ngôn ngữ dịch: <b>{{ $section->sectionLanguages ? $section->sectionLanguages->count() : 0 }}</b></span> •
                <span>Ngày tạo: {{ $section->created_at ? $section->created_at->format('d/m/Y H:i') : '—' }}</span>
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('section.edit', ['section' => $section->id]) }}" class="btn-create-modern">
                <i class="fas fa-pen mr-1"></i> Chỉnh sửa nội dung
            </a>
        </div>
    </div>

    {{-- Nội dung theo từng ngôn ngữ --}}
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card-modern">
                <div class="card-header-modern">
                    <h6 class="title-header">
                        <i class="fas fa-language"></i> Nội dung bản dịch các ngôn ngữ
                    </h6>
                </div>

                <div class="card-body p-4">
                    @if(!empty($languages))
                        @foreach ($languages as $language)
                            @php
                                $langEntry = $section->sectionLanguages->firstWhere('language_id', $language->id);
                                $content = $langEntry?->content ?? '';
                            @endphp

                            <div class="section-content-card">
                                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($language->image)
                                            <img width="22" height="15" src="{{ Storage::url($language->image) }}" alt="{{ $language->name }}" style="border-radius: 2px; object-fit: cover;">
                                        @endif
                                        <h6 class="font-weight-bold text-dark m-0">{{ $language->name }}</h6>
                                        <span class="id-chip" style="font-size: 0.72rem;">{{ strtoupper($language->code) }}</span>
                                    </div>
                                    @if($content)
                                        <span class="badge-status-modern success" style="font-size: 0.72rem;">Đã có nội dung</span>
                                    @else
                                        <span class="badge-status-modern secondary" style="font-size: 0.72rem;">Chưa có nội dung</span>
                                    @endif
                                </div>

                                <div class="rendered-html-box">
                                    @if($content)
                                        {!! $content !!}
                                    @else
                                        <span class="text-muted font-italic">Chưa nhập nội dung cho ngôn ngữ này.</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            {{-- Metadata Card --}}
            <div class="detail-card-modern">
                <h5 class="detail-card-title">
                    <i class="fas fa-circle-info"></i> Thông số Section
                </h5>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">ID</span>
                    <span class="detail-value-modern">#{{ $section->id }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Tên Section</span>
                    <span class="detail-value-modern">{{ $section->name }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Mã slug</span>
                    <span class="detail-value-modern"><code>{{ $section->code }}</code></span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Trạng thái</span>
                    <span class="detail-value-modern">
                        @if($section->status)
                            <span class="badge-status-modern success">Đang hoạt động</span>
                        @else
                            <span class="badge-status-modern danger">Đang tạm dừng</span>
                        @endif
                    </span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Ngày khởi tạo</span>
                    <span class="detail-value-modern">{{ $section->created_at ? $section->created_at->format('d/m/Y H:i') : '—' }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Cập nhật cuối</span>
                    <span class="detail-value-modern">{{ $section->updated_at ? $section->updated_at->format('d/m/Y H:i') : '—' }}</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection