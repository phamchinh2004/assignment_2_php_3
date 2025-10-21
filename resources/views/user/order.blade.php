@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/order.css')
@endsection
@section('script-libs')
<script>
    const trans = {
        ThoiGianDatPhanPhoi: @json(__('order.ThoiGianDatPhanPhoi')),
        MaDonHang: @json(__('order.MaDonHang')),
        TongTienPhanPhoi: @json(__('order.TongTienPhanPhoi')),
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
    const userBalance = @json($user->balance ?? 0);
</script>
@vite('resources/js/user/order.js')
@endsection
@section('content')

<div class="history-top d-flex flex-column justify-content-center align-items-center">
    <span class="tittle_order">{{__('order.LichSuPhanPhoi')}}</span>
    <span class="so_du" id="so_du_user">{{__('order.SoDuHienTai'). format_money($user->balance, 7) }}$</span>
    <span align="center" class="history-top-text-3">{{__('order.DuLieuNayDuocCungCap')}}</span>
</div>
<div class="status_btns d-flex flex-row justify-content-center align-items-center">
    <a data-tab="tat-ca" class="btn_status cspt">
        <span class="btn_status_text" id="btn_tat_ca">{{__('order.TatCa')}}</span>
    </a>
    <a data-tab="cho-xu-ly" class="btn_status cspt">
        <span class="btn_status_text" id="btn_cho_xu_ly">{{__('order.ChoXuLy')}}</span>
    </a>
    <a data-tab="hoan-thanh" class="btn_status cspt">
        <span class="btn_status_text" id="btn_hoan_thanh">{{__('order.HoanThanh')}}</span>
    </a>
    <a data-tab="dong-bang" class="btn_status cspt">
        <span class="btn_status_text" id="btn_dong_bang">{{__('order.DongBang')}}</span>
    </a>
</div>
<div class="list_orders" id="list_orders">

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