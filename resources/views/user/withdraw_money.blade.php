@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/withdraw_money.css')
@endsection
@section('script-libs')
@vite('resources/js/user/withdraw_money.js')
@endsection
@section('content')
<div class="bg-white d-flex flex-row position-relative">
    <a class="text-dark fw-bold text-decoration-none p-2 hover btn-back" href="#" onclick="history.back(); return false;"><i class="fa fa-arrow-left fa-sm pe-1"></i>Quay lại</a>
    <h3 class="position-absolute title">Rút tiền</h3>
</div>
<div class="box_content_withdraw_money">
    <div class="pt-2 ps-4">
        <i class="text-secondary">({{ $rank->name }})</i>
        <p class="m-0 p-0 text-danger">Còn <b class="text-success">{{ $maximum_number_of_withdrawals }}</b> lần rút tiền</p>
        <p class="m-0 p-0 text-danger">Mỗi lần rút tối đa: <b class="text-success">{{ format_money($maximum_withdrawal_amount) }}$ </b></p>
    </div>
    <div class="d-flex flex-column box_content">
        <div>
            <span class="tittle_so_tien_rut">Số tiền rút</span>
        </div>
        <div class="position-relative">
            <input type="text" class="input_nhap_tien" id="amount_input_field" placeholder="Nhập số tiền rút">
            <input type="text" id="temple_amount" hidden value="{{ Auth::user()->balance }}">
            <input type="text" id="has_password" hidden value="{{ $has_password }}">
        </div>
        <div class="d-flex flex-row justify-content-between">
            <span class="so_du_tai_khoan">Số dư tài khoản: {{format_money(Auth::user()->balance)}}$</span>
            <span class="btn_rut_toan_bo cspt" id="withdraw_all">Rút toàn bộ</span>
        </div>
    </div>
    <div class="d-flex flex-column box_content mt-2">
        <table>
            <tbody>
                <tr>
                    <th>Tên chủ tài khoản</th>
                    <td><input id="username_bank" class="text-uppercase bank_infor_input text-nowrap" type="text" value="{{ $user->username_bank?$user->username_bank:"" }}"></td>
                </tr>
                <tr>
                    <th>Tên ngân hàng</th>
                    <td><input id="bank_name" class="bank_infor_input" type="text" value="{{ $user->bank_name?$user->bank_name:"" }}"></td>
                </tr>
                <tr>
                    <th>Số tài khoản</th>
                    <td><input id="account_number" class="bank_infor_input" type="text" value="{{ $user->account_number?$user->account_number:"" }}"></td>
                </tr>
                <tr>
                    <th>Mật khẩu giao dịch</th>
                    <td><input id="transaction_password" class="bank_infor_input" type="password"></td>
                </tr>
                <tr {{ $has_password?"hidden":"" }}>
                    <th>Xác nhận lại mật khẩu</th>
                    <td><input id="confirm_transaction_password" class="bank_infor_input" type="password"></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="d-flex flex-column box_content mt-2">
        <span class="luu_y_tittle">* Lưu ý:</span>
        <span class="luu_y_content">Vui lòng kiểm tra kỹ thông tin thanh toán của bạn. Việc rút tiền này chịu phí xử lý 0%</span>
    </div>
    <div class="d-flex flex-column mt-3 justify-content-center box_btn_rut_tien_ngay m-auto">
        <button class="btn btn-dark w-100" id="btn_withdraw_now">Rút tiền ngay lập tức</button>
    </div>
</div>
@endsection