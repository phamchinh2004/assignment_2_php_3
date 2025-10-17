@extends('admin.layouts.master')
@section('title')
Chỉnh sửa người dùng
@endsection

@section('style-libs')
<!-- Custom styles for this page -->
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/order/edit.css')
@endsection

@section('script-libs')
<!-- Page level plugins -->
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/user/edit.js')
<!-- Page level custom scripts -->
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_tat_ca_nguoi_dung";
</script>
@endsection

@section('content')
<!-- Begin Page Content -->
<div class="mb-2 ml-3">
    <a href="{{route('user.index')}}" class="btn btn-outline-dark btn-sm text-decoration-none"><i class="fas fa-arrow-left"></i> Quay lại</a>
</div>
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="card shadow mb-4 section_1">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h6 class="m-0 font-weight-bold text-primary" id="tittle">Sửa người dùng <i class="text-dark">{{ $user->name }}</i></h6>
            </div>
        </div>
    </div>
    <section class="container-fluid">
        <form action="{{ route('user.update',['user'=>$user->id]) }}" method="post" enctype="multipart/form-data" id="form">
            @csrf
            @method('PUT')
            <div class="mt-2 fw-bold">
                <label for="">Họ và tên</label>
                <input type="text" name="full_name" id="full_name" value="{{ old('full_name',$user->full_name) }}" class="form-control" placeholder="Nhập họ và tên thật">
                @error('full_name')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Tên đăng nhập</label>
                <input type="text" name="username" id="username" value="{{ old('username',$user->username) }}" class="form-control" placeholder="Nhập tên đăng nhập">
                @error('username')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Số điện thoại</label>
                <input type="number" name="phone" id="phone" value="{{ old('phone',$user->phone) }}" class="form-control" placeholder="Nhập số điện thoại">
                @error('phone')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Tên tài khoản ngân hàng</label>
                <input type="number" name="username_bank" id="username_bank" value="{{ old('username_bank',$user->username_bank) }}" class="form-control" placeholder="Nhập tên tài khoản ngân hàng">
                @error('username_bank')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Tên ngân hàng</label>
                <select class="" name="bank_name" id="bank_name" value="{{ old('bank_name',$user->bank_name) }}">
                    <optgroup label="Ngân hàng Việt Nam">
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
                    </optgroup>
                    <optgroup label="Ngân hàng Nhật Bản">
                        <option value="MUFG Bank (三菱UFJ銀行)">MUFG Bank (三菱UFJ銀行)</option>
                        <option value="SMBC (Sumitomo Mitsui Banking Corporation, 三井住友銀行)">SMBC (Sumitomo Mitsui Banking Corporation, 三井住友銀行)</option>
                        <option value="Mizuho Bank (みずほ銀行)">Mizuho Bank (みずほ銀行)</option>
                        <option value="Resona Bank (りそな銀行)">Resona Bank (りそな銀行)</option>
                        <option value="Shinsei Bank (新生銀行)">Shinsei Bank (新生銀行)</option>
                        <option value="Japan Post Bank (ゆうちょ銀行)">Japan Post Bank (ゆうちょ銀行)</option>
                        <option value="Rakuten Bank (楽天銀行)">Rakuten Bank (楽天銀行)</option>
                        <option value="PayPay Bank (旧ジャパンネット銀行)">PayPay Bank (旧ジャパンネット銀行)</option>
                        <option value="Sony Bank (ソニー銀行)">Sony Bank (ソニー銀行)</option>
                    </optgroup>
                    <optgroup label="Ngân hàng Đài Loan">
                        <option value="Bank of Taiwan (臺灣銀行)">Bank of Taiwan (臺灣銀行)</option>
                        <option value="Taipei Fubon Bank (台北富邦銀行)">Taipei Fubon Bank (台北富邦銀行)</option>
                        <option value="CTBC Bank/ChinaTrust (中國信託商業銀行)">CTBC Bank/ChinaTrust (中國信託商業銀行)</option>
                        <option value="Mega International Commercial Bank (兆豐國際商業銀行)">Mega International Commercial Bank (兆豐國際商業銀行)</option>
                        <option value="First Commercial Bank (第一商業銀行)">First Commercial Bank (第一商業銀行)</option>
                        <option value="Cathay United Bank (國泰世華銀行)">Cathay United Bank (國泰世華銀行)</option>
                        <option value="Taishin International Bank (台新銀行)">Taishin International Bank (台新銀行)</option>
                        <option value="Richart Digital Bank (by Taishin Bank)">Richart Digital Bank (by Taishin Bank)</option>
                        <option value="LINE Bank (by LINE & Union Bank of Taiwan)">LINE Bank (by LINE & Union Bank of Taiwan)</option>
                    </optgroup>
                    <optgroup label="Ngân hàng Hàn Quốc">
                        <option value="Kookmin Bank (KB국민은행)">Kookmin Bank (KB국민은행)</option>
                        <option value="Shinhan Bank (신한은행)">Shinhan Bank (신한은행)</option>
                        <option value="Woori Bank (우리은행)">Woori Bank (우리은행)</option>
                        <option value="Hana Bank (하나은행)">Hana Bank (하나은행)</option>
                        <option value="IBK Industrial Bank (IBK기업은행)">IBK Industrial Bank (IBK기업은행)</option>
                        <option value="NongHyup Bank (NH농협은행)">NongHyup Bank (NH농협은행)</option>
                        <option value="KakaoBank (카카오뱅크)">KakaoBank (카카오뱅크)</option>
                        <option value="Toss Bank (토스뱅크)">Toss Bank (토스뱅크)</option>
                        <option value="K Bank (케이뱅크)">K Bank (케이뱅크)</option>
                    </optgroup>
                    <optgroup label="Ngân hàng Trung Quốc">
                        <option value="ICBC (中国工商银行)">ICBC (中国工商银行)</option>
                        <option value="Bank of China (中国银行)">Bank of China (中国银行)</option>
                        <option value="China Construction Bank (中国建设银行)">China Construction Bank (中国建设银行)</option>
                        <option value="Agricultural Bank of China (中国农业银行)">Agricultural Bank of China (中国农业银行)</option>
                        <option value="China Merchants Bank (招商银行)">China Merchants Bank (招商银行)</option>
                    </optgroup>
                    <optgroup label="Ngân hàng Mỹ">
                        <option value="JPMorgan Chase Bank">JPMorgan Chase Bank</option>
                        <option value="Bank of America">Bank of America</option>
                        <option value="Wells Fargo Bank">Wells Fargo Bank</option>
                        <option value="Citibank">Citibank</option>
                        <option value="US Bank">US Bank</option>
                        <option value="PNC Bank">PNC Bank</option>
                        <option value="Capital One Bank">Capital One Bank</option>
                        <option value="TD Bank">TD Bank</option>
                        <option value="BB&T (Truist Bank)">BB&T (Truist Bank)</option>
                        <option value="SunTrust (Truist Bank)">SunTrust (Truist Bank)</option>
                    </optgroup>
                    <optgroup label="Ngân hàng Tây Ban Nha">
                        <option value="Banco Santander">Banco Santander</option>
                        <option value="BBVA (Banco Bilbao Vizcaya Argentaria)">BBVA (Banco Bilbao Vizcaya Argentaria)</option>
                        <option value="CaixaBank">CaixaBank</option>
                        <option value="Bankia">Bankia</option>
                        <option value="Banco Sabadell">Banco Sabadell</option>
                        <option value="Banco Popular Español">Banco Popular Español</option>
                    </optgroup>
                </select>
                @error('bank_name')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Số tài khoản</label>
                <input type="number" name="account_number" id="account_number" value="{{ old('account_number',$user->account_number) }}" class="form-control" placeholder="Nhập số tài khoản">
                @error('account_number')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Số dư</label>
                <input type="number" name="balance" id="balance" value="{{ old('balance',$user->balance?:0) }}" class="form-control" placeholder="Nhập số dư">
                @error('balance')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-2 fw-bold">
                <label for="">Chọn cấp độ</label>
                <select name="rank" id="" class="form-select">
                    <option value="">--- Chọn cấp độ ---</option>
                    @if (!empty($list_ranks))
                    @foreach ($list_ranks as $rank)
                    <option value="{{ $rank['id'] }}"
                        {{ $rank['id']==$user->rank_id?"selected":"" }}>
                        {{ $rank['name']}} - Có
                        {{ $rank['spin_count']}} đơn hàng
                    </option>
                    @endforeach
                    @endif
                </select>
                @error('rank')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="mt-3 fw-bold form-check">
                <input type="checkbox" name="reset_progress" id="reset_progress" class="form-check-input" style="transform: scale(1.5);">
                <label class="form-check-label nsl ms-2" for="reset_progress">Làm mới tiến trình</label>
            </div>
            <div class="mt-3 fw-bold form-check">
                <input type="checkbox" name="clone_account" id="clone_account" class="form-check-input" style="transform: scale(1.5);" {{$user->clone_account?'checked':''}}>
                <label class="form-check-label nsl ms-2" for="clone_account">Tài khoản clone</label>
            </div>
            <div class="d-flex mt-3 justify-content-center">
                <button class="btn btn-warning" type="button" id="btn_submit">Xong</button>
            </div>
        </form>
    </section>
</div>

@endsection