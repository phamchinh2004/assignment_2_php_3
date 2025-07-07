@extends('admin.layouts.master')
@section('title')
Cập nhật section
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_section";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="mb-2 ml-3">
    <a href="{{route('section.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Chi tiết section</h6>
            </div>
        </div>
    </div>
    <section class="container-fluid">
        <form>
            <div class="mt-2 fw-bold">
                <label for="">Tên section</label>
                <input type="text" name="name" value="{{$section->name }}" class="form-control" disabled>
            </div>
            @if (!empty($languages))
            <label class="mt-2 fw-bold text-secondary">Các phiên bản ngôn ngữ</label>
            @foreach ($languages as $language)
            @php
            $existingContent = old('content.' . $language->id);
            if (!$existingContent) {
            $langEntry = $section->sectionLanguages->firstWhere('language_id', $language->id);
            $existingContent = $langEntry?->content ?? '';
            }
            @endphp

            <div class="mt-2 fw-bold">
                <label>
                    <div class="d-flex flex-row align-items-center">
                        <span class="me-2">{{ $language->name }}</span>
                        <img width="20px" src="{{ asset('uploads/language/images/'.$language->image) }}" alt="">
                    </div>
                </label>
                <textarea
                    disabled
                    name="content[{{ $language->id }}]"
                    class="sectionContent">{{ old("content.{$language->id}", $existingContent) }}</textarea>
            </div>
            @endforeach

            @endif
            <div class="d-flex mt-3 justify-content-center">
                <a href="{{route('section.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </form>
    </section>
</div>

@endsection