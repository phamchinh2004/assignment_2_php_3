@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/withdraw_money.css')
@endsection
@section('script-libs')
<script>
    const trans = {
        VuiLongNhapSoTienRut: @json(__('withdraw_money.VuiLongNhapSoTienRut')),
        CanhBao: @json(__('withdraw_money.CanhBao')),
        VuiLongNhapDayDuThongTinNganHang: @json(__('withdraw_money.VuiLongNhapDayDuThongTinNganHang')),
        XacNhanMatKhauGiaoDichKhongKhop: @json(__('withdraw_money.XacNhanMatKhauGiaoDichKhongKhop')),
        ThanhCong: @json(__('withdraw_money.ThanhCong')),
    };
</script>
@vite('resources/js/user/withdraw_money.js')
@endsection
@section('content')
<div class="bg-white d-flex flex-row position-relative">
    <a class="text-dark fw-bold text-decoration-none p-2 hover btn-back" href="#" onclick="history.back(); return false;">
        <i class="fa fa-arrow-left fa-sm"></i>
        <span>{{__('withdraw_money.QuayLai')}}</span>
    </a>
    <h3 class="position-absolute title">💰 {{__('withdraw_money.RutTien')}}</h3>
</div>
<div class="box_content_withdraw_money">
    <!-- Rank Info Card with Progress -->
    <div class="rank-info-card">
        <div class="rank-header">
            <div class="rank-badge">
                <i class="fas fa-crown"></i>
                <span>{{ $rank->name }}</span>
            </div>
            <div class="rank-level">Cấp độ {{ $rank->id }}</div>
        </div>
        
        <div class="rank-stats">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Lượt rút tiền còn lại</div>
                    <div class="stat-value">{{ $maximum_number_of_withdrawals }} lượt</div>
                </div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Hạn mức mỗi lần rút</div>
                    <div class="stat-value">{{ format_money($maximum_withdrawal_amount) }}$</div>
                </div>
            </div>
            
            <div class="stat-item stat-highlight">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Tiến độ hoàn thành đơn hàng</div>
                    <div class="stat-value progress-value">
                        <span class="current">{{ $current_orders }}</span>
                        <span class="separator">/</span>
                        <span class="total">{{ $total_orders }}</span>
                        <span class="unit">đơn</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: {{ $total_orders > 0 ? ($current_orders / $total_orders * 100) : 0 }}%"></div>
                        </div>
                        <div class="progress-percentage">{{ $total_orders > 0 ? round($current_orders / $total_orders * 100) : 0 }}%</div>
                    </div>
                </div>
            </div>
        </div>
        
        @php
            $remaining_orders = max(0, $total_orders - $current_orders);
            $can_withdraw = $current_orders >= $total_orders;
        @endphp
        
        <!-- Remaining Orders Alert -->
        @if(!$can_withdraw && $remaining_orders > 0)
        <div class="remaining-orders-alert">
            <div class="alert-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="alert-content">
                <div class="alert-title">Cần hoàn thành thêm đơn hàng</div>
                <div class="alert-message">
                    Bạn cần hoàn thành thêm <strong>{{ $remaining_orders }} đơn hàng</strong> nữa để đủ điều kiện rút tiền.
                </div>
                <a href="{{ route('distribution') }}" class="alert-action">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Đi phân phối ngay</span>
                </a>
            </div>
        </div>
        @elseif($can_withdraw)
        <div class="success-alert">
            <div class="alert-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="alert-content">
                <div class="alert-title">🎉 Chúc mừng!</div>
                <div class="alert-message">
                    Bạn đã hoàn thành đủ <strong>{{ $total_orders }} đơn hàng</strong>. Bạn có thể rút tiền ngay bây giờ!
                </div>
            </div>
        </div>
        @endif
    </div>
    <div class="d-flex flex-column box_content">
        <div>
            <span class="tittle_so_tien_rut">{{__('withdraw_money.SoTienRut')}}</span>
        </div>
        <div class="position-relative">
            <input type="text" class="input_nhap_tien" id="amount_input_field" placeholder="{{__('withdraw_money.NhapSoTienRut')}}">
            <input type="text" id="temple_amount" hidden value="{{ Auth::user()->balance }}">
            <input type="text" id="has_password" hidden value="{{ $has_password }}">
        </div>
        <div class="d-flex flex-row justify-content-between">
            <span class="so_du_tai_khoan">{{__('withdraw_money.SoDuTaiKhoan')}} {{format_money(Auth::user()->balance)}}$</span>
            <span class="btn_rut_toan_bo cspt" id="withdraw_all">{{__('withdraw_money.RutToanBo')}}</span>
        </div>
    </div>
    <div class="d-flex flex-column box_content mt-2">
        <table>
            <tbody>
                <tr>
                    <th>{{__('withdraw_money.TenChuTaiKhoan')}}</th>
                    <td><input id="username_bank" class="text-uppercase bank_infor_input text-nowrap" type="text" value="{{ $user->username_bank?$user->username_bank:"" }}"></td>
                </tr>
                <tr>
                    <th>{{__('withdraw_money.TenNganHang')}}</th>
                    <td>
                        <select class="" name="" id="select_bank_name" data-value="{{ $user->bank_name?:"" }}">
                            <option value="">--- Chọn ngân hàng ---</option>
                            @foreach ($banks as $group=> $options)
                            <optgroup label="{{$group}}">
                                @foreach ($options as $bank)
                                <option value="{{$bank}}" {{ $user->bank_name==$bank?"selected":"" }}>{{$bank}}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>{{__('withdraw_money.SoTaiKhoan')}}</th>
                    <td><input id="account_number" class="bank_infor_input" type="text" value="{{ $user->account_number?$user->account_number:"" }}"></td>
                </tr>
                <tr>
                    <th>{{__('withdraw_money.MatKhauGiaoDich')}}</th>
                    <td><input id="transaction_password" class="bank_infor_input" type="password"></td>
                </tr>
                <tr {{ $has_password?"hidden":"" }}>
                    <th>{{__('withdraw_money.XacNhanLaiMatKhau')}}</th>
                    <td><input id="confirm_transaction_password" class="bank_infor_input" type="password"></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-column box_content mt-2">
        <span class="luu_y_tittle">{{__('withdraw_money.LuuY')}}</span>
        <span class="luu_y_content">{{__('withdraw_money.VuiLongKiemTra')}}</span>
    </div>
    <div class="d-flex flex-column mt-3 justify-content-center box_btn_rut_tien_ngay m-auto">
        <button class="btn btn-dark w-100" id="btn_withdraw_now">{{__('withdraw_money.RutTienNgayLapTuc')}}</button>
    </div>
</div>
@endsection