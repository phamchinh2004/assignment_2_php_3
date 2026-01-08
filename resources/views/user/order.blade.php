@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/order.css')
@endsection
@section('script-libs')
<script>
    // Định nghĩa các biến global trước khi load script
    window.trans = {
        ThoiGianDatPhanPhoi: @json(__('order.ThoiGianDatPhanPhoi')),
        MaDonHang: @json(__('order.MaDonHang')),
        TongTienDonHang: @json(__('order.TongTienDonHang')),
        ChietKhau: @json(__('order.ChietKhau')),
        SoTienHoanNhap: @json(__('order.SoTienHoanNhap')),
        PhanPhoiNgay: @json(__('order.PhanPhoiNgay')),
        CanhBao: @json(__('order.CanhBao')),
        Loi: @json(__('order.Loi')),
        ChoXuLy2: @json(__('order.ChoXuLy2')),
        DangPhanPhoi: @json(__('order.DangPhanPhoi')),
        ThanhCong: @json(__('order.ThanhCong')),
        PhanPhoiThanhCong: @json(__('order.PhanPhoiThanhCong')),
        KhongTimThayDuLieuDonHang: @json(__('order.KhongTimThayDuLieuDonHang')),
        KhongCoDuLieu: @json(__('order.KhongCoDuLieu')),
        SoDuHienTai: @json(__('order.SoDuHienTai')),
        PhanPhoiThanhCong2: @json(__('home.PhanPhoiThanhCong2')),
    };
    window.userBalance = @json($user->balance ?? 0);
    window.route_order = "{{ route('order') }}";
    window.route_get_list_orders_by_tab = "{{ route('get_list_orders_by_tab') }}";
    window.route_accept_order = "{{ route('accept_order') }}";
    window.csrf = "{{ csrf_token() }}";
</script>
@vite('resources/js/user/order.js')
@endsection
@section('content')

<!-- Header Section with Modern Design -->
<div class="order-header-wrapper">
    <div class="order-header-container">
        <div class="order-header-content">
            <!-- Icon & Title -->
            <div class="header-title-section">
                <div class="header-icon">
                    <i class="fas fa-history"></i>
                </div>
                <div class="header-text">
                    <h1 class="header-title">{{__('order.LichSuPhanPhoi')}}</h1>
                    <p class="header-subtitle">{{__('order.DuLieuNayDuocCungCap')}}</p>
                </div>
            </div>
            
            <!-- Balance Card -->
            <div class="balance-card">
                <div class="balance-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="balance-info">
                    <span class="balance-label">{{__('order.SoDuHienTai')}}</span>
                    <span class="balance-amount" id="so_du_user">{{ format_money($user->balance, 7) }}$</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modern Tab Navigation with Horizontal Scroll -->
<div class="tab-navigation-wrapper">
    <div class="tab-navigation-container">
        <button class="tab-scroll-btn tab-scroll-left" id="tabScrollLeft" aria-label="Scroll left">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="tab-navigation" id="tabNavigation">
            <button data-tab="tat-ca" class="tab-btn cspt">
                <i class="fas fa-list"></i>
                <span class="btn_status_text" id="btn_tat_ca">{{__('order.TatCa')}}</span>
            </button>
            <button data-tab="cho-xu-ly" class="tab-btn cspt">
                <i class="fas fa-clock"></i>
                <span class="btn_status_text" id="btn_cho_xu_ly">{{__('order.ChoXuLy')}}</span>
            </button>
            <button data-tab="da-xac-nhan" class="tab-btn cspt">
                <i class="fas fa-check"></i>
                <span class="btn_status_text" id="btn_da_xac_nhan">{{__('order.DaXacNhan')}}</span>
            </button>
            <button data-tab="dang-chuan-bi" class="tab-btn cspt">
                <i class="fas fa-box"></i>
                <span class="btn_status_text" id="btn_dang_chuan_bi">{{__('order.DangChuanBi')}}</span>
            </button>
            <button data-tab="dang-trung-chuyen" class="tab-btn cspt">
                <i class="fas fa-truck"></i>
                <span class="btn_status_text" id="btn_dang_trung_chuyen">{{__('order.DangTrungChuyen')}}</span>
            </button>
            <button data-tab="dang-van-chuyen" class="tab-btn cspt">
                <i class="fas fa-shipping-fast"></i>
                <span class="btn_status_text" id="btn_dang_van_chuyen">{{__('order.DangVanChuyen')}}</span>
            </button>
            <button data-tab="da-giao-hang" class="tab-btn cspt">
                <i class="fas fa-check-double"></i>
                <span class="btn_status_text" id="btn_da_giao_hang">{{__('order.DaGiaoHang')}}</span>
            </button>
            <button data-tab="hoan-thanh" class="tab-btn cspt">
                <i class="fas fa-check-circle"></i>
                <span class="btn_status_text" id="btn_hoan_thanh">{{__('order.HoanThanh')}}</span>
            </button>
            <button data-tab="da-huy" class="tab-btn cspt">
                <i class="fas fa-times-circle"></i>
                <span class="btn_status_text" id="btn_da_huy">{{__('order.DaHuy')}}</span>
            </button>
            <button data-tab="dong-bang" class="tab-btn cspt">
                <i class="fas fa-snowflake"></i>
                <span class="btn_status_text" id="btn_dong_bang">{{__('order.DongBang')}}</span>
            </button>
        </div>
        <button class="tab-scroll-btn tab-scroll-right" id="tabScrollRight" aria-label="Scroll right">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

<!-- Orders List with Modern Layout -->
<div class="orders-wrapper">
    <div class="orders-container">
        <div class="list_orders" id="list_orders">
            <!-- Orders will be loaded here -->
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="success-modal-overlay" id="successModalOverlay">
    <div class="success-modal-container">
        <div class="success-modal-content">
            <!-- Icon thành công với animation -->
            <div class="success-icon-wrapper">
                <div class="success-checkmark">
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>
            </div>
            
            <!-- Tiêu đề -->
            <h2 class="success-title">
                <i class="fas fa-party-horn"></i>
                Phân Phối Thành Công!
            </h2>
            <p class="success-subtitle">Chúc mừng bạn đã hoàn thành đơn hàng</p>
            
            <!-- Thông tin chi tiết -->
            <div class="success-details">
                <!-- Tổng lợi nhuận thực tế (sau khi trừ phạt nếu có) -->
                <div class="profit-highlight">
                    <div class="profit-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="profit-info">
                        <span class="profit-label">Lợi nhuận thực tế</span>
                        <span class="profit-amount" id="success_profit_amount">+$0.00</span>
                    </div>
                </div>
                
                <!-- Chi tiết giao dịch -->
                <div class="transaction-details">
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-money-bill-wave"></i>
                            Giá trị đơn hàng
                        </span>
                        <span class="detail-value" id="success_total_amount">$0.00</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">
                            <i class="fas fa-percentage"></i>
                            Hoa hồng nhận được
                        </span>
                        <span class="detail-value profit-color" id="success_commission">+$0.00</span>
                    </div>
                    <div class="detail-row" id="success_penalty_row" style="display: none;">
                        <span class="detail-label">
                            <i class="fas fa-exclamation-triangle"></i>
                            Tiền phạt quá hạn
                        </span>
                        <span class="detail-value penalty-color" id="success_penalty_amount">-$0.00</span>
                    </div>
                    <div class="detail-divider"></div>
                    <div class="detail-row total-row">
                        <span class="detail-label-total">
                            <i class="fas fa-wallet"></i>
                            Tổng tiền hoàn nhập
                        </span>
                        <span class="detail-value-total" id="success_total_refund">+$0.00</span>
                    </div>
                </div>
                
                <!-- Thông tin thời gian -->
                <div class="success-footer">
                    <div class="footer-info">
                        <i class="fas fa-clock"></i>
                        <span id="success_time"></span>
                    </div>
                    <div class="footer-badge">
                        <i class="fas fa-check-circle"></i>
                        <span>Đã xác nhận</span>
                    </div>
                </div>
            </div>
            
            <!-- Nút đóng -->
            <button class="success-close-btn" onclick="closeSuccessModal()">
                <span>Hoàn tất</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Distribution Modal -->
<div class="loading-modal-overlay" id="distributionModalOverlay">
    <div class="loading-modal-container">
        <h2 class="loading-title">Đang xử lý phân phối</h2>
        <p class="loading-subtitle">Hệ thống đang xử lý yêu cầu của bạn...</p>
        
        <div class="loading-steps">
            <div class="loading-step active" id="dist-step-1">
                <div class="step-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="step-content">
                    <div class="step-label">Kiểm tra đơn hàng</div>
                    <div class="step-description">Xác thực thông tin</div>
                </div>
            </div>
            
            <div class="loading-step" id="dist-step-2">
                <div class="step-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="step-content">
                    <div class="step-label">Xử lý thanh toán</div>
                    <div class="step-description">Cập nhật số dư</div>
                </div>
            </div>
            
            <div class="loading-step" id="dist-step-3">
                <div class="step-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="step-content">
                    <div class="step-label">Hoàn tất giao dịch</div>
                    <div class="step-description">Cập nhật hệ thống</div>
                </div>
            </div>
        </div>
        
        <div class="loading-progress">
            <div class="loading-progress-bar" id="dist-progress-bar"></div>
        </div>
    </div>
</div>

@endsection