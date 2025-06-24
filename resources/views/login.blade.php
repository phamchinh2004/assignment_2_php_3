<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" href="{{ asset('css/general.css') }}"> -->
    @vite ('resources/css/general.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <title>Login</title>
</head>

<body class="container_login_register">
    <div class="background-overlay">
        <div class="background-blur"></div>
    </div>
    <div class="d-flex flex-column align-items-center">
        <!-- Nội dung đăng nhập -->
        <div class="around_logo_lr">
            <img src="{{ asset('images/login_and_register/logo_2.png') }}" alt="">
        </div>
        <div class="title_gereral d-flex justify-content-center align-items-center">
            <a class="fw-bold me-5 text-white cspt title-login">Đăng Nhập</a>
            <a href="register" class="text-white cspt">Đăng Ký</a>
        </div>
        <form class="d-flex flex-column mt-4" id="form_login" method="post">
            @csrf
            @method('POST')
            <div>
                <label for="" class="text-white">Tên đăng nhập</label>
                <div class="position-relative w-auto">
                    <i class="fa-solid fa-user position-absolute icon-input" style="top: 50%;"></i>
                    <input class="form-control input-text" id="username_login" value="{{ old('username',"") }}" name="username" type="text" placeholder="Nhập tên tài khoản">
                </div>
                @error('username')
                <span class="invalid-feedback">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="mt-3">
                <label for="" class="text-white">Mật khẩu</label>
                <div class="position-relative w-auto">
                    <i class="fa-solid fa-lock position-absolute icon-input"></i>
                    <input class="form-control input-text" id="password_login" name="password" value="{{ old('password',"") }}" type="password" placeholder="Nhập mật khẩu">
                    <i class="fa-regular fa-eye position-absolute cspt" id="show_password_login"></i>
                    <i hidden class="fa-regular fa-eye-slash position-absolute cspt" id="hide_password_login"></i>
                </div>
                @error('password')
                <span class="invalid-feedback">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input p-2" name="remember_password" {{ old('remember_password') ? 'checked' : '' }} type="checkbox" value="" id="remember_password">
                    <label class="form-check-label text-white" style="font-size: 14px;" for="remember_password">
                        Nhớ mật khẩu
                    </label>
                </div>
                <span class="text-white text-decoration-underline cspt" style="font-size: 14px;">Quên mật khẩu?</span>
            </div>
            <div class="mt-3 d-flex justify-content-center">
                <button class="btn btn-warning fw-bold text-white w-100" id="login" type="button">Đăng nhập</button>
            </div>
        </form>
        <div class="mt-3">
            <span class="text-white">Bạn chưa có tài khoản? <a href="register" class="fw-bold text-warning cspt text-decoration-underline">Đăng ký</a> ngay!</span>
        </div>
        <div class="mt-3 w-auto d-flex flex-row justify-content-center align-items-center">
            <img width="40px" class="me-3 cspt" src="{{ asset('images/login_and_register/fb-logo.png') }}" alt="">
            <img width="45px" class="cspt" src="{{ asset('images/login_and_register/gg-logo.png') }}" alt="">
        </div>

    </div>
    @vite ('resources/js/general.js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/26096abf41.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.js.map"></script>
    <script>
        const route_check_username = "{{ route('check_username') }}";
        const csrf = "{{ csrf_token() }}";

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