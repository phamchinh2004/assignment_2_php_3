@extends('admin.layouts.master')
@section('title')
    Thêm mới chức năng
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('manager_setting.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách chức năng
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon teal"><i class="fas fa-shield-halved"></i></span>
                Thêm mới chức năng quản lý
            </h1>
            <p class="page-subtitle">Tạo quyền hạn mới cho hệ thống quản trị nhân viên</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('manager_setting.store') }}" method="POST">
            @csrf

            <div class="form-body-modern">
                <div class="row">
                    <div class="col-12 col-md-8 mx-auto">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-key"></i> Thông tin chức năng
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="manager_name">
                                    Tên chức năng / Quyền hạn <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="manager_name" id="manager_name"
                                       value="{{ old('manager_name', '') }}"
                                       class="form-control-modern @error('manager_name') is-invalid @enderror"
                                       placeholder="Ví dụ: Quản lý nạp tiền, Quản lý báo cáo..." required>
                                @error('manager_name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                                <span class="form-hint-modern">Mã chức năng (permission code) sẽ được tạo tự động dạng slug.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions-bar">
                <a href="{{ route('manager_setting.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button type="submit" class="btn-submit-modern">
                    <i class="fas fa-check"></i> Lưu chức năng
                </button>
            </div>
        </form>
    </div>

</div>
@endsection