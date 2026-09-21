@extends('admin.layouts.master')
@section('title')
    Cập nhật Section — {{ $section->name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
@endsection

@section('script-libs')
    @vite('resources/js/admin/section/edit.js')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('section.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách section
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon primary"><i class="fas fa-file-pen"></i></span>
                Chỉnh sửa Section: {{ $section->name }}
            </h1>
            <p class="page-subtitle">
                Mã định danh: <code>{{ $section->code }}</code> • Cập nhật nội dung văn bản cho từng ngôn ngữ
            </p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('section.update', ['section' => $section->id]) }}" method="POST" id="form">
            @csrf
            @method('PUT')

            <div class="form-body-modern">
                <div class="row">
                    <div class="col-12 col-md-10 mx-auto">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-heading"></i> Tiêu đề Section
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="name">
                                    Tên Section <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name"
                                       value="{{ old('name', $section->name) }}"
                                       class="form-control-modern @error('name') is-invalid @enderror"
                                       required>
                                @error('name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        @if (!empty($languages))
                            <div class="form-section-modern">
                                <div class="form-section-title">
                                    <i class="fas fa-language"></i> Nội dung theo từng ngôn ngữ
                                </div>

                                @foreach ($languages as $language)
                                    @php
                                        $existingContent = old('content.' . $language->id);
                                        if (!$existingContent) {
                                            $langEntry = $section->sectionLanguages->firstWhere('language_id', $language->id);
                                            $existingContent = $langEntry?->content ?? '';
                                        }
                                    @endphp

                                    <div class="form-group-modern mb-4">
                                        <label class="form-label-modern d-flex align-items-center gap-2">
                                            @if($language->image)
                                                <img width="20" height="14" src="{{ Storage::url($language->image) }}" alt="{{ $language->name }}" style="border-radius: 2px; object-fit: cover;">
                                            @endif
                                            <span>Nội dung ({{ $language->name }} - {{ strtoupper($language->code) }})</span>
                                        </label>
                                        <textarea
                                            id="sectionContent_{{ $language->id }}"
                                            name="content[{{ $language->id }}]"
                                            rows="8"
                                            class="form-control-modern sectionContent">{{ old("content.{$language->id}", $existingContent) }}</textarea>
                                        @error('content.' . $language->id)
                                            <span class="form-error-modern">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="form-actions-bar">
                <a href="{{ route('section.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button type="button" class="btn-submit-modern" id="btn_submit">
                    <i class="fas fa-save"></i> Cập nhật Section
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
