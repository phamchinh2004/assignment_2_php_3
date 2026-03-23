<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.Laravel = {
            userId: @json(Auth::id())
        };
    </script>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    <link rel="icon" href="data:,">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />

    @vite('resources/css/user.css')
    @vite('resources/css/general.css')
    @vite('resources/css/user/notification.css')
    @vite('resources/css/floating-chat.css')
    @yield('css-libs')
    @livewireStyles
    <style>
        /* Smooth scroll for better UX */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>

<body class="m-auto">
    <div class="container-1">

        <div class="around-content">
            @yield('content')
        </div>

        <footer>
            <div class="footer text-center w-100 m-0 d-flex align-items-center">
                <a class="cspt footer-item text-dark text-decoration-none" href="{{ route('home') }}">
                    <i class="fa-solid fa-house"></i>
                    <div class="fw-bold text-footer">{{__('layout.TrangChu')}}</div>
                </a>
                <a class="cspt footer-item text-dark text-decoration-none" href="{{ route('order') }}?tab=tat-ca">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <div class="fw-bold text-footer">{{__('layout.LichSu')}}</div>
                </a>
                <a href="{{ route('distribution') }}"
                    class="d-flex footer-item justify-content-center align-items-center p-0 cspt text-dark text-decoration-none">
                    <div class="amazon_btn d-flex justify-content-center align-items-center">
                        <i class="fa-solid fa-box-open" style="font-size: 28px; color: white;"></i>
                    </div>
                </a>

                <!-- Thống kê giao dịch -->
                <a class="cspt footer-item text-dark text-decoration-none" href="{{ route('balance_fluctuation') }}">
                    <i class="fa-solid fa-chart-line"></i>
                    <div class="fw-bold text-footer">{{__('layout.ThongKe')}}</div>
                </a>

                <a class="cspt footer-item text-dark text-decoration-none" href="{{ route('me') }}">
                    <i class="fa fa-regular fa-user"></i>
                    <div class="fw-bold text-footer">{{__('layout.Toi')}}</div>
                </a>
            </div>
        </footer>

        <!-- Floating Chat Bubble -->
        @livewire('user.chat-component')
        <!-- End Floating Chat Bubble -->

        <!-- Change Password Modal-->
        <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Đổi mật khẩu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                    </div>
                    <div class="modal-body">
                        <form action="{{ route('change_password') }}" method="POST" id="form_change_password">
                            @csrf
                            @method("POST")
                            <div class="form-group">
                                <label for="">Mật khẩu hiện tại</label>
                                <input type="password" name="present_password" id="present_password"
                                    class="form-control" placeholder="Nhập mật khẩu hiện tại!" required>
                            </div>
                            <div class="form-group">
                                <label for="">Mật khẩu mới</label>
                                <input type="password" name="new_password" id="new_password" class="form-control"
                                    placeholder="Nhập mật khẩu mới!" required>
                            </div>
                            <div class="form-group">
                                <label for="">Xác nhận mật khẩu mới</label>
                                <input type="password" name="confirm_new_password" id="confirm_new_password"
                                    class="form-control" placeholder="Xác nhận mật khẩu mới!" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Hủy</button>
                        <a class="btn btn-primary" onclick="change_password()">Xác nhận đổi</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Change Transaction Password Modal-->
        <div class="modal fade" id="changeTransactionPasswordModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Đổi mật khẩu giao dịch hoặc <a
                                id="resetTransactionPasswordLink" data-bs-toggle="modal"
                                data-bs-target="#resetTransactionPasswordModal" href="#">cấp lại mật khẩu!</a></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('change_transaction_password') }}" method="POST"
                            id="form_change_transaction_password">
                            @csrf
                            @method("POST")
                            <div class="form-group">
                                <label for="">Mật khẩu hiện tại</label>
                                <input type="password" name="present_transaction_password"
                                    id="present_transaction_password" class="form-control"
                                    placeholder="Nhập mật khẩu giao dịch hiện tại!" required>
                            </div>
                            <div class="form-group">
                                <label for="">Mật khẩu mới</label>
                                <input type="password" name="new_transaction_password" id="present_transaction_password"
                                    class="form-control" placeholder="Nhập mật khẩu giao dịch mới!" required>
                            </div>
                            <div class="form-group">
                                <label for="">Xác nhận mật khẩu mới</label>
                                <input type="password" name="confirm_new_transaction_password"
                                    id="confirm_new_transaction_password" class="form-control"
                                    placeholder="Xác nhận mật khẩu giao dịch mới!" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Hủy</button>
                        <a class="btn btn-primary" onclick="change_transaction_password()">Xác nhận đổi</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Cấp lại mật khẩu giao dịch Modal-->
        <div class="modal fade" id="resetTransactionPasswordModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Cấp lại mật khẩu giao dịch!</a></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('reset_transaction_password') }}" method="POST"
                            id="form_reset_transaction_password">
                            @csrf
                            @method("POST")
                            <div class="form-group">
                                <label for="">Mật khẩu đăng nhập hiện tại</label>
                                <input type="password" name="present_login_password" id="present_login_password"
                                    class="form-control" placeholder="Nhập mật khẩu đăng nhập hiện tại!" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Hủy</button>
                        <a class="btn btn-primary" onclick="reset_transaction_password()">Xác nhận cấp lại</a>
                    </div>
                </div>
            </div>
        </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"
        integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg=="
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.js.map"></script>
    @vite('resources/js/general.js')
    @vite('resources/js/user/footer-active.js')

    <script>
        const spinner = document.getElementById('spinner');

        const csrf = "{{ csrf_token() }}";
        const route_distribution = "{{ route('distribution') }}";
        const route_balance_fluctuation = "{{ route('balance_fluctuation') }}?tab=distribution";
        const route_withdraw_money = "{{ route('withdraw_money') }}";
        const route_get_10_orders_next = "{{ route('get_10_orders_next') }}";
        const route_check_frozen_order = "{{ route('check_frozen_order') }}";
        const route_get_list_orders_by_tab = "{{ route('get_list_orders_by_tab') }}";
        const route_accept_order = "{{ route('accept_order') }}";
        const route_order = "{{ route('order') }}";
        const route_handle_withdraw = "{{ route('handle_withdraw') }}";
        const route_handle_withdraw_frozen = "{{ route('handle_withdraw_frozen') }}";
        const route_bank_link = "{{ route('bank_link') }}";

        const route_change_password = "{{ route('change_password') }}";
        const route_change_transaction_password = "{{ route('change_transaction_password') }}";
        const route_reset_transaction_password = "{{ route('reset_transaction_password') }}";

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
        // Định dạng tiền tệ
        function format_currency(currency, min = 2, max = 4) {
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
            // ===== HỆ THỐNG NOTIFICATION =====

            // Function phát âm thanh notification
            function playNotificationSound(soundFile = 'notification.mp3') {
                try {
                    // Tạo audio element mới mỗi lần để tránh conflict
                    const audio = new Audio('/audio/' + soundFile);
                    audio.volume = 1.0;

                    // Play âm thanh
                    const playPromise = audio.play();

                    if (playPromise !== undefined) {
                        playPromise.catch(() => {
                            // Ignore error - user may need to interact with page first
                        });
                    }
                } catch (error) {
                    // Ignore error
                }
            }

        // Define userId for notification system
        @auth
            const userId = {{ auth()->id() }};
        @endauth

        window.addEventListener('load', function () {
            @auth
                    if (window.Echo) {
                    window.Echo.private(`user.{{ auth()->id() }}`)
                        .listen('.UserLocked', function (e) {
                            location.href = '/log-out-by-locked';
                        });

                } else {
                    console.error('Echo is not loaded');
                }
            @endauth
        });
    </script>
    @yield('script-libs')
    @stack('scripts')
    @vite('resources/js/user/notification.js')
    @livewireScripts
</body>

</html>