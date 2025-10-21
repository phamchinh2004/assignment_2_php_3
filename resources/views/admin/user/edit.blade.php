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

                        <div class="row row-cols-custom">
                            <div class="col-md-6">
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

                            <div class="col-md-6">
                                <div class="form-group-custom">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email"
                                        value="{{ old('email',$user->email) }}"
                                        class="form-control form-control-custom"
                                        placeholder="Nhập địa chỉ email">
                                    @error('email')
                                    <small class="error-message">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
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
                                    <select name="bank_name" id="bank_name" class="" value="{{ old('bank_name',$user->bank_name) }}">
                                        <option value="">--- Chọn ngân hàng ---</option>
                                        @foreach ($banks as $group=> $options)
                                        <optgroup label="{{$group}}">
                                            @foreach ($options as $bank)
                                            <option value="{{$bank}}" {{ $user->bank_name==$bank?"selected":"" }}>{{$bank}}</option>
                                            @endforeach
                                        </optgroup>
                                        @endforeach
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