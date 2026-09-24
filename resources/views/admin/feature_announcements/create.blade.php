@extends('admin.layouts.master')

@section('title', 'Tạo thông báo tính năng')

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
            <span class="page-title-icon info"><i class="fas fa-bullhorn"></i></span>
            Tạo thông báo tính năng mới
        </h1>
        <p class="page-subtitle">Thông báo sẽ chỉ xuất hiện cho đúng role và trong thời gian hiệu lực đã cấu hình.</p>
    </div>

    <div class="form-card-modern">
        <form action="{{ route('feature_announcements.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('admin.feature_announcements._form')

            <div class="form-actions-bar">
                <a href="{{ route('feature_announcements.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy
                </a>
                <button type="submit" class="btn-submit-modern">
                    <i class="fas fa-check"></i> Tạo thông báo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
