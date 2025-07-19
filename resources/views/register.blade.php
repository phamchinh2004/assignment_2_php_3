<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" href="{{ asset('css/general.css') }}"> -->
    @vite ('resources/css/general.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}" type="image/x-icon">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <title>Register</title>
</head>

<body class="container_register">
    <div class="background-overlay">
        <div class="background-blur"></div>
    </div>
    <div class="d-flex flex-column align-items-center">
        <!-- Nội dung đăng nhập -->
        <div class="around_logo_lr">
            <img src="{{ asset('images/login_and_register/logo_2.png') }}" alt="">
        </div>
        <div class="title_gereral d-flex justify-content-center align-items-center">
            <a href="login" class="me-5 text-white cspt">Đăng Nhập</a>
            <a class="fw-bold text-white cspt title-register">Đăng Ký</a>
        </div>
        <form class="d-flex flex-column mt-4" id="form_register" method="post">
            @csrf
            @method('POST')
            <div>
                <label for="" class="text-white label-register">Họ và tên</label>
                <input class="form-control" id="full_name_register" value="{{ old('full_name') }}" name="full_name" type="text" placeholder="Nhập họ và tên thật của bạn">
            </div>
            <div class="mt-2">
                <label for="" class="text-white label-register">Tên đăng nhập</label>
                <input class="form-control" id="username_register" value="{{ old('username') }}" name="username" type="text" placeholder="Nhập tên tài khoản">
                @error('username')
                <span class="invalid-feedback">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="mt-2">
                <label for="" class="text-white label-register">Số điện thoại</label>
                <input class="form-control" id="phone_register" value="{{ old('phone') }}" name="phone" type="number" placeholder="Nhập số điện thoại">
                @error('phone')
                <span class="invalid-feedback">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="mt-2">
                <label for="" class="text-white label-register">Email</label>
                <input class="form-control" id="email_register" value="{{ old('email') }}" name="email" type="email" placeholder="Nhập email của bạn">
                @error('email')
                <span class="invalid-feedback">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="mt-2">
                <label for="" class="text-white label-register">Mật khẩu</label>
                <div class="position-relative w-auto">
                    <input class="form-control input-text-register" value="{{ old('password') }}" id="password_register" name="password" type="password" placeholder="Nhập mật khẩu">
                    <i class="fa-regular fa-eye position-absolute cspt" id="show_password_register"></i>
                    <i hidden class="fa-regular fa-eye-slash position-absolute cspt" id="hide_password_register"></i>
                </div>
                @error('password')
                <span class="invalid-feedback">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="mt-2">
                <label for="" class="text-white label-register">Nhập lại mật khẩu</label>
                <div class="position-relative w-auto">
                    <input class="form-control input-text-register" id="repassword_register" name="repassword" type="password" placeholder="Nhập lại mật khẩu">
                    <i class="fa-regular fa-eye position-absolute cspt" id="show_repassword_register"></i>
                    <i hidden class="fa-regular fa-eye-slash position-absolute cspt" id="hide_repassword_register"></i>
                </div>
            </div>
            <div class="mt-2">
                <label for="" class="text-white label-register">Mã giới thiệu</label>
                <div class="position-relative w-auto">
                    <input class="form-control input-text-register" value="{{ old('referral_code') }}" id="referral_code_register" name="referral_code" type="text" placeholder="Nhập mã giới thiệu">
                </div>
                @error('referral_code')
                <span class="invalid-feedback">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="form-check mt-2">
                <input class="form-check-input p-2" type="checkbox" name="accept_terms" value="" id="accept_terms">
                <label class="form-check-label text-white" style="font-size: 14px;" for="accept_terms">
                    Đồng ý với <span class="text-decoration-underline">điều khoản</span> của chúng tôi.
                </label>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                <button type="button" class="btn btn-warning fw-bold text-white w-100" id="register">Đăng ký</button>
            </div>
        </form>
        <div class="mt-3">
            <span class="text-white">Bạn đã có tài khoản? <a href="login" class="fw-bold text-warning cspt text-decoration-underline">Đăng nhập</a> ngay!</span>
        </div>
        <div class="mt-3 w-auto d-flex flex-row justify-content-center align-items-center other-login">
            <img width="40px" class="me-3 cspt" src="{{ asset('images/login_and_register/fb-logo.png') }}" alt="">
            <img width="45px" class="cspt" src="{{ asset('images/login_and_register/gg-logo.png') }}" alt="">
        </div>
    </div>
    <!-- SPINNER -->
    <div class="absolute-spinner" id="spinner" hidden>
        <div class="lds-spinner">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
    @vite ('resources/js/general.js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/26096abf41.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.js.map"></script>
    <script>
        const route_check_referral_code = "{{ route('check_referral_code') }}";
        const route_check_email = "{{ route('check_email') }}";
        const csrf = "{{ csrf_token() }}";
        const spinner = document.getElementById('spinner');

        function notification(type, data, title, timeOut = "10000") {
            $(document).ready();
            $(function() {
                Command: toastr[type](data, title);
                toastr.options = {
                    closeButton: true,
                    debug: false,
                    newestOnTop: true,
                    progressBar: true,
                    positionClass: "toast-top-right",
                    preventDuplicates: true,
                    onclick: null,
                    showDuration: "300",
                    hideDuration: "1000",
                    timeOut: timeOut,
                    extendedTimeOut: "1000",
                    showEasing: "swing",
                    hideEasing: "linear",
                    showMethod: "fadeIn",
                    hideMethod: "fadeOut",
                };
            });
        };
        @if(session('success'))
        var message = @json(session('success'));
        notification('success', message, 'Thông báo!');
        @elseif(session('error'))
        var message = @json(session('error'));
        notification('error', message, 'Thông báo!');
        @elseif(session('warning'))
        var message = @json(session('warning'));
        notification('warning', message, 'Cảnh báo!');
        @endif
    </script>
</body>

</html>