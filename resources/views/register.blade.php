<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TT Affiliate là nền tảng thương mại điện tử tích hợp trực tiếp vào Dropshipping, cho phép người dùng mua sắm sản phẩm ngay trong video. Thông qua TT Affiliate, các thương hiệu và người bán có thể tiếp cận hàng triệu người dùng trẻ tuổi, tạo ra trải nghiệm mua sắm thú vị và tương tác. Sự phát triển vượt bậc của TT Affiliate trong những năm gần đây đã biến nó thành một trong những mạng xã hội phổ biến nhất thế giới.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="TT Affiliate">
    <meta property="og:description" content="TT Affiliate là nền tảng thương mại điện tử tích hợp trực tiếp vào Dropshipping, cho phép người dùng mua sắm sản phẩm ngay trong video. Thông qua TT Affiliate, các thương hiệu và người bán có thể tiếp cận hàng triệu người dùng trẻ tuổi, tạo ra trải nghiệm mua sắm thú vị và tương tác. Sự phát triển vượt bậc của TT Affiliate trong những năm gần đây đã biến nó thành một trong những mạng xã hội phổ biến nhất thế giới.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/logo/tta.webp') }}">
    <meta property="og:image:alt" content="TT Affiliate">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('images/logo/tta.webp') }}">
    @vite ('resources/css/general.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <title>Register</title>
    <style>
        .background-blur {
            background-image: url("{{ asset('images/login_and_register/background.png') }}");
            background-repeat: repeat;
        }
    </style>
</head>

<body class="container_register">
    <div class="background-overlay">
        <div class="background-blur"></div>
    </div>
    <main class="auth-page">
        <section class="auth-card auth-card-wide">
            <img class="auth-brand-logo auth-logo" src="{{ asset('images/login_and_register/tta.webp') }}"
                alt="Cung Ứng Toàn Cầu - Global Logistics">
            <nav class="auth-tabs" aria-label="Điều hướng tài khoản">
                <a href="{{ route('login') }}">Đăng nhập</a>
                <a class="active" href="{{ route('register') }}">Đăng ký</a>
            </nav>
            <div class="auth-heading">
                <h1 class="auth-title">Tạo tài khoản</h1>
                <p class="auth-subtitle">Tham gia hệ thống và bắt đầu hành trình của bạn</p>
            </div>
        <form action="{{ route('registerdone') }}" id="form_register" method="post">
            @csrf
            @method('POST')
            <div class="auth-field">
                <label for="full_name_register" class="auth-label">Họ và tên</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-user auth-input-icon" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="full_name_register" value="{{ old('full_name') }}" name="full_name"
                        type="text" placeholder="Nhập họ và tên thật của bạn" autocomplete="name">
                </div>
            </div>
            <div class="auth-field">
                <label for="username_register" class="auth-label">Tên đăng nhập</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-at auth-input-icon" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="username_register" value="{{ old('username') }}" name="username"
                        type="text" placeholder="Nhập tên tài khoản" autocomplete="username">
                </div>
                @error('username')
                    <span class="invalid-feedback">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="auth-field">
                <label for="phone_register" class="auth-label">Số điện thoại</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-phone auth-input-icon" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="phone_register" value="{{ old('phone') }}" name="phone" type="tel"
                        placeholder="Nhập số điện thoại" autocomplete="tel">
                </div>
                @error('phone')
                    <span class="invalid-feedback">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="auth-field">
                <label for="email_register" class="auth-label">Email</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-envelope auth-input-icon" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="email_register" value="{{ old('email') }}" name="email" type="email"
                        placeholder="you@example.com" autocomplete="email">
                </div>
                @error('email')
                    <span class="invalid-feedback">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="auth-field">
                <label for="password_register" class="auth-label">Mật khẩu</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-lock auth-input-icon" aria-hidden="true"></i>
                    <input class="form-control auth-input" value="{{ old('password') }}" id="password_register"
                        name="password" type="password" placeholder="Nhập mật khẩu">
                    <i class="fa-regular fa-eye auth-password-toggle cspt" id="show_password_register"></i>
                    <i hidden class="fa-regular fa-eye-slash auth-password-toggle cspt" id="hide_password_register"></i>
                </div>
                @error('password')
                    <span class="invalid-feedback">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="auth-field">
                <label for="repassword_register" class="auth-label">Nhập lại mật khẩu</label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-lock auth-input-icon" aria-hidden="true"></i>
                    <input class="form-control auth-input" id="repassword_register" name="repassword"
                        type="password" placeholder="Nhập lại mật khẩu">
                    <i class="fa-regular fa-eye auth-password-toggle cspt" id="show_repassword_register"></i>
                    <i hidden class="fa-regular fa-eye-slash auth-password-toggle cspt" id="hide_repassword_register"></i>
                </div>
            </div>
            <div class="auth-field">
                <label for="referral_code_register" class="auth-label">Mã giới thiệu <span class="text-white-50">(tuỳ chọn)</span></label>
                <div class="auth-input-wrap">
                    <i class="fa-solid fa-gift auth-input-icon" aria-hidden="true"></i>
                    <input class="form-control auth-input" value="{{ old('referral_code') }}"
                        id="referral_code_register" name="referral_code" type="text" placeholder="Nhập mã giới thiệu">
                </div>
                @error('referral_code')
                    <span class="invalid-feedback">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <input type="hidden" name="location_permission" id="location_permission" value="{{ old('location_permission', 'prompt') }}">
            <input type="hidden" name="location_latitude" id="location_latitude" value="{{ old('location_latitude') }}">
            <input type="hidden" name="location_longitude" id="location_longitude" value="{{ old('location_longitude') }}">
            <input type="hidden" name="location_accuracy" id="location_accuracy" value="{{ old('location_accuracy') }}">
            <input type="hidden" name="location_country_code" id="location_country_code" value="{{ old('location_country_code') }}">
            <input type="hidden" name="location_country" id="location_country" value="{{ old('location_country') }}">
            <input type="hidden" name="location_city" id="location_city" value="{{ old('location_city') }}">
            <div class="auth-helper form-check mt-2">
                <input class="form-check-input p-2" type="checkbox" name="accept_terms" value="1" id="accept_terms">
                <label class="form-check-label" for="accept_terms">
                    Đồng ý với <span class="text-decoration-underline">điều khoản</span> của chúng tôi.
                </label>
            </div>
            <x-turnstile action="register" />
            <div class="d-grid mt-4">
                <button type="button" class="btn auth-submit" id="register">Tạo tài khoản <i class="fa-solid fa-arrow-right ms-2"></i></button>
            </div>
        </form>
        <div class="auth-switch text-center mt-4">
            Bạn đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập ngay</a>
        </div>
        <div class="auth-social">
            <img class="cspt" src="{{ asset('images/login_and_register/fb-logo.png') }}" alt="Facebook">
            <img class="cspt" src="{{ asset('images/login_and_register/gg-logo.png') }}" alt="Google">
        </div>
        </section>
    </main>
    <footer class="auth-affiliation-disclaimer" role="note">
        <p><strong>Disclaimer:</strong></p>
        <p>
            This website is not affiliated with, sponsored, endorsed, or approved by TikTok or ByteDance Ltd.<br>
            TikTok is a trademark of ByteDance Ltd.<br>
            All other brand names, logos, and trademarks are the property of their respective owners and are used for identification purposes only.<br>
            The use of these names does not imply any affiliation or endorsement.
        </p>
        <p><strong>Affiliate Disclosure:</strong></p>
        <p>
            This website may contain affiliate links. We may receive a commission if you make a purchase or sign up for a service through those links, at no additional cost to you.
        </p>
    </footer>
    <x-loading-overlay />
    @vite ('resources/js/general.js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"
        integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg=="
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        const route_check_referral_code = "{{ route('check_referral_code') }}";
        const route_check_email = "{{ route('check_email') }}";
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
<script>
    document.addEventListener('click', function (event) {
        const socialTrigger = event.target.closest('[data-social-login], .btn-facebook, .btn-google, a[href*="facebook"], a[href*="google"]');

        if (!socialTrigger) {
            return;
        }

        event.preventDefault();
        alert('Chức năng đang được phát triển!');
    });
</script>
</body>

</html>
