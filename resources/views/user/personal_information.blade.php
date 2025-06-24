@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/personal_information.css')
@endsection
@section('content')
<div>
    <h3 class="text-center">Thông tin cá nhân</h3>
</div>
<div class="p-3">
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_1.png') }}" alt="">
            <span>Ảnh đại diện</span>
        </div>
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="image_avt_temp" src="{{ asset('images/personal_information/image_7.png') }}" alt="">
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_2.png') }}" alt="">
            <span>Tên tài khoản</span>
        </div>
        <div class="d-flex flex-row align-items-center justify-content-center">
            <span class="me-3">{{$user->full_name}}</span>
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_3.png') }}" alt="">
            <span>Phương thức thanh toán</span>
        </div>
        <div>
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_4.png') }}" alt="">
            <span>Mật khẩu đăng nhập</span>
        </div>
        <div>
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_5.png') }}" alt="">
            <span>Mật khẩu giao dịch</span>
        </div>
        <div>
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_6.png') }}" alt="">
            <span>Địa chỉ kho</span>
        </div>
        <div>
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
</div>
<div class="d-flex justify-content-center">
    <a class="btn btn-outline-dark fw-bold text-decoration-none hover btn-back" href="#" onclick="history.back(); return false;"><i class="fa fa-arrow-left fa-sm pe-1"></i>Quay lại</a>
</div>
@endsection