@extends('admin.layouts.master')
@section('title')
    Thêm mới người dùng
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('script-libs')
    @vite('resources/js/admin/user/create.js')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('user.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon"><i class="fas fa-user-plus"></i></span>
                Tạo tài khoản người dùng
            </h1>
            <p class="page-subtitle">Thêm thành viên mới vào hệ thống với thông tin đăng nhập và cấp độ khởi đầu</p>
        </div>
    </div>

    {{-- Form Card --}}
    <div class="form-card-modern">
        <form action="{{ route('user.store') }}" method="post" id="form">
            @csrf
            @method('POST')

            <div class="form-body-modern">
                <div class="row">
                    {{-- Column 1: Thông tin tài khoản --}}
                    <div class="col-12 col-lg-6">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-id-card"></i> Thông tin cá nhân & Tài khoản
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="full_name">
                                    Họ và tên thật <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="full_name" id="full_name"
                                       value="{{ old('full_name', '') }}"
                                       class="form-control-modern @error('full_name') is-invalid @enderror"
                                       placeholder="Ví dụ: Nguyễn Văn A">
                                @error('full_name')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="username">
                                    Tên đăng nhập (Username) <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="username" id="username"
                                       value="{{ old('username', '') }}"
                                       class="form-control-modern @error('username') is-invalid @enderror"
                                       placeholder="Ví dụ: nguyenvana">
                                @error('username')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                                <span class="form-hint-modern">Dùng để đăng nhập vào ứng dụng, viết liền không dấu.</span>
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="phone">
                                    Số điện thoại <span class="text-danger">*</span>
                                </label>
                                <input type="tel" name="phone" id="phone"
                                       value="{{ old('phone', '') }}"
                                       class="form-control-modern @error('phone') is-invalid @enderror"
                                       placeholder="Ví dụ: 0987654321">
                                @error('phone')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Column 2: Mật khẩu & Cấp độ --}}
                    <div class="col-12 col-lg-6">
                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-lock"></i> Mật khẩu & Bảo mật
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="password">
                                    Mật khẩu <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="password" id="password"
                                       value="{{ old('password', '123456') }}"
                                       class="form-control-modern @error('password') is-invalid @enderror"
                                       placeholder="Tối thiểu 6 ký tự">
                                @error('password')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                                <span class="form-hint-modern">Mặc định là 123456 nếu không đổi.</span>
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="password_confirmation">
                                    Nhập lại mật khẩu <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="password_confirmation" id="password_confirmation"
                                       value="{{ old('password_confirmation', '123456') }}"
                                       class="form-control-modern @error('password_confirmation') is-invalid @enderror"
                                       placeholder="Xác nhận lại mật khẩu">
                                @error('password_confirmation')
                                    <span class="form-error-modern">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-section-modern">
                            <div class="form-section-title">
                                <i class="fas fa-crown text-warning"></i> Cấp độ thành viên (Rank)
                            </div>

                            <div class="form-group-modern">
                                <label class="form-label-modern" for="rank">Chọn cấp độ</label>
                                <select name="rank" id="rank" class="form-select-modern">
                                    <option value="">--- Chọn cấp độ thành viên ---</option>
                                    @if (!empty($list_ranks))
                                        @foreach ($list_ranks as $rank)
                                            <option value="{{ $rank['id'] }}" {{ old('rank') == $rank['id'] ? 'selected' : '' }}>
                                                {{ $rank['name'] }} — {{ $rank['spin_count'] }} đơn hàng (HH: {{ $rank['commission_percentage'] }}%)
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <span class="form-hint-modern">Quyết định số lượt đặt đơn và tỷ lệ hoa hồng ban đầu của tài khoản.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Actions Bar --}}
            <div class="form-actions-bar">
                <a href="{{ route('user.index') }}" class="btn-cancel-modern">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
                <button class="btn-submit-modern" type="button" id="btn_submit">
                    <i class="fas fa-check"></i> Tạo người dùng
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
