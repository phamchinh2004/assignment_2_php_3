<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('images/logo.png') }}" type="image/x-icon">
    <title>@yield('title')</title>

    <!-- Custom fonts for this template-->
    <link href="{{ asset('theme/admin/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="{{ asset('theme/admin/css/sb-admin-2.min.css') }}" rel="stylesheet">

    <!-- Notification library -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />

    <!-- Slimselect -->
    <link href="https://unpkg.com/slim-select@latest/dist/slimselect.css" rel="stylesheet">
    </link>
    <script>
        window.Laravel = {
            userId: @json(Auth::id()),
        };
    </script>
    <!-- RateYo -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.css">
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @vite('resources/css/general.css')
    @vite('resources/css/admin/general.css')
    @yield('style-libs')
    @stack('css')
    @livewireStyles
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        @include('admin.layouts.sidebar')
        <!-- End of Sidebar -->
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <div class="arround_topbar">
                    @include('admin.layouts.header')
                </div>
                <!-- End of Topbar -->
                @yield('content')

            </div>
            <!-- Footer -->
            @include('admin.layouts.footer')
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
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
    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" onclick="log_out()">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Change Password Modal-->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Đổi mật khẩu</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('change_password') }}" method="POST" id="form_change_password">
                        @csrf
                        @method("POST")
                        <div class="form-group">
                            <label for="">Mật khẩu hiện tại</label>
                            <input type="password" name="present_password" id="present_password" class="form-control" placeholder="Nhập mật khẩu hiện tại!" required>
                        </div>
                        <div class="form-group">
                            <label for="">Mật khẩu mới</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Nhập mật khẩu mới!" required>
                        </div>
                        <div class="form-group">
                            <label for="">Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" placeholder="Xác nhận mật khẩu mới!" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Thôi đéo đổi nữa</button>
                    <a class="btn btn-primary" onclick="change_password()">Xác nhận đổi</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Form logout -->
    <form id="form_logout" action="{{ route('logout') }}" method="get">
        @csrf
        @method('GET')
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js" integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg==" crossorigin="anonymous"></script>
    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('theme/admin/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('theme/admin/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ asset('theme/admin/vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('theme/admin/js/sb-admin-2.min.js') }}"></script>

    <!-- Page level plugins -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Page level custom scripts -->
    <script src="{{ asset('theme/admin/js/demo/chart-area-demo.js') }}"></script>
    <script src="{{ asset('theme/admin/js/demo/chart-pie-demo.js') }}"></script>
    <script src="https://kit.fontawesome.com/be9ed8669f.js" crossorigin="anonymous"></script>

    <!-- Notification library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.js.map"></script>
    <!-- Notification function -->
    <script>
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
        const csrf = "{{ csrf_token() }}";
        const route_index_order = "{{ route('order.index') }}";
        const route_create_order = "{{ route('order.store') }}";
        const route_check_username = "{{ route('check_username') }}";
        const route_check_phone = "{{ route('check_phone') }}";
        const route_plus_money = "{{ route('plus_money') }}";
        const route_change_status_permission = "{{ route('staff.change.status.permission') }}";
        const route_api_statistical_revenue = "{{ route('api.statistical.revenue') }}";

        const route_api_staff_list = "{{ route('api.staff.list') }}";
        const route_api_revenue_by_staff = "{{ route('api.revenue.by.staff') }}";
        const route_api_revenue_detail = "{{ route('api.revenue.detail') }}";

        const route_change_password = "{{ route('change_password') }}";
    </script>

    <!-- Slim Select -->
    <script src="https://unpkg.com/slim-select@latest/dist/slimselect.min.js">
        new SlimSelect({
            select: '#selectElement'
        })
    </script>


    <!-- Tinymce -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.0.0/tinymce.min.js"></script>
    <script>
        tinymce.init({
            selector: '#sectionContent',
            plugins: 'anchor autolink charmap codesample emoticons link lists media searchreplace table visualblocks wordcount',
            automatic_uploads: true,
            license_key: 'gpl'
        });

        function format_currency(currency) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2, // số chữ số sau dấu phẩy
                maximumFractionDigits: 2
            }).format(currency);
        }
    </script>

    <!-- Link libs -->
    @vite('resources/js/general.js')
    @yield('script-libs')

    <!-- Short notification commands -->

    <script>
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
    <!-- SPINNER -->
    <script>
        const spinner = document.getElementById('spinner');
    </script>
    <script>
        window.addEventListener('load', function() {
            @auth
            if (window.Echo) {
                window.Echo.private(`join.conversation`)
                    .listen('.UserJoinChat', function(e) {
                        notification('warning', 'Người dùng ' + e.full_name + ' (' + e.username + ') đã tham gia hội thoại! ', 'Tham gia hội thoại!', 100000);
                        playNotificationSound(1, 3, 500);
                    });
                window.Echo.private(`staff.{{ auth()->id() }}`)
                    .listen('.StaffLocked', function(e) {
                        location.href = '/log-out-by-locked';
                    });
                window.Echo.private(`staff.{{ auth()->id() }}`)
                    .listen('.PermissionRevoked', (e) => {
                        const currentPermission = window.currentPermissionCode;
                        if (e.revokedPermissionCode === currentPermission) {
                            if (currentPermission == "quan_ly_tat_ca_nguoi_dung" || currentPermission == "quan_ly_tat_ca_giao_dich_nguoi_dung") {
                                location.reload();
                            } else {
                                window.location.href = "/";
                            }
                        }
                    });
            } else {
                console.error('Echo is not loaded');
            }
            @endauth
        });
    </script>
    @livewireScripts
    @stack('scripts')
</body>

</html>