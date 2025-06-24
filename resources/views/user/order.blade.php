@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/order.css')
@endsection
@section('script-libs')
@vite('resources/js/user/order.js')
@endsection
@section('content')
<div class="history-top d-flex flex-column justify-content-center align-items-center">
    <span class="tittle_order">Lịch sử phân phối</span>
    <span class="so_du" id="so_du_user">Số dư hiện tại: {{ format_money($user->balance, 7) }}$</span>
    <span align="center" class="history-top-text-3">Dữ liệu này được cung cấp chính thức bởi Mercado Libre</span>
</div>
<div class="status_btns d-flex flex-row justify-content-center align-items-center">
    <a data-tab="tat-ca" class="btn_status cspt">
        <span class="btn_status_text" id="btn_tat_ca">Tất cả</span>
    </a>
    <a data-tab="cho-xu-ly" class="btn_status cspt">
        <span class="btn_status_text" id="btn_cho_xu_ly">Chờ xử lý</span>
    </a>
    <a data-tab="hoan-thanh" class="btn_status cspt">
        <span class="btn_status_text" id="btn_hoan_thanh">Hoàn thành</span>
    </a>
    <a data-tab="dong-bang" class="btn_status cspt">
        <span class="btn_status_text" id="btn_dong_bang">Đóng băng</span>
    </a>
</div>
<div class="list_orders" id="list_orders">

</div>


@endsection