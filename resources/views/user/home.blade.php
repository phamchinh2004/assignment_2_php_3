@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/home.css')
@vite('resources/css/user/winwheel/main.css')
@endsection
@section('script-libs')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
@vite('resources/js/user/home.js')
@endsection

@section('content')
<div id="fireworks-container"></div>

<div>
    <!-- Thông báo -->
    <div class="w-100 noti-top bg-white position-absolute d-flex align-items-center ps-2 pe-2">
        <marquee class="text-center p-1">Hệ thống Amazon SHOP đang triển khai "Chương Trình Khuyến Mãi Lớn Ngày Lễ 30/4-1/5 Thể lệ chương trình
            "Gian Hàng Ghép Đôi". *1. Thưởng ngay 88 USD cho mỗi cặp đôi đăng ký và mở thành công
            "Gian Hàng Ghép Đôi". *2. Một trong hai bạn sẽ có cơ hội nhận được "Đơn thưởng đặc biệt"
            ngẫu nhiên từ hệ thống, bạn sẽ nhận được từ 15%-30% hoa hồng giá trị đơn thưởng. =>
            Lưu Ý: Chương trình khuyến mãi chỉ áp dụng cho thành viên "Cấp Bậc Vàng " trở lên và
            mỗi tài khoản chỉ được ghép đôi một lần duy nhất.
        </marquee>
    </div>
    <!-- Các nút -->
    <div class="w-100 ps-4 pe-4 section-1 d-flex align-items-center justify-content-between">

        <div class="w-25 position-relative d-flex align-items-center justify-content-center flex-column cspt" id="btn_phan_phoi">
            <div class="position-absolute item-section-1">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/logo_4.png') }}" alt="">
            </div>
            <div class="display">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/display.png') }}" alt="">
            </div>
            <span class="text-white tittle-section-1">Phân phối</span>
        </div>
        <div class="w-25 position-relative d-flex align-items-center justify-content-center flex-column cspt" id="btn_bien_dong_so_du">
            <div class="position-absolute item-section-1">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/logo_1.png') }}" alt="">
            </div>
            <div class="display">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/display.png') }}" alt="">
            </div>
            <span class="text-white tittle-section-1">Biến động số dư</span>
        </div>
        <div class="w-25 position-relative d-flex align-items-center justify-content-center flex-column cspt" id="btn_nap_tien">
            <div class="position-absolute item-section-1">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/logo_2.png') }}" alt="">
            </div>
            <div class="display">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/display.png') }}" alt="">
            </div>
            <span class="text-white tittle-section-1">Nạp tiền</span>
        </div>
        <div class="w-25 position-relative d-flex align-items-center justify-content-center flex-column cspt" id="btn_rut_tien">
            <div class="position-absolute item-section-1">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/logo_3.png') }}" alt="">
            </div>
            <div class="display">
                <img class="image-section-1" width="50px" src="{{ asset('images/home/display.png') }}" alt="">
            </div>
            <span class="text-white tittle-section-1">Rút tiền</span>
        </div>

    </div>
    <!-- Banner -->
    <div id="carouselExampleAutoplaying" class="carousel slide mt-4" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img class="banner-image" src="{{ asset('images/home/banner_1.webp') }}" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
                <img class="banner-image" src="{{ asset('images/home/banner_2.webp') }}" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
                <img class="banner-image" src="{{ asset('images/home/banner_3.webp') }}" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
                <img class="banner-image" src="{{ asset('images/home/banner_4.webp') }}" class="d-block w-100" alt="...">
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
    <!-- Vòng quay may mắn -->
    <div class="section-3" id="section-3">
        <div class="position-relative">
            <span class="text-white fw-bold text-title">Vòng quay may mắn</span>
            <img class="title-image" src="{{ asset('images/home/title.png') }}" alt="">
        </div>
        <div class="mainbox m-auto vong-quay" id="mainbox" hidden>
            <div class="box" id="box">
                <div class="box1">
                    <span class="font span1"><b>Đơn hàng 1</b></span>
                    <span class="font span2"><b>Đơn hàng 2</b></span>
                    <span class="font span3"><b>Đơn hàng 3</b></span>
                    <span class="font span4"><b>Đơn hàng 4</b></span>
                    <span class="font span5"><b>Đơn hàng 5</b></span>
                </div>
                <div class="box2">
                    <span class="font span1"><b>Đơn hàng 6</b></span>
                    <span class="font span2"><b>Đơn hàng 7</b></span>

                    <span class="font span3"><b>Đơn hàng 8</b></span>
                    <span class="font span4"><b>Đơn hàng 9</b></span>
                    <span class="font span5"><b>Đơn hàng 10</b></span>
                </div>
            </div>
            <button class="spin" id="spin" onclick="spin()">SPIN</button>
        </div>
        <audio
            controls="controls"
            id="applause"
            src="{{asset('audio/applause.mp3')}}"
            type="audio/mp3"></audio>
        <audio
            controls="controls"
            id="wheel"
            src="{{asset('audio/wheel.mp3')}}"
            type="audio/mp3"></audio>
    </div>
    <div class="dark_surface" id="order_award" hidden>
        <div class="" id="order">
            <div class="order_bg">
                <div class="div_img">
                    <img src="{{ asset('images/home/congratulations.png') }}" alt="">
                </div>
                <div class="order_details">
                    <div class="order_details_vien">
                        <div class="div_img_cho_xu_ly">
                            <img src="{{ asset('images/nhan_cho_xu_ly.png') }}" alt="">
                        </div>
                        <span class="order_details_time" id="order_details_time">Thời gian đặt phân phối: </span>
                        <div class="order_details_info">
                            <div class="order_details_img">
                                <img id="order_details_img" src="{{ asset('images/orders/syglp5via6r7rxqjc1k8.jpg') }}" alt="">
                            </div>
                            <div class="order_details_info_2">
                                <span class="order_details_name" id="order_details_name">Apple iPhone 14 Pro Max</span>
                                <div class="d-flex flex-row justify-content-between">
                                    <span class="order_details_price" id="order_details_price">10.000$</span>
                                    <span class="order_details_quantity" id="order_details_quantity">x1</span>
                                </div>
                            </div>
                        </div>
                        <table class="order_details_end">
                            <tbody>
                                <tr>
                                    <td class="order_details_end_title">Tổng tiền phân phối</td>
                                    <th class="order_details_end_value" id="order_details_end_value_total_price">10.000$</th>
                                </tr>
                                <tr>
                                    <td class="order_details_end_title">Hoa hồng</td>
                                    <th class="order_details_end_value" id="order_details_end_value_price_rose">20$</th>
                                </tr>
                                <tr>
                                    <td class="order_details_end_title_total">Số tiền hoàn nhập</td>
                                    <th class="order_details_end_value_total" id="order_details_end_value_total">10.020$</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="order_button">
                <button class="btn btn-secondary me-5" id="later"><i class="fas fa-arrow-left me-1"></i>Để sau</button>
                <button class="btn btn-dark" id="btn_phan_phoi_ngay"><i class="fa-solid fa-gears me-2"></i>Phân phối ngay</button>
            </div>
        </div>
    </div>
</div>
<!-- Tập đoàn amazon -->
<div class="section-4">
    <div class="position-relative">
        <span class="text-white fw-bold text-title">Tập đoàn AMAZON</span>
        <img class="title-image" src="{{ asset('images/home/title.png') }}" alt="">
    </div>
    <div class="section-4-box-content">
        <div class="section-4-content d-flex flex-column" id="view_amazon">
            <img class="section-4-amazon-image" src="{{ asset('images/home/section-4.1.webp') }}" alt="">
            <span class="fw-bold">AMAZON</span>
        </div>
        <div id="amazon_content">
            <div class="d-flex justify-content-end">
                <div id="close_xmark_amazon">
                    <i class="fa fa-solid fa-xmark fa-xl"></i>
                </div>
            </div>
            <div class="amazon-title">
                <h3 class="fw-bold text-center">Giới thiệu nền tảng</h3>
            </div>
            <div class="amazon-detail-content">
                <p>Khi nền tảng khớp lệnh cho người dùng Nền tảng gửi thông tin đặt hàng đến hậu trường của người bán. Nếu người dùng không gửi đơn đặt hàng trong vòng hai phút Đơn đặt hàng sẽ bị tạm ngưng để tránh sự giám sát của nền tảng mua sắm trực tuyến. sau khi lệnh bị đình chỉ Việc tài trợ cho đơn đặt hàng cũng sẽ bị đình chỉ. Bạn phải liên hệ với dịch vụ khách hàng trong vòng 24 giờ để giải phóng ứng dụng. Hãy chú ý đến điều này</p>
                <p>Thành viên VIP 1: 200$ hoa hồng 0,0002%</p>
                <p>Thành viên VIP 2: 1000$ hoa hồng 0,003%</p>
                <p>Thành viên VIP 3: 5000$ hoa hồng 0,005%</p>
                <p>Thành viên VIP 4: 10000$ hoa hồng 0,01 %</p>
            </div>
        </div>
        <div class="section-4-content d-flex flex-column" id="view_mo_ta">
            <img src="{{ asset('images/home/section-4.2.webp') }}" alt="">
            <span class="fw-bold">Mô tả</span>
        </div>
        <div id="mo_ta_content">
            <div class="d-flex justify-content-end">
                <div id="close_xmark_mo_ta">
                    <i class="fa fa-solid fa-xmark fa-xl"></i>
                </div>
            </div>
            <div class="amazon-title">
                <h3 class="fw-bold text-center">Quy tắc lấy đơn</h3>
            </div>
            <div class="amazon-detail-content">
                <p>Thành viên VIP 1: 200$ hoa hồng 0,0002%</p>
                <p>Thành viên VIP 2: 1000$ hoa hồng 0,003%</p>
                <p>Thành viên VIP 3: 5000$ hoa hồng 0,005%</p>
                <p>Thành viên VIP 4: 10000$ hoa hồng 0,01 %</p>
            </div>
        </div>
        <div class="section-4-content d-flex flex-column" id="view_tai_chinh">
            <img src="{{ asset('images/home/section-4.3.webp') }}" alt="">
            <span class="fw-bold">Tài chính</span>
        </div>
        <div id="tai_chinh_content">
            <div class="d-flex justify-content-end">
                <div id="close_xmark_tai_chinh">
                    <i class="fa fa-solid fa-xmark fa-xl"></i>
                </div>
            </div>
            <div class="amazon-title">
                <h3 class="fw-bold text-center">Hợp tác đại lý</h3>
            </div>
            <div class="amazon-detail-content">
                <p class="bg-dark text-white p-2">TIKTOKSHOPSWORLD là hệ thống chạy quảng cáo, tăng tương tác và lượt mua hàng cho các sản phẩm của những thương hiệu lớn, trên nhiều trang thương mại điện tử toàn cầu. TIKTOKSHOPSWORLD hợp tác với rất nhiều thương hiệu lớn có tên tuổi trên thị trường như Apple, Rolex, Hublot, Omega... và rất nhiều nhà cung cấp nhỏ lẻ khác, để luôn đảm bảo về số lượng đơn đặt hàng mỗi ngày,TIKTOKSHOPSWORLD cũng có hàng ngàn khách hàng thân thiết với quy mô trên toàn cầu đang sử dụng dịch vụ củaTIKTOKSHOPSWORLD mỗi ngày, cũng như đang tham gia vào hệ thống quảng cáo và bán hàng củaTIKTOKSHOPSWORLD . Hệ thống bán hàng TIKTOKSHOPSWORLD có thâm niên hoạt động trên thị trường thương mại điện tử, có nhiều kinh nghiệm trong lĩnh vực quảng cáo và bán hàng, TIKTOKSHOPSWORLD đã từng bước từng bước vươn tầm ảnh hưởng ra thị trường quốc tế. Những chuỗi cung ứng hàng hóa của thương hiệu TIKTOKSHOPSWORLD cũng đã được phát triển tốt hơn qua nhiều thập kỷ.Để ngăn chặn các hoạt động bất hợp pháp rửa tiền Theo Khoản 1, Điều 3 Nghị định số 74/2005/NĐ-CP ngày 7/6/2005, người dùng phải hoàn thành nhiều nhiệm vụ và rút tiền mặt trong cùng một ngày. Sau khi xác nhận rút tiền thành công, thời gian nhận là 1 ~ 5 phút,Khoảng thời gian cao điểm không quá 30 phút, và thời gian nhận do các ngân hàng. Tham gia công việc bằng phương thức nhận đơn hàng làm nhiệm vụ:</p>
                <p>① Đăng ký tài khoản</p>
                <p>② Nạp tiền online</p>
                <p>③ Nhận đơn hàng</p>
                <p>④ Hoàn thành đơn hàng</p>
                <p>⑤ Rút tiền gốc</p>
            </div>
        </div>
        <div class="section-4-content d-flex flex-column" id="view_quy_dinh">
            <img src="{{ asset('images/home/section-4.4.webp') }}" alt="">
            <span class="fw-bold">Quy định</span>
        </div>
        <div id="quy_dinh_content">
            <div class="d-flex justify-content-end">
                <div id="close_xmark_quy_dinh">
                    <i class="fa fa-solid fa-xmark fa-xl"></i>
                </div>
            </div>
            <div class="amazon-title">
                <h3 class="fw-bold text-center">Quy định công ty</h3>
            </div>
            <div class="amazon-detail-content">
                <p>Mỗi khách hàng khi tham gia ĐẦU TƯ cần sử dụng mã kích hoạt tài khoản mới được bộ phận chăm sóc khách hàng cung cấp để đăng ký tài khoản (Lưu ý: Mỗi cá nhân chỉ được đăng ký 1 tài khoản ứng dụng hệ thống, trong quá trình xem xét rà soát nếu khách hàng cố ý sai phạm sử dụng nhiều hơn 1 tài khoản, hệ thống có thể khóa vĩnh viễn tài khoản của khách hàng.)② Hệ thống có 5 gian hàng ĐẦU TƯ với 5 mức vốn ĐẦU TƯ khác nhau, mỗi gian hàng sẽ có số lượng sản phẩm quảng cáo và lợi nhuận khác nhau tương ứng với gian hàng khách hàng tham gia ĐẦU TƯ.③ Đơn hàng thưởng là các đơn hàng có giá trị ngẫu nhiên từ hệ thống dành cho tài khoản may mắn. 1 tài khoản may mắn sẽ có cơ hội nhận được từ 1 đến tối đa 3 đơn hàng thưởng trong 1 tháng. Khi may mắn nhận được đơn hàng thưởng số dư tài khoản sẽ tạm thời đóng băng để tránh trường hợp khách hàng nhận được nhiều đơn thưởng cùng lúc. Sau khi khách hàng nạp tiền và quảng cáo thành công sẽ nhận được tiền thưởng riêng từ 15% - 30% tổng giá trị đơn hàng và toàn bộ số tiền tạm đóng băng sẽ được tự động hoàn trả về tài khoản khách hàng.④ Khi tham gia, khách hàng cần quảng cáo thành công các sản phẩm tương ứng với khu vực ĐẦU TƯ trước khi có thể rút tiền về tài khoản ( 1 khu vực chỉ được rút tiền 1 lần / ngày ).⑤ Đối với khách hàng không đủ nguồn lực tài chính hoặc không chuẩn bị vốn đầu tư cho mình, khách hàng có thể yêu cầu hệ thống hủy tất cả các đơn hàng thưởng trong tháng (NẾU CÓ) trước khi thực hiện để tránh trường hợp khách hàng nhận được đơn hàng thưởng nhưng không có đủ nguồn lực tài chính để xử lý, khi đó tài khoản của khách hàng sẽ không hoạt động được và số tiền cọc sẽ treo vĩnh viễn trong hệ thống cho đến khi khách hàng xử lý đơn hàng thưởng lúc này mới có thể hoạt động và rút tiền !</p>
            </div>
        </div>
    </div>
</div>
<!-- Thành viên Amazon -->
<div class="section-5">
    <div class="position-relative">
        <span class="text-white fw-bold text-title">Thành viên Amazon</span>
        <img class="title-image" src="{{ asset('images/home/title.png') }}" alt="">
    </div>
    <div class="section-5-box-content">
        @if (!empty($list_ranks))
        @foreach ($list_ranks as $item)
        <div class="section-5-item-content mb-3">
            <span class="section-5-vip-title badge text-warning">
                {{$item->name}}
            </span>
            <div class="section-5-content d-flex align-items-center justify-content-between flex-row">
                <div class="d-flex flex-column align-items-center">
                    <span class="section-5-content-tittle">
                        Phí nâng cấp
                    </span>
                    <span class="section-5-content-value text-danger">
                        {{$item->upgrade_fee}}$
                    </span>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <span class="section-5-content-tittle">
                        Chiết khấu
                    </span>
                    <span class="section-5-content-value">
                        {{$item->commission_percentage}}%
                    </span>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <span class="section-5-content-tittle">
                        Lượt phân phối
                    </span>
                    <span class="section-5-content-value">
                        {{$item->spin_count}}
                    </span>
                </div>
            </div>
        </div>
        @endforeach
        @endif

    </div>
</div>
<div class="section-6">
    <div class="position-relative">
        <span class="text-white fw-bold section-6-title badge bg-warning">Giới thiệu</span>
    </div>
    <div class="section-6-box-content bg-white">
        <p class="text-secondary">Amazon Ho Chi Minh City Office is located at 6th Floor, 29A Nguyen Dinh Chieu,
            Da Kao Ward, District 1, Ho Chi Minh City.

            Amazon's headquarters is located at 1000 N. Zeeb Road, Venice, CA 90291, United
            States. This is Amazon's global headquarters, which houses the company's headquarters, development and marketing departments, as well as offices of its subsidiaries and partners.

            Asia: Beijing, China; Tokyo, Japan; Singapore; Mumbai, India; Bangkok, Thailand;
            Jakarta, Indonesia; Seoul, South Korea; and Manila, Philippines. Europe: London,
            United Kingdom; Paris, France; Berlin, Germany; Madrid, Spain; and Moscow, Russia.
            Americas: São Paulo, Brazil; Mexico City, Mexico; and Buenos Aires, Argentina.
            Africa: Johannesburg, South Africa; Lagos, Nigeria; and Nairobi, Kenya. Email address
            : tiktikshopworld@gmail.com</p>
    </div>
</div>
<div class="section-7">
    <div class="position-relative">
        <span class="text-white fw-bold text-title">Các thành viên khác</span>
        <img class="title-image" src="{{ asset('images/home/title.png') }}" alt="">
    </div>
    <div id="distribution-list" class="text-white"></div>
</div>
<div class="section-8">
    <div class="bg-warning p-2">
        <span class="text-white fw-bold fs-3 cac-doi-tac">— CÁC ĐỐI TÁC —</span>
    </div>
    <table class="table mt-2 table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th class="text-center">STT</th>
                <th class="text-center">Tên đối tác</th>
                <th class="text-center">Hình ảnh</th>
                <th class="text-center">Link trang web</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td class="text-center fw-bold">Shopee</td>
                <td class="text-center">
                    <div class="p-1 d-flex justify-content-center align-items-center">
                        <img class="image-doi-tac" width="100px" src="{{ asset('images/home/doi-tac/shopee.webp') }}" alt="">
                    </div>
                </td>
                <td class="text-center"><a class="btn btn-sm btn-warning link-doi-tac" href="https://shopee.vn/">Xem trang web</a></td>
            </tr>
            <tr>
                <td class="text-center">2</td>
                <td class="text-center fw-bold">Lazada</td>
                <td class="text-center">
                    <div class="p-1 d-flex justify-content-center align-items-center">
                        <img class="image-doi-tac" width="100px" src="{{ asset('images/home/doi-tac/lazada.svg') }}" alt="">
                    </div>
                </td>
                <td class="text-center"><a class="btn btn-sm btn-warning link-doi-tac" target="_blank" rel="noopener noreferrer" href="https://www.lazada.vn/">Xem trang web</a></td>
            </tr>
        </tbody>
    </table>
</div>
</div>
@endsection