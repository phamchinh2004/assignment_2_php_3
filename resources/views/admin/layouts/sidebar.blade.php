<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Amazon</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item active">
        <a class="nav-link" href="">
            <img src="{{ asset('images/admin/icons/dashboard.svg') }}" alt="img">
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Interface
    </div>
    @if (Auth::user()->role === 'admin')
    <!-- Thống kê -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseStatistics" aria-expanded="true" aria-controls="collapseStatistics">
            <img src="{{ asset('images/admin/icons/statistical.svg') }}" alt="img">
            <span>Thống kê</span>
        </a>
        <div id="collapseStatistics" class="collapse" aria-labelledby="headingStatistics" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng:</h6>
                <a class="collapse-item" href="">Doanh thu sản phẩm</a>
                <a class="collapse-item" href="">Doanh thu thương hiệu</a>
                <a class="collapse-item" href="">Doanh thu khách hàng</a>
                <a class="collapse-item" href="">Sản phẩm giỏ hàng</a>
                <a class="collapse-item" href="">Lượt xem sản phẩm</a>
            </div>
        </div>
    </li>
    @endif
    {{-- Quản lý tin nhắn --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseChatbox" aria-expanded="true" aria-controls="collapseChatbox">
            <!-- <i class="fa-regular fa-comment-dots text-light fa-5xl"></i> -->
            <img src="{{ asset('images/admin/icons/users1.svg') }}" alt="img">
            <span>Quản lý tin nhắn</span>
        </a>
        <div id="collapseChatbox" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('chat-panel') }}">Danh sách</a>
            </div>
        </div>
    </li>
    {{-- Quản lý vouchers --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseVouchers" aria-expanded="true" aria-controls="collapseVouchers">
            <img src="{{ asset('images/admin/icons/users1.svg') }}" alt="img">
            <span>Quản lý khách hàng</span>
        </a>
        <div id="collapseVouchers" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('user.index') }}">Danh sách</a>
                <a class="collapse-item" href="{{ route('user.create') }}">Thêm</a>
            </div>
        </div>
    </li>

    {{-- Quản lý đơn hàng --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseOrder" aria-expanded="true" aria-controls="collapseOrder">
            <img src="{{ asset('images/admin/icons/sales1.svg') }}" alt="img">
            <span>Quản lý giao dịch khách hàng</span>
        </a>
        <div id="collapseOrder" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('withdraw_transaction') }}">Rút tiền</a>
                <a class="collapse-item" href="{{ route('deposit_transaction') }}">Nạp tiền</a>
            </div>
        </div>
    </li>

    {{-- Quản lý đánh giá --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseRatings" aria-expanded="true" aria-controls="collapseRatings">
            <!-- <i class="fa-regular fa-comment-dots text-light fa-5xl"></i> -->
            <img src="{{ asset('images/admin/icons/users1.svg') }}" alt="img">
            <span>Quản lý nhân viên</span>
        </a>
        <div id="collapseRatings" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('staff.index') }}">Danh sách</a>
            </div>
        </div>
    </li>
    <!-- Quản lý đơn hàng -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities" aria-expanded="true" aria-controls="collapseUtilities">
            <img src="{{ asset('images/admin/icons/product.svg') }}" alt="img">
            <span>Quản lý đơn hàng</span>
        </a>
        <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('order.index') }}">Danh sách</a>
                <a class="collapse-item" href="{{ route('order.create') }}">Thêm</a>
            </div>
        </div>
    </li>
    <!-- Quản lý cấp độ -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
            <img src="{{ asset('images/admin/icons/category.svg') }}" alt="img">
            <span>Quản lý cấp độ</span>
        </a>
        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng:</h6>
                <a class="collapse-item" href="{{ route('rank.index') }}">Danh sách</a>
                <a class="collapse-item" href="{{ route('rank.create') }}">Thêm</a>
            </div>
        </div>
    </li>
    {{-- Liên hệ --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseContacts" aria-expanded="true" aria-controls="collapseContacts">
            <i class="fa-solid fa-phone text-light fa-5xl"></i>
            <span>Quản lý chức năng phân quyền hệ thống</span>
        </a>
        <div id="collapseContacts" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('manager_setting.index') }}">Danh sách</a>
            </div>
        </div>
    </li>

    {{-- Quản lý thuộc tính --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
            <img src="{{ asset('images/admin/icons/attribute.svg') }}" alt="img">
            <span>Quản lý thuộc tính</span>
        </a>
        <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng:</h6>
                {{-- <a class="collapse-item" href="">Gía trị thuộc tính</a> --}}
                <a class="collapse-item" href="">Thuộc tính</a>
                <a class="collapse-item" href="">Loại thuộc tính</a>
                {{-- <a class="collapse-item" href="">Thêm dl thuộc tính</a> --}}

            </div>
        </div>
    </li>

    {{-- Quản lý banner --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBanners" aria-expanded="true" aria-controls="collapseBanners">
            <img src="{{ asset('images/admin/icons/banner.svg') }}" alt="img">

            <span>Quản lý banner</span>
        </a>
        <div id="collapseBanners" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="">Danh sách</a>
                <a class="collapse-item" href="">Thêm</a>
            </div>
        </div>
    </li>

    {{-- quản lý thương hiệu(brand) --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseBrands" aria-expanded="true" aria-controls="collapseBrands">
            <img src="{{ asset('images/admin/icons/brand.svg') }}" alt="img">
            <span>Quản lý thương hiệu</span>
        </a>
        <div id="collapseBrands" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="">Danh sách</a>
                <a class="collapse-item" href="">Thêm</a>
            </div>
        </div>
    </li>

    {{-- Quản lý khách hàng --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseCustomers" aria-expanded="true" aria-controls="collapseCustomers">
            <img src="{{ asset('images/admin/icons/users1.svg') }}" alt="img">
            <span>Quản lý khách hàng</span>
        </a>
        <div id="collapseCustomers" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="">Danh sách</a>
            </div>
        </div>
    </li>

    {{-- Kiểm tra nếu là admin --}}
    @if (Auth::user()->role === 'admin')
    {{-- Quản lý nhân viên --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseStaffs" aria-expanded="true" aria-controls="collapseStaffs">
            <img src="" alt="img">
            <span>Quản lý nhân viên</span>
        </a>
        <div id="collapseStaffs" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="">Danh sách</a>
                <a class="collapse-item" href="">Thêm</a>
            </div>
        </div>
    </li>

    {{-- Quản lý manager setting --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseManagers" aria-expanded="true" aria-controls="collapseManagers">
            <img src="{{ asset('images/admin/icons/function.svg') }}" alt="img">
            <span>Quản lý chức năng</span>
        </a>
        <div id="collapseManagers" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="">Danh sách</a>
                <a class="collapse-item" href="">Thêm</a>
            </div>
        </div>
    </li>
    @endif

    {{-- Lịch sử nhập hàng --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseHistory" aria-expanded="true" aria-controls="collapseHistory">
            <img src="{{ asset('images/admin/icons/import.svg') }}" alt="img">
            <span>Lịch sử nhập hàng</span>
        </a>
        <div id="collapseHistory" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="">Danh sách</a>
                {{-- <a class="collapse-item" href="">Thêm</a> --}}
            </div>
        </div>
    </li>

    {{-- <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        Addons
    </div>

    <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages"
           aria-expanded="true" aria-controls="collapsePages">
            <i class="fas fa-fw fa-folder"></i>
            <span>Pages</span>
        </a>
        <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Login Screens:</h6>
                <a class="collapse-item" href="login.html">Login</a>
                <a class="collapse-item" href="register.html">Register</a>
                <a class="collapse-item" href="forgot-password.html">Forgot Password</a>
                <div class="collapse-divider"></div>
                <h6 class="collapse-header">Other Pages:</h6>
                <a class="collapse-item" href="404.html">404 Page</a>
                <a class="collapse-item" href="blank.html">Blank Page</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Charts -->
    <li class="nav-item">
        <a class="nav-link" href="charts.html">
            <i class="fas fa-fw fa-chart-area"></i>
            <span>Charts</span></a>
    </li>

    <!-- Nav Item - Tables -->
    <li class="nav-item">
        <a class="nav-link" href="tables.html">
            <i class="fas fa-fw fa-table"></i>
            <span>Tables</span></a>
    </li> --}}

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

    <!-- Sidebar Message -->
    {{-- <div class="sidebar-card d-none d-lg-flex">
        <img class="sidebar-card-illustration mb-2" src="{{asset('theme/admin/img/undraw_rocket.svg')}}" alt="...">
    <p class="text-center mb-2"><strong>SB Admin Pro</strong> is packed with premium features, components, and more!</p>
    <a class="btn btn-success btn-sm" href="https://startbootstrap.com/theme/sb-admin-pro">Upgrade to Pro!</a>
    </div> --}}

</ul>