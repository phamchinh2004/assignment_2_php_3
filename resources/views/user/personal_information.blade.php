@extends('user.layouts.master')
@section('css-libs')
    @vite('resources/css/user/personal_information.css')
@endsection
@section('script-libs')
    @vite('resources/js/user/personal_information.js')
@endsection
@section('content')
    <!-- Modern Professional Personal Information Page -->
    <div class="profile-container">
        <!-- Header Section -->
        <div class="profile-header">
            <div class="header-content">
                <div class="profile-avatar">
                    <div class="avatar-container">
                        <img src="{{ asset('storage/'.$user->avatar) }}" alt="Profile Avatar"
                            class="avatar-image">
                        <div class="avatar-badge">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name">{{$user->full_name}}</h1>
                    <p class="profile-subtitle">{{__('personal_information.ThongTinCaNhan')}}</p>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-number">6</span>
                            <span class="stat-label">Mục cài đặt</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">100%</span>
                            <span class="stat-label">Bảo mật</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Grid -->
        <div class="settings-grid">
            <!-- Account Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-user-circle"></i>
                        Tài khoản
                    </h2>
                    <p class="section-subtitle">Quản lý thông tin tài khoản cá nhân</p>
                </div>

                <div class="settings-cards">
                    <!-- Avatar Setting -->
                    <div class="setting-card" data-category="account" onclick="openAvatarUpload()">
                        <div class="card-icon">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">{{__('personal_information.AnhDaiDien')}}</h3>
                            <p class="card-description">Cập nhật ảnh đại diện của bạn</p>
                            <div class="card-status">
                                @if($user->avatar)
                                    <span class="status-badge status-active">Đã cập nhật</span>
                                @else
                                    <span class="status-badge status-pending">Chưa cập nhật</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-action">
                            <button class="action-btn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Username Setting -->
                    <div class="setting-card" data-category="account">
                        <div class="card-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">{{__('personal_information.TenTaiKhoan')}}</h3>
                            <p class="card-description">Tên hiển thị trong hệ thống</p>
                            <div class="card-status">
                                <span class="status-value">{{$user->full_name}}</span>
                            </div>
                        </div>
                        <div class="card-action">
                            <button class="action-btn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-shield-alt"></i>
                        Bảo mật
                    </h2>
                    <p class="section-subtitle">Quản lý mật khẩu và bảo mật tài khoản</p>
                </div>

                <div class="settings-cards">
                    <!-- Login Password -->
                    <div class="setting-card" data-category="security" data-bs-toggle="modal"
                        data-bs-target="#changePasswordModal">
                        <div class="card-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">{{__('personal_information.MatKhauDangNhap')}}</h3>
                            <p class="card-description">Thay đổi mật khẩu đăng nhập</p>
                            <div class="card-status">
                                <span class="status-badge status-secure">Đã bảo mật</span>
                            </div>
                        </div>
                        <div class="card-action">
                            <button class="action-btn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Transaction Password -->
                    <div class="setting-card" data-category="security" data-bs-toggle="modal"
                        data-bs-target="#changeTransactionPasswordModal">
                        <div class="card-icon">
                            <i class="fas fa-key"></i>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">{{__('personal_information.MatKhauGiaoDich')}}</h3>
                            <p class="card-description">Mật khẩu cho các giao dịch</p>
                            <div class="card-status">
                                @if($user->transaction_password)
                                    <span class="status-badge status-secure">Đã bảo mật</span>
                                @else
                                    <span class="status-badge status-pending">Chưa thiết lập</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-action">
                            <button class="action-btn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-credit-card"></i>
                        Thanh toán
                    </h2>
                    <p class="section-subtitle">Quản lý phương thức thanh toán và ví</p>
                </div>

                <div class="settings-cards">
                    <!-- Payment Method -->
                    <div class="setting-card" data-category="payment" onclick="handlePaymentMethodClick()">
                        <div class="card-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">{{__('personal_information.PhuongThucThanhToan')}}</h3>
                            <p class="card-description">Thiết lập phương thức thanh toán</p>
                            <div class="card-status">
                                @if($user->username_bank && $user->bank_name && $user->account_number)
                                    <span class="status-badge status-active">{{ $user->bank_name }}</span>
                                @else
                                    <span class="status-badge status-pending">Chưa cấu hình</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-action">
                            <button class="action-btn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Wallet Address -->
                    <div class="setting-card" data-category="payment">
                        <div class="card-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="card-content">
                            <h3 class="card-title">{{__('personal_information.DiaChiKho')}}</h3>
                            <p class="card-description">Quản lý địa chỉ kho</p>
                        </div>
                        <div class="card-action">
                            <button class="action-btn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-secondary" onclick="history.back(); return false;">
                <i class="fas fa-arrow-left"></i>
                {{__('personal_information.QuayLai')}}
            </button>
        </div>
    </div>

    <!-- Bank Link Modal -->
    <input type="text" hidden value="{{ Auth::user()->username_bank ?: "" }}" id="username_bank_input">
    <div id="user-data"
        data-bank-status="{{ $user->username_bank && $user->bank_name && $user->account_number ? 'true' : 'false' }}"
        data-bank-name="{{ $user->bank_name ?: '' }}" data-bank-link-route="{{ route('bank_link') }}"
        data-avatar-upload-route="{{ route('upload_avatar') }}" style="display: none;"></div>
    <div class="modal fade" id="bankLinkModal" tabindex="-1" aria-labelledby="bankLinkModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bankLinkModalLabel">
                        <i class="fas fa-university me-2"></i>{{__('home.LienKetTaiKhoanNganHang')}}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bankLinkForm">
                        <div class="form-group">
                            <label for="accountName"
                                class="form-label required">{{__('withdraw_money.TenChuTaiKhoan')}}</label>
                            <input type="text" class="form-control" id="accountName" name="accountName"
                                placeholder="Nhập tên chủ tài khoản" required>
                        </div>

                        <div class="form-group">
                            <label for="bankName" class="form-label required">{{ __('withdraw_money.TenNganHang') }}</label>
                            <select id="bankName" name="bankName" required>
                                <option value="">{{__('home.ChonNganHang')}}</option>
                                @foreach ($banks as $group => $options)
                                    <optgroup label="{{$group}}">
                                        @foreach ($options as $bank)
                                            <option value="{{$bank}}" {{ $user->bank_name == $bank ? "selected" : "" }}>{{$bank}}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="accountNumber"
                                class="form-label required">{{__('withdraw_money.SoTaiKhoan')}}</label>
                            <input type="text" class="form-control" id="accountNumber" name="accountNumber"
                                placeholder="Nhập số tài khoản" required>
                        </div>

                        <div class="form-group">
                            <label for="transactionPassword"
                                class="form-label required">{{__('withdraw_money.MatKhauGiaoDich')}}</label>
                            <div class="input-group-password">
                                <input type="password" class="form-control" id="transactionPassword"
                                    name="transactionPassword" placeholder="Nhập mật khẩu giao dịch" required>
                                <button type="button" class="password-toggle"
                                    onclick="togglePassword('transactionPassword')">
                                    <i class="fas fa-eye" id="transactionPasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword"
                                class="form-label required">{{__('home.XacNhanMatKhauGiaoDich')}}</label>
                            <div class="input-group-password">
                                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
                                    placeholder="Nhập lại mật khẩu giao dịch" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                    <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>{{__('home.LuuY')}}</strong> {{__('home.ThongTinTaiKhoanNganHangCuaBan')}}
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>{{__('home.Huy')}}
                    </button>
                    <button type="button" class="btn btn-primary" onclick="submitBankLinkForm()">
                        <i class="fas fa-check me-2"></i>{{__('home.XacNhanLienKet')}}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Avatar Upload Modal -->
    <div class="modal fade" id="avatarUploadModal" tabindex="-1" aria-labelledby="avatarUploadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="avatarUploadModalLabel">
                        <i class="fas fa-user-edit me-2"></i>Cập nhật ảnh đại diện
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="avatarUploadForm" enctype="multipart/form-data">
                        <div class="avatar-upload-section">
                            <!-- Current Avatar Preview -->
                            <div class="current-avatar-preview">
                                <div class="avatar-preview-container">
                                    <img id="currentAvatarPreview"
                                        src="{{ $user->avatar ? asset('storage/' . $user->avatar) : asset('images/personal_information/image_7.png') }}"
                                        alt="Current Avatar" class="avatar-preview-image">
                                    <div class="avatar-overlay">
                                        <i class="fas fa-camera"></i>
                                    </div>
                                </div>
                                <p class="avatar-preview-text">Ảnh hiện tại</p>
                            </div>

                            <!-- New Avatar Preview -->
                            <div class="new-avatar-preview" style="display: none;">
                                <div class="avatar-preview-container">
                                    <img id="newAvatarPreview" src="" alt="New Avatar" class="avatar-preview-image">
                                    <div class="avatar-overlay">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                                <p class="avatar-preview-text">Ảnh mới</p>
                            </div>
                        </div>

                        <!-- File Input -->
                        <div class="form-group">
                            <label for="avatarFile" class="form-label required">Chọn ảnh đại diện</label>
                            <input type="file" class="form-control" id="avatarFile" name="avatar" accept="image/*" required>
                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Định dạng: JPG, PNG, GIF. Kích thước tối đa: 2MB. Kích thước khuyến nghị: 200x200px
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="upload-progress" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div class="progress-text">Đang tải lên...</div>
                        </div>

                        <!-- Error/Success Messages -->
                        <div id="avatarUploadMessages"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Hủy
                    </button>
                    <button type="button" class="btn btn-primary" id="uploadAvatarBtn" onclick="uploadAvatar()">
                        <i class="fas fa-upload me-2"></i>Cập nhật ảnh
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection