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
    };
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


@endsection