@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/personal_information.css')
@endsection
@section('content')
<div>
    <h3 class="text-center">{{__('personal_information.ThongTinCaNhan')}}</h3>
</div>
<div class="p-3">
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_1.png') }}" alt="">
            <span>{{__('personal_information.AnhDaiDien')}}</span>
        </div>
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="image_avt_temp" src="{{ asset('images/personal_information/image_7.png') }}" alt="">
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_2.png') }}" alt="">
            <span>{{__('personal_information.TenTaiKhoan')}}</span>
        </div>
        <div class="d-flex flex-row align-items-center justify-content-center">
            <span class="me-3">{{$user->full_name}}</span>
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_3.png') }}" alt="">
            <span>{{__('personal_information.PhuongThucThanhToan')}}</span>
        </div>
        <div>
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_4.png') }}" alt="">
            <span>{{__('personal_information.MatKhauDangNhap')}}</span>
        </div>
        <div>
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_5.png') }}" alt="">
            <span>{{__('personal_information.MatKhauGiaoDich')}}</span>
        </div>
        <div>
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
    <a class="d-flex flex-row justify-content-between align-items-center border-bottom pt-3 pb-3 cspt text-decoration-none text-dark">
        <div class="d-flex flex-row align-items-center justify-content-center">
            <img class="images" src="{{ asset('images/personal_information/image_6.png') }}" alt="">
            <span>{{__('personal_information.DiaChiKho')}}</span>
        </div>
        <div>
            <i class="fa fa-solid fa-chevron-right"></i>
        </div>
    </a>
</div>
<div class="d-flex justify-content-center">
    <a class="btn btn-outline-dark fw-bold text-decoration-none hover btn-back" href="#" onclick="history.back(); return false;"><i class="fa fa-arrow-left fa-sm pe-1"></i>{{__('personal_information.QuayLai')}}</a>
</div>
@endsection