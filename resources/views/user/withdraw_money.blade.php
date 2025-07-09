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
    <a class="text-dark fw-bold text-decoration-none p-2 hover btn-back" href="#" onclick="history.back(); return false;"><i class="fa fa-arrow-left fa-sm pe-1"></i>{{__('withdraw_money.QuayLai')}}</a>
    <h3 class="position-absolute title">{{__('withdraw_money.RutTien')}}</h3>
</div>
<div class="box_content_withdraw_money">
    <div class="pt-2 ps-4">
        <i class="text-secondary">({{ $rank->name }})</i>
        <p class="m-0 p-0 text-danger">{{__('withdraw_money.Con')}} <b class="text-success">{{ $maximum_number_of_withdrawals }}</b> {{__('withdraw_money.LanRutTien')}}</p>
        <p class="m-0 p-0 text-danger">{{__('withdraw_money.MoiLanRutToiDa')}} <b class="text-success">{{ format_money($maximum_withdrawal_amount) }}$ </b></p>
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
                        <select class="form-select" name="" id="select_bank_name" data-value="{{ $user->bank_name?:"" }}">
                            <option value="VPBank">VPBank</option>
                            <option value="BIDV">BIDV</option>
                            <option value="Vietcombank">Vietcombank</option>
                            <option value="VietinBank">VietinBank</option>
                            <option value="MBBANK">MBBANK</option>
                            <option value="ACB">ACB</option>
                            <option value="SHB">SHB</option>
                            <option value="Techcombank">Techcombank</option>
                            <option value="Agribank">Agribank</option>
                            <option value="Sacombank">Sacombank</option>
                            <option value="HDBank">HDBank</option>
                            <option value="LienVietPostBank">LienVietPostBank</option>
                            <option value="VIB">VIB</option>
                            <option value="SeABank">SeABank</option>
                            <option value="VBSP">VBSP</option>
                            <option value="TPBank">TPBank</option>
                            <option value="OCB">OCB</option>
                            <option value="MSB">MSB</option>
                            <option value="Eximbank">Eximbank</option>
                            <option value="SCB">SCB</option>
                            <option value="VDB">VDB</option>
                            <option value="Nam A Bank">Nam A Bank</option>
                            <option value="ABBANK">ABBANK</option>
                            <option value="PVcomBank">PVcomBank</option>
                            <option value="Bac A Bank">Bac A Bank</option>
                            <option value="UOB">UOB</option>
                            <option value="Woori">Woori</option>
                            <option value="HSBC">HSBC</option>
                            <option value="SCBVL">SCBVL</option>
                            <option value="PBVN">PBVN</option>
                            <option value="SHBVN">SHBVN</option>
                            <option value="NCB">NCB</option>
                            <option value="VietABank">VietABank</option>
                            <option value="BVBank">BVBank</option>
                            <option value="Vikki Bank">Vikki Bank</option>
                            <option value="Vietbank">Vietbank</option>
                            <option value="ANZVL">ANZVL</option>
                            <option value="MBV">MBV</option>
                            <option value="CIMB">CIMB</option>
                            <option value="Kienlongbank">Kienlongbank</option>
                            <option value="IVB">IVB</option>
                            <option value="BAOVIET Bank">BAOVIET Bank</option>
                            <option value="SAIGONBANK">SAIGONBANK</option>
                            <option value="Co-opBank">Co-opBank</option>
                            <option value="GPBank">GPBank</option>
                            <option value="VRB">VRB</option>
                            <option value="VCBNeo">VCBNeo</option>
                            <option value="HLBVN">HLBVN</option>
                            <option value="PGBank">PGBank</option>
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