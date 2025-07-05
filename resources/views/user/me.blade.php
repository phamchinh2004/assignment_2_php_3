@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/me.css')
@endsection
@section('script-libs')
<script>
    const trans = {
        VuiLongLienHeCskh: @json(__('me.VuiLongLienHeCskh')),
        ThongBao: @json(__('me.ThongBao')),
    }
</script>
@vite('resources/js/user/me.js')
@endsection
@section('content')
<div class="me_top d-flex flex-column pt-4 ps-3 pe-3 justify-content-center align-items-center">
    <div class="me_top_1 d-flex flex-column align-items-center">
        <img class="me_image" src="{{asset('images/me/head.png')}}" alt="">
        <div>
            <span class="badge fs-6 bg-warning">{{$rank->name}}</span>
        </div>
    </div>
    <div class="me_top_2 d-flex flex-column pt-2 align-items-center">
        <span class="fw-bold">{{$user->full_name}}</span>
        <span class="ma_moi">{{__('me.MaMoi').$user->referral_code}}</span>
    </div>
</div>
<div class="so_du">
    <span>{{__('me.SoDuTaiKhoan')}}</span>
    <h3 class="number_so_du text-center">{{format_money($user->balance)}}$</h3>
    <div class="d-flex justify-content-center">
        <a href="{{ route('withdraw_money') }}" class="btn btn-success btn-sm">&nbsp;&nbsp;&nbsp;{{__('me.Rut')}}&nbsp;&nbsp;&nbsp;</a>
        <a onclick="thong_bao_lien_he_cskh()" class="btn btn-primary btn-sm ms-2">&nbsp;&nbsp;&nbsp;{{__('me.Nap')}}&nbsp;&nbsp;&nbsp;</a>
    </div>
</div>
<div class="row blocks g-0">
    <div class="div_block_item p-1">
        <a href="{{ route('personal_information') }}" class="block_item border">
            <div>
                <img class="image_block_item" src="{{ asset('images/me/image_1.png') }}" alt="">
            </div>
            <span class="tittle_block_item">{{__('me.ThongTin')}}</span>
        </a>
    </div>
    <div class="div_block_item p-1">
        <a href="{{ route('vip') }}" class="block_item border">
            <div>
                <img class="image_block_item" width="50px" src="{{ asset('images/me/image_2.png') }}" alt="">
            </div>
            <span class="tittle_block_item">{{__('me.Vip')}}</span>
        </a>
    </div>
    <div class="div_block_item p-1">
        <a href="" class="block_item border">
            <div>
                <img class="image_block_item" width="50px" src="{{ asset('images/me/image_3.png') }}" alt="">
            </div>
            <span class="tittle_block_item">{{__('me.DiaChiKho')}}</span>
        </a>
    </div>
    <div class="div_block_item p-1">
        <a href="{{ route('order') }}" class="block_item border">
            <div>
                <img class="image_block_item" width="50px" src="{{ asset('images/me/image_4.png') }}" alt="">
            </div>
            <span class="tittle_block_item">{{__('me.PhanPhoi')}}</span>
        </a>
    </div>
    <div class="div_block_item p-1">
        <a href="{{ route('balance_fluctuation') }}?tab=distribution" class="block_item border">
            <div>
                <img class="image_block_item" width="50px" src="{{ asset('images/me/image_5.png') }}" alt="">
            </div>
            <span class="tittle_block_item">{{__('me.BienDong')}}</span>
        </a>
    </div>
    <div class="div_block_item p-1">
        <a href="{{ route('balance_fluctuation') }}?tab=deposit" class="block_item border">
            <div>
                <img class="image_block_item" width="50px" src="{{ asset('images/me/image_6.png') }}" alt="">
            </div>
            <span class="tittle_block_item">{{__('me.LichSuNap')}}</span>
        </a>
    </div>
    <div class="div_block_item p-1">
        <a href="{{ route('balance_fluctuation') }}?tab=withdraw" class="block_item border">
            <div>
                <img class="image_block_item" width="50px" src="{{ asset('images/me/image_7.png') }}" alt="">
            </div>
            <span class="tittle_block_item">{{__('me.LichSuRut')}}</span>
        </a>
    </div>
    <div class="div_block_item p-1">
        <a href="" class="block_item border">
            <div>
                <img class="image_block_item" width="50px" src="{{ asset('images/me/image_8.png') }}" alt="">
            </div>
            <span class="tittle_block_item">{{__('me.BaoCaoNhom')}}</span>
        </a>
    </div>
    <div class="dropdown div_block_item p-1">
        <a href="javascript:void(0)" class="block_item border dropdown-toggle" onclick="toggleLanguageDropdown()" id="languageDropdownButton" role="button">
            <img src="{{ asset('images/me/image_9.png') }}" width="40">
            <span class="tittle_block_item">{{__('me.NgonNgu')}}</span>
        </a>

        <div id="languageDropdown" class="dropdown-menu" aria-labelledby="languageDropdownButton">
            <form action="{{ route('language.change') }}" method="POST">
                @csrf
                @foreach (\App\Models\Language::all() as $lang)
                <button type="submit"
                    name="locale"
                    value="{{ $lang->code }}"
                    class="dropdown-item d-flex align-items-center gap-2
               {{ App::getLocale() === $lang->code ? 'active fw-bold bg-light text-primary' : '' }}">
                    <img src="{{ asset('uploads/language/images/' . $lang->image) }}" width="20">
                    {{ $lang->name }}
                </button>
                @endforeach

            </form>
        </div>
    </div>


</div>
<div class="mt-3 mb-3 d-flex justify-content-center">
    <a onclick="log_out()" class="btn btn-dark">{{__('me.DangXuat')}}</a>
</div>
@endsection