@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/vip.css')
@endsection
@section('content')
<div class="div_tittle">
    <h3 class="text-center tittle">Cấp độ thành viên</h3>
</div>
<!-- Phần 1 -->
<section class="section_1 d-flex flex-row justify-content-between align-items-center">
    <div class="d-flex flex-row align-items-center">
        <div class="me-2">
            <img class="avatar" src="{{ asset('images/personal_information/image_7.png') }}" alt="">
        </div>
        <div class="d-flex flex-column">
            <span class="fw-bold section_1_text_1">
                Cấp: {{$rank->name}}
            </span>
            <span class="section_1_text_2">
                Số lượng đơn hàng：{{$rank->spin_count}} (Unit)
            </span>
        </div>
    </div>
    <div class="d-flex justify-content-center align-items-center">
        <a class="section_1_text_3" href="{{ route('me') }}">Chi tiết người dùng</a>
    </div>
</section>
<!-- Phần 2 -->
<section class="section_2 d-flex flex-row justify-content-between">
    <div class="section_2_item d-flex flex-column align-items-center justify-content-center">
        <img class="section_2_image" src="{{ asset('images/vip/image_1.png') }}" alt="">
        <span class="section_2_text" style="color: #febd38;">Hoa hồng nhiều hơn</span>
    </div>
    <div class="section_2_item d-flex flex-column align-items-center justify-content-center">
        <img class="section_2_image" src="{{ asset('images/vip/image_2.png') }}" alt="">
        <span class="section_2_text" style="color: #e55a69;">Nhiệm vụ nhiều hơn
        </span>
    </div>
    <div class="section_2_item d-flex flex-column align-items-center justify-content-center">
        <img class="section_2_image" src="{{ asset('images/vip/image_3.png') }}" alt="">
        <span class="section_2_text" style="color: #39d2f8;">Hỗ trợ khách hàng VIP</span>
    </div>
</section>
<!-- Phần 3 -->
<section class="section_3">
    <div class="row">
        @if(!empty($list_ranks))
        @foreach($list_ranks as $item)
        <div class="col-6 section_3_item">
            <div class="section_3_item_content text-center d-flex flex-column">
                <span class="section_3_item_tittle">{{ $item->name }} {{ $item->id===$rank->id?"(Bạn)":"" }}</span>
                <span class="section_3_item_price">{{ format_money($item->upgrade_fee) }}$</span>
                <span class="section_3_item_text">Số lượt rút tiền: {{ $item->maximum_number_of_withdrawals."/ngày" }}</span>
                <span class="section_3_item_text">Số tiền rút tối đa: {{ format_money($item->maximum_withdrawal_amount)."$/ngày" }}</span>
                <span class="section_3_item_text">Số lượng nhiệm vụ: {{ $item->spin_count."/ngày" }}</span>
                <span class="section_3_item_text">Tỷ lệ hoa hồng: {{ $item->commission_percentage."%" }}</span>
                <span class="section_3_item_subtext">Thành viên vĩnh viễn</span>
            </div>
        </div>
        @endforeach
        @endif
    </div>
</section>
<div class="d-flex justify-content-center mt-5">
    <a class="btn btn-outline-dark fw-bold text-decoration-none hover btn-sm btn-back" href="#" onclick="history.back(); return false;"><i class=" fa fa-arrow-left fa-sm pe-1"></i>Quay lại</a>
</div>
@endsection