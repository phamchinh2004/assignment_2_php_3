@extends('user.layouts.master')

@section('css-libs')
@vite('resources/css/user/order.css')
@endsection

@section('script-libs')
<script>
    window.orderHistoryConfig = {
        routes: {
            list: @json(route('get_list_orders_by_tab')),
            order: @json(route('order')),
        },
        csrf: @json(csrf_token()),
        userBalance: @json((float) ($user->balance ?? 0)),
        labels: {
            empty: @json(__('order.KhongCoDuLieu')),
            noData: @json(__('order.KhongTimThayDuLieuDonHang')),
            time: @json(__('order.ThoiGianDatPhanPhoi')),
            orderCode: @json(__('order.MaDonHang')),
            orderTotal: @json(__('order.TongTienDonHang')),
            commission: @json(__('order.ChietKhau')),
            refund: @json(__('order.SoTienHoanNhap')),
        },
    };
</script>
@vite('resources/js/user/order.js')
@endsection

@section('content')
<main class="order-history-page" data-order-history>
    <header class="order-history-nav">
        <a class="order-history-back" href="{{ route('distribution') }}" aria-label="Quay lại trang phân phối">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="order-history-heading">
            <span class="order-history-eyebrow">Distribution history</span>
            <h1>{{ __('order.LichSuPhanPhoi') }}</h1>
        </div>
        <div class="order-history-balance" aria-label="{{ __('order.SoDuHienTai') }}">
            <span>{{ __('order.SoDuHienTai') }}</span>
            <strong id="so_du_user">${{ format_money($user->balance ?? 0, 7) }}</strong>
        </div>
    </header>

    <section class="order-history-intro" aria-labelledby="order-history-title">
        <div class="order-history-intro__copy">
            <span class="order-history-live"><i></i> Lịch sử phân phối</span>
            <h2 id="order-history-title">Theo dõi từng đơn.<br><em>Nhìn nhanh, hiểu ngay.</em></h2>
            <p>{{ __('order.DuLieuNayDuocCungCap') }}</p>
        </div>
        <div class="order-history-intro__legend" aria-label="Chú thích trạng thái">
            <span><i class="is-success"></i> Hoàn thành</span>
            <span><i class="is-progress"></i> Đang xử lý</span>
            <span><i class="is-warning"></i> Cần chú ý</span>
        </div>
    </section>

    <section class="order-history-filter" aria-label="Lọc lịch sử đơn hàng">
        <div class="order-history-filter__rail" id="tabNavigation">
            <button type="button" data-tab="tat-ca" data-filter-id="btn_tat_ca" class="history-filter-chip is-active">
                <i class="fas fa-border-all"></i><span id="btn_tat_ca">{{ __('order.TatCa') }}</span>
            </button>
            <button type="button" data-tab="cho-xu-ly" data-filter-id="btn_cho_xu_ly" class="history-filter-chip">
                <i class="fas fa-clock"></i><span id="btn_cho_xu_ly">{{ __('order.ChoXuLy') }}</span>
            </button>
            <button type="button" data-tab="da-xac-nhan" data-filter-id="btn_da_xac_nhan" class="history-filter-chip">
                <i class="fas fa-check"></i><span id="btn_da_xac_nhan">{{ __('order.DaXacNhan') }}</span>
            </button>
            <button type="button" data-tab="dang-chuan-bi" data-filter-id="btn_dang_chuan_bi" class="history-filter-chip">
                <i class="fas fa-box"></i><span id="btn_dang_chuan_bi">{{ __('order.DangChuanBi') }}</span>
            </button>
            <button type="button" data-tab="dang-trung-chuyen" data-filter-id="btn_dang_trung_chuyen" class="history-filter-chip">
                <i class="fas fa-route"></i><span id="btn_dang_trung_chuyen">{{ __('order.DangTrungChuyen') }}</span>
            </button>
            <button type="button" data-tab="dang-van-chuyen" data-filter-id="btn_dang_van_chuyen" class="history-filter-chip">
                <i class="fas fa-truck"></i><span id="btn_dang_van_chuyen">{{ __('order.DangVanChuyen') }}</span>
            </button>
            <button type="button" data-tab="da-giao-hang" data-filter-id="btn_da_giao_hang" class="history-filter-chip">
                <i class="fas fa-box-open"></i><span id="btn_da_giao_hang">{{ __('order.DaGiaoHang') }}</span>
            </button>
            <button type="button" data-tab="hoan-thanh" data-filter-id="btn_hoan_thanh" class="history-filter-chip">
                <i class="fas fa-circle-check"></i><span id="btn_hoan_thanh">{{ __('order.HoanThanh') }}</span>
            </button>
            <button type="button" data-tab="da-huy" data-filter-id="btn_da_huy" class="history-filter-chip">
                <i class="fas fa-ban"></i><span id="btn_da_huy">{{ __('order.DaHuy') }}</span>
            </button>
            <button type="button" data-tab="dong-bang" data-filter-id="btn_dong_bang" class="history-filter-chip history-filter-chip--hvo">
                <i class="fas fa-gem"></i><span id="btn_dong_bang">{{ __('order.GiaTriCao') }}</span>
            </button>
            <button type="button" data-tab="bi-phat" data-filter-id="btn_bi_phat" class="history-filter-chip history-filter-chip--danger">
                <i class="fas fa-triangle-exclamation"></i><span id="btn_bi_phat">Bị phạt</span>
            </button>
        </div>
    </section>

    <section class="order-history-feed" aria-labelledby="history-feed-title">
        <div class="order-history-feed__head">
            <div>
                <span class="order-history-section-kicker">Danh sách đơn hàng</span>
                <h2 id="history-feed-title">Tất cả đơn hàng</h2>
            </div>
            <span class="order-history-count" id="historyResultCount" aria-live="polite">Đang tải</span>
        </div>

        <div class="order-history-list" id="list_orders" aria-live="polite" aria-busy="true">
            <div class="history-loading" aria-label="Đang tải lịch sử đơn hàng">
                @for ($i = 0; $i < 3; $i++)
                    <div class="history-skeleton">
                        <span class="history-skeleton__line history-skeleton__line--short"></span>
                        <span class="history-skeleton__block"></span>
                        <span class="history-skeleton__line"></span>
                    </div>
                @endfor
            </div>
        </div>
    </section>
</main>
@endsection
