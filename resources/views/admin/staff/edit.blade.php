@extends('admin.layouts.master')
@section('title')
    Chỉnh sửa thông tin nhân viên
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
                <span class="page-title-icon teal"><i class="fas fa-user-pen"></i></span>
                Chỉnh sửa tài khoản nhân viên
            </h1>
            <p class="page-subtitle">Cập nhật hồ sơ và thông tin liên hệ của {{ $get_staff_old->full_name }}</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('staff.update', ['staff' => $get_staff_old->id]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-body-modern">
                <div class="row">
                    <div class="col-12 col-md-8 mx-auto">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-id-card"></i> Thông tin cơ bản
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="full_name">
                                    Họ và tên <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="full_name" id="full_name"
                                       value="{{ old('full_name', $get_staff_old->full_name) }}"
                                       class="form-control-modern @error('full_name') is-invalid @enderror"
                                       required>
                                @error('full_name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="username">
                                    Tên đăng nhập (Username) <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="username" id="username"
                                       value="{{ old('username', $get_staff_old->username) }}"
                                       class="form-control-modern @error('username') is-invalid @enderror"
                                       minlength="6" maxlength="255" required>
                                @error('username')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="email">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="email" id="email"
                                       value="{{ old('email', $get_staff_old->email) }}"
                                       class="form-control-modern @error('email') is-invalid @enderror"
                                       required>
                                @error('email')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            @if($canChooseRole)
                                <div class="form-group-modern">
                                    <label class="form-label-modern" for="role">Vai trò <span class="text-danger">*</span></label>
                                    <select name="role" id="role"
                                            class="form-control-modern @error('role') is-invalid @enderror" required>
                                        <option value="staff" @selected(old('role', $get_staff_old->role) === 'staff')>Nhân viên (staff)</option>
                                        <option value="admin" @selected(old('role', $get_staff_old->role) === 'admin')>Quản trị viên (admin)</option>
                                    </select>
                                    @error('role')
                                        <span class="form-error-modern">{{ $message }}</span>
                                    @enderror
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
                    <i class="fas fa-save"></i> Cập nhật nhân viên
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
