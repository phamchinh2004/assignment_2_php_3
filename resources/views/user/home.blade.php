@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/home.css')
@vite('resources/css/user/lucky-wheel.css')
@endsection
@section('script-libs')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
    const trans = {
        justNow: @json(__('home.VuaXong')),
        secondsAgo: @json(__('home.GiayTruoc')),
        minutesAgo: @json(__('home.PhutTruoc')),
        heThongDangQuaTai: @json(__('home.HeThongDangQuaTai')),
        vuiLongLienHeCskhDeNapTien: @json(__('home.VuiLongLienHeCskhDeNapTien')),
        MatKhauXacNhanKhongKhop: @json(__('home.MatKhauXacNhanKhongKhop')),
        SoTaiKhoanPhaiLaSo: @json(__('home.SoTaiKhoanPhaiLaSo')),
        successText: @json(__('home.successText')),
    };
</script>
@vite('resources/js/user/home.js')
@vite('resources/js/user/lucky-wheel.js')
@endsection

@section('content')
<div id="fireworks-container"></div>

<div>
    <!-- Thông báo -->
    <div class="w-100 noti-top bg-white position-absolute d-flex align-items-center ps-2 pe-2">
        @php
        $content = null;
        @endphp

        @if (!empty($list_sections))
        @foreach ($list_sections as $item)
        @if ($item->code === 'chu_chay_tren_dau_trang_web')
        @php
        $content = $item->getTranslatedContent();
        break;
        @endphp
        @endif
        @endforeach
        @endif

        <marquee class="text-center text-nowrap p-1">
            {!! strip_tags(str_replace(['<div>', '</div>', '<p>', '</p>'], '&nbsp;', $content)) ?? 'Đang cập nhật...' !!}
        </marquee>

    </div>
    <!-- Các nút - Amazon Theme -->
    <div class="w-100 ps-4 pe-4 section-1 d-flex align-items-center justify-content-between">

        <div class="w-25 position-relative d-flex align-items-center justify-content-center flex-column cspt" id="btn_phan_phoi">
            <div class="position-absolute item-section-1">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/logo_4.png') }}" alt="">
            </div>
            <div class="display">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/display.png') }}" alt="">
            </div>
            <span class="text-white tittle-section-1">{{__('home.PhanPhoi')}}</span>
        </div>
        <div class="w-25 position-relative d-flex align-items-center justify-content-center flex-column cspt" id="btn_bien_dong_so_du">
            <div class="position-absolute item-section-1">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/logo_1.png') }}" alt="">
            </div>
            <div class="display">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/display.png') }}" alt="">
            </div>
            <span class="text-white tittle-section-1">{{__('home.BienDongSoDu')}}</span>
        </div>
        <div class="w-25 position-relative d-flex align-items-center justify-content-center flex-column cspt" id="btn_nap_tien">
            <div class="position-absolute item-section-1">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/logo_2.png') }}" alt="">
            </div>
            <div class="display">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/display.png') }}" alt="">
            </div>
            <span class="text-white tittle-section-1">{{__('home.NapTien')}}</span>
        </div>
        <div class="w-25 position-relative d-flex align-items-center justify-content-center flex-column cspt" id="btn_rut_tien">
            <div class="position-absolute item-section-1">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/logo_3.png') }}" alt="">
            </div>
            <div class="display">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/display.png') }}" alt="">
            </div>
            <span class="text-white tittle-section-1">{{__('home.RutTien')}}</span>
        </div>

    </div>
    <!-- Banner -->
    <div id="carouselExampleAutoplaying" class="carousel slide mt-4" data-bs-ride="carousel">
        <div class="carousel-inner">
            @if (!empty($get_banner))
            @foreach ($get_banner->banner_images as $key=> $item)
            <div class="carousel-item {{$key==0?'active':''}}">
                <img class="banner-image" src="{{ Storage::url($item->path) }}" class="d-block w-100" alt="...">
            </div>
            @endforeach
            @else
            <div class="carousel-item active">
                <img class="banner-image" src="{{ asset('images/home/banner_1.webp') }}" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
                <img class="banner-image" src="{{ asset('images/home/banner_2.webp') }}" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
                <img class="banner-image" src="{{ asset('images/home/banner_3.webp') }}" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
                <img class="banner-image" src="{{ asset('images/home/banner_4.webp') }}" class="d-block w-100" alt="...">
            </div>
            @endif
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
    <!-- Vòng quay may mắn - Giải thưởng -->
    <div class="section-3" id="section-3">
        <div class="position-relative text-center">
            <div class="amazon-banner">
                <span class="text-white fw-bold text-title">🎁 {{__('home.VongQuayMayMan')}}</span>
            </div>
        </div>
        
        <!-- Kiểm tra điều kiện quay -->
        @php
            $can_spin = false;
            $spin_message = '';
            
            // Kiểm tra đã quay hôm nay chưa
            if ($has_spun_today) {
                $spin_message = 'Bạn đã quay vòng quay hôm nay rồi. Hãy quay lại vào ngày mai!';
            } elseif ($user_spin_progress && $rank) {
                $current = $user_spin_progress->current_spin ?? 0;
                $total = $rank->spin_count ?? 0;
                if ($current >= $total && $total > 0) {
                    $can_spin = true;
                    $spin_message = 'Bạn đã hoàn thành ' . $total . ' đơn hàng! Hãy quay thử vận may!';
                } else {
                    $remaining = $total - $current;
                    $spin_message = 'Hoàn thành thêm ' . $remaining . ' đơn hàng nữa để được quay!';
                }
            } else {
                $spin_message = 'Bạn cần có cấp độ để tham gia quay thưởng!';
            }
        @endphp
        
        <div class="wheel-container-modern">
            <div class="wheel-status-card">
                <i class="fas {{ $can_spin ? 'fa-check-circle text-success' : 'fa-lock text-warning' }}"></i>
                <p class="wheel-status-message">{{ $spin_message }}</p>
            </div>
            
            <div class="wheel-wrapper">
                <div class="wheel-pointer">
                    <i class="fas fa-caret-down"></i>
                </div>
                
                <div class="prize-wheel" id="prizeWheel">
                    <div class="wheel-slice slice-1" data-prize="SH Mode">
                        <div class="slice-content">
                            <i class="fas fa-motorcycle"></i>
                            <span>SH Mode</span>
                        </div>
                    </div>
                    <div class="wheel-slice slice-2" data-prize="$2">
                        <div class="slice-content">
                            <i class="fas fa-dollar-sign"></i>
                            <span>$2</span>
                        </div>
                    </div>
                    <div class="wheel-slice slice-3" data-prize="Chúc bạn may mắn lần sau">
                        <div class="slice-content">
                            <i class="fas fa-gem"></i>
                            <span>Chúc bạn may mắn lần sau</span>
                        </div>
                    </div>
                    <div class="wheel-slice slice-4" data-prize="$10">
                        <div class="slice-content">
                            <i class="fas fa-dollar-sign"></i>
                            <span>$10</span>
                        </div>
                    </div>
                    <div class="wheel-slice slice-5" data-prize="$2">
                        <div class="slice-content">
                            <i class="fas fa-dollar-sign"></i>
                            <span>$2</span>
                        </div>
                    </div>
                    <div class="wheel-slice slice-6" data-prize="$5">
                        <div class="slice-content">
                            <i class="fas fa-dollar-sign"></i>
                            <span>$5</span>
                        </div>
                    </div>
                    <div class="wheel-slice slice-7" data-prize="Chúc bạn may mắn lần sau">
                        <div class="slice-content">
                            <i class="fas fa-gem"></i>
                            <span>Chúc bạn may mắn lần sau</span>
                        </div>
                    </div>
                    <div class="wheel-slice slice-8" data-prize="$2">
                        <div class="slice-content">
                            <i class="fas fa-dollar-sign"></i>
                            <span>$2</span>
                        </div>
                    </div>
                </div>
                
                <button class="wheel-spin-button" id="wheelSpinButton" {{ !$can_spin ? 'disabled' : '' }}>
                    <span class="spin-text">QUAY</span>
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            @if ($user_spin_progress && $rank)
            <div class="wheel-progress-info">
                <div class="progress-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Đã hoàn thành: <strong>{{ $user_spin_progress->current_spin }}/{{ $rank->spin_count }}</strong></span>
                </div>
            </div>
            @endif
        </div>
        
        <audio id="wheelSpinSound" src="{{asset('audio/wheel.mp3')}}" preload="auto"></audio>
        <audio id="applauseSound" src="{{asset('audio/applause.mp3')}}" preload="auto"></audio>
    </div>
    
    <!-- Modal giải thưởng -->
    <div class="prize-modal-overlay" id="prizeModalOverlay">
        <div class="prize-modal-container">
            <div class="prize-modal-content">
                <div class="prize-confetti" id="prizeConfetti"></div>
                
                <div class="prize-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                
                <h2 class="prize-title">Chúc Mừng!</h2>
                <p class="prize-subtitle">Bạn đã trúng giải:</p>
                
                <div class="prize-name-display" id="prizeNameDisplay">
                    <i class="prize-icon-display" id="prizeIconDisplay"></i>
                    <span class="prize-text-display" id="prizeTextDisplay">Đang tải...</span>
                </div>
                
                <div class="prize-message">
                    Vui lòng liên hệ CSKH để nhận thưởng!
                </div>
                
                <button class="prize-close-button" onclick="closePrizeModal()">
                    <span>Đóng</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Tập đoàn amazon - Amazon Theme -->
<div class="section-4">
    <div class="position-relative text-center">
        <div class="amazon-banner">
            <span class="text-white fw-bold text-title">🏢 {{__('home.TapDoanAmazon')}}</span>
        </div>
    </div>
    <div class="section-4-box-content">
        <div class="section-4-content d-flex flex-column" id="view_amazon">
            <img class="section-4-amazon-image" src="{{ asset('images/home/section-4.1.webp') }}" alt="">
            <span class="fw-bold">AMAZON</span>
        </div>
        <div id="amazon_content">
            <div class="d-flex justify-content-end">
                <div id="close_xmark_amazon">
                    <i class="fa fa-solid fa-xmark fa-xl"></i>
                </div>
            </div>
            <div class="amazon-title">
                <h3 class="fw-bold text-center">{{__('home.GioiThieuNenTang')}}</h3>
            </div>
            <div class="amazon-detail-content">
                @if (!empty($list_sections))
                @foreach ($list_sections as $item)
                @if ($item->code === 'gioi_thieu_nen_tang')
                @php
                $content = $item->getTranslatedContent();
                break;
                @endphp
                @endif
                @endforeach
                @endif
                {!! $content?? __('home.DangCapNhat')!!}
            </div>
        </div>
        <div class="section-4-content d-flex flex-column" id="view_mo_ta">
            <img src="{{ asset('images/home/section-4.2.webp') }}" alt="">
            <span class="fw-bold">{{__('home.MoTa')}}</span>
        </div>
        <div id="mo_ta_content">
            <div class="d-flex justify-content-end">
                <div id="close_xmark_mo_ta">
                    <i class="fa fa-solid fa-xmark fa-xl"></i>
                </div>
            </div>
            <div class="amazon-title">
                <h3 class="fw-bold text-center">{{__('home.QuyTacLayDon')}}</h3>
            </div>
            <div class="amazon-detail-content">
                @if (!empty($list_sections))
                @foreach ($list_sections as $item)
                @if ($item->code === 'quy_tac_lay_don')
                @php
                $content = $item->getTranslatedContent();
                break;
                @endphp
                @endif
                @endforeach
                @endif
                {!! $content?? __('home.DangCapNhat')!!}
            </div>
        </div>
        <div class="section-4-content d-flex flex-column" id="view_tai_chinh">
            <img src="{{ asset(path: 'images/home/section-4.3.webp') }}" alt="">
            <span class="fw-bold">{{__('home.TaiChinh')}}</span>
        </div>
        <div id="tai_chinh_content">
            <div class="d-flex justify-content-end">
                <div id="close_xmark_tai_chinh">
                    <i class="fa fa-solid fa-xmark fa-xl"></i>
                </div>
            </div>
            <div class="amazon-title">
                <h3 class="fw-bold text-center">{{__('home.HopTacDaiLy')}}</h3>
            </div>
            <div class="amazon-detail-content">
                @if (!empty($list_sections))
                @foreach ($list_sections as $item)
                @if ($item->code === 'hop_tac_dai_ly')
                @php
                $content = $item->getTranslatedContent();
                break;
                @endphp
                @endif
                @endforeach
                @endif
                {!! $content?? __('home.DangCapNhat')!!}
            </div>
        </div>
        <div class="section-4-content d-flex flex-column" id="view_quy_dinh">
            <img src="{{ asset(path: 'images/home/section-4.4.webp') }}" alt="">
            <span class="fw-bold">{{__('home.QuyDinh')}}</span>
        </div>
        <div id="quy_dinh_content">
            <div class="d-flex justify-content-end">
                <div id="close_xmark_quy_dinh">
                    <i class="fa fa-solid fa-xmark fa-xl"></i>
                </div>
            </div>
            <div class="amazon-title">
                <h3 class="fw-bold text-center">{{__('home.QuyDinhCongTy')}}</h3>
            </div>
            <div class="amazon-detail-content">
                @if (!empty($list_sections))
                @foreach ($list_sections as $item)
                @if ($item->code === 'quy_dinh_cong_ty')
                @php
                $content = $item->getTranslatedContent();
                break;
                @endphp
                @endif
                @endforeach
                @endif
                {!! $content?? __('home.DangCapNhat')!!}
            </div>
        </div>
    </div>
</div>
<!-- Thống kê tổng quan hệ thống -->
<div class="section-stats">
    <div class="position-relative text-center">
        <div class="amazon-banner">
            <span class="text-white fw-bold text-title">📊 Thống kê hệ thống</span>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-number">50,000+</div>
            <div class="stat-label">Thành viên</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-number">$2M+</div>
            <div class="stat-label">Tổng giao dịch</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-number">100K+</div>
            <div class="stat-label">Đơn hàng đã phân phối</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-number">4.9/5</div>
            <div class="stat-label">Đánh giá</div>
        </div>
    </div>
</div>

<!-- Chứng nhận và bảo mật -->
<div class="section-certificates">
    <div class="position-relative text-center">
        <div class="amazon-banner">
            <span class="text-white fw-bold text-title">🛡️ Bảo mật & Chứng nhận</span>
        </div>
    </div>
    <div class="certificates-grid">
        <div class="cert-item">
            <div class="cert-icon ssl-icon">
                <i class="fas fa-lock"></i>
            </div>
            <div class="cert-info">
                <h4>SSL 256-bit</h4>
                <p>Mã hóa bảo mật</p>
            </div>
        </div>
        <div class="cert-item">
            <div class="cert-icon pci-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="cert-info">
                <h4>PCI DSS</h4>
                <p>Bảo mật thanh toán</p>
            </div>
        </div>
        <div class="cert-item">
            <div class="cert-icon iso-icon">
                <i class="fas fa-certificate"></i>
            </div>
            <div class="cert-info">
                <h4>ISO 27001</h4>
                <p>Quản lý bảo mật</p>
            </div>
        </div>
    </div>
</div>

<!-- Lịch sử hoạt động và thành tựu -->
<div class="section-timeline">
    <div class="position-relative text-center">
        <div class="amazon-banner">
            <span class="text-white fw-bold text-title">🏆 Hành trình phát triển</span>
        </div>
    </div>
    <div class="timeline">
        <div class="timeline-item">
            <div class="timeline-year">2020</div>
            <div class="timeline-content">Thành lập công ty và ra mắt nền tảng phân phối đơn hàng</div>
        </div>
        <div class="timeline-item">
            <div class="timeline-year">2021</div>
            <div class="timeline-content">Đạt 10,000 thành viên đầu tiên và mở rộng hệ thống gian hàng</div>
        </div>
        <div class="timeline-item">
            <div class="timeline-year">2022</div>
            <div class="timeline-content">Mở rộng sang thị trường quốc tế và tích hợp thanh toán đa dạng</div>
        </div>
        <div class="timeline-item">
            <div class="timeline-year">2023</div>
            <div class="timeline-content">Đạt mốc $1M tổng giao dịch và ra mắt hệ thống VIP</div>
        </div>
        <div class="timeline-item">
            <div class="timeline-year">2024</div>
            <div class="timeline-content">Tiếp tục phát triển và nâng cấp hệ thống bảo mật</div>
        </div>
    </div>
</div>

<!-- Đánh giá từ khách hàng -->
<div class="section-testimonials">
    <div class="position-relative text-center">
        <div class="amazon-banner">
            <span class="text-white fw-bold text-title">💬 Đánh giá từ khách hàng</span>
        </div>
    </div>
    <div class="testimonials-section">
        <div class="testimonials-wrapper">
            <div class="testimonial-slide active">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "Mk kiếm cũng được kha khá tiền ở đây, nhưng hệ thống cho ít đơn thưởng quá săn mãi mới dc 1 đơn hicc"
                    </div>
                    <div class="testimonial-author">
                        <img src="{{ asset('images/avatars/1.jpg') }}" alt="User">
                        <div class="author-info">
                            <div class="author-name">Hà Phạm Thị</div>
                            <div class="author-rank">VIP Gold</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-slide">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "Làm được gần 1 năm thấy cũng ổn, ae làm mà nhận dc đơn thưởng thì bú luôn đi ko là ko đủ sống đâu, chịu khó đầu tư 1 tí"
                    </div>
                    <div class="testimonial-author">
                        <img src="{{ asset('images/avatars/2.jpg') }}" alt="User">
                        <div class="author-info">
                            <div class="author-name">Nguyen Vu</div>
                            <div class="author-rank">VIP Platinum</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-slide">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "Đội ngũ hỗ trợ chuyên nghiệp, giải quyết vấn đề nhanh chóng. Tôi tin tưởng và sẽ tiếp tục sử dụng dịch vụ lâu dài."
                    </div>
                    <div class="testimonial-author">
                        <img src="{{ asset('images/avatars/3.jpg') }}" alt="User">
                        <div class="author-info">
                            <div class="author-name">Phanhh Nguyễn</div>
                            <div class="author-rank">VIP Diamond</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-slide">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "Hệ thống phân phối rất minh bạch và hiệu quả. Tôi đã kiếm được thu nhập ổn định từ đây. Giao diện dễ sử dụng và hỗ trợ khách hàng rất tốt."
                    </div>
                    <div class="testimonial-author">
                        <img src="{{ asset('images/avatars/4.jpg') }}" alt="User">
                        <div class="author-info">
                            <div class="author-name">Trần Minh Tuấn</div>
                            <div class="author-rank">VIP Gold</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-slide">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "Tôi đã tham gia từ năm 2021 và rất hài lòng với dịch vụ. Hệ thống gian hàng phân cấp giúp tôi có thu nhập tăng dần theo thời gian."
                    </div>
                    <div class="testimonial-author">
                        <img src="{{ asset('images/avatars/5.jpg') }}" alt="User">
                        <div class="author-info">
                            <div class="author-name">Lê Thị Mai</div>
                            <div class="author-rank">VIP Platinum</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-slide">
                <div class="testimonial-card">
                    <div class="testimonial-text">
                        "Rất hài lòng với dịch vụ! Hệ thống hoạt động ổn định, không có lỗi gì. Thu nhập hàng tháng đều đặn, đúng như cam kết."
                    </div>
                    <div class="testimonial-author">
                        <img src="{{ asset('images/avatars/6.jpg') }}" alt="User">
                        <div class="author-info">
                            <div class="author-name">Nguyễn Văn Đức</div>
                            <div class="author-rank">VIP Diamond</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="testimonial-controls">
            <button class="testimonial-btn prev-btn" onclick="showPrevTestimonial()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="testimonial-btn next-btn" onclick="showNextTestimonial()">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <div class="testimonial-dots">
            <span class="dot active" onclick="showTestimonial(0)"></span>
            <span class="dot" onclick="showTestimonial(1)"></span>
            <span class="dot" onclick="showTestimonial(2)"></span>
            <span class="dot" onclick="showTestimonial(3)"></span>
            <span class="dot" onclick="showTestimonial(4)"></span>
            <span class="dot" onclick="showTestimonial(5)"></span>
        </div>
    </div>
</div>


<!-- Thành viên Amazon - Amazon Theme -->
<div class="section-5">
    <div class="position-relative text-center">
        <div class="amazon-banner">
            <span class="text-white fw-bold text-title">👥 {{__('home.ThanhVienAmazon')}}</span>
        </div>
    </div>
    <div class="ranks-comparison">
        <div class="comparison-header">
            <h3><i class="fas fa-chart-bar me-2"></i>So sánh các gian hàng</h3>
            <p class="comparison-subtitle">Chọn gian hàng phù hợp với nhu cầu của bạn</p>
        </div>
        <div class="comparison-table">
            <div class="table-header">
                <div class="col-rank">
                    <i class="fas fa-crown me-1"></i>Gian hàng
                </div>
                <div class="col-members">
                    <i class="fas fa-users me-1"></i>Thành viên
                </div>
                <div class="col-fee">
                    <i class="fas fa-dollar-sign me-1"></i>Phí nâng cấp
                </div>
                <div class="col-commission">
                    <i class="fas fa-percentage me-1"></i>Chiết khấu
                </div>
                <div class="col-spins">
                    <i class="fas fa-sync-alt me-1"></i>Lượt phân phối
                </div>
                <div class="col-value">
                    <i class="fas fa-gem me-1"></i>Giá trị
                </div>
            </div>
            @if (!empty($list_ranks_with_member_count))
            @foreach($list_ranks_with_member_count as $index => $item)
            <div class="table-row {{$index % 2 == 0 ? 'even' : 'odd'}}">
                <div class="col-rank">
                    <div class="rank-badge rank-badge-{{$index + 1}}">
                        <div class="rank-icon">
                            @if($index == 0)
                                <i class="fas fa-gem"></i>
                            @elseif($index == 1)
                                <i class="fas fa-crown"></i>
                            @elseif($index == 2)
                                <i class="fas fa-trophy"></i>
                            @else
                                <i class="fas fa-star"></i>
                            @endif
                        </div>
                        <div class="rank-name">{{$item->name}}</div>
                    </div>
                </div>
                <div class="col-members">
                    <div class="member-count">
                        <span class="count-number">{{$item->user_count}}</span>
                        <span class="count-label">thành viên</span>
                    </div>
                </div>
                <div class="col-fee">
                    <div class="fee-amount">
                        <span class="currency">$</span>
                        <span class="amount">{{number_format($item->upgrade_fee)}}</span>
                    </div>
                </div>
                <div class="col-commission">
                    <div class="commission-rate">
                        <span class="rate">{{$item->commission_percentage}}</span>
                        <span class="percent">%</span>
                    </div>
                </div>
                <div class="col-spins">
                    <div class="spin-count">
                        <span class="count">{{$item->spin_count}}</span>
                        <span class="label">lượt/ngày</span>
                    </div>
                </div>
                <div class="col-value">
                    <div class="value-amount">
                        <span class="currency">$</span>
                        <span class="amount">{{number_format($item->value)}}</span>
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>
<!-- Minh bạch thông tin -->
<div class="section-transparency">
    <div class="position-relative text-center">
        <div class="amazon-banner">
            <span class="text-white fw-bold text-title">🔍 Minh bạch thông tin</span>
        </div>
    </div>
    <div class="transparency-grid">
        <div class="transparency-item">
            <div class="transparency-icon">📋</div>
            <div class="transparency-title">Báo cáo tài chính</div>
            <div class="transparency-desc">Công khai báo cáo hàng tháng về tổng giao dịch và phân phối lợi nhuận</div>
        </div>
        <div class="transparency-item">
            <div class="transparency-icon">⚖️</div>
            <div class="transparency-title">Quy định pháp lý</div>
            <div class="transparency-desc">Tuân thủ đầy đủ các quy định về kinh doanh và bảo vệ người tiêu dùng</div>
        </div>
        <div class="transparency-item">
            <div class="transparency-icon">🔒</div>
            <div class="transparency-title">Bảo vệ dữ liệu</div>
            <div class="transparency-desc">Cam kết bảo mật thông tin cá nhân và tài chính của khách hàng</div>
        </div>
    </div>
</div>

<div class="section-6">
    <div class="position-relative">
        <span class="text-white fw-bold section-6-title badge bg-warning">📖 {{__('home.GioiThieu')}}</span>
    </div>
    <div class="section-6-box-content bg-white">
        <div class="operation-info mb-3">
            <div class="info-item">
                <i class="fas fa-clock text-warning me-2"></i>
                <span>Thời gian hoạt động: 24/7</span>
            </div>
            <div class="info-item">
                <i class="fas fa-shield-alt text-success me-2"></i>
                <span>Bảo mật: SSL 256-bit</span>
            </div>
            <div class="info-item">
                <i class="fas fa-headset text-info me-2"></i>
                <span>Hỗ trợ: 24/7</span>
            </div>
        </div>
        <p class="text-secondary">
            @if (!empty($list_sections))
            @foreach ($list_sections as $item)
            @if ($item->code === 'tieu_de_lon_gioi_thieu_o_trang_chu')
            @php
            $content = $item->getTranslatedContent();
            break;
            @endphp
            @endif
            @endforeach
            @endif
            {!! $content?? __('home.DangCapNhat')!!}
        </p>
    </div>
</div>
<div class="section-7">
    <div class="position-relative text-center">
        <div class="amazon-banner">
            <span class="text-white fw-bold text-title">🌟 {{__('home.CacThanhVienKhac')}}</span>
        </div>
    </div>
    <div id="distribution-list" class="text-white"></div>
</div>
<!-- Đối tác - Amazon Theme -->
<div class="section-8">
    <div class="position-relative text-center">
        <div class="amazon-banner">
            <span class="text-white fw-bold text-title">🤝 {{__('home.CacDoiTac')}}</span>
        </div>
    </div>
    
    <!-- Desktop Table View -->
    <table class="table mt-2 table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th class="text-center">🏢 {{__('home.TenDoiTac')}}</th>
                <th class="text-center">🖼️ {{__('home.HinhAnh')}}</th>
                <th class="text-center">🔗 {{__('home.LinkTrangWeb')}}</th>
            </tr>
        </thead>
        <tbody>
            @if (!empty($list_partners))
            @foreach ($list_partners as $index=> $item)
            <tr>
                <td class="text-center">
                    <span class="badge badge-primary" style="background: linear-gradient(45deg, #FF9500, #FF8C00); color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold;">{{$index+1}}</span>
                </td>
                <td class="text-center fw-bold">{{$item->name}}</td>
                <td class="text-center">
                    <div class="p-1 d-flex justify-content-center align-items-center">
                        <img class="image-doi-tac" src="{{ Storage::url($item->image) }}" alt="{{$item->name}}">
                    </div>
                </td>
                <td class="text-center"><a class="btn btn-sm btn-warning link-doi-tac" href="{{$item->link}}" target="_blank">{{__('home.XemTrangWeb')}}</a></td>
            </tr>
            @endforeach
            @endif
        </tbody>
    </table>
    
    <!-- Mobile Card View -->
    <div class="partners-mobile-grid">
        @if (!empty($list_partners))
        @foreach ($list_partners as $index=> $item)
        <div class="partner-card">
            <div class="partner-number">{{$index+1}}</div>
            <div class="partner-name">{{$item->name}}</div>
            <div class="partner-image-container">
                <img class="partner-image" src="{{ Storage::url($item->image) }}" alt="{{$item->name}}">
            </div>
            <a class="partner-link" href="{{$item->link}}" target="_blank">{{__('home.XemTrangWeb')}}</a>
        </div>
        @endforeach
        @endif
    </div>
</div>
<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content notification-board">
            <div class="modal-header notification-header">
                <div class="header-icon">🎊</div>
                <h1 class="modal-title notification-title" id="notificationModalLabel">Chào Mừng Năm Mới 2026</h1>
                <button type="button" class="btn-close btn-close-white" onclick="closeNotification()" aria-label="Close"></button>
            </div>
            <div class="modal-body notification-body">
                <div class="notification-content">
                    <div class="content-item">
                        <div class="item-icon">🎁</div>
                        <div class="item-content">
                            <h3>Ưu đãi cho khách hàng mới</h3>
                            <p>Hệ thống Amazon đang tri ân khách hàng mới với phần thưởng lớn khi đăng ký tài khoản và tham gia gian hàng lần đầu.</p>
                        </div>
                    </div>

                    <div class="special-announcement">
                        <div class="announcement-badge">Sự Kiện Đặc Biệt</div>
                        <h2>Sự kiện chào mừng năm mới 2026</h2>
                        <p>Tham gia ngay để có cơ hội nhận thưởng <span class="reward-highlight">lên tới hàng triệu USD</span> cùng nhiều phần quà năm mới hấp dẫn!</p>
                        <p class="announcement-note">📅 Chương trình chỉ diễn ra trong dịp năm mới 2026 này, hãy nhanh tay tham gia để không bỏ lỡ cơ hội vàng.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer notification-footer">
                <label class="checkbox-wrapper">
                    <input type="checkbox" id="dontShowAgain">
                    <span class="checkbox-label">Không hiển thị thông báo này nữa</span>
                </label>
                <button type="button" class="cta-button" onclick="participateEvent()">
                    <i class="fas fa-gift"></i>
                    <span>Tham gia ngay</span>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Liên kết ngân hàng -->
<input type="text" hidden value="{{ Auth::user()->username_bank?:"" }}" id="username_bank_input">
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
                        <label for="accountName" class="form-label required">{{__('withdraw_money.TenChuTaiKhoan')}}</label>
                        <input type="text" class="form-control" id="accountName" name="accountName"
                            placeholder="Nhập tên chủ tài khoản" required>
                    </div>

                    <div class="form-group">
                        <label for="bankName" class="form-label required">{{ __('withdraw_money.TenNganHang') }}</label>
                        <select class="" id="bankName" name="bankName" required>
                            <option value="">{{__('home.ChonNganHang')}}</option>
                            <optgroup label="Ngân hàng Việt Nam">
                                <option value="VPBank">VPBank</option>
                                <option value="BIDV">BIDV</option>
                                <option value="Vietcombank">Vietcombank</option>
                                <option value="VietinBank">VietinBank</option>
                                <option value="MBBANK">MBBANK</option>
                                <option value="ACB">ACB</option>
                                <option value="SHB">SHB</option>
                                <option value="Techcombank">Techcombank</option>
                                <option value="Agribank">Agribank</option>
                                <option value="Sacombank">Sacombank</option>
                                <option value="HDBank">HDBank</option>
                                <option value="LienVietPostBank">LienVietPostBank</option>
                                <option value="VIB">VIB</option>
                                <option value="SeABank">SeABank</option>
                                <option value="VBSP">VBSP</option>
                                <option value="TPBank">TPBank</option>
                                <option value="OCB">OCB</option>
                                <option value="MSB">MSB</option>
                                <option value="Eximbank">Eximbank</option>
                                <option value="SCB">SCB</option>
                                <option value="VDB">VDB</option>
                                <option value="Nam A Bank">Nam A Bank</option>
                                <option value="ABBANK">ABBANK</option>
                                <option value="PVcomBank">PVcomBank</option>
                                <option value="Bac A Bank">Bac A Bank</option>
                                <option value="UOB">UOB</option>
                                <option value="Woori">Woori</option>
                                <option value="HSBC">HSBC</option>
                                <option value="SCBVL">SCBVL</option>
                                <option value="PBVN">PBVN</option>
                                <option value="SHBVN">SHBVN</option>
                                <option value="NCB">NCB</option>
                                <option value="VietABank">VietABank</option>
                                <option value="BVBank">BVBank</option>
                                <option value="Vikki Bank">Vikki Bank</option>
                                <option value="Vietbank">Vietbank</option>
                                <option value="ANZVL">ANZVL</option>
                                <option value="MBV">MBV</option>
                                <option value="CIMB">CIMB</option>
                                <option value="Kienlongbank">Kienlongbank</option>
                                <option value="IVB">IVB</option>
                                <option value="BAOVIET Bank">BAOVIET Bank</option>
                                <option value="SAIGONBANK">SAIGONBANK</option>
                                <option value="Co-opBank">Co-opBank</option>
                                <option value="GPBank">GPBank</option>
                                <option value="VRB">VRB</option>
                                <option value="VCBNeo">VCBNeo</option>
                                <option value="HLBVN">HLBVN</option>
                                <option value="PGBank">PGBank</option>
                            </optgroup>
                            <optgroup label="Ngân hàng Nhật Bản">
                                <option value="MUFG Bank (三菱UFJ銀行)">MUFG Bank (三菱UFJ銀行)</option>
                                <option value="SMBC (Sumitomo Mitsui Banking Corporation, 三井住友銀行)">SMBC (Sumitomo Mitsui Banking Corporation, 三井住友銀行)</option>
                                <option value="Mizuho Bank (みずほ銀行)">Mizuho Bank (みずほ銀行)</option>
                                <option value="Resona Bank (りそな銀行)">Resona Bank (りそな銀行)</option>
                                <option value="Shinsei Bank (新生銀行)">Shinsei Bank (新生銀行)</option>
                                <option value="Japan Post Bank (ゆうちょ銀行)">Japan Post Bank (ゆうちょ銀行)</option>
                                <option value="Rakuten Bank (楽天銀行)">Rakuten Bank (楽天銀行)</option>
                                <option value="PayPay Bank (旧ジャパンネット銀行)">PayPay Bank (旧ジャパンネット銀行)</option>
                                <option value="Sony Bank (ソニー銀行)">Sony Bank (ソニー銀行)</option>
                            </optgroup>
                            <optgroup label="Ngân hàng Đài Loan">
                                <option value="Bank of Taiwan (臺灣銀行)">Bank of Taiwan (臺灣銀行)</option>
                                <option value="Taipei Fubon Bank (台北富邦銀行)">Taipei Fubon Bank (台北富邦銀行)</option>
                                <option value="CTBC Bank/ChinaTrust (中國信託商業銀行)">CTBC Bank/ChinaTrust (中國信託商業銀行)</option>
                                <option value="Mega International Commercial Bank (兆豐國際商業銀行)">Mega International Commercial Bank (兆豐國際商業銀行)</option>
                                <option value="First Commercial Bank (第一商業銀行)">First Commercial Bank (第一商業銀行)</option>
                                <option value="Cathay United Bank (國泰世華銀行)">Cathay United Bank (國泰世華銀行)</option>
                                <option value="Taishin International Bank (台新銀行)">Taishin International Bank (台新銀行)</option>
                                <option value="Richart Digital Bank (by Taishin Bank)">Richart Digital Bank (by Taishin Bank)</option>
                                <option value="LINE Bank (by LINE & Union Bank of Taiwan)">LINE Bank (by LINE & Union Bank of Taiwan)</option>
                            </optgroup>
                            <optgroup label="Ngân hàng Hàn Quốc">
                                <option value="Kookmin Bank (KB국민은행)">Kookmin Bank (KB국민은행)</option>
                                <option value="Shinhan Bank (신한은행)">Shinhan Bank (신한은행)</option>
                                <option value="Woori Bank (우리은행)">Woori Bank (우리은행)</option>
                                <option value="Hana Bank (하나은행)">Hana Bank (하나은행)</option>
                                <option value="IBK Industrial Bank (IBK기업은행)">IBK Industrial Bank (IBK기업은행)</option>
                                <option value="NongHyup Bank (NH농협은행)">NongHyup Bank (NH농협은행)</option>
                                <option value="KakaoBank (카카오뱅크)">KakaoBank (카카오뱅크)</option>
                                <option value="Toss Bank (토스뱅크)">Toss Bank (토스뱅크)</option>
                                <option value="K Bank (케이뱅크)">K Bank (케이뱅크)</option>
                            </optgroup>
                            <optgroup label="Ngân hàng Trung Quốc">
                                <option value="ICBC (中国工商银行)">ICBC (中国工商银行)</option>
                                <option value="Bank of China (中国银行)">Bank of China (中国银行)</option>
                                <option value="China Construction Bank (中国建设银行)">China Construction Bank (中国建设银行)</option>
                                <option value="Agricultural Bank of China (中国农业银行)">Agricultural Bank of China (中国农业银行)</option>
                                <option value="China Merchants Bank (招商银行)">China Merchants Bank (招商银行)</option>
                            </optgroup>
                            <optgroup label="Ngân hàng Mỹ">
                                <option value="JPMorgan Chase Bank">JPMorgan Chase Bank</option>
                                <option value="Bank of America">Bank of America</option>
                                <option value="Wells Fargo Bank">Wells Fargo Bank</option>
                                <option value="Citibank">Citibank</option>
                                <option value="US Bank">US Bank</option>
                                <option value="PNC Bank">PNC Bank</option>
                                <option value="Capital One Bank">Capital One Bank</option>
                                <option value="TD Bank">TD Bank</option>
                                <option value="BB&T (Truist Bank)">BB&T (Truist Bank)</option>
                                <option value="SunTrust (Truist Bank)">SunTrust (Truist Bank)</option>
                            </optgroup>
                            <optgroup label="Ngân hàng Tây Ban Nha">
                                <option value="Banco Santander">Banco Santander</option>
                                <option value="BBVA (Banco Bilbao Vizcaya Argentaria)">BBVA (Banco Bilbao Vizcaya Argentaria)</option>
                                <option value="CaixaBank">CaixaBank</option>
                                <option value="Bankia">Bankia</option>
                                <option value="Banco Sabadell">Banco Sabadell</option>
                                <option value="Banco Popular Español">Banco Popular Español</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="accountNumber" class="form-label required">{{__('withdraw_money.SoTaiKhoan')}}</label>
                        <input type="text" class="form-control" id="accountNumber" name="accountNumber"
                            placeholder="Nhập số tài khoản" required>
                    </div>

                    <div class="form-group">
                        <label for="transactionPassword" class="form-label required">{{__('withdraw_money.MatKhauGiaoDich')}}</label>
                        <div class="input-group-password">
                            <input type="password" class="form-control" id="transactionPassword"
                                name="transactionPassword" placeholder="Nhập mật khẩu giao dịch" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('transactionPassword')">
                                <i class="fas fa-eye" id="transactionPasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword" class="form-label required">{{__('home.XacNhanMatKhauGiaoDich')}}</label>
                        <div class="input-group-password">
                            <input type="password" class="form-control" id="confirmPassword"
                                name="confirmPassword" placeholder="Nhập lại mật khẩu giao dịch" required>
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
                <button type="button" class="btn btn-primary" onclick="submitForm()">
                    <i class="fas fa-check me-2"></i>{{__('home.XacNhanLienKet')}}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection