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
</script>
@endsection
@section('content')
<div id="fireworks-container"></div>
<div class="bg-white d-flex flex-row position-relative">
    <a class="text-dark fw-bold text-decoration-none p-2 hover btn-back" href="#" onclick="history.back(); return false;"><i class="fa fa-arrow-left fa-sm pe-1"></i>{{__('distribution.QuayLai')}}</a>
    <h3 class="position-absolute title">{{__('distribution.HeThongPhanPhoi')}}</h3>
</div>
<div class="banner bg-white m-auto mt-4 p-3 rounded">
    <h2 class="fw-bold vip-level-banner">{{$get_first_rank->name??__('distribution.DangCapNhat')}}</h2>
    <span class="text-secondary fw-bold detail-vip-level-banner">{{$get_first_rank->name ." - " .__('distribution.LoiNhuan')." ". $get_first_rank->commission_percentage."%"}}</span>
    <div class="w-100 mt-3">
        <img class="banner-image" src="{{ asset('images/distribution/banner.gif') }}" alt="">
    </div>
</div>
<div class="mt-3">
    <div class="d-flex justify-content-center">
        <button class="btn-import" id="btn_import" onclick="distribution()">{{__('distribution.Nhap')}}</button>
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
                            <span class="order_details_time" id="order_details_time">{{__('order.ThoiGianDatPhanPhoi')}}</span>
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
    </div>
    <div class="text-white mt-2 d-flex justify-content-center flex-column">
        <span class="text-center fw-bold tong-phan-phoi">{{__('distribution.TongPhanPhoi')}}</span>
        <div class="report row p-1 d-flex justify-content-center">
            <div class="report-item col-5 position-relative p-2 d-flex justify-content-between flex-column m-2">
                <span class="fw-bold report-item-title text-center" style="color: #0051ab;">{{__('distribution.TongSoDu')}}</span>
                <span class="fw-bold report-item-value text-center" style="color:#21b0ff">{{format_money($user->balance)}}$</span>
                <div class="div-image-logo d-flex justify-content-end">
                    <img class="image-logo" class="" src="{{ asset('images/distribution/report/coin.png') }}" alt="">
                </div>
            </div>
            <div class="report-item col-5 position-relative p-2 d-flex justify-content-between flex-column m-2">
                <span class="fw-bold report-item-title text-center" style="color: #0051ab;">{{__('distribution.PhanPhoiHomNay')}}</span>
                <span class="fw-bold report-item-value text-center" style="color:#21b0ff">+{{ $user->distribution_today!=null?$user->distribution_today:0 }}</span>
                <div class="div-image-logo d-flex justify-content-end">
                    <img class="image-logo" class="" src="{{ asset('images/distribution/report/coin-2.png') }}" alt="">
                </div>
            </div>
            <div class="report-item col-5 position-relative p-2 d-flex justify-content-between flex-column m-2">
                <span class="fw-bold report-item-title text-center" style="color: #0051ab;">{{__('distribution.ChietKhauHomNay')}}</span>
                <span class="fw-bold report-item-value text-center" style="color:#21b0ff">+{{format_money($user->todays_discount!=null?$user->todays_discount:0)}}$</span>
                <div class="div-image-logo d-flex justify-content-end">
                    <img class="image-logo" class="" src="{{ asset('images/distribution/report/tui-tien.png') }}" alt="">
                </div>
            </div>
            <div class="report-item col-5 position-relative p-2 d-flex justify-content-between flex-column m-2">
                <span class="fw-bold report-item-title text-center" style="color: #0051ab;">{{__('distribution.SoDuDongBang')}}</span>
                <span class="fw-bold report-item-value text-center" style="color:#21b0ff">{{format_money($frozen_price!=null?$frozen_price:0)}}$</span>
                <div class="div-image-logo d-flex justify-content-end">
                    <img class="image-logo" class="" src="{{ asset('images/distribution/report/tui-tien.png') }}" alt="">
                </div>
            </div>
        </div>
    </div>
</div>
<div class="mo-ta">
    <div class="position-relative">
        <span class="text-white fw-bold mo-ta-title badge">{{__('distribution.MoTa')}}</span>
    </div>
    <div class="mo-ta-box-content bg-white">
        <p class="text-secondary">
            {!! $section_mo_ta->getTranslatedContent()?? __('distribution.DangCapNhat')!!}
        </p>
    </div>
</div>
@endsection