@extends('admin.layouts.master')
@section('title')
    Thêm mới nhân viên
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('staff.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách nhân viên
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon teal"><i class="fas fa-user-plus"></i></span>
                Tạo tài khoản nhân viên
            </h1>
            <p class="page-subtitle">Thêm nhân sự mới vào hệ thống quản trị nội bộ</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('staff.store') }}" method="POST">
            @csrf

            <div class="form-body-modern">
                <div class="row">
                    {{-- Column 1: Thông tin cá nhân --}}
                    <div class="col-12 col-lg-6">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-user-tie"></i> Thông tin cá nhân
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="full_name">
                                    Họ và tên thật <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="full_name" id="full_name"
                                       value="{{ old('full_name', '') }}"
                                       class="form-control-modern @error('full_name') is-invalid @enderror"
                                       placeholder="Ví dụ: Trần Văn B" required>
                                @error('full_name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="phone">
                                    Số điện thoại <span class="text-danger">*</span>
                                </label>
                                <input type="tel" name="phone" id="phone"
                                       value="{{ old('phone', '') }}"
                                       class="form-control-modern @error('phone') is-invalid @enderror"
                                       placeholder="Ví dụ: 0912345678" required>
                                @error('phone')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Column 2: Thông tin đăng nhập --}}
                    <div class="col-12 col-lg-6">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-key"></i> Tài khoản & Mật khẩu
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="username">
                                    Tên đăng nhập (Username) <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="username" id="username"
                                       value="{{ old('username', '') }}"
                                       class="form-control-modern @error('username') is-invalid @enderror"
                                       placeholder="Ví dụ: nhanvien_01" required>
                                @error('username')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                                <span class="form-hint-modern">Viết liền không dấu, dùng để đăng nhập vào Admin.</span>
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="password">
                                    Mật khẩu đăng nhập
                                </label>
                                <input type="text" name="password" id="password"
                                       value="{{ old('password', '123456') }}"
                                       class="form-control-modern @error('password') is-invalid @enderror"
                                       placeholder="Tối thiểu 6 ký tự">
                                @error('password')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                                <span class="form-hint-modern">Mặc định là 123456 nếu để trống.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Actions Bar --}}
            <div class="form-actions-bar">
                <a href="{{ route('staff.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button type="submit" class="btn-submit-modern">
                    <i class="fas fa-check"></i> Lưu tài khoản nhân viên
                </button>
            </div>
        </form>
    </div>

</div>
@endsection