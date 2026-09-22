@extends('admin.layouts.master')
@section('title')
Chỉnh sửa người dùng
@endsection

@section('style-libs')
@vite('resources/css/admin/common-modern.css')
@vite('resources/css/admin/user/edit.css')
@endsection

@section('script-libs')
@vite('resources/js/admin/user/edit.js')
@endsection

@section('content')
@php
    $displayName = $user->full_name ?: ($user->username ?: 'Người dùng');
    $initials = mb_strtoupper(mb_substr($displayName, 0, 2));
    $hasPreciseLocation = $user->location_latitude !== null || $user->location_longitude !== null
        || filled($user->location_country_code) || filled($user->location_country) || filled($user->location_city);
    $hasApproxLocation = filled($user->approx_location_country_code) || filled($user->approx_location_country);
    $hasLocation = $hasPreciseLocation || $hasApproxLocation;
    $isUserOnline = $user->isOnline();
    $statusMeta = match ($user->status) {
        'activated' => ['label' => 'Đang hoạt động', 'class' => 'success', 'icon' => 'fa-circle-check'],
        'banned' => ['label' => 'Đã khóa', 'class' => 'danger', 'icon' => 'fa-lock'],
        default => ['label' => 'Chưa kích hoạt', 'class' => 'warning', 'icon' => 'fa-clock'],
    };
@endphp

<div class="container-fluid px-3 px-lg-4 py-3 user-edit-page">
    <div class="user-edit-toolbar">
        <a href="{{ route('user.index') }}" class="btn-back-modern mb-0">
            <i class="fas fa-arrow-left"></i> Quay lại danh sách
        </a>
    </div>

    <section class="user-edit-hero">
        <div class="user-edit-identity">
            <div class="user-edit-avatar" aria-hidden="true">{{ $initials }}</div>
            <div>
                <span class="user-edit-kicker">Hồ sơ người dùng #{{ $user->id }}</span>
                <h1>{{ $displayName }}</h1>
                <div class="user-edit-meta">
                    <span><i class="fas fa-at"></i>{{ $user->username }}</span>
                    <span><i class="fas fa-phone"></i>{{ $user->phone ?: 'Chưa có số điện thoại' }}</span>
                    <span><i class="fas fa-envelope"></i>{{ $user->email ?: 'Chưa có email' }}</span>
                </div>
            </div>
        </div>
        <span class="user-status-pill {{ $statusMeta['class'] }}">
            <i class="fas {{ $statusMeta['icon'] }}"></i>{{ $statusMeta['label'] }}
        </span>
    </section>

    @if ($errors->any())
        <div class="user-edit-alert" role="alert">
            <i class="fas fa-circle-exclamation"></i>
            <div><strong>Chưa thể lưu thay đổi.</strong><span> Kiểm tra lại các trường được đánh dấu.</span></div>
        </div>
    @endif

    <div class="user-edit-grid">
        <div class="form-card">
            <div class="form-header">
                <div>
                    <span class="form-header-kicker">Thông tin có thể chỉnh sửa</span>
                    <h5><i class="fas fa-user-pen me-2"></i>Cập nhật hồ sơ</h5>
                </div>
            </div>

            <div class="form-body">
                <form action="{{ route('user.update',['user'=>$user->id]) }}" method="post" enctype="multipart/form-data" id="form">
                    @csrf
                    @method('PUT')

                    <!-- Thông tin cá nhân -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-user me-2"></i>Thông tin cá nhân
                        </div>

                        <div class="row row-cols-custom">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="full_name">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" id="full_name"
                                        value="{{ old('full_name',$user->full_name) }}"
                                        class="form-control form-control-custom"
                                        placeholder="Nhập họ và tên thật">
                                    @error('full_name')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="username">Tên đăng nhập <span class="text-danger">*</span></label>
                                    <input type="text" name="username" id="username"
                                        value="{{ old('username',$user->username) }}"
                                        class="form-control form-control-custom"
                                        placeholder="Nhập tên đăng nhập">
                                    @error('username')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row row-cols-custom">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="phone">Số điện thoại <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" id="phone"
                                        value="{{ old('phone',$user->phone) }}"
                                        class="form-control form-control-custom @error('phone') is-invalid @enderror"
                                        autocomplete="tel" maxlength="50"
                                        placeholder="Nhập số điện thoại">
                                    @error('phone')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email"
                                        value="{{ old('email',$user->email) }}"
                                        class="form-control form-control-custom"
                                        placeholder="Nhập địa chỉ email">
                                    @error('email')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row row-cols-custom">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="status">Trạng thái tài khoản <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-control form-select-custom">
                                        <option value="inactivated" @selected(old('status', $user->status) === 'inactivated')>Chưa kích hoạt</option>
                                        <option value="activated" @selected(old('status', $user->status) === 'activated')>Đang hoạt động</option>
                                        <option value="banned" @selected(old('status', $user->status) === 'banned')>Đã khóa</option>
                                    </select>
                                    @error('status')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            @if (auth()->user()->role === \App\Models\User::ROLE_OWNER)
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="role">Vai trò <span class="text-danger">*</span></label>
                                    <select name="role" id="role" class="form-control form-select-custom">
                                        <option value="member" @selected(old('role', $user->role) === 'member')>Người dùng</option>
                                        <option value="staff" @selected(old('role', $user->role) === 'staff')>Nhân viên</option>
                                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Quản trị viên</option>
                                    </select>
                                    @error('role')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Thông tin kho -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-warehouse me-2"></i>Thông tin kho
                        </div>

                        <div class="form-group-custom">
                            <label for="warehouse_area">Khu vực phân phối / kho</label>
                            <input type="text" name="warehouse_area" id="warehouse_area"
                                value="{{ old('warehouse_area', $user->warehouse_area) }}"
                                class="form-control form-control-custom @error('warehouse_area') is-invalid @enderror"
                                maxlength="191"
                                placeholder="Nhập khu vực hoặc tên kho">
                            @error('warehouse_area')
                                <small class="error-message">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="form-group-custom mb-0">
                            <label for="warehouse_address">Địa chỉ kho</label>
                            <textarea name="warehouse_address" id="warehouse_address" rows="3"
                                class="form-control form-control-custom @error('warehouse_address') is-invalid @enderror"
                                maxlength="1000"
                                placeholder="Nhập địa chỉ kho hiện tại">{{ old('warehouse_address', $user->warehouse_address) }}</textarea>
                            @error('warehouse_address')
                                <small class="error-message">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Thông tin ngân hàng -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-university me-2"></i>Thông tin ngân hàng
                        </div>

                        <div class="form-group-custom">
                            <label for="username_bank">Tên tài khoản ngân hàng</label>
                            <input type="text" name="username_bank" id="username_bank"
                                value="{{ old('username_bank',$user->username_bank) }}"
                                class="form-control form-control-custom"
                                placeholder="Nhập tên tài khoản ngân hàng">
                            @error('username_bank')
                            <small class="error-message">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="row row-cols-custom">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="bank_name">Tên ngân hàng</label>
                                    <select name="bank_name" id="bank_name" class="form-control form-select-custom @error('bank_name') is-invalid @enderror">
                                        <option value="">--- Chọn ngân hàng ---</option>
                                        @foreach ($banks as $group=> $options)
                                        <optgroup label="{{$group}}">
                                            @foreach ($options as $bank)
                                            <option value="{{$bank}}" @selected(old('bank_name', $user->bank_name) === $bank)>{{$bank}}</option>
                                            @endforeach
                                        </optgroup>
                                        @endforeach
                                    </select>
                                    @error('bank_name')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="account_number">Số tài khoản</label>
                                    <input type="text" name="account_number" id="account_number"
                                        value="{{ old('account_number',$user->account_number) }}"
                                        class="form-control form-control-custom @error('account_number') is-invalid @enderror"
                                        inputmode="numeric" maxlength="100"
                                        placeholder="Nhập số tài khoản">
                                    @error('account_number')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin tài khoản -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-cog me-2"></i>Cài đặt tài khoản
                        </div>

                        <div class="row row-cols-custom">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="balance">Số dư</label>
                                    <input type="number" name="balance" id="balance"
                                        value="{{ old('balance',$user->balance?:0) }}"
                                        class="form-control form-control-custom"
                                        placeholder="Nhập số dư">
                                    @error('balance')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="frozen_balance">Số dư đóng băng</label>
                                    <input type="number" name="frozen_balance" id="frozen_balance"
                                        value="{{ old('frozen_balance', $user->frozen_balance ?: 0) }}"
                                        class="form-control form-control-custom"
                                        placeholder="Nhập số dư đóng băng"
                                        step="0.00000001"
                                        min="0">
                                    @error('frozen_balance')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="rank">Cấp độ</label>
                                    <select name="rank" id="rank" class="form-control form-select-custom">
                                        <option value="">--- Chọn cấp độ ---</option>
                                        @if (!empty($list_ranks))
                                        @foreach ($list_ranks as $rank)
                                        <option value="{{ $rank['id'] }}" @selected((string) old('rank', $user->rank_id) === (string) $rank['id'])>
                                            {{ $rank['name']}} - Có {{ $rank['spin_count']}} đơn hàng
                                        </option>
                                        @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            @if (app(\App\Services\AuthorizationService::class)->can(auth()->user(), config('authorization.capabilities.manage_all_users')))
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="lucky_wheel_bonus_spins">Lượt quay may mắn được cấp còn lại</label>
                                    <input type="number" name="lucky_wheel_bonus_spins" id="lucky_wheel_bonus_spins"
                                        value="{{ old('lucky_wheel_bonus_spins', $user->lucky_wheel_bonus_spins ?? 0) }}"
                                        class="form-control form-control-custom"
                                        min="0" step="1">
                                    @error('lucky_wheel_bonus_spins')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="checkbox-card">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" name="reset_progress" id="reset_progress" value="1" class="form-check-input me-3">
                                <label class="form-check-label" for="reset_progress">
                                    <i class="fas fa-sync-alt me-2"></i>Làm mới tiến trình
                                </label>
                            </div>
                        </div>

                        <div class="checkbox-card">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" name="clone_account" id="clone_account" value="1" class="form-check-input me-3" @checked(old('clone_account', $user->clone_account))>
                                <label class="form-check-label" for="clone_account">
                                    <i class="fas fa-clone me-2"></i>Tài khoản clone
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="user-edit-actions">
                        <a href="{{ route('user.index') }}" class="btn-edit-secondary">Hủy</a>
                        <button class="btn-edit-primary" type="button" id="btn_submit">
                            <i class="fas fa-floppy-disk"></i>Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <aside class="user-edit-sidebar">
            <section class="side-card address-card">
                <div class="side-card-header">
                    <div>
                        <span class="side-card-eyebrow">Vị trí</span>
                        <h2>Địa chỉ hiện tại</h2>
                    </div>
                    <span class="side-card-icon"><i class="fas fa-location-dot"></i></span>
                </div>

                @if ($hasLocation)
                    <div class="location-groups">
                        @if ($hasPreciseLocation)
                            <div class="location-group">
                                <div class="location-group-head">
                                    <span class="location-source-badge precise">
                                        <i class="fas fa-crosshairs"></i>Vị trí chính xác
                                    </span>
                                    <span>{{ $user->location_updated_at?->format('d/m/Y H:i') ?: 'Chưa rõ thời gian' }}</span>
                                </div>
                                <div class="address-display">
                                    <div class="address-row">
                                        <span>Thành phố</span>
                                        <strong>{{ $user->location_city ?: '—' }}</strong>
                                    </div>
                                    <div class="address-row">
                                        <span>Quốc gia</span>
                                        <strong>
                                            {{ $user->location_country ?: '—' }}
                                            @if($user->location_country_code)
                                                ({{ strtoupper($user->location_country_code) }})
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="address-row">
                                        <span>Tọa độ</span>
                                        <strong class="account-fact-code">
                                            @if($user->location_latitude !== null && $user->location_longitude !== null)
                                                {{ $user->location_latitude }}, {{ $user->location_longitude }}
                                            @else
                                                —
                                            @endif
                                        </strong>
                                    </div>
                                    <div class="address-row">
                                        <span>Độ chính xác</span>
                                        <strong>{{ $user->location_accuracy !== null ? number_format($user->location_accuracy, 0) . ' m' : '—' }}</strong>
                                    </div>
                                </div>
                                @if($user->location_latitude !== null && $user->location_longitude !== null)
                                    <a class="location-map-link"
                                        href="https://www.google.com/maps/search/?api=1&amp;query={{ $user->location_latitude }},{{ $user->location_longitude }}"
                                        target="_blank" rel="noopener noreferrer">
                                        <i class="fas fa-map"></i>Xem trên Google Maps
                                    </a>
                                @endif
                            </div>
                        @endif

                        @if ($hasApproxLocation)
                            <div class="location-group">
                                <div class="location-group-head">
                                    <span class="location-source-badge approximate">
                                        <i class="fas fa-globe"></i>Vị trí tương đối
                                    </span>
                                    <span>{{ $user->approx_location_updated_at?->format('d/m/Y H:i') ?: 'Chưa rõ thời gian' }}</span>
                                </div>
                                <div class="address-display">
                                    <div class="address-row">
                                        <span>Quốc gia</span>
                                        <strong>
                                            {{ $user->approx_location_country ?: '—' }}
                                            @if($user->approx_location_country_code)
                                                ({{ strtoupper($user->approx_location_country_code) }})
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="address-empty">
                        <i class="fas fa-map-location-dot"></i>
                        <strong>Chưa có dữ liệu vị trí</strong>
                        <span>Hệ thống chưa ghi nhận vị trí chính xác hoặc vị trí tương đối của người dùng.</span>
                    </div>
                @endif

                <div class="address-actions">
                    <form action="{{ route('user.location.refresh', ['user' => $user->id]) }}" method="post" id="refreshLocationForm">
                        @csrf
                        <button type="submit" class="btn-address-update">
                            <i class="fas fa-rotate"></i>Cập nhật vị trí tương đối
                        </button>
                    </form>
                    <form action="{{ route('user.location.destroy', ['user' => $user->id]) }}" method="post" id="deleteLocationForm">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn-address-delete" id="deleteLocationButton" @disabled(!$hasLocation)>
                            <i class="fas fa-trash"></i>Xóa
                        </button>
                    </form>
                </div>
                <p class="side-card-note">
                    <i class="fas {{ $isUserOnline ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
                    {{ $isUserOnline
                        ? 'Người dùng đang online. Bấm cập nhật để yêu cầu trình duyệt của họ lấy lại vị trí tương đối theo IP.'
                        : 'Người dùng đang offline. Hệ thống sẽ không cập nhật vị trí tương đối cho đến khi họ online.' }}
                </p>
            </section>

            <section class="side-card">
                <div class="side-card-header">
                    <div>
                        <span class="side-card-eyebrow">Thông tin hệ thống</span>
                        <h2>Tổng quan tài khoản</h2>
                    </div>
                    <span class="side-card-icon neutral"><i class="fas fa-circle-info"></i></span>
                </div>
                <dl class="account-facts">
                    <div><dt>Mã giới thiệu</dt><dd>{{ $user->referral_code ?: 'Chưa có' }}</dd></div>
                    <div><dt>Ngày tạo</dt><dd>{{ $user->created_at?->format('d/m/Y H:i') ?: 'Không xác định' }}</dd></div>
                    <div><dt>Hoạt động gần nhất</dt><dd>{{ $user->last_seen?->format('d/m/Y H:i') ?: 'Chưa ghi nhận' }}</dd></div>
                    <div><dt>IP đăng ký</dt><dd class="account-fact-code">{{ $user->register_ip ?: 'Chưa ghi nhận' }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
</div>

@endsection
