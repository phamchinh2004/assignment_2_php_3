@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/me.css')
@endsection
@section('script-libs')
<script>
    const trans = {
        VuiLongLienHeCskh: @json(__('me.VuiLongLienHeCskh')),
        ThongBao: @json(__('me.ThongBao')),
    };
</script>
@vite('resources/js/user/me.js')
@endsection
@section('content')
@php
    $statusClass = match ($user->status) {
        'activated' => 'is-active',
        'banned' => 'is-banned',
        default => 'is-inactive',
    };
    $statusLabel = match ($user->status) {
        'activated' => __('me.HoatDong'),
        'banned' => __('me.BiCam'),
        'inactivated' => __('me.ChuaKichHoat'),
        default => __('me.KhongHoatDong'),
    };
    $hasBankAccount = filled($user->bank_name) && filled($user->account_number);
    $hasWarehouse = filled($user->warehouse_area) && filled($user->warehouse_address);
@endphp

<main class="me-page">
    <section class="profile-hero" aria-labelledby="profile-name">
        <div class="profile-hero__glow" aria-hidden="true"></div>
        <div class="profile-identity">
            <a href="{{ route('personal_information') }}" class="profile-avatar" aria-label="Cập nhật thông tin cá nhân">
                <img src="{{ get_user_avatar($user) }}" alt="Ảnh đại diện của {{ $user->full_name }}"
                     onerror="this.src='{{ asset('images/default-avatar-gray.svg') }}'">
                <span class="profile-avatar__edit"><i class="fa-solid fa-pen"></i></span>
            </a>
            <div class="profile-copy">
                <div class="profile-copy__badges">
                    @if ($rank && $rank->name)
                        <span class="account-badge account-badge--rank"><i class="fa-solid fa-crown"></i>{{ $rank->name }}</span>
                    @endif
                    <span class="account-badge account-badge--status {{ $statusClass }}">
                        <span class="status-dot"></span>{{ $statusLabel }}
                    </span>
                </div>
                <h1 id="profile-name">{{ $user->full_name }}</h1>
                <p class="profile-username"><i class="fa-regular fa-user"></i>{{ '@' . $user->username }}</p>
                <div class="referral-code"><span>{{ __('me.MaMoi') }}</span><strong>{{ $user->referral_code }}</strong></div>
            </div>
        </div>
        <a href="{{ route('personal_information') }}" class="profile-edit-link">
            <span>Chỉnh sửa hồ sơ</span><i class="fa-solid fa-chevron-right"></i>
        </a>
    </section>

    <div class="me-layout">
        <div class="me-main-column">
            <section class="wallet-card" aria-labelledby="wallet-title">
                <div class="wallet-card__top">
                    <div>
                        <p class="section-eyebrow"><i class="fa-solid fa-wallet"></i>{{ __('me.SoDuTaiKhoan') }}</p>
                        <h2 id="wallet-title" class="wallet-balance"><span>{{ format_money($user->balance) }}</span><small>USD</small></h2>
                        <p class="wallet-caption">{{ __('me.SoDuHienTai') }}</p>
                    </div>
                    <a href="{{ route('balance_fluctuation') }}" class="wallet-history-link" aria-label="Xem thống kê giao dịch">
                        <i class="fa-solid fa-chart-line"></i>
                    </a>
                </div>
                <div class="wallet-metrics">
                    <div class="wallet-metric"><span>Hoa hồng thực nhận</span><strong>{{ format_money($accountSummary['received_commission']) }} USD</strong></div>
                    <div class="wallet-metric"><span>Số dư đang giữ</span><strong>{{ format_money($user->frozen_balance ?? 0) }} USD</strong></div>
                    <div class="wallet-metric"><span>{{ __('me.GiaoDichHomNay') }}</span><strong>{{ $accountSummary['today_transactions'] }}</strong></div>
                </div>
                <div class="wallet-actions">
                    <button type="button" class="wallet-action wallet-action--primary" onclick="thong_bao_lien_he_cskh()">
                        <span class="wallet-action__icon"><i class="fa-solid fa-plus"></i></span><span>{{ __('me.Nap') }}</span>
                    </button>
                    <a href="{{ route('withdraw_money') }}" class="wallet-action">
                        <span class="wallet-action__icon"><i class="fa-solid fa-arrow-up"></i></span><span>{{ __('me.Rut') }}</span>
                    </a>
                    <a href="{{ route('balance_fluctuation') }}" class="wallet-action">
                        <span class="wallet-action__icon"><i class="fa-solid fa-clock-rotate-left"></i></span><span>Lịch sử</span>
                    </a>
                </div>
            </section>

            <section class="me-section" aria-labelledby="quick-actions-title">
                <div class="section-heading"><div><p class="section-eyebrow">Truy cập nhanh</p><h2 id="quick-actions-title">Hoạt động của bạn</h2></div></div>
                <div class="quick-actions">
                    <a href="{{ route('distribution') }}" class="quick-action">
                        <span class="quick-action__icon is-pink"><i class="fa-solid fa-store"></i></span>
                        <span><strong>{{ __('me.PhanPhoi') }}</strong><small>Nhận đơn mới</small></span>
                    </a>
                    <a href="{{ route('order') }}" class="quick-action">
                        <span class="quick-action__icon is-blue"><i class="fa-solid fa-box"></i></span>
                        <span><strong>Đơn hàng</strong><small>Theo dõi xử lý</small></span>
                    </a>
                    <a href="{{ route('balance_fluctuation') }}" class="quick-action">
                        <span class="quick-action__icon is-green"><i class="fa-solid fa-chart-column"></i></span>
                        <span><strong>{{ __('me.BienDong') }}</strong><small>Thống kê tài chính</small></span>
                    </a>
                    <a href="{{ route('vip') }}" class="quick-action">
                        <span class="quick-action__icon is-gold"><i class="fa-solid fa-gem"></i></span>
                        <span><strong>{{ __('me.Vip') }}</strong><small>Quyền lợi thành viên</small></span>
                    </a>
                </div>
            </section>
        </div>

        <aside class="me-side-column">
            <section class="me-section settings-card" aria-labelledby="account-title">
                <div class="section-heading"><div><p class="section-eyebrow">Quản lý</p><h2 id="account-title">Tài khoản</h2></div></div>
                <nav class="settings-list" aria-label="Quản lý tài khoản">
                    <a href="{{ route('personal_information') }}" class="settings-item">
                        <span class="settings-item__icon is-blue"><i class="fa-regular fa-id-card"></i></span>
                        <span class="settings-item__copy"><strong>{{ __('me.ThongTin') }}</strong><small>Hồ sơ và tài khoản ngân hàng</small></span>
                        <span class="settings-item__meta {{ $hasBankAccount ? 'is-complete' : '' }}">{{ $hasBankAccount ? 'Đã liên kết' : 'Chưa liên kết' }}</span>
                        <i class="fa-solid fa-chevron-right settings-item__arrow"></i>
                    </a>
                    <button type="button" class="settings-item" data-bs-toggle="modal" data-bs-target="#warehouseAddressModal">
                        <span class="settings-item__icon is-green"><i class="fa-solid fa-location-dot"></i></span>
                        <span class="settings-item__copy"><strong>{{ __('me.DiaChiKho') }}</strong><small>{{ $user->warehouse_area ?: 'Thiết lập khu vực và địa chỉ' }}</small></span>
                        <span class="settings-item__meta {{ $hasWarehouse ? 'is-complete' : '' }}">{{ $hasWarehouse ? 'Đã cập nhật' : 'Chưa có' }}</span>
                        <i class="fa-solid fa-chevron-right settings-item__arrow"></i>
                    </button>
                    <a href="{{ route('balance_fluctuation') }}?tab=deposit" class="settings-item">
                        <span class="settings-item__icon is-violet"><i class="fa-solid fa-arrow-down"></i></span>
                        <span class="settings-item__copy"><strong>{{ __('me.LichSuNap') }}</strong><small>Giao dịch nạp tiền</small></span>
                        <i class="fa-solid fa-chevron-right settings-item__arrow"></i>
                    </a>
                    <a href="{{ route('balance_fluctuation') }}?tab=withdraw" class="settings-item">
                        <span class="settings-item__icon is-orange"><i class="fa-solid fa-arrow-up"></i></span>
                        <span class="settings-item__copy"><strong>{{ __('me.LichSuRut') }}</strong><small>Giao dịch rút tiền</small></span>
                        <i class="fa-solid fa-chevron-right settings-item__arrow"></i>
                    </a>
                </nav>
            </section>

            <section class="me-section settings-card" aria-labelledby="security-title">
                <div class="section-heading"><div><p class="section-eyebrow">Thiết lập</p><h2 id="security-title">Bảo mật & tuỳ chọn</h2></div></div>
                <div class="settings-list">
                    <button type="button" class="settings-item" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                        <span class="settings-item__icon is-slate"><i class="fa-solid fa-lock"></i></span>
                        <span class="settings-item__copy"><strong>Mật khẩu đăng nhập</strong><small>Thay đổi mật khẩu tài khoản</small></span>
                        <i class="fa-solid fa-chevron-right settings-item__arrow"></i>
                    </button>
                    <button type="button" class="settings-item" data-bs-toggle="modal" data-bs-target="#changeTransactionPasswordModal">
                        <span class="settings-item__icon is-pink"><i class="fa-solid fa-shield-halved"></i></span>
                        <span class="settings-item__copy"><strong>Mật khẩu giao dịch</strong><small>{{ filled($user->transaction_password) ? 'Đã thiết lập' : 'Chưa thiết lập' }}</small></span>
                        <i class="fa-solid fa-chevron-right settings-item__arrow"></i>
                    </button>
                    <div class="language-setting">
                        <button type="button" class="settings-item" onclick="toggleLanguageDropdown()" id="languageDropdownButton" aria-expanded="false" aria-controls="languageDropdown">
                            <span class="settings-item__icon is-violet"><i class="fa-solid fa-language"></i></span>
                            <span class="settings-item__copy"><strong>{{ __('me.NgonNgu') }}</strong><small>{{ strtoupper(App::getLocale()) }}</small></span>
                            <i class="fa-solid fa-chevron-right settings-item__arrow"></i>
                        </button>
                        <div id="languageDropdown" class="language-menu" hidden>
                            <form action="{{ route('language.change') }}" method="POST">
                                @csrf
                                @foreach (\App\Models\Language::all() as $lang)
                                    <button type="submit" name="locale" value="{{ $lang->code }}" class="language-option {{ App::getLocale() === $lang->code ? 'is-current' : '' }}">
                                        <img src="{{ Storage::url($lang->image) }}" width="24" height="24" alt="">
                                        <span>{{ $lang->name }}</span>
                                        @if (App::getLocale() === $lang->code)<i class="fa-solid fa-check"></i>@endif
                                    </button>
                                @endforeach
                            </form>
                        </div>
                    </div>
                </div>
            </section>

            <button type="button" onclick="log_out()" class="logout-button">
                <i class="fa-solid fa-arrow-right-from-bracket"></i><span>{{ __('me.DangXuat') }}</span>
            </button>
        </aside>
    </div>
</main>

<div class="modal fade me-modal" id="warehouseAddressModal" tabindex="-1" aria-labelledby="warehouseAddressLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div><p class="section-eyebrow">Tài khoản</p><h5 class="modal-title" id="warehouseAddressLabel">{{ __('me.TieuDeModalDiaChiKho') }}</h5></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('warehouse_address.update') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required" for="warehouse_area">{{ __('me.KhuVuc') }}</label>
                        <input type="text" class="form-control" id="warehouse_area" name="warehouse_area" placeholder="{{ __('me.NhapKhuVuc') }}" value="{{ old('warehouse_area', $user->warehouse_area) }}" required maxlength="191">
                    </div>
                    <div>
                        <label class="form-label required" for="warehouse_address">{{ __('me.DiaChiHienTai') }}</label>
                        <textarea class="form-control" id="warehouse_address" name="warehouse_address" rows="3" placeholder="{{ __('me.NhapDiaChiHienTai') }}" required maxlength="1000">{{ old('warehouse_address', $user->warehouse_address) }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('me.Dong') }}</button>
                    <button type="submit" class="btn btn-dark">{{ __('me.Luu') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
