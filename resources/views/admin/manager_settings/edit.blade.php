@extends('admin.layouts.master')
@section('title')
    Chỉnh sửa chức năng — {{ $manager_setting->manager_name }}
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
                <span class="page-title-icon teal"><i class="fas fa-pen-to-square"></i></span>
                Chỉnh sửa chức năng: {{ $manager_setting->manager_name }}
            </h1>
            <p class="page-subtitle">Mã quyền hạn: <code>{{ $manager_setting->manager_code }}</code></p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('manager_setting.update', ['manager_setting' => $manager_setting->id]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-body-modern">
                <div class="row">
                    <div class="col-12 col-md-8 mx-auto">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-key"></i> Thông tin chức năng
                            </div>

                            @include('admin.manager_settings.parent-field')
                            <div class="form-group-modern">
                                <label class="form-label-modern" for="manager_name">
                                    Tên chức năng / Quyền hạn <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="manager_name" id="manager_name"
                                       value="{{ old('manager_name', $manager_setting->manager_name) }}"
                                       class="form-control-modern @error('manager_name') is-invalid @enderror"
                                       required>
                                @error('manager_name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern">Mã chức năng (Permission Code)</label>
                                <input type="text" value="{{ $manager_setting->manager_code }}" class="form-control-modern" disabled style="background: #f1f5f9; font-family: monospace;">
                                <span class="form-hint-modern">Mã định danh bảo mật được cố định để đối chiếu trong code.</span>
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
                    <i class="fas fa-save"></i> Cập nhật chức năng
                </button>
            </div>
        </form>
    </div>

</div>
@endsection