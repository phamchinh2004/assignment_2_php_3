@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/distribution.css')
@endsection
@section('script-libs')
@vite('resources/js/user/distribution.js')
@endsection
@section('content')
<div class="bg-white d-flex flex-row position-relative">
    <a class="text-dark fw-bold text-decoration-none p-2 hover btn-back" href="#" onclick="history.back(); return false;"><i class="fa fa-arrow-left fa-sm pe-1"></i>Quay lại</a>
    <h3 class="position-absolute title">Hệ thống phân phối</h3>
</div>
<div class="banner bg-white m-auto mt-4 p-3 rounded">
    <h2 class="fw-bold vip-level-banner">VIP 1</h2>
    <span class="text-secondary fw-bold detail-vip-level-banner">VIP 1 exclusive channel - Lợi nhuận 0.002%</span>
    <div class="w-100 mt-3">
        <img class="banner-image" src="{{ asset('images/distribution/banner.gif') }}" alt="">
    </div>
</div>
<div class="mt-3">
    <div class="d-flex justify-content-center">
        <button class="btn-import" id="btn_import">NHẬP</button>
    </div>
    <div class="text-white mt-2 d-flex justify-content-center flex-column">
        <span class="text-center fw-bold tong-phan-phoi">Tổng phân phối</span>
        <div class="report row p-1 d-flex justify-content-center">
            <div class="report-item col-5 position-relative p-2 d-flex justify-content-between flex-column m-2">
                <span class="fw-bold report-item-title text-center" style="color: #0051ab;">Tổng số dư</span>
                <span class="fw-bold report-item-value text-center" style="color:#21b0ff">{{format_money($user->balance)}}$</span>
                <div class="div-image-logo d-flex justify-content-end">
                    <img class="image-logo" class="" src="{{ asset('images/distribution/report/coin.png') }}" alt="">
                </div>
            </div>
            <div class="report-item col-5 position-relative p-2 d-flex justify-content-between flex-column m-2">
                <span class="fw-bold report-item-title text-center" style="color: #0051ab;">Phân phối hôm nay</span>
                <span class="fw-bold report-item-value text-center" style="color:#21b0ff">+{{ $user->distribution_today!=null?$user->distribution_today:0 }}</span>
                <div class="div-image-logo d-flex justify-content-end">
                    <img class="image-logo" class="" src="{{ asset('images/distribution/report/coin-2.png') }}" alt="">
                </div>
            </div>
            <div class="report-item col-5 position-relative p-2 d-flex justify-content-between flex-column m-2">
                <span class="fw-bold report-item-title text-center" style="color: #0051ab;">Chiết khấu hôm nay</span>
                <span class="fw-bold report-item-value text-center" style="color:#21b0ff">+{{format_money($user->todays_discount!=null?$user->todays_discount:0)}}$</span>
                <div class="div-image-logo d-flex justify-content-end">
                    <img class="image-logo" class="" src="{{ asset('images/distribution/report/tui-tien.png') }}" alt="">
                </div>
            </div>
            <div class="report-item col-5 position-relative p-2 d-flex justify-content-between flex-column m-2">
                <span class="fw-bold report-item-title text-center" style="color: #0051ab;">Số dư đóng băng</span>
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
        <span class="text-white fw-bold mo-ta-title badge">Mô tả</span>
    </div>
    <div class="mo-ta-box-content bg-white">
        <p class="text-secondary">1：Thành viên xử lý nhập phân phối là người tiếp nhận đơn hàng cần được phân phối từ bộ phận kinh doanh hoặc bán hàng và xác nhận chuyển giao phân phối , hỗ trợ vận chuyển đến khách hàng.
            <br>
            2：Thành viên sẽ được nhận chiết khấu trên mỗi từng giá trị phân phối khác nhau & chiết khấu phụ thuộc vào cấp thành viên mà thành viên đăng ký.
            <br>
            3：Thành viên cấp càng cao chiết khấu càng cao & số lượng nhập phân phối càng nhiều.
        </p>
    </div>
</div>
@endsection