<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống</title>
    <meta name="description" content="TT Affiliate là nền tảng thương mại điện tử tích hợp trực tiếp vào ứng dụng Dropshipping, cho phép người dùng mua sắm sản phẩm ngay trong video. Thông qua TT Affiliate, các thương hiệu và người bán có thể tiếp cận hàng triệu người dùng trẻ tuổi, tạo ra trải nghiệm mua sắm thú vị và tương tác. Sự phát triển vượt bậc của TT Affiliate trong những năm gần đây đã biến nó thành một trong những mạng xã hội phổ biến nhất thế giới.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="TT Affiliate">
    <meta property="og:description" content="TT Affiliate là nền tảng thương mại điện tử tích hợp trực tiếp vào ứng dụng Dropshipping, cho phép người dùng mua sắm sản phẩm ngay trong video. Thông qua TT Affiliate, các thương hiệu và người bán có thể tiếp cận hàng triệu người dùng trẻ tuổi, tạo ra trải nghiệm mua sắm thú vị và tương tác. Sự phát triển vượt bậc của TT Affiliate trong những năm gần đây đã biến nó thành một trong những mạng xã hội phổ biến nhất thế giới.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/logo/tta.webp') }}">
    <meta property="og:image:alt" content="TT Affiliate">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="TT Affiliate">
    <meta name="twitter:description"
        content="TT Affiliate là nền tảng thương mại điện tử tích hợp trực tiếp vào Dropshipping, cho phép người dùng mua sắm sản phẩm ngay trong video. Thông qua TT Affiliate, các thương hiệu và người bán có thể tiếp cận hàng triệu người dùng trẻ tuổi, tạo ra trải nghiệm mua sắm thú vị và tương tác. Sự phát triển vượt bậc của TT Affiliate trong những năm gần đây đã biến nó thành một trong những mạng xã hội phổ biến nhất thế giới.">
    <meta name="twitter:image" content="{{ asset('images/logo/tta.webp') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        window.Laravel = {
            userId: @json(Auth::id())
        };
    </script>
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    <link rel="icon" href="{{ asset('images/logo/tta.png') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />

    @vite('resources/css/user.css')
    @vite('resources/css/general.css')
    @vite('resources/css/user/notification.css')
    @vite('resources/css/floating-chat.css')
    @yield('css-libs')
    @stack('page-styles')
    @vite('resources/css/user/tiktok-theme.css')
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
            <div class="site-affiliation-disclaimer" role="note">
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
            </div>
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
                        <img class="footer-logo" src="{{ asset('images/home/distribution_button.webp') }}"
                            alt="Trang phân phối">
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
        <div class="modal fade account-security-modal" id="changePasswordModal" tabindex="-1" role="dialog"
            aria-labelledby="changePasswordModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="account-security-modal__header">
                        <span class="account-security-modal__icon is-slate" aria-hidden="true">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <div class="account-security-modal__heading">
                            <span class="account-security-modal__eyebrow">Bảo mật tài khoản</span>
                            <h5 class="modal-title" id="changePasswordModalLabel">Đổi mật khẩu đăng nhập</h5>
                            <p>Cập nhật mật khẩu dùng để đăng nhập vào tài khoản.</p>
                        </div>
                        <button type="button" class="account-security-modal__close" data-bs-dismiss="modal"
                            aria-label="Đóng">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('change_password') }}" method="POST" id="form_change_password">
                            @csrf
                            @method("POST")
                            <div class="account-security-field">
                                <label for="present_password">Mật khẩu hiện tại</label>
                                <div class="account-security-field__control">
                                    <i class="fa-solid fa-key" aria-hidden="true"></i>
                                    <input type="password" name="present_password" id="present_password"
                                        class="form-control" placeholder="Nhập mật khẩu hiện tại"
                                        autocomplete="current-password" required>
                                </div>
                            </div>
                            <div class="account-security-field">
                                <label for="new_password">Mật khẩu mới</label>
                                <div class="account-security-field__control">
                                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                    <input type="password" name="new_password" id="new_password" class="form-control"
                                        placeholder="Nhập mật khẩu mới" autocomplete="new-password" minlength="6"
                                        required>
                                </div>
                                <small>Tối thiểu 6 ký tự.</small>
                            </div>
                            <div class="account-security-field">
                                <label for="confirm_new_password">Xác nhận mật khẩu mới</label>
                                <div class="account-security-field__control">
                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                    <input type="password" name="confirm_new_password" id="confirm_new_password"
                                        class="form-control" placeholder="Nhập lại mật khẩu mới"
                                        autocomplete="new-password" required>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="account-security-modal__button is-secondary" type="button"
                            data-bs-dismiss="modal">Hủy</button>
                        <button class="account-security-modal__button is-primary" type="button"
                            onclick="change_password()">
                            <span>Cập nhật mật khẩu</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Change Transaction Password Modal-->
        <div class="modal fade account-security-modal" id="changeTransactionPasswordModal" tabindex="-1" role="dialog"
            aria-labelledby="changeTransactionPasswordModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="account-security-modal__header">
                        <span class="account-security-modal__icon is-coral" aria-hidden="true">
                            <i class="fa-solid fa-shield-halved"></i>
                        </span>
                        <div class="account-security-modal__heading">
                            <span class="account-security-modal__eyebrow">Xác thực giao dịch</span>
                            <h5 class="modal-title" id="changeTransactionPasswordModalLabel">Đổi mật khẩu giao dịch</h5>
                            <p>Mật khẩu này được dùng khi xác nhận các thao tác tài chính.</p>
                        </div>
                        <button type="button" class="account-security-modal__close" data-bs-dismiss="modal"
                            aria-label="Đóng">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('change_transaction_password') }}" method="POST"
                            id="form_change_transaction_password">
                            @csrf
                            @method("POST")
                            <div class="account-security-field">
                                <label for="present_transaction_password">Mật khẩu giao dịch hiện tại</label>
                                <div class="account-security-field__control">
                                    <i class="fa-solid fa-key" aria-hidden="true"></i>
                                    <input type="password" name="present_transaction_password"
                                        id="present_transaction_password" class="form-control"
                                        placeholder="Nhập mật khẩu giao dịch hiện tại" autocomplete="current-password"
                                        required>
                                </div>
                            </div>
                            <div class="account-security-field">
                                <label for="new_transaction_password">Mật khẩu giao dịch mới</label>
                                <div class="account-security-field__control">
                                    <i class="fa-solid fa-shield" aria-hidden="true"></i>
                                    <input type="password" name="new_transaction_password" id="new_transaction_password"
                                        class="form-control" placeholder="Nhập mật khẩu giao dịch mới"
                                        autocomplete="new-password" minlength="6" required>
                                </div>
                                <small>Tối thiểu 6 ký tự.</small>
                            </div>
                            <div class="account-security-field">
                                <label for="confirm_new_transaction_password">Xác nhận mật khẩu mới</label>
                                <div class="account-security-field__control">
                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                    <input type="password" name="confirm_new_transaction_password"
                                        id="confirm_new_transaction_password" class="form-control"
                                        placeholder="Nhập lại mật khẩu giao dịch mới" autocomplete="new-password"
                                        required>
                                </div>
                            </div>
                        </form>

                        <button type="button" class="account-security-reset-link" id="resetTransactionPasswordLink"
                            data-reset-transaction-password>
                            <span class="account-security-reset-link__icon"><i class="fa-solid fa-rotate"
                                    aria-hidden="true"></i></span>
                            <span>
                                <strong>Quên mật khẩu giao dịch?</strong>
                                <small>Xác minh bằng mật khẩu đăng nhập để cấp lại.</small>
                            </span>
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button class="account-security-modal__button is-secondary" type="button"
                            data-bs-dismiss="modal">Hủy</button>
                        <button class="account-security-modal__button is-primary" type="button"
                            onclick="change_transaction_password()">
                            <span>Cập nhật mật khẩu</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Cấp lại mật khẩu giao dịch Modal-->
        <div class="modal fade account-security-modal account-security-modal--reset" id="resetTransactionPasswordModal"
            tabindex="-1" role="dialog" aria-labelledby="resetTransactionPasswordModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="account-security-modal__header">
                        <span class="account-security-modal__icon is-gold" aria-hidden="true">
                            <i class="fa-solid fa-rotate"></i>
                        </span>
                        <div class="account-security-modal__heading">
                            <span class="account-security-modal__eyebrow">Khôi phục truy cập</span>
                            <h5 class="modal-title" id="resetTransactionPasswordModalLabel">Cấp lại mật khẩu giao dịch
                            </h5>
                            <p>Xác minh mật khẩu đăng nhập trước khi hệ thống tạo mật khẩu giao dịch mới.</p>
                        </div>
                        <button type="button" class="account-security-modal__close" data-bs-dismiss="modal"
                            aria-label="Đóng">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="account-security-modal__notice">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            <span>Mật khẩu giao dịch mới sẽ được hiển thị sau khi xác minh thành công.</span>
                        </div>
                        <form action="{{ route('reset_transaction_password') }}" method="POST"
                            id="form_reset_transaction_password">
                            @csrf
                            @method("POST")
                            <div class="account-security-field">
                                <label for="present_login_password">Mật khẩu đăng nhập hiện tại</label>
                                <div class="account-security-field__control">
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                    <input type="password" name="present_login_password" id="present_login_password"
                                        class="form-control" placeholder="Nhập mật khẩu đăng nhập hiện tại"
                                        autocomplete="current-password" required>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button class="account-security-modal__button is-secondary" type="button"
                            data-bs-dismiss="modal">Hủy</button>
                        <button class="account-security-modal__button is-primary" type="button"
                            onclick="reset_transaction_password()">
                            <span>Xác nhận cấp lại</span><i class="fa-solid fa-rotate" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Form logout -->
        <form id="form_logout" action="{{ route('logout') }}" method="get">
            @csrf
            @method('GET')
        </form>
        <x-loading-overlay />
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"
        integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg=="
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
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
        // Không ép làm tròn theo 2 chữ số cố định; giữ giá trị thực với tối đa 8 chữ số thập phân
        function format_currency(currency, min = 0, max = 8) {
            const value = Number(currency);

            if (!Number.isFinite(value)) {
                return '$0';
            }

            const safeMax = Math.max(0, Math.min(8, Number(max) || 8));
            const safeMin = Math.max(0, Number(min) || 0);

            const formatted = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: safeMin,
                maximumFractionDigits: safeMax
            }).format(value);

            return `$${formatted}`;
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
        function playNotificationSound(soundFile = 'notification_fb.mp3') {
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
            window.userId = {{ auth()->id() }};
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
