@extends('user.layouts.master')

@push('page-styles')
    @vite('resources/css/user/bank-account.css')
    @vite('resources/css/user/personal_information.css')
@endpush

@section('script-libs')
    @vite('resources/js/user/personal_information.js')
    @vite('resources/js/user/bank-account.js')
@endsection

@section('content')
    @php
        $accountStatusClass = match ($user->status) {
            'activated' => 'is-active',
            'banned' => 'is-banned',
            default => 'is-inactive',
        };
        $accountStatusLabel = match ($user->status) {
            'activated' => 'Đang hoạt động',
            'banned' => 'Đã bị khóa',
            'inactivated' => 'Chưa kích hoạt',
            default => 'Không hoạt động',
        };
        $bankLinked = filled($user->username_bank) && filled($user->bank_name) && filled($user->account_number);
        $hasTransactionPassword = filled($user->transaction_password);
        $hasWarehouse = filled($user->warehouse_area) && filled($user->warehouse_address);
        $warehouseHasErrors = $errors->has('warehouse_area') || $errors->has('warehouse_address');
    @endphp

    <main class="personal-profile-page"
        data-personal-profile-page
        data-avatar-upload-route="{{ route('upload_avatar') }}"
        data-open-warehouse-on-load="{{ $warehouseHasErrors ? 'true' : 'false' }}"
        data-flash-success="{{ session('success') }}">

        <header class="profile-appbar" aria-labelledby="personal-profile-title">
            <a href="{{ route('me') }}" class="profile-appbar__back" aria-label="Quay lại trang Tôi">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            </a>
            <div class="profile-appbar__copy">
                <span>Quản lý tài khoản</span>
                <h1 id="personal-profile-title">{{ __('personal_information.ThongTinCaNhan') }}</h1>
            </div>
            <button type="button" class="profile-appbar__action" data-avatar-open>
                <i class="fa-solid fa-camera" aria-hidden="true"></i>
                <span>Đổi ảnh</span>
            </button>
        </header>

        <section class="profile-identity-hero" aria-labelledby="profile-display-name">
            <div class="profile-identity-hero__glow" aria-hidden="true"></div>
            <div class="profile-identity-hero__main">
                <button type="button" class="profile-avatar-button" data-avatar-open aria-label="Cập nhật ảnh đại diện">
                    <img src="{{ get_user_avatar($user) }}"
                        alt="Ảnh đại diện của {{ $user->full_name }}"
                        data-profile-avatar
                        onerror="this.src='{{ asset('images/default-avatar-gray.svg') }}'">
                    <span class="profile-avatar-button__camera">
                        <i class="fa-solid fa-camera" aria-hidden="true"></i>
                    </span>
                </button>

                <div class="profile-identity-hero__copy">
                    <div class="profile-identity-hero__badges">
                        <span class="profile-status-pill {{ $accountStatusClass }}">
                            <i aria-hidden="true"></i>{{ $accountStatusLabel }}
                        </span>
                        <span class="profile-type-pill">
                            <i class="fa-regular fa-user" aria-hidden="true"></i> Thành viên
                        </span>
                    </div>
                    <h2 id="profile-display-name">{{ $user->full_name }}</h2>
                    <p class="profile-identity-hero__username">{{ '@' . $user->username }}</p>

                    <div class="profile-identity-hero__signals" aria-label="Trạng thái thiết lập tài khoản">
                        <span class="{{ $user->avatar ? 'is-complete' : '' }}" data-avatar-hero-status>
                            <i class="fa-solid {{ $user->avatar ? 'fa-circle-check' : 'fa-circle' }}" aria-hidden="true"></i>
                            Ảnh đại diện
                        </span>
                        <span class="{{ $bankLinked ? 'is-complete' : '' }}" data-bank-hero-status>
                            <i class="fa-solid {{ $bankLinked ? 'fa-circle-check' : 'fa-circle' }}" aria-hidden="true"></i>
                            Ngân hàng
                        </span>
                        <span class="{{ $hasTransactionPassword ? 'is-complete' : '' }}" data-transaction-hero-status>
                            <i class="fa-solid {{ $hasTransactionPassword ? 'fa-circle-check' : 'fa-circle' }}" aria-hidden="true"></i>
                            Mật khẩu giao dịch
                        </span>
                    </div>
                </div>
            </div>

            <button type="button" class="profile-identity-hero__edit" data-avatar-open>
                <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                <span>Cập nhật ảnh</span>
            </button>
        </section>

        <div class="profile-content-grid">
            <div class="profile-main-column">
                <section class="profile-panel profile-details-panel" aria-labelledby="identity-details-title">
                    <div class="profile-section-heading">
                        <div>
                            <span class="profile-section-kicker">Thông tin định danh</span>
                            <h2 id="identity-details-title">Hồ sơ của bạn</h2>
                            <p>Các thông tin dưới đây được quản lý bởi hệ thống và hiện chỉ có thể xem.</p>
                        </div>
                        <span class="profile-readonly-badge">
                            <i class="fa-solid fa-lock" aria-hidden="true"></i> Chỉ xem
                        </span>
                    </div>

                    <dl class="profile-detail-list">
                        <div class="profile-detail-item">
                            <dt>
                                <span class="profile-detail-item__icon is-coral"><i class="fa-regular fa-id-card" aria-hidden="true"></i></span>
                                <span>Họ và tên</span>
                            </dt>
                            <dd>{{ $user->full_name ?: 'Chưa cập nhật' }}</dd>
                        </div>
                        <div class="profile-detail-item">
                            <dt>
                                <span class="profile-detail-item__icon is-blue"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
                                <span>{{ __('personal_information.TenTaiKhoan') }}</span>
                            </dt>
                            <dd>{{ $user->username ?: 'Chưa cập nhật' }}</dd>
                        </div>
                        <div class="profile-detail-item">
                            <dt>
                                <span class="profile-detail-item__icon is-violet"><i class="fa-regular fa-envelope" aria-hidden="true"></i></span>
                                <span>Email</span>
                            </dt>
                            <dd class="{{ filled($user->email) ? '' : 'is-empty' }}">{{ $user->email ?: 'Chưa cập nhật' }}</dd>
                        </div>
                        <div class="profile-detail-item">
                            <dt>
                                <span class="profile-detail-item__icon is-green"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
                                <span>Số điện thoại</span>
                            </dt>
                            <dd class="{{ filled($user->phone) ? '' : 'is-empty' }}">{{ $user->phone ?: 'Chưa cập nhật' }}</dd>
                        </div>
                        <div class="profile-detail-item">
                            <dt>
                                <span class="profile-detail-item__icon is-gold"><i class="fa-solid fa-link" aria-hidden="true"></i></span>
                                <span>Mã giới thiệu</span>
                            </dt>
                            <dd>{{ $user->referral_code ?: '—' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="profile-panel profile-payment-panel" id="payment-method" aria-labelledby="payment-title">
                    <div class="profile-section-heading">
                        <div>
                            <span class="profile-section-kicker">Thanh toán & nhận tiền</span>
                            <h2 id="payment-title">Tài khoản ngân hàng</h2>
                            <p>Quản lý tài khoản nhận tiền bằng flow liên kết hiện có của hệ thống.</p>
                        </div>
                    </div>
                    <x-user.bank-account-link class="bank-account--profile" :user="$user" :banks="$banks" />
                </section>
            </div>

            <aside class="profile-side-column">
                <section class="profile-panel profile-security-panel" aria-labelledby="security-title">
                    <div class="profile-section-heading profile-section-heading--compact">
                        <div>
                            <span class="profile-section-kicker">Bảo mật</span>
                            <h2 id="security-title">Quyền truy cập</h2>
                        </div>
                    </div>

                    <div class="profile-action-list">
                        <button type="button" class="profile-action-row" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <span class="profile-action-row__icon is-slate"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                            <span class="profile-action-row__copy">
                                <strong>{{ __('personal_information.MatKhauDangNhap') }}</strong>
                                <small>Tối thiểu 6 ký tự khi thay đổi</small>
                            </span>
                            <span class="profile-action-row__meta">Thay đổi</span>
                            <i class="fa-solid fa-chevron-right profile-action-row__arrow" aria-hidden="true"></i>
                        </button>

                        @if($hasTransactionPassword)
                            <button type="button" class="profile-action-row" data-bs-toggle="modal" data-bs-target="#changeTransactionPasswordModal">
                                <span class="profile-action-row__icon is-coral"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
                                <span class="profile-action-row__copy">
                                    <strong>{{ __('personal_information.MatKhauGiaoDich') }}</strong>
                                    <small>Đã thiết lập cho giao dịch</small>
                                </span>
                                <span class="profile-action-row__meta is-complete">Đã có</span>
                                <i class="fa-solid fa-chevron-right profile-action-row__arrow" aria-hidden="true"></i>
                            </button>
                        @else
                            <button type="button" class="profile-action-row" data-open-bank-account>
                                <span class="profile-action-row__icon is-coral"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
                                <span class="profile-action-row__copy">
                                    <strong>{{ __('personal_information.MatKhauGiaoDich') }}</strong>
                                    <small>Thiết lập cùng tài khoản ngân hàng</small>
                                </span>
                                <span class="profile-action-row__meta is-pending">Chưa có</span>
                                <i class="fa-solid fa-chevron-right profile-action-row__arrow" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                </section>

                <section class="profile-panel profile-warehouse-panel" aria-labelledby="warehouse-title">
                    <div class="profile-section-heading profile-section-heading--compact">
                        <div>
                            <span class="profile-section-kicker">Thông tin vận hành</span>
                            <h2 id="warehouse-title">{{ __('personal_information.DiaChiKho') }}</h2>
                        </div>
                        <span class="profile-state-badge {{ $hasWarehouse ? 'is-complete' : '' }}">
                            {{ $hasWarehouse ? 'Đã cập nhật' : 'Chưa có' }}
                        </span>
                    </div>
                    <div class="profile-warehouse-summary">
                        <div>
                            <span>Khu vực</span>
                            <strong>{{ $user->warehouse_area ?: 'Chưa cập nhật' }}</strong>
                        </div>
                        <div>
                            <span>Địa chỉ</span>
                            <strong>{{ $user->warehouse_address ?: 'Chưa cập nhật' }}</strong>
                        </div>
                    </div>
                    <button type="button" class="profile-secondary-action" data-bs-toggle="modal" data-bs-target="#profileWarehouseModal">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        <span>{{ $hasWarehouse ? 'Cập nhật địa chỉ kho' : 'Thiết lập địa chỉ kho' }}</span>
                    </button>
                </section>
            </aside>
        </div>

        <div class="modal fade profile-modal" id="profileAvatarModal" tabindex="-1" aria-labelledby="profileAvatarModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <header class="profile-modal__header">
                        <div>
                            <span class="profile-section-kicker">Hồ sơ</span>
                            <h2 id="profileAvatarModalTitle">{{ __('personal_information.AnhDaiDien') }}</h2>
                            <p>Ảnh vuông sẽ hiển thị tốt nhất trên hồ sơ.</p>
                        </div>
                        <button type="button" class="profile-modal__close" data-bs-dismiss="modal" aria-label="Đóng">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div class="profile-modal__body">
                        <form id="avatarUploadForm" novalidate>
                            <div class="avatar-editor">
                                <div class="avatar-editor__preview">
                                    <img src="{{ get_user_avatar($user) }}" alt="Ảnh đại diện hiện tại" id="avatarEditorPreview"
                                        onerror="this.src='{{ asset('images/default-avatar-gray.svg') }}'">
                                    <span data-avatar-preview-state>Ảnh hiện tại</span>
                                </div>
                                <label class="avatar-file-picker" for="avatarFile">
                                    <span class="avatar-file-picker__icon"><i class="fa-regular fa-image" aria-hidden="true"></i></span>
                                    <span class="avatar-file-picker__copy">
                                        <strong>Chọn ảnh mới</strong>
                                        <small>JPG, PNG hoặc GIF · tối đa 2MB</small>
                                    </span>
                                    <span class="avatar-file-picker__button">Chọn ảnh</span>
                                </label>
                                <input type="file" id="avatarFile" name="avatar" accept="image/jpeg,image/png,image/gif" hidden>
                            </div>
                            <div class="avatar-upload-status" data-avatar-message hidden role="status"></div>
                            <div class="avatar-upload-progress" data-avatar-progress hidden>
                                <div class="avatar-upload-progress__track"><span data-avatar-progress-bar></span></div>
                                <span data-avatar-progress-text>Đang tải lên...</span>
                            </div>
                        </form>
                    </div>

                    <footer class="profile-modal__footer">
                        <button type="button" class="profile-modal-button profile-modal-button--secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="profile-modal-button profile-modal-button--primary" data-avatar-submit disabled>
                            <span>Cập nhật ảnh</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </footer>
                </div>
            </div>
        </div>

        <div class="modal fade profile-modal profile-warehouse-modal" id="profileWarehouseModal" tabindex="-1" aria-labelledby="profileWarehouseModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <header class="profile-modal__header profile-warehouse-modal__header">
                        <span class="profile-warehouse-modal__icon" aria-hidden="true">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>
                        <div class="profile-warehouse-modal__heading">
                            <span class="profile-section-kicker">Thông tin vận hành</span>
                            <h2 id="profileWarehouseModalTitle">{{ __('personal_information.DiaChiKho') }}</h2>
                            <p>Cập nhật khu vực và địa chỉ đang sử dụng cho tài khoản.</p>
                        </div>
                        <button type="button" class="profile-modal__close" data-bs-dismiss="modal" aria-label="Đóng">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </header>

                    <form method="POST" action="{{ route('warehouse_address.update') }}">
                        @csrf
                        <div class="profile-modal__body">
                            <div class="profile-warehouse-modal__notice">
                                <i class="fa-regular fa-map" aria-hidden="true"></i>
                                <span>Kiểm tra kỹ thông tin trước khi lưu để địa chỉ trên hồ sơ luôn chính xác.</span>
                            </div>

                            <div class="profile-warehouse-form-card">
                                <div class="profile-form-field">
                                    <label for="profileWarehouseArea">Khu vực <span>*</span></label>
                                    <div class="profile-form-control-wrap">
                                        <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                                        <input type="text" id="profileWarehouseArea" name="warehouse_area"
                                            value="{{ old('warehouse_area', $user->warehouse_area) }}"
                                            maxlength="191" required autocomplete="address-level1"
                                            class="@error('warehouse_area') is-invalid @enderror"
                                            placeholder="Ví dụ: Hà Nội">
                                    </div>
                                    @error('warehouse_area')
                                        <div class="profile-form-field__error">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="profile-form-field">
                                    <label for="profileWarehouseAddress">Địa chỉ hiện tại <span>*</span></label>
                                    <div class="profile-form-control-wrap profile-form-control-wrap--textarea">
                                        <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                                        <textarea id="profileWarehouseAddress" name="warehouse_address" rows="4"
                                            maxlength="1000" required autocomplete="street-address"
                                            class="@error('warehouse_address') is-invalid @enderror"
                                            placeholder="Nhập địa chỉ chi tiết">{{ old('warehouse_address', $user->warehouse_address) }}</textarea>
                                    </div>
                                    @error('warehouse_address')
                                        <div class="profile-form-field__error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <footer class="profile-modal__footer">
                            <button type="button" class="profile-modal-button profile-modal-button--secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="profile-modal-button profile-modal-button--primary">
                                <span>Lưu địa chỉ</span>
                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                            </button>
                        </footer>
                    </form>
                </div>
            </div>
        </div>
    </main>
@endsection
