@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/distribution.css')
@endsection
@section('script-libs')
@vite('resources/js/user/distribution.js')
<script>
    const trans = {
        coLoiXayRa: @json(__('home.CoLoiXayRa')),
        donHangChuaXuLy: @json(__('home.DonHangChuaXuLy')),
        DonHangDangBiDongBang: @json(__('home.DonHangDangBiDongBang')),
        HetLuotQuay: @json(__('home.HetLuotQuay')),
        QuayLaiNhaBan: @json(__('home.QuayLaiNhaBan')),
        LoiDanhSachDonHang: @json(__('home.LoiDanhSachDonHang')),
        ThoiGianDatPhanPhoi: @json(__('home.ThoiGianDatPhanPhoi')),
        CanhBao: @json(__('home.CanhBao')),
        Loi: @json(__('home.Loi')),
        ChoXuLy: @json(__('home.ChoXuLy')),
        DangPhanPhoi: @json(__('home.DangPhanPhoi')),
        ThanhCong: @json(__('home.ThanhCong')),
        PhanPhoiThanhCong2: @json(__('home.PhanPhoiThanhCong2')),
    };
    const route_update_location = @json(route('location.update'));
    const route_update_approximate_location = @json(route('location.approximate.update'));
</script>
@endsection
@section('content')
<div id="fireworks-container"></div>
@php
    $remaining = max(0, $total_orders - $current_order);
    $percentage = $total_orders > 0 ? min(100, round(($current_order / $total_orders) * 100, 1)) : 0;
@endphp

<main class="distribution-page">
    <header class="distribution-nav">
        <a class="btn-back-modern" href="#" onclick="history.back(); return false;" aria-label="Quay lại">
            <i class="fa fa-arrow-left"></i>
        </a>
        <div>
            <span class="nav-eyebrow">Distribution center</span>
            <h1 class="header-title">{{__('distribution.HeThongPhanPhoi')}}</h1>
        </div>
        @if($user_rank)
            <div class="rank-badge">
                <i class="fas fa-crown"></i>
                <span>{{$user_rank->name}}</span>
                <strong>{{$user_rank->commission_percentage}}%</strong>
            </div>
        @else
            <div class="rank-badge no-rank">
                <i class="fas fa-circle-exclamation"></i>
                <span>{{__('distribution.ChuaCoCapDo')}}</span>
            </div>
        @endif
    </header>

    <section class="distribution-command">
        <div class="command-copy">
            <span class="live-status"><i></i> Hệ thống đang hoạt động</span>
            <h2>Đơn hàng tiếp theo<br><em>đang chờ bạn.</em></h2>
            <p>{{__('distribution.TongPhanPhoi')}}</p>
        </div>

        <button class="btn-distribute-modern" id="btn_import" onclick="distribution()">
            <span class="btn-pulse" aria-hidden="true"></span>
            <span class="btn-icon"><i class="fas fa-box-open"></i></span>
            <span class="btn-copy">
                <small>Bắt đầu tác vụ</small>
                <span class="btn-text">{{__('distribution.NhanDonHang')}}</span>
            </span>
            <i class="fas fa-arrow-right btn-arrow"></i>
        </button>

        @if($user_rank)
            <div class="progress-card-modern">
                <div class="progress-route" aria-hidden="true">
                    <span class="route-start"></span>
                    <div class="progress-bar-container">
                        <div class="progress-bar-modern" id="progress-bar" style="width: {{ $percentage }}%">
                            <span class="route-package"><i class="fas fa-box"></i></span>
                        </div>
                    </div>
                    <span class="route-finish"><i class="fas fa-flag-checkered"></i></span>
                </div>
                <div class="progress-meta">
                    <div>
                        <span class="progress-label">Lộ trình hôm nay</span>
                        <span class="progress-text" id="progress-text">Còn lại {{ $remaining }} đơn hàng • {{ $percentage }}% hoàn thành</span>
                    </div>
                    <div class="progress-numbers">
                        <span class="current-number" id="progress-current">{{ $current_order }}</span>
                        <span class="separator">/</span>
                        <span class="total-number" id="progress-total">{{ $total_orders }}</span>
                    </div>
                </div>
            </div>
        @else
            <div class="rank-notice">
                <i class="fas fa-lock"></i>
                <span>Nâng cấp cấp độ thành viên để mở lộ trình phân phối.</span>
            </div>
        @endif
    </section>

    <section class="distribution-ledger" aria-labelledby="ledger-title">
        <div class="section-heading">
            <div>
                <span class="section-kicker">Tổng quan trực tiếp</span>
                <h2 id="ledger-title">Dòng tiền hôm nay</h2>
            </div>
            <span class="ledger-date">{{ now()->format('d/m') }}</span>
        </div>

        <div class="balance-card stat-card">
            <div class="metric-icon"><i class="fas fa-wallet"></i></div>
            <div class="metric-copy">
                <h3 class="stat-label">{{__('distribution.TongSoDu')}}</h3>
                <p class="stat-value">${{format_money($user->balance)}}</p>
            </div>
            <span class="metric-note">Khả dụng</span>
        </div>

        <div class="ledger-strip">
            <article class="distribution-card stat-card">
                <span class="metric-index">01</span>
                <h3 class="stat-label">{{__('distribution.PhanPhoiHomNay')}}</h3>
                <p class="stat-value">+{{ $user->distribution_today ?? 0 }}</p>
                <span class="metric-unit">đơn hàng</span>
            </article>
            <article class="commission-card stat-card">
                <span class="metric-index">02</span>
                <h3 class="stat-label">{{__('distribution.HoaHongDuTinhHomNay')}}</h3>
                <p class="stat-value">${{format_money($todays_discount, 5)}}</p>
                <span class="metric-unit">dự tính</span>
            </article>
            <article class="refund-card stat-card">
                <span class="metric-index">03</span>
                <h3 class="stat-label">Hoàn nhập dự tính</h3>
                <p class="stat-value">${{format_money($todays_expected_refund, 5)}}</p>
                <span class="metric-unit">hôm nay</span>
            </article>
            <article class="commission-added-card stat-card">
                <span class="metric-index">04</span>
                <h3 class="stat-label">Hoa hồng đã cộng</h3>
                <p class="stat-value">${{format_money($today_commission_added ?? 0, 5)}}</p>
                <span class="metric-unit">đã ghi nhận</span>
            </article>
        </div>

        <div class="frozen-card stat-card">
            <div class="frozen-symbol"><i class="fas fa-snowflake"></i></div>
            <div class="metric-copy">
                <h3 class="stat-label">{{__('distribution.SoDuDongBang')}}</h3>
                <p class="stat-value">${{format_money($frozen_price ?? 0)}}</p>
                <span class="metric-unit">Tách biệt khỏi số dư khả dụng</span>
            </div>
            @if($frozen_price > 0)
                <span class="frozen-state"><i class="fas fa-lock"></i> Đang khóa</span>
            @else
                <span class="frozen-state"><i class="fas fa-lock-open"></i> Không có</span>
            @endif
        </div>
    </section>

    <section class="description-section">
        <div class="description-marker"><span>GUIDE</span></div>
        <div class="description-card">
            <div class="description-header">
                <span class="description-icon"><i class="fas fa-route"></i></span>
                <div><small>Thông tin vận hành</small><h2>{{__('distribution.MoTa')}}</h2></div>
            </div>
            <div class="description-content">
                {!! $section_mo_ta?->getTranslatedContent() ?? __('distribution.DangCapNhat') !!}
            </div>
        </div>
    </section>
</main>
    
        <div class="dark_surface" id="order_award" hidden>
            <div class="order-modal-modern" id="order">
                <!-- Header thường (ẩn khi là đơn hàng giá trị cao) -->
                <div class="order-header-normal">
                    <div class="normal-badge">
                        <i class="fas fa-box"></i>
                        <span>ĐƠN HÀNG MỚI</span>
                    </div>
                    <h2 class="normal-title">Đơn hàng phân phối</h2>
                    <p class="normal-message">Bạn có đơn hàng mới cần xử lý</p>
                </div>

                <!-- Header đặc biệt (ẩn khi là đơn thường) -->
                <div class="order-header-hvo" style="display: none;">
                    <div class="celebration-badge">
                        <i class="fas fa-gift"></i>
                        <span>ĐƠN HÀNG THƯỞNG</span>
                        <i class="fas fa-gift"></i>
                    </div>
                    <div class="congratulations-text">
                        <i class="fas fa-star"></i>
                        <h2>Chúc mừng!</h2>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="celebration-message">Bạn đã nhận được đơn hàng giá trị cao</p>
                </div>

                <!-- Main Content -->
                <div class="order-content-modern">
                    <!-- Status Badge -->
                    <div class="status-badge-wrapper">
                        <div class="status-badge pending">
                            <i class="fas fa-clock-rotate-left"></i>
                            <span>Chờ xử lý</span>
                        </div>
                        <span class="order-time" id="order_details_time">{{__('order.ThoiGianDatPhanPhoi')}}</span>
                    </div>

                    <!-- Product Card -->
                    <div class="product-card-modern">
                        <div class="product-image-wrapper">
                            <div class="image-shine"></div>
                            <img id="order_details_img" src="{{ asset('images/orders/syglp5via6r7rxqjc1k8.jpg') }}" alt="" class="product-image">
                            <div class="hvo-tag">
                                <i class="fas fa-crown"></i>
                                HVO
                            </div>
                        </div>
                        <div class="product-info-modern">
                            <h3 class="product-name" id="order_details_name">Apple iPhone 14 Pro Max</h3>
                            <div class="product-price-row">
                                <div class="price-info">
                                    <span class="price-label">Giá:</span>
                                    <span class="price-value" id="order_details_price">10.000$</span>
                                </div>
                                <div class="quantity-info">
                                    <span class="quantity-label">SL:</span>
                                    <span class="quantity-value" id="order_details_quantity">x1</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Card -->
                    <div class="summary-card-modern">
                        <div class="summary-row">
                            <span class="summary-label">
                                <i class="fas fa-money-bill-wave"></i>
                                {{__('order.TongTienDonHang')}}
                            </span>
                            <span class="summary-value" id="order_details_end_value_total_price">10.000$</span>
                        </div>
                        <div class="summary-row highlight">
                            <span class="summary-label">
                                <i class="fas fa-percentage"></i>
                                {{__('order.HoaHong')}}
                            </span>
                            <span class="summary-value profit" id="order_details_end_value_price_rose">+20$</span>
                        </div>
                        <div class="summary-row" id="bonus_hvo_row" style="display: none; background: linear-gradient(135deg, #fff9e6 0%, #ffe8a1 100%); padding: 12px; border-radius: 8px; border: 2px solid #ffd700; margin: 8px 0;">
                            <span class="summary-label" style="color: #d4a100; font-weight: 600;">
                                <i class="fas fa-gift" style="color: #ff6b6b;"></i>
                                Thưởng đơn hàng giá trị cao (10%)
                            </span>
                            <span class="summary-value" style="color: #d4a100; font-weight: 700;">
                                <i class="fas fa-star" style="color: #ffd700; font-size: 0.9em;"></i>
                                Hệ thống cộng thủ công
                            </span>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-row total">
                            <span class="summary-label-total">
                                <i class="fas fa-wallet"></i>
                                {{__('order.SoTienHoanNhap')}}
                            </span>
                            <span class="summary-value-total" id="order_details_end_value_total">10.020$</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="order-actions-modern">
                    <button class="btn-later-modern" id="later">
                        <i class="fas fa-clock"></i>
                        <span>{{__('order.DeSau')}}</span>
                    </button>
                    <button class="btn-process-modern" id="btn_phan_phoi_ngay">
                        <span class="btn-shine-effect"></span>
                        <i class="fas fa-bolt"></i>
                        <span>Xử lý luôn</span>
                    </button>
                </div>
            </div>
        </div>
    

<!-- Searching Modal -->
<div class="loading-modal-overlay" id="searchingModalOverlay">
    <div class="loading-modal-container">
        <h2 class="loading-title">Đang tìm kiếm đơn hàng</h2>
        <p class="loading-subtitle">Hệ thống đang tìm kiếm đơn hàng phù hợp...</p>
        
        <div class="loading-steps">
            <div class="loading-step active" id="search-step-1">
                <div class="step-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="step-content">
                    <div class="step-label">Kết nối hệ thống</div>
                    <div class="step-description">Đang kết nối...</div>
                </div>
            </div>
            
            <div class="loading-step" id="search-step-2">
                <div class="step-icon">
                    <i class="fas fa-database"></i>
                </div>
                <div class="step-content">
                    <div class="step-label">Tìm kiếm đơn hàng</div>
                    <div class="step-description">Đang tìm...</div>
                </div>
            </div>
            
            <div class="loading-step" id="search-step-3">
                <div class="step-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="step-content">
                    <div class="step-label">Hoàn tất</div>
                    <div class="step-description">Đã tìm thấy</div>
                </div>
            </div>
        </div>
        
        <div class="loading-progress">
            <div class="loading-progress-bar" id="search-progress-bar"></div>
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
                    <div class="detail-row" id="success_bonus_row" style="display: none; background: linear-gradient(135deg, #fff9e6 0%, #ffe8a1 100%); padding: 12px; border-radius: 8px; border: 2px solid #ffd700; margin: 8px 0;">
                        <span class="detail-label" style="color: #d4a100; font-weight: 600;">
                            <i class="fas fa-gift" style="color: #ff6b6b;"></i>
                            Thưởng đơn hàng giá trị cao (10%)
                        </span>
                        <span class="detail-value" style="color: #d4a100; font-weight: 700; font-size: 1.1em;">
                            <i class="fas fa-star" style="color: #ffd700; font-size: 0.8em;"></i>
                            Sẽ được cộng thủ công
                        </span>
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

@endsection
