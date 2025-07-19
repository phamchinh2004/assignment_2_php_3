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
    <title>Forgot password</title>
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
        <form action="{{ route('send_new_password') }}" class="mt-2" method="post">
            @csrf
            @method('post')
            <div class="mt-2">
                <label for="" class="text-white">Email</label>
                <input type="email" class="form-control" value="{{ old('email') }}" name="email" placeholder="Nhập email đã dùng để đăng ký tài khoản">
                @error('email')
                <span class="invalid-feedback">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="d-flex justify-content-center mt-2">
                <button type="submit" class="btn btn-warning btn-sm">Gửi mã</button>
            </div>
        </form>

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