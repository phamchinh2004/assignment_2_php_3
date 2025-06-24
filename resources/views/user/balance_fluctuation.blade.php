@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/balance_fluctuation.css')
@endsection
@section('script-libs')
@vite('resources/js/user/balance_fluctuation.js')
@endsection
@section('content')
<div class="bg-white d-flex flex-row position-relative">
    <a class="text-dark fw-bold text-decoration-none p-2 hover btn-back" href="{{ route('home') }}"><i class="fa fa-arrow-left fa-sm pe-1"></i>Quay lại</a>
    <h3 class="position-absolute title">Biến động số dư</h3>
</div>
<div class="d-flex flex-row align-items-center p-2 bg-white justify-content-center">
    <a href="{{ route('balance_fluctuation') }}?tab=distribution" class="btn_tittle cspt pt-2 pb-2 text-center" style="width: 32%;" id="btn_distribution">
        <span>Phân phối</span>
    </a>
    <a href="{{ route('balance_fluctuation') }}?tab=withdraw" class="btn_tittle cspt pt-2 pb-2 text-center" style="width: 32%;" id="btn_withdraw">
        <span>Rút tiền</span>
    </a>
    <a href="{{ route('balance_fluctuation') }}?tab=deposit" class="btn_tittle cspt pt-2 pb-2 text-center" style="width: 32%;" id="btn_deposit">
        <span>Nạp tiền</span>
    </a>
</div>
<div class="bg-white" id="content_items">
    <!-- Distribution -->
    @if(optional($list_distribution)->isNotEmpty())
    @foreach($list_distribution as $item)
    @if($item->type==="profit")
    <div class="d-flex flex-column p-3 border-bottom">
        <span>{{$item->created_at}}</span>
        <div class="d-flex justify-content-end">
            <span class="badge bg-success text-end">+{{ format_money($item->value) }}$</span>
        </div>
        <span class="text-success fw-bold content_items_text_3">Lợi nhuận</span>
    </div>
    @elseif($item->type==="order")
    <div class="d-flex flex-column p-3 border-bottom">
        <span>{{$item->created_at}}</span>
        <div class="d-flex justify-content-end">
            <span class="badge bg-danger text-end">-{{format_money($item->value) }}$</span>
        </div>
        <span class="text-primary fw-bold content_items_text_3">Đặt hàng</span>
    </div>
    @endif
    @endforeach
    <!-- Withdraw -->
    @elseif(optional($list_withdraw)->isNotEmpty())
    @foreach($list_withdraw as $item)
    <div class="d-flex flex-row justify-content-between align-items-center p-3 border-bottom position-relative">
        <div class="d-flex flex-column">
            <span><b>Tên tài khoản: </b>{{$item->username_bank}}</span>
            <span><b>Số tài khoản: </b>{{$item->account_number}}</span>
            <span><b>Ngân hàng: </b>{{$item->bank_name}}</span>
            <span><b>Thời gian: </b>{{$item->created_at}}</span>
        </div>
        <div>
            <span class="badge bg-danger text-end">-{{ format_money($item->value) }}$</span>
        </div>
        @if($item->status=="processing")
        <span class="status_withdraw_deposit badge bg-warning">Chờ xác nhận</span>
        @elseif($item->status=="completed")
        <span class="status_withdraw_deposit badge bg-success">Hoàn thành</span>
        @else
        <span class="status_withdraw_deposit badge bg-danger">Hủy</span>
        @endif
    </div>
    @endforeach
    <!-- Deposit -->
    @elseif(optional($list_deposit)->isNotEmpty())
    @foreach($list_deposit as $item)
    <div class="d-flex flex-row justify-content-between align-items-center p-3 border-bottom">
        <span>{{$item->created_at}}</span>
        <span class="badge bg-success text-end">+{{format_money($item->value)}}$</span>
    </div>
    @endforeach
    @else
    <div class="text-center p-2">
        <span class="text-center">Lịch sử trống!</span>
    </div>
    @endif
</div>
@endsection