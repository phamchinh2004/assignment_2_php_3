@extends('admin.layouts.master')
@section('title')
Chỉnh sửa người dùng
@endsection

@section('style-libs')
<link href="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
@vite('resources/css/admin/user/edit.css')
@endsection

@section('script-libs')
<script src="{{ asset('theme/admin/vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('theme/admin/vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
@vite('resources/js/admin/user/edit.js')
<script src="{{ asset('theme/admin/js/demo/datatables-demo.js') }}"></script>
<script>
    window.currentPermissionCode = "quan_ly_tat_ca_nguoi_dung";
</script>
@endsection

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="mb-4">
        <a href="{{route('user.index')}}" class="btn-back">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
    </div>

    <div class="user-edit-container">
        <div class="form-card">
            <div class="form-header">
                <h5>
                    <i class="fas fa-user-edit me-2"></i>
                    Chỉnh sửa người dùng: <span class="user-name">{{ $user->name }}</span>
                </h5>
            </div>

            <div class="form-body">
                <form action="{{ route('user.update',['user'=>$user->id]) }}" method="post" enctype="multipart/form-data" id="form">
                    @csrf
                    @method('PUT')

                    <!-- Thông tin cá nhân -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-user me-2"></i>Thông tin cá nhân
                        </div>

                        <div class="row row-cols-custom">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="full_name">Họ và tên <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" id="full_name"
                                        value="{{ old('full_name',$user->full_name) }}"
                                        class="form-control form-control-custom"
                                        placeholder="Nhập họ và tên thật">
                                    @error('full_name')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="username">Tên đăng nhập <span class="text-danger">*</span></label>
                                    <input type="text" name="username" id="username"
                                        value="{{ old('username',$user->username) }}"
                                        class="form-control form-control-custom"
                                        placeholder="Nhập tên đăng nhập">
                                    @error('username')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group-custom">
                            <label for="phone">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="number" name="phone" id="phone"
                                value="{{ old('phone',$user->phone) }}"
                                class="form-control form-control-custom"
                                placeholder="Nhập số điện thoại">
                            @error('phone')
                            <small class="error-message">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Thông tin ngân hàng -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-university me-2"></i>Thông tin ngân hàng
                        </div>

                        <div class="form-group-custom">
                            <label for="username_bank">Tên tài khoản ngân hàng</label>
                            <input type="text" name="username_bank" id="username_bank"
                                value="{{ old('username_bank',$user->username_bank) }}"
                                class="form-control form-control-custom"
                                placeholder="Nhập tên tài khoản ngân hàng">
                            @error('username_bank')
                            <small class="error-message">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="row row-cols-custom">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="bank_name">Tên ngân hàng</label>
                                    <select name="bank_name" id="bank_name" class="">
                                        <option value="">--- Chọn ngân hàng ---</option>
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
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="account_number">Số tài khoản</label>
                                    <input type="number" name="account_number" id="account_number"
                                        value="{{ old('account_number',$user->account_number) }}"
                                        class="form-control form-control-custom"
                                        placeholder="Nhập số tài khoản">
                                    @error('account_number')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin tài khoản -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-cog me-2"></i>Cài đặt tài khoản
                        </div>

                        <div class="row row-cols-custom">
                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="balance">Số dư</label>
                                    <input type="number" name="balance" id="balance"
                                        value="{{ old('balance',$user->balance?:0) }}"
                                        class="form-control form-control-custom"
                                        placeholder="Nhập số dư">
                                    @error('balance')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="rank">Cấp độ</label>
                                    <select name="rank" id="rank" class="form-control form-select-custom">
                                        <option value="">--- Chọn cấp độ ---</option>
                                        @if (!empty($list_ranks))
                                        @foreach ($list_ranks as $rank)
                                        <option value="{{ $rank['id'] }}" {{ $rank['id']==$user->rank_id?"selected":"" }}>
                                            {{ $rank['name']}} - Có {{ $rank['spin_count']}} đơn hàng
                                        </option>
                                        @endforeach
                                        @endif
                                    </select>
                                    @error('rank')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="checkbox-card">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" name="reset_progress" id="reset_progress" class="form-check-input me-3">
                                <label class="form-check-label" for="reset_progress">
                                    <i class="fas fa-sync-alt me-2"></i>Làm mới tiến trình
                                </label>
                            </div>
                        </div>

                        <div class="checkbox-card">
                            <div class="d-flex align-items-center">
                                <input type="checkbox" name="clone_account" id="clone_account" class="form-check-input me-3" {{$user->clone_account?'checked':''}}>
                                <label class="form-check-label" for="clone_account">
                                    <i class="fas fa-clone me-2"></i>Tài khoản clone
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Button submit -->
                    <div class="d-flex justify-content-center mt-4">
                        <button class="btn btn-submit" type="button" id="btn_submit">
                            <i class="fas fa-save me-2"></i>Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection