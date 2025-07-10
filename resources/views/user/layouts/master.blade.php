<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amazon</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}" type="image/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.Laravel = {
            userId: @json(Auth::id())
        };
    </script>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    @vite('resources/css/user.css')
    @vite('resources/css/general.css')
    @yield('css-libs')
    @livewireStyles
</head>

<body class="m-auto">
    <div class="container-1">

        <div class="around-content">
            @yield('content')
        </div>

        <footer>
            <div class="footer text-center w-100 m-0 d-flex align-items-center">
                <a class="cspt footer-item text-dark text-decoration-none" href="{{ route('home') }}">
                    <img width="16.5px" src="{{ asset('images/footer/home.png') }}" alt="">
                    <div class="fw-bold text-footer">{{__('layout.TrangChu')}}</div>
                </a>
                <a class="cspt footer-item text-dark text-decoration-none" href="{{ route('order') }}?tab=tat-ca">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <div class="fw-bold text-footer">{{__('layout.LichSu')}}</div>
                </a>
                <a href="{{ route('distribution') }}" class="d-flex footer-item justify-content-center align-items-center p-0 cspt text-dark text-decoration-none">
                    <div class="amazon_btn d-flex justify-content-center align-items-center">
                        <img class="footer-logo" src="{{ asset('images/logo_2_white.webp') }}" alt="">
                    </div>
                </a>


                <!-- Hộp thoại -->
                @livewire('user.chat-component')
                <!-- End hộp thoại -->
                <a class="cspt footer-item text-dark text-decoration-none" href="{{ route('me') }}">
                    <i class="fa fa-regular fa-user"></i>
                    <div class="fw-bold text-footer">{{__('layout.Toi')}}</div>
                </a>
            </div>
        </footer>
        <!-- Form logout -->
        <form id="form_logout" action="{{ route('logout') }}" method="get">
            @csrf
            @method('GET')
        </form>
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
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/26096abf41.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.js.map"></script>
    @vite('resources/js/general.js')
    @yield('script-libs')
    @stack('scripts')
    <script>
        const spinner = document.getElementById('spinner');

        const csrf = "{{ csrf_token() }}";
        const route_distribution = "{{ route('distribution') }}";
        const route_balance_fluctuation = "{{ route('balance_fluctuation') }}?tab=distribution";
        const route_withdraw_money = "{{ route('withdraw_money') }}";
        const route_get_10_orders_next = "{{ route('get_10_orders_next') }}";
        const route_check_frozen_order = "{{ route('check_frozen_order') }}";
        const route_get_list_orders_by_tab = "{{ route('get_list_orders_by_tab') }}";
        const route_handle_distribution = "{{ route('handle_distribution') }}";
        const route_handle_withdraw = "{{ route('handle_withdraw') }}";
        const route_bank_link = "{{ route('bank_link') }}";

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
        // Định dạng tiền tệ
        function format_currency(currency, min = 2, max = 2) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: min, // số chữ số sau dấu phẩy
                maximumFractionDigits: max
            }).format(currency);
        }

        @if(session('success'))
        var message = @json(session('success'));
        notification('success', message, 'Thành công!');
        @elseif(session('error'))
        var message = @json(session('error'));
        notification('error', message, 'Thông báo!');
        @elseif(session('warning'))
        var message = @json(session('warning'));
        notification('warning', message, 'Cảnh báo!');
        @endif
    </script>
    <script>
        window.addEventListener('load', function() {
            @auth
            if (window.Echo) {
                window.Echo.private(`user.{{ auth()->id() }}`)
                    .listen('.UserLocked', function(e) {
                        location.href = '/log-out-by-locked';
                    });

            } else {
                console.error('Echo is not loaded');
            }
            @endauth
        });
    </script>
    @livewireScripts
</body>

</html>