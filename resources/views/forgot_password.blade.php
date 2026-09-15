<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite ('resources/css/general.css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <title>Forgot password</title>
    <style>
        .background-blur {
            background-image: url("{{ asset('images/login_and_register/background.png') }}");
            background-repeat: repeat;
        }

        .forgot-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .forgot-card {
            width: min(100%, 460px);
            padding: 38px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 24px;
            background: rgba(18, 18, 22, 0.82);
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.42);
            backdrop-filter: blur(18px);
        }

        .forgot-logo {
            width: min(170px, 65vw);
            margin: 0 auto 28px;
        }

        .forgot-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            margin: 0 auto 18px;
            border-radius: 18px;
            color: #fff;
            background: linear-gradient(135deg, #fe2c55, #ff7a59);
            box-shadow: 0 10px 24px rgba(254, 44, 85, 0.28);
            font-size: 24px;
        }

        .forgot-title {
            margin: 0;
            color: #fff;
            font-size: clamp(26px, 6vw, 34px);
            font-weight: 700;
            text-align: center;
        }

        .forgot-description {
            max-width: 340px;
            margin: 10px auto 28px;
            color: rgba(255, 255, 255, 0.68);
            font-size: 14px;
            line-height: 1.6;
            text-align: center;
        }

        .forgot-label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            font-weight: 600;
        }

        .forgot-input-wrap {
            position: relative;
        }

        .forgot-input-wrap i {
            position: absolute;
            top: 50%;
            left: 16px;
            z-index: 1;
            color: #fe2c55;
            transform: translateY(-50%);
        }

        .forgot-input {
            min-height: 52px;
            padding-left: 46px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 12px;
            color: #fff;
            background: rgba(255, 255, 255, 0.09);
        }

        .forgot-input::placeholder {
            color: rgba(255, 255, 255, 0.45);
        }

        .forgot-input:focus {
            border-color: #fe2c55;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 4px rgba(254, 44, 85, 0.16);
            color: #fff;
        }

        .forgot-submit {
            min-height: 52px;
            border: 0;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, #fe2c55, #ff5c70);
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(254, 44, 85, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .forgot-submit:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(254, 44, 85, 0.35);
        }

        .forgot-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 13px;
            text-decoration: none;
        }

        .forgot-back:hover {
            color: #fff;
        }

        @media (max-width: 480px) {
            .forgot-card {
                padding: 30px 22px;
                border-radius: 20px;
            }
        }
    </style>
</head>

<body class="container_login_register">
    <div class="background-overlay">
        <div class="background-blur"></div>
    </div>
    <main class="forgot-page">
        <section class="forgot-card">
            <img class="auth-brand-logo forgot-logo" src="{{ asset('images/login_and_register/tiktok-shop.webp') }}"
                alt="Cung Ứng Toàn Cầu - Global Logistics">
            <div class="forgot-icon" aria-hidden="true">
                <i class="fa-solid fa-key"></i>
            </div>
            <h1 class="forgot-title">Quên mật khẩu?</h1>
            <p class="forgot-description">
                Nhập email đã đăng ký. Chúng tôi sẽ gửi mật khẩu mới đến địa chỉ này.
            </p>

            <form action="{{ route('send_new_password') }}" method="post" id="forgot-password-form">
            @csrf
            @method('post')
            <div>
                <label for="email" class="forgot-label">Email đăng ký</label>
                <div class="forgot-input-wrap">
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    <input type="email" id="email" class="form-control forgot-input" value="{{ old('email') }}"
                        name="email" placeholder="you@example.com" autocomplete="email" required>
                </div>
                @error('email')
                    <span class="invalid-feedback">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="d-grid mt-4">
                <button type="submit" class="btn forgot-submit" id="forgot-submit-button">
                    <span id="forgot-submit-label">Gửi mật khẩu mới <i class="fa-solid fa-arrow-right ms-2"></i></span>
                    <span id="forgot-submit-loading" class="d-none">
                        <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                        Đang gửi...
                    </span>
                </button>
            </div>
            </form>
            <div class="text-center">
                <a class="forgot-back" href="{{ route('login') }}">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại đăng nhập
                </a>
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
        const csrf = "{{ csrf_token() }}";
        const spinner = document.getElementById('spinner');

        document.getElementById('forgot-password-form')?.addEventListener('submit', function () {
            const submitButton = document.getElementById('forgot-submit-button');
            const submitLabel = document.getElementById('forgot-submit-label');
            const submitLoading = document.getElementById('forgot-submit-loading');

            submitButton.disabled = true;
            submitLabel.classList.add('d-none');
            submitLoading.classList.remove('d-none');
        });

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