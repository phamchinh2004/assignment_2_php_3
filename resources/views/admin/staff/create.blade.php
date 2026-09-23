@extends('admin.layouts.master')
@section('title')
    Thêm tài khoản quản trị
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
                Tạo tài khoản quản trị
            </h1>
            <p class="page-subtitle">
                {{ ($canChooseRole ?? false) ? 'Tạo tài khoản nhân viên hoặc admin' : 'Tạo tài khoản nhân viên mới' }}
            </p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('staff.store') }}" method="POST">
            @csrf

            <div class="form-body-modern">
                <div class="row">
                    {{-- Column 1: Thông tin tài khoản --}}
                    <div class="col-12 col-lg-6">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-user-tie"></i> Thông tin tài khoản
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
                                <label class="form-label-modern" for="email">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="email" id="email"
                                       value="{{ old('email', '') }}"
                                       class="form-control-modern @error('email') is-invalid @enderror"
                                       placeholder="Ví dụ: nhanvien@example.com" required>
                                @error('email')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                                <span class="form-hint-modern text-danger">
                                    <i class="fas fa-circle-exclamation"></i>
                                    Bắt buộc sử dụng email thật để nhận thông báo và khôi phục tài khoản khi cần.
                                </span>
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

                            @if($canChooseRole ?? false)
                                <div class="form-group-modern">
                                    <label class="form-label-modern" for="role">Vai trò <span class="text-danger">*</span></label>
                                    <select name="role" id="role" class="form-control-modern" required>
                                        <option value="staff" @selected(old('role', 'staff') === 'staff')>Nhân viên (staff)</option>
                                        <option value="admin" @selected(old('role') === 'admin')>Quản trị viên (admin)</option>
                                    </select>
                                    <span class="form-hint-modern">Admin vẫn hoạt động theo permission do owner cấp.</span>
                                </div>
                            @endif
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
                    <i class="fas fa-check"></i> Lưu tài khoản
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
