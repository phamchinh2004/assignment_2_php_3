<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        // Trang được khôi phục bằng nút Quay lại có thể giữ token của phiên cũ.
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    <meta name="description" content="TikTok Shop - Nền tảng mua sắm trực tuyến">
    <meta property="og:type" content="website">
    <meta property="og:title" content="TikTok Shop">
    <meta property="og:description" content="TikTok Shop - Nền tảng mua sắm trực tuyến">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/logo/tiktok-shop.webp') }}">
    <meta property="og:image:alt" content="TikTok Shop">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('images/logo/tiktok-shop.webp') }}">
    @vite ('resources/css/general.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <title>Login</title>
    <style>
        .background-blur {
            background-image: url("{{ asset('images/login_and_register/background.png') }}");
            background-repeat: repeat;
        }
    </style>
</head>

<body class="container_login_register">
    <div class="background-overlay">
        <div class="background-blur"></div>
    </div>
    <main class="auth-page">
        <section class="auth-card">
            <img class="auth-brand-logo auth-logo" src="{{ asset('images/login_and_register/tiktok-shop.webp') }}"
                alt="Cung Ứng Toàn Cầu - Global Logistics">
            <nav class="auth-tabs" aria-label="Điều hướng tài khoản">
                <a class="active" href="{{ route('login') }}">Đăng nhập</a>
                <a href="{{ route('register') }}">Đăng ký</a>
            </nav>
            <div class="auth-heading">
                <h1 class="auth-title">Chào mừng trở lại</h1>
                <p class="auth-subtitle">Đăng nhập để tiếp tục quản lý tài khoản của bạn</p>
            </div>
        <form id="form_login" method="post" action="{{ route('login_done') }}"
            autocomplete="{{ session('clear_login_form') ? 'off' : 'on' }}">
            @csrf
            @method('POST')
            <div class="auth-field">
                <label for="username_login" class="auth-label">Tên đăng nhập</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-user auth-input-icon" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="username_login" value="{{ old('username', "") }}"
                        name="username" type="text" placeholder="Nhập tên tài khoản"
                        autocomplete="{{ session('clear_login_form') ? 'off' : 'username' }}">
                </div>
                @error('username')
                    <span class="invalid-feedback">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="auth-field">
                <label for="password_login" class="auth-label">Mật khẩu</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-lock auth-input-icon" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="password_login" name="password"
                        value="{{ old('password', "") }}" type="password" placeholder="Nhập mật khẩu"
                        autocomplete="{{ session('clear_login_form') ? 'new-password' : 'current-password' }}">
                    <i class="fa-regular fa-eye auth-password-toggle cspt" id="show_password_login"></i>
                    <i hidden class="fa-regular fa-eye-slash auth-password-toggle cspt" id="hide_password_login"></i>
                </div>
                @error('password')
                    <span class="invalid-feedback">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="auth-helper d-flex justify-content-between align-items-center mt-2">
                <div class="form-check">
                    <input class="form-check-input p-2" name="remember_password" {{ old('remember_password') ? 'checked' : '' }} type="checkbox" value="1" id="remember_password">
                    <label class="form-check-label" id="label_remember_password" for="remember_password">
                        Nhớ mật khẩu
                    </label>
                </div>
                <a href="{{ route('forgot_password') }}" class="cspt"
                    id="label_forgot_password">Quên mật khẩu?</a>
            </div>
            <div class="d-grid mt-4">
                <button class="btn auth-submit" id="login" type="submit">Đăng nhập <i class="fa-solid fa-arrow-right ms-2"></i></button>
            </div>
        </form>
        <div class="auth-switch text-center mt-4">
            Bạn chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký ngay</a>
        </div>
        <div class="auth-social">
            <img class="cspt" src="{{ asset('images/login_and_register/fb-logo.png') }}" alt="Facebook">
            <img class="cspt" src="{{ asset('images/login_and_register/gg-logo.png') }}" alt="Google">
        </div>
        </section>
    </main>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"
        integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg=="
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        @if(session('clear_login_form'))
            // Ngăn trình duyệt tự điền thông tin ngay sau khi người dùng chủ động đăng xuất.
            window.addEventListener('pageshow', function () {
                const clearLoginFields = function () {
                    document.getElementById('form_login')?.reset();
                };

                clearLoginFields();
                window.setTimeout(clearLoginFields, 100);
            });
        @endif

        const route_check_username = "{{ route('check_username') }}";
        const csrf = "{{ csrf_token() }}";
        const spinner = document.getElementById('spinner');

        function notification(type, data, title, timeOut = "10000") {
            $(document).ready();
            $(function () {
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
