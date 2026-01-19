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
<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="me_top d-flex flex-column justify-content-center align-items-center">
        <div class="me_top_1 d-flex flex-column align-items-center">
            <img class="me_image" 
                 src="{{ get_user_avatar($user) }}" 
                 alt="Avatar"
                 onerror="this.src='{{ asset('images/default-avatar-gray.svg') }}'">
            @if ($rank && $rank->name)
            <div class="mt-2">
                <span class="badge bg-warning text-dark">{{$rank->name}}</span>
            </div>
            @endif
        </div>
        <div class="me_top_2 d-flex flex-column align-items-center text-center">
            <h4 class="fw-bold mb-2">{{$user->full_name}}</h4>
            <span class="ma_moi">{{__('me.MaMoi').$user->referral_code}}</span>
        </div>
    </div>
    <!-- Balance Section -->
    <div class="balance-container">
        <div class="balance-card">
            <!-- Main Balance Display -->
            <div class="balance-main">
                <div class="balance-header">
                    <div class="balance-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="balance-title">
                        <h4>{{__('me.SoDuTaiKhoan')}}</h4>
                        <span class="balance-subtitle">{{__('me.SoDuHienTai')}}</span>
                    </div>
                </div>
                <div class="balance-amount">
                    <h2 class="balance-number">{{format_money($user->balance)}}</h2>
                    <span class="balance-currency">USD</span>
                </div>
            </div>

            <!-- Balance Statistics -->
            <!-- <div class="balance-stats">
                <div class="stat-item">
                    <div class="stat-icon deposit">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{__('me.TongNap')}}</span>
                        <span class="stat-value">{{format_money($user->total_deposit ?? 0)}}</span>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon withdraw">
                        <i class="fas fa-minus-circle"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{__('me.TongRut')}}</span>
                        <span class="stat-value">{{format_money($user->total_withdraw ?? 0)}}</span>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon profit">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{__('me.LoiNhuan')}}</span>
                        <span class="stat-value profit-value">{{format_money($user->profit)}}</span>
                    </div>
                </div>
            </div> -->

            <!-- Action Buttons -->
            <div class="balance-actions">
                <a href="{{ route('withdraw_money') }}" class="btn-action withdraw-btn">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>{{__('me.Rut')}}</span>
                </a>
                <a onclick="thong_bao_lien_he_cskh()" class="btn-action deposit-btn">
                    <i class="fas fa-credit-card"></i>
                    <span>{{__('me.Nap')}}</span>
                </a>
            </div>

            <!-- Quick Stats -->
            <div class="balance-quick-stats">
                <div class="quick-stat">
                    <span class="quick-stat-label">{{__('me.GiaoDichHomNay')}}</span>
                    <span class="quick-stat-value">{{$user->today_transactions ?? 0}}</span>
                </div>
                <div class="quick-stat">
                    <span class="quick-stat-label">{{__('me.TrangThai')}}</span>
                    <span class="quick-stat-status 
                        @if($user->status === 'activated') active
                        @elseif($user->status === 'inactivated') inactive
                        @elseif($user->status === 'banned') banned
                        @else inactive
                        @endif">
                        @if($user->status === 'activated')
                            {{__('me.HoatDong')}}
                        @elseif($user->status === 'inactivated')
                            {{__('me.ChuaKichHoat')}}
                        @elseif($user->status === 'banned')
                            {{__('me.BiCam')}}
                        @else
                            {{__('me.KhongHoatDong')}}
                        @endif
                    </span>
                </div>
            </div>

            <!-- Explanation Section -->
            <!-- <div class="balance-explanation">
                <div class="explanation-item">
                    <i class="fas fa-info-circle"></i>
                    <span>{{__('me.LoiNhuanGiaiThich')}}</span>
                </div>
                <div class="explanation-item">
                    <i class="fas fa-calculator"></i>
                    <span>{{__('me.CongThucTinh')}}: {{format_money($user->total_withdraw)}} - {{format_money($user->total_deposit)}} = {{format_money($user->profit)}}</span>
                </div>
            </div> -->
        </div>
    </div>
    <!-- Menu Blocks Section -->
    <div class="row blocks g-2">
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="{{ route('personal_information') }}" class="block_item">
                <div class="d-flex justify-content-center mb-2">
                    <img class="image_block_item" src="{{ asset('images/me/image_1.png') }}" alt="Thông tin cá nhân">
                </div>
                <span class="tittle_block_item">{{__('me.ThongTin')}}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="{{ route('vip') }}" class="block_item">
                <div class="d-flex justify-content-center mb-2">
                    <img class="image_block_item" src="{{ asset('images/me/image_2.png') }}" alt="VIP">
                </div>
                <span class="tittle_block_item">{{__('me.Vip')}}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="javascript:void(0)" class="block_item" data-bs-toggle="modal" data-bs-target="#warehouseAddressModal">
                <div class="d-flex justify-content-center mb-2">
                    <img class="image_block_item" src="{{ asset('images/me/image_3.png') }}" alt="Địa chỉ kho">
                </div>
                <span class="tittle_block_item">{{__('me.DiaChiKho')}}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="{{ route('order') }}" class="block_item">
                <div class="d-flex justify-content-center mb-2">
                    <img class="image_block_item" src="{{ asset('images/me/image_4.png') }}" alt="Phân phối">
                </div>
                <span class="tittle_block_item">{{__('me.PhanPhoi')}}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="{{ route('balance_fluctuation') }}?tab=distribution" class="block_item">
                <div class="d-flex justify-content-center mb-2">
                    <img class="image_block_item" src="{{ asset('images/me/image_5.png') }}" alt="Biến động">
                </div>
                <span class="tittle_block_item">{{__('me.BienDong')}}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="{{ route('balance_fluctuation') }}?tab=deposit" class="block_item">
                <div class="d-flex justify-content-center mb-2">
                    <img class="image_block_item" src="{{ asset('images/me/image_6.png') }}" alt="Lịch sử nạp">
                </div>
                <span class="tittle_block_item">{{__('me.LichSuNap')}}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="{{ route('balance_fluctuation') }}?tab=withdraw" class="block_item">
                <div class="d-flex justify-content-center mb-2">
                    <img class="image_block_item" src="{{ asset('images/me/image_7.png') }}" alt="Lịch sử rút">
                </div>
                <span class="tittle_block_item">{{__('me.LichSuRut')}}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <a href="" class="block_item">
                <div class="d-flex justify-content-center mb-2">
                    <img class="image_block_item" src="{{ asset('images/me/image_8.png') }}" alt="Báo cáo nhóm">
                </div>
                <span class="tittle_block_item">{{__('me.BaoCaoNhom')}}</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="dropdown">
                <a href="javascript:void(0)" class="block_item dropdown-toggle" onclick="toggleLanguageDropdown()" id="languageDropdownButton" role="button">
                    <div class="d-flex justify-content-center mb-2">
                        <img src="{{ asset('images/me/image_9.png') }}" class="image_block_item" alt="Ngôn ngữ">
                    </div>
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
                            <img src="{{ Storage::url($lang->image) }}" width="20" height="20" class="rounded">
                            {{ $lang->name }}
                        </button>
                        @endforeach
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Logout Section -->
    <div class="d-flex justify-content-center py-4">
        <a onclick="log_out()" class="btn btn-dark">{{__('me.DangXuat')}}</a>
    </div>
</div>

<!-- Warehouse Address Modal -->
<div class="modal fade" id="warehouseAddressModal" tabindex="-1" aria-labelledby="warehouseAddressLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="warehouseAddressLabel">{{ __('me.TieuDeModalDiaChiKho') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('warehouse_address.update') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required" for="warehouse_area">{{ __('me.KhuVuc') }}</label>
                        <input type="text" class="form-control" id="warehouse_area" name="warehouse_area"
                               placeholder="{{ __('me.NhapKhuVuc') }}"
                               value="{{ old('warehouse_area', $user->warehouse_area) }}" required maxlength="191">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required" for="warehouse_address">{{ __('me.DiaChiHienTai') }}</label>
                        <textarea class="form-control" id="warehouse_address" name="warehouse_address" rows="3"
                                  placeholder="{{ __('me.NhapDiaChiHienTai') }}" required maxlength="1000">{{ old('warehouse_address', $user->warehouse_address) }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('me.Dong') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('me.Luu') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection