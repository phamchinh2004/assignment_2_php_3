@extends('admin.layouts.master')

@section('title', 'Chỉnh sửa thông báo tính năng')

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">
    <a href="{{ route('feature_announcements.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách
    </a>

    <div class="page-header-wrapper">
        <h1 class="page-title-main">
            <span class="page-title-icon info"><i class="fas fa-pen-to-square"></i></span>
            Chỉnh sửa thông báo
        </h1>
        <p class="page-subtitle">Version hiện tại: {{ $featureAnnouncement->version }}</p>
    </div>

    <div class="form-card-modern">
        <form action="{{ route('feature_announcements.update', $featureAnnouncement) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.feature_announcements._form')

            <div class="form-actions-bar">
                <a href="{{ route('feature_announcements.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy
                </a>
                <button type="submit" class="btn-submit-modern">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
