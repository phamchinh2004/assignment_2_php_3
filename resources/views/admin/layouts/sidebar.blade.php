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
        <a class="nav-link" href="{{ route('tong.doanh.thu') }}">
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
                <a class="collapse-item" href="{{ route('tong.doanh.thu') }}">Tổng doanh thu</a>
                <a class="collapse-item" href="{{ route('doanh.thu.theo.nhan.vien') }}">Doanh thu nhân viên</a>
                <a class="collapse-item" href="{{ route('doanh.thu.tu.khach.hang') }}">Doanh thu từ khách hàng</a>
                <a class="collapse-item" href="{{ route('doanh.thu.ban.than') }}">Doanh thu bản thân</a>
            </div>
        </div>
    </li>
    @endif
    {{-- Quản lý tin nhắn --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('chat-panel') }}" data-target="#collapseChatbox" aria-expanded="true" aria-controls="collapseChatbox">
            <!-- <i class="fa-regular fa-comment-dots text-light fa-5xl"></i> -->
            <!-- <img src="{{ asset('images/admin/icons/users1.svg') }}" alt="img"> -->
            <i class="fa-solid fa-message text-white"></i>
            <span>Quản lý tin nhắn</span>
        </a>
        <!-- <div id="collapseChatbox" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('chat-panel') }}">Danh sách</a>
            </div>
        </div> -->
    </li>
    {{-- Quản lý vouchers --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('user.index') }}" data-target="#collapseVouchers" aria-expanded="true" aria-controls="collapseVouchers">
            <img src="{{ asset('images/admin/icons/users1.svg') }}" alt="img">
            <span>Quản lý khách hàng</span>
        </a>
        <!-- <div id="collapseVouchers" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('user.index') }}">Danh sách</a>
                <a class="collapse-item" href="{{ route('user.create') }}">Thêm</a>
            </div>
        </div> -->
    </li>

    {{-- Quản lý đơn hàng --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseOrder" aria-expanded="true" aria-controls="collapseOrder">
            <!-- <img src="{{ asset('images/admin/icons/sales1.svg') }}" alt="img"> -->
            <i class="fa-solid fa-arrow-right-arrow-left text-white"></i>
            <span>Quản lý GDKH</span>
        </a>
        <div id="collapseOrder" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('withdraw_transaction') }}">Rút tiền</a>
                <a class="collapse-item" href="{{ route('deposit_transaction') }}">Nạp tiền</a>
            </div>
        </div>
    </li>

    {{-- Quản lý nhân viên --}}
    @if (Auth::user()->role==="admin")
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('staff.index') }}" data-target="#collapseRatings" aria-expanded="true" aria-controls="collapseRatings">
            <!-- <i class="fa-regular fa-comment-dots text-light fa-5xl"></i> -->
            <!-- <img src="{{ asset('images/admin/icons/users1.svg') }}" alt="img"> -->
            <i class="fa-solid fa-user-nurse" style="color: #ffffff;"></i>
            <span>Quản lý nhân viên</span>
        </a>
        <!-- <div id="collapseRatings" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('staff.index') }}">Danh sách</a>
            </div>
        </div> -->
    </li>
    @endif
    <!-- Quản lý đơn hàng -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('order.index') }}" data-target="#collapseUtilities" aria-expanded="true" aria-controls="collapseUtilities">
            <img src="{{ asset('images/admin/icons/product.svg') }}" alt="img">
            <span>Quản lý đơn hàng</span>
        </a>
        <!-- <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('order.index') }}">Danh sách</a>
                <a class="collapse-item" href="{{ route('order.create') }}">Thêm</a>
            </div>
        </div> -->
    </li>

    <!-- Đơn hàng bị báo cáo -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('order_reports.index') }}">
            <i class="fa-solid fa-flag text-white"></i>
            <span>Đơn hàng bị báo cáo</span>
        </a>
    </li>
    
    @if (Auth::user()->role === 'admin')
    {{-- Cấu hình thời gian chuyển trạng thái đơn hàng --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('admin.order_status_timing.index') }}" data-target="#collapseOrderTiming" aria-expanded="true" aria-controls="collapseOrderTiming">
            <i class="fa-solid fa-clock text-white"></i>
            <span>Cấu hình thời gian đơn hàng</span>
        </a>
    </li>
    @endif
    <!-- Quản lý cấp độ -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('rank.index') }}" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
            <!-- <img src="{{ asset('images/admin/icons/category.svg') }}" alt="img"> -->
            <i class="fa-solid fa-ranking-star text-white"></i>
            <span>Quản lý cấp độ</span>
        </a>
        <!-- <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng:</h6>
                <a class="collapse-item" href="{{ route('rank.index') }}">Danh sách</a>
                <a class="collapse-item" href="{{ route('rank.create') }}">Thêm</a>
            </div>
        </div> -->
    </li>

    {{-- Quản lý banner --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route("banner.index") }}" data-target="#collapseBanners" aria-expanded="true" aria-controls="collapseBanners">
            <img src="{{ asset('images/admin/icons/banner.svg') }}" alt="img">

            <span>Quản lý banner</span>
        </a>
        <!-- <div id="collapseBanners" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route("banner.index") }}">Danh sách</a>
                <a class="collapse-item" href="{{ route("banner.create") }}">Thêm</a>
            </div>
        </div> -->
    </li>


    {{-- Quản lý section --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('section.index') }}" data-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
            <img src="{{ asset('images/admin/icons/attribute.svg') }}" alt="img">
            <span>Quản lý nội dung trên trang web</span>
        </a>
        <!-- <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng:</h6>
                <a class="collapse-item" href="{{ route('section.index') }}">Danh sách</a>
            </div>
        </div> -->
    </li>



    {{-- quản lý thương hiệu(brand) --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('partner.index') }}" data-target="#collapseBrands" aria-expanded="true" aria-controls="collapseBrands">
            <!-- <img src="{{ asset('images/admin/icons/brand.svg') }}" alt="img"> -->
            <i class="fa-solid fa-handshake text-white"></i>
            <span>Quản lý đối tác</span>
        </a>
        <!-- <div id="collapseBrands" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('partner.index') }}">Danh sách</a>
                <a class="collapse-item" href="{{ route('partner.create') }}">Thêm</a>
            </div>
        </div> -->
    </li>

    {{-- Quản lý khách hàng --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('language.index') }}" data-target="#collapseCustomers" aria-expanded="true" aria-controls="collapseCustomers">
            <!-- <img src="{{ asset('images/admin/icons/language.png') }}" alt="img"> -->
            <i class="fa-solid fa-language" style="color: #ffffff;"></i>
            <span>Quản lý ngôn ngữ</span>
        </a>
        <!-- <div id="collapseCustomers" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('language.index') }}">Danh sách</a>
                <a class="collapse-item" href="{{ route('language.create') }}">Thêm</a>
            </div>
        </div> -->
    </li>

    @if (Auth::user()->role === 'admin')
    {{-- Quản lý manager setting --}}
    <li class="nav-item">
        <a class="nav-link collapsed" href="{{ route('manager_setting.index') }}" data-target="#collapseManagers" aria-expanded="true" aria-controls="collapseManagers">
            <img src="{{ asset('images/admin/icons/function.svg') }}" alt="img">
            <span>Quản lý chức năng</span>
        </a>
        <!-- <div id="collapseManagers" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Danh sách chức năng</h6>
                <a class="collapse-item" href="{{ route('manager_setting.index') }}">Danh sách</a>
                <a class="collapse-item" href="{{ route('manager_setting.create') }}">Thêm</a>
            </div>
        </div> -->
    </li>
    @endif

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