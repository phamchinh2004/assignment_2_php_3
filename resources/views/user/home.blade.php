@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/home.css')
@vite('resources/css/user/winwheel/main.css')
@endsection
@section('script-libs')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script>
    const trans = {
        justNow: @json(__('home.VuaXong')), // Vừa xong
        secondsAgo: @json(__('home.GiayTruoc')), // giây trước
        minutesAgo: @json(__('home.PhutTruoc')), // phút trước
        successText: @json(__('home.PhanPhoiThanhCong')), // Phân phối thành công
        heThongDangQuaTai: @json(__('home.HeThongDangQuaTai')), // Phân phối thành công
        vuiLongLienHeCskhDeNapTien: @json(__('home.VuiLongLienHeCskhDeNapTien')), // Phân phối thành công
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

        MatKhauXacNhanKhongKhop: @json(__('home.MatKhauXacNhanKhongKhop')),
        SoTaiKhoanPhaiLaSo: @json(__('home.SoTaiKhoanPhaiLaSo')),
    };
</script>
@vite('resources/js/user/home.js')
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
    <!-- Vòng quay may mắn - Amazon Theme -->
    <div class="section-3" id="section-3">
        <div class="position-relative text-center">
            <div class="amazon-banner">
                <span class="text-white fw-bold text-title">🎯 {{__('home.VongQuayMayMan')}}</span>
            </div>
        </div>
        <div class="mainbox m-auto vong-quay" id="mainbox" hidden>
            <div class="box" id="box">
                <div class="box1">
                    <span class="font span1"><b>Đơn hàng 1</b></span>
                    <span class="font span2"><b>Đơn hàng 2</b></span>
                    <span class="font span3"><b>Đơn hàng 3</b></span>
                    <span class="font span4"><b>Đơn hàng 4</b></span>
                    <span class="font span5"><b>Đơn hàng 5</b></span>
                </div>
                <div class="box2">
                    <span class="font span1"><b>Đơn hàng 6</b></span>
                    <span class="font span2"><b>Đơn hàng 7</b></span>

                    <span class="font span3"><b>Đơn hàng 8</b></span>
                    <span class="font span4"><b>Đơn hàng 9</b></span>
                    <span class="font span5"><b>Đơn hàng 10</b></span>
                </div>
            </div>
            <button class="spin" id="spin" onclick="spin()">{{__('home.Quay')}}</button>
        </div>
        <audio
            controls="controls"
            id="applause"
            src="{{asset('audio/applause.mp3')}}"
            type="audio/mp3"></audio>
        <audio
            controls="controls"
            id="wheel"
            src="{{asset('audio/wheel.mp3')}}"
            type="audio/mp3"></audio>
    </div>
    <div class="dark_surface" id="order_award" hidden>
        <div class="" id="order">
            <div class="order_bg">
                <div class="div_img">
                    <img src="{{ asset('images/home/congratulations.png') }}" alt="">
                </div>
                <div class="order_details">
                    <div class="order_details_vien">
                        <div class="div_img_cho_xu_ly">
                            <img src="{{ asset('images/nhan_cho_xu_ly.png') }}" alt="">
                        </div>
                        <span class="order_details_time" id="order_details_time">{{__('home.ThoiGianDatPhanPhoi')}}</span>
                        <div class="order_details_info">
                            <div class="order_details_img">
                                <img id="order_details_img" src="{{ asset('images/orders/syglp5via6r7rxqjc1k8.jpg') }}" alt="">
                            </div>
                            <div class="order_details_info_2">
                                <span class="order_details_name" id="order_details_name">Apple iPhone 14 Pro Max</span>
                                <div class="d-flex flex-row justify-content-between">
                                    <span class="order_details_price" id="order_details_price">10.000$</span>
                                    <span class="order_details_quantity" id="order_details_quantity">x1</span>
                                </div>
                            </div>
                        </div>
                        <table class="order_details_end">
                            <tbody>
                                <tr>
                                    <td class="order_details_end_title">{{__('order.TongTienPhanPhoi')}}</td>
                                    <th class="order_details_end_value" id="order_details_end_value_total_price">10.000$</th>
                                </tr>
                                <tr>
                                    <td class="order_details_end_title">{{__('order.HoaHong')}}</td>
                                    <th class="order_details_end_value" id="order_details_end_value_price_rose">20$</th>
                                </tr>
                                <tr>
                                    <td class="order_details_end_title_total">{{__('order.SoTienHoanNhap')}}</td>
                                    <th class="order_details_end_value_total" id="order_details_end_value_total">10.020$</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="order_button">
                <button class="btn btn-secondary me-5" id="later"><i class="fas fa-arrow-left me-1"></i>{{__('order.DeSau')}}</button>
                <button class="btn btn-dark" id="btn_phan_phoi_ngay"><i class="fa-solid fa-gears me-2"></i>{{__('order.PhanPhoiNgay')}}</button>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex justify-content-center align-items-center text-white">
        @if ($user_spin_progress && $rank)
        <span>
            Đã quay được:
            <span id="current_spin">{{ $user_spin_progress->current_spin }}</span>/{{ $rank->spin_count }} đơn hàng
        </span>
        @else
        <span>Chưa có cấp độ</span>
        @endif
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
<!-- Thành viên Amazon - Amazon Theme -->
<div class="section-5">
    <div class="position-relative text-center">
        <div class="amazon-banner">
            <span class="text-white fw-bold text-title">👥 {{__('home.ThanhVienAmazon')}}</span>
        </div>
    </div>
    <div class="section-5-box-content">
        @if (!empty($list_ranks))
        @foreach ($list_ranks as $item)
        <div class="section-5-item-content mb-3">
            <span class="section-5-vip-title badge text-warning">
                {{$item->name}}
            </span>
            <div class="section-5-content d-flex align-items-center justify-content-between flex-row">
                <div class="d-flex flex-column align-items-center">
                    <span class="section-5-content-tittle">
                        {{__('home.PhiNangCap')}}
                    </span>
                    <span class="section-5-content-value text-danger">
                        {{$item->upgrade_fee}}$
                    </span>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <span class="section-5-content-tittle">
                        {{__('home.ChietKhau')}}
                    </span>
                    <span class="section-5-content-value">
                        {{$item->commission_percentage}}%
                    </span>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <span class="section-5-content-tittle">
                        {{__('home.LuotPhanPhoi')}}
                    </span>
                    <span class="section-5-content-value">
                        {{$item->spin_count}}
                    </span>
                </div>
            </div>
        </div>
        @endforeach
        @endif

    </div>
</div>
<div class="section-6">
    <div class="position-relative">
        <span class="text-white fw-bold section-6-title badge bg-warning">📖 {{__('home.GioiThieu')}}</span>
    </div>
    <div class="section-6-box-content bg-white">
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
<!-- Notification Overlay -->
<div class="arround_notification">
    <div class="notification-overlay" id="notificationOverlay">
        <div class="notification-board">
            <button class="close-btn" onclick="closeNotification()">
                <i class="fas fa-times"></i>
            </button>

            <div class="notification-header">
                <h1 class="notification-title">
                    <i class="fas fa-bullhorn"></i> THÔNG BÁO
                </h1>
                <p class="notification-subtitle">Chương trình ưu đãi đặc biệt từ Amazon</p>
            </div>

            <div class="notification-body">
                <div class="notification-item">
                    <div class="notification-number">1</div>
                    <p>Hệ thống Amazon đang tri ân khách hàng mới thưởng lớn cho các khách hàng đăng ký tài khoản tham gia gian hàng lần đầu.</p>
                </div>

                <div class="notification-item">
                    <div class="notification-number">2</div>
                    <div class="special-event">
                        <div class="special-title">>>> Thông Báo Đặc Biệt <<< </div>
                                <p><strong>Sự kiện mừng ngày 20/10 - Ngày Phụ Nữ Việt Nam</strong></p>
                                <p>Khi khách hàng tham gia sự kiện đặc biệt, bạn có cơ hội nhận thưởng <span class="reward-amount">lên tới hàng triệu USD</span> cùng nhiều phần quà giá trị khác!</p>
                                <p>Chương trình chỉ diễn ra trong dịp lễ 20/10 này, hãy nhanh tay tham gia để không bỏ lỡ cơ hội vàng.</p>
                        </div>
                    </div>
                </div>

                <div class="notification-footer">
                    <button class="cta-button" onclick="participateEvent()">
                        <i class="fas fa-gift"></i> Tham gia ngay
                    </button>
                </div>
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