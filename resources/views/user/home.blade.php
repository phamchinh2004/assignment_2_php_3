@extends('user.layouts.master')
@push('page-styles')
    @vite('resources/css/user/home.css')
    @vite('resources/css/user/lucky-wheel.css')
@endpush
@section('script-libs')
    <script>
        window.homePageConfig = {
            messages: {
                depositUnavailableTitle: @json(__('home.HeThongDangQuaTai')),
                depositUnavailableText: @json(__('home.VuiLongLienHeCskhDeNapTien')),
            },
            decorativeSocialProof: {
                justNow: @json(__('home.VuaXong')),
                secondsAgo: @json(__('home.GiayTruoc')),
                minutesAgo: @json(__('home.PhutTruoc')),
                successText: @json(__('home.successText')),
            }
        };
    </script>
    @vite('resources/js/user/home.js')
    @vite('resources/js/user/lucky-wheel.js')
@endsection

@section('content')
    @php
        $homeSections = collect($list_sections ?? [])->keyBy('code');
        $announcement = trim(html_entity_decode(
            strip_tags((string) optional($homeSections->get('chu_chay_tren_dau_trang_web'))->getTranslatedContent()),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        ));
        $announcementKey = $announcement !== '' ? substr(hash('sha256', $announcement), 0, 20) : '';
        $currentOrders = (int) ($user_spin_progress->current_spin ?? 0);
        $totalOrders = (int) ($rank->spin_count ?? 0);
        $orderProgress = $totalOrders > 0 ? min(100, round(($currentOrders / $totalOrders) * 100)) : 0;
        $remainingOrders = max(0, $totalOrders - $currentOrders);
    @endphp

    <main class="page-home" data-home-page>
        <section class="home-dashboard" aria-label="Tổng quan trang chủ">
            @if($announcement !== '')
                <div class="home-announcement" role="status" aria-live="polite" data-home-announcement
                    data-announcement-key="{{ $announcementKey }}" data-reappear-after="21600000">
                    <span class="home-announcement__icon" aria-hidden="true"><i class="fa-solid fa-bullhorn"></i></span>
                    <div class="home-announcement__content">
                        <div class="home-announcement__heading">
                            <strong>Thông báo hệ thống</strong>
                            <span>Mới</span>
                        </div>
                        <p>{{ $announcement }}</p>
                    </div>
                    <button type="button" class="home-announcement__close" data-home-announcement-close
                        aria-label="Ẩn thông báo này">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            @endif

            @if (!empty($get_banner) && $get_banner->banner_images->isNotEmpty())
                <section class="home-banner" aria-label="Banner">
                    <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                        @foreach ($get_banner->banner_images as $key => $item)
                            <div class="carousel-item {{$key == 0 ? 'active' : ''}}">
                                <img class="home-banner__image" src="{{ Storage::url($item->path) }}" alt="Banner {{ $key + 1 }}">
                            </div>
                        @endforeach
                        </div>
                        @if($get_banner->banner_images->count() > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying"
                                data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying"
                                data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        @endif
                    </div>
                </section>
            @endif

            <div class="home-progress-card">
                <div class="home-progress-card__header">
                    <span class="home-progress-card__icon"><i class="fa-solid fa-route"></i></span>
                    <div>
                        <span class="home-kicker">Lộ trình phân phối</span>
                        <h2>{{ $totalOrders > 0 ? ($remainingOrders > 0 ? 'Còn ' . $remainingOrders . ' đơn cần hoàn tất' : 'Đã hoàn tất lộ trình') : 'Chưa có lộ trình' }}</h2>
                    </div>
                </div>
                <div class="home-progress-track" aria-label="Tiến độ đơn hàng {{ $orderProgress }}%">
                    <span style="width: {{ $orderProgress }}%"></span>
                </div>
                <div class="home-progress-card__footer">
                    <span>{{ $orderProgress }}% hoàn thành</span>
                    <a href="{{ route('distribution') }}">Xem phân phối <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>

            <nav class="home-actions" aria-label="Thao tác nhanh">
                <a class="home-action home-action--primary" id="btn_phan_phoi" href="{{ route('distribution') }}">
                    <span class="home-action__icon"><i class="fa-solid fa-box-open"></i></span>
                    <span class="home-action__copy"><small>Tác vụ chính</small><strong>{{ __('home.PhanPhoi') }}</strong></span>
                    <i class="fa-solid fa-arrow-right home-action__arrow"></i>
                </a>
                <a class="home-action" id="btn_bien_dong_so_du" href="{{ route('balance_fluctuation') }}?tab=distribution">
                    <span class="home-action__icon"><i class="fa-solid fa-chart-line"></i></span>
                    <span class="home-action__copy"><small>Tài chính</small><strong>{{ __('home.BienDongSoDu') }}</strong></span>
                </a>
                <button class="home-action" id="btn_nap_tien" type="button">
                    <span class="home-action__icon"><i class="fa-solid fa-wallet"></i></span>
                    <span class="home-action__copy"><small>Tài khoản</small><strong>{{ __('home.NapTien') }}</strong></span>
                </button>
                <a class="home-action" id="btn_rut_tien" href="{{ route('withdraw_money') }}">
                    <span class="home-action__icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
                    <span class="home-action__copy"><small>Tài chính</small><strong>{{ __('home.RutTien') }}</strong></span>
                </a>
            </nav>
        </section>

        <section class="home-section home-wheel-section" id="section-3">
            <div class="home-section-heading">
                <div>
                    <span class="home-kicker">Quyền lợi hằng ngày</span>
                    <h2>{{ __('home.VongQuayMayMan') }}</h2>
                </div>
                <span class="home-section-heading__icon"><i class="fa-solid fa-gift"></i></span>
            </div>

            <!-- Kiểm tra điều kiện quay -->
            @php
                $can_spin = false;
                $spin_message = '';

                // Lượt admin cấp được dùng trước và không phụ thuộc tiến trình đơn hàng.
                if (($bonus_spins_remaining ?? 0) > 0) {
                    $can_spin = true;
                    $spin_message = 'Bạn có ' . $bonus_spins_remaining . ' lượt quay được cấp!';
                } elseif ($has_spun_today) {
                    $spin_message = 'Bạn đã quay vòng quay hôm nay rồi. Hãy quay lại vào ngày mai!';
                } elseif ($user_spin_progress && $rank) {
                    $current = $user_spin_progress->current_spin ?? 0;
                    $total = $rank->spin_count ?? 0;
                    if ($current >= $total && $total > 0) {
                        $can_spin = true;
                        $spin_message = 'Bạn đã hoàn thành ' . $total . ' đơn hàng! Hãy quay thử vận may!';
                    } else {
                        $remaining = $total - $current;
                        $spin_message = 'Hoàn thành thêm ' . $remaining . ' đơn hàng nữa để quay!';
                    }
                } else {
                    $spin_message = 'Bạn cần có cấp độ để tham gia quay thưởng!';
                }
            @endphp

            <div class="wheel-container-modern">
                <div class="wheel-status-card">
                    <i class="fas {{ $can_spin ? 'fa-check-circle text-success' : 'fa-lock text-warning' }}"></i>
                    <p class="wheel-status-message">{{ $spin_message }}</p>
                </div>

                <div class="wheel-wrapper">
                    <div class="wheel-pointer">
                        <i class="fas fa-caret-down"></i>
                    </div>

                    <div class="prize-wheel" id="prizeWheel">
                        <div class="wheel-slice slice-1" data-prize="SH Mode">
                            <div class="slice-content">
                                <img class="slice-prize-image" src="{{ asset('images/spin/18prm.webp') }}" alt="18 Pro Max">
                                <span>18 Pro Max</span>
                            </div>
                        </div>
                        <div class="wheel-slice slice-2" data-prize="$2">
                            <div class="slice-content">
                                <img class="slice-prize-image" src="{{ asset('images/spin/dollars.webp') }}" alt="$2">
                                <span>$2</span>
                            </div>
                        </div>
                        <div class="wheel-slice slice-3" data-prize="Chúc bạn may mắn lần sau">
                            <div class="slice-content">
                                <i class="fas fa-gem"></i>
                                <span>Chúc bạn may mắn lần sau</span>
                            </div>
                        </div>
                        <div class="wheel-slice slice-4" data-prize="$10">
                            <div class="slice-content">
                                <img class="slice-prize-image" src="{{ asset('images/spin/dollars.webp') }}" alt="$10">
                                <span>$10</span>
                            </div>
                        </div>
                        <div class="wheel-slice slice-5" data-prize="$2">
                            <div class="slice-content">
                                <img class="slice-prize-image" src="{{ asset('images/spin/dollars.webp') }}" alt="$2">
                                <span>$2</span>
                            </div>
                        </div>
                        <div class="wheel-slice slice-6" data-prize="$5">
                            <div class="slice-content">
                                <img class="slice-prize-image" src="{{ asset('images/spin/dollars.webp') }}" alt="$5">
                                <span>$5</span>
                            </div>
                        </div>
                        <div class="wheel-slice slice-7" data-prize="Chúc bạn may mắn lần sau">
                            <div class="slice-content">
                                <i class="fas fa-gem"></i>
                                <span>Chúc bạn may mắn lần sau</span>
                            </div>
                        </div>
                        <div class="wheel-slice slice-8" data-prize="$2">
                            <div class="slice-content">
                                <img class="slice-prize-image" src="{{ asset('images/spin/dollars.webp') }}" alt="$2">
                                <span>$2</span>
                            </div>
                        </div>
                    </div>

                    <button class="wheel-spin-button" id="wheelSpinButton" {{ !$can_spin ? 'disabled' : '' }}>
                        <span class="spin-text">QUAY</span>
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

                @if ($user_spin_progress && $rank)
                    <div class="wheel-progress-info">
                        <div class="progress-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Đã hoàn thành:
                                <strong>{{ $user_spin_progress->current_spin }}/{{ $rank->spin_count }}</strong></span>
                        </div>
                    </div>
                @endif
            </div>

            <audio id="wheelSpinSound" src="{{asset('audio/wheel.mp3')}}" preload="auto"></audio>
            <audio id="applauseSound" src="{{asset('audio/applause.mp3')}}" preload="auto"></audio>
        </section>

        <section class="home-social-proof" aria-labelledby="home-proof-title">
            <aside class="home-proof-metrics" aria-label="Số liệu cộng đồng">
                <div class="home-proof-metrics__intro">
                    <span class="home-kicker">Social proof</span>
                    <strong id="home-proof-title">Số liệu thực tế</strong>
                </div>
                <div class="home-proof-metrics__grid">
                    <div><strong>50,000+</strong><span>Thành viên</span></div>
                    <div><strong>$1B+</strong><span>Tổng giao dịch</span></div>
                    <div><strong>200M+</strong><span>Đơn đã phân phối</span></div>
                    <div><strong>4.9/5</strong><span>Đánh giá</span></div>
                </div>
            </aside>
        </section>

        <!-- Modal giải thưởng -->
        <div class="prize-modal-overlay" id="prizeModalOverlay">
            <div class="prize-modal-container">
                <div class="prize-modal-content">
                    <div class="prize-confetti" id="prizeConfetti"></div>

                    <div class="prize-icon">
                        <i class="fas fa-trophy"></i>
                    </div>

                    <h2 class="prize-title">Chúc Mừng!</h2>
                    <p class="prize-subtitle">Bạn đã trúng giải:</p>

                    <div class="prize-name-display" id="prizeNameDisplay">
                        <img class="prize-icon-display" id="prizeIconDisplay" src="" alt="Giải thưởng">
                        <span class="prize-text-display" id="prizeTextDisplay">Đang tải...</span>
                    </div>

                    <div class="prize-message">
                        Vui lòng liên hệ CSKH để nhận thưởng!
                    </div>

                    <button class="prize-close-button" onclick="closePrizeModal()">
                        <span>Đóng</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <section class="home-section home-info-section" aria-labelledby="home-info-title">
            <div class="home-section-heading">
                <div>
                    <span class="home-kicker">Thông tin hệ thống</span>
                    <h2 id="home-info-title">{{ __('home.TapDoanAmazon') }}</h2>
                </div>
                <span class="home-section-heading__icon"><i class="fa-solid fa-building"></i></span>
            </div>

            <div class="home-info-layout">
                <div class="home-info-tabs" role="tablist" aria-label="Thông tin hệ thống">
                    <button class="home-info-tab is-active" type="button" data-content-target="amazon_content" aria-selected="true">
                        <i class="fa-solid fa-circle-info"></i><span>Hệ thống</span>
                    </button>
                    <button class="home-info-tab" type="button" data-content-target="mo_ta_content" aria-selected="false">
                        <i class="fa-solid fa-list-check"></i><span>{{ __('home.MoTa') }}</span>
                    </button>
                    <button class="home-info-tab" type="button" data-content-target="tai_chinh_content" aria-selected="false">
                        <i class="fa-solid fa-handshake"></i><span>{{ __('home.TaiChinh') }}</span>
                    </button>
                    <button class="home-info-tab" type="button" data-content-target="quy_dinh_content" aria-selected="false">
                        <i class="fa-solid fa-scale-balanced"></i><span>{{ __('home.QuyDinh') }}</span>
                    </button>
                </div>

                <div class="home-info-panels">
                    <article class="home-info-panel is-active" id="amazon_content">
                        <span class="home-info-panel__eyebrow">{{ __('home.GioiThieuNenTang') }}</span>
                        <div class="home-rich-content">{!! optional($homeSections->get('gioi_thieu_nen_tang'))->getTranslatedContent() ?? __('home.DangCapNhat') !!}</div>
                    </article>
                    <article class="home-info-panel" id="mo_ta_content" hidden>
                        <span class="home-info-panel__eyebrow">{{ __('home.QuyTacLayDon') }}</span>
                        <div class="home-rich-content">{!! optional($homeSections->get('quy_tac_lay_don'))->getTranslatedContent() ?? __('home.DangCapNhat') !!}</div>
                    </article>
                    <article class="home-info-panel" id="tai_chinh_content" hidden>
                        <span class="home-info-panel__eyebrow">{{ __('home.HopTacDaiLy') }}</span>
                        <div class="home-rich-content">{!! optional($homeSections->get('hop_tac_dai_ly'))->getTranslatedContent() ?? __('home.DangCapNhat') !!}</div>
                    </article>
                    <article class="home-info-panel" id="quy_dinh_content" hidden>
                        <span class="home-info-panel__eyebrow">{{ __('home.QuyDinhCongTy') }}</span>
                        <div class="home-rich-content">{!! optional($homeSections->get('quy_dinh_cong_ty'))->getTranslatedContent() ?? __('home.DangCapNhat') !!}</div>
                    </article>
                </div>
            </div>
        </section>

        <section class="home-story-surface" aria-labelledby="home-story-title">
            <div class="home-story-timeline">
                <div class="home-story-heading">
                    <div>
                        <span class="home-kicker">Dấu mốc giới thiệu</span>
                        <h2 id="home-story-title">Hành trình phát triển</h2>
                    </div>
                    <span class="home-story-heading__mark"><i class="fa-solid fa-arrow-trend-up"></i></span>
                </div>

                <div class="home-timeline-track">
                    <article><time>2020</time><p>Thành lập công ty và ra mắt nền tảng phân phối đơn hàng</p></article>
                    <article><time>2021</time><p>Đạt 10,000 thành viên đầu tiên và mở rộng hệ thống gian hàng</p></article>
                    <article><time>2022</time><p>Mở rộng sang thị trường quốc tế và tích hợp thanh toán đa dạng</p></article>
                    <article><time>2023</time><p>Đạt mốc $1M tổng giao dịch và ra mắt hệ thống VIP</p></article>
                    <article><time>2024</time><p>Tiếp tục phát triển và nâng cấp hệ thống bảo mật</p></article>
                </div>
            </div>

            <aside class="home-transparency-panel">
                <div class="home-transparency-panel__heading">
                    <span class="home-kicker">Thông tin giới thiệu</span>
                    <h2>Minh bạch thông tin</h2>
                </div>
                <div class="home-transparency-list">
                    <article>
                        <span><i class="fa-regular fa-file-lines"></i></span>
                        <div><h3>Báo cáo tài chính</h3><p>Công khai báo cáo hàng tháng về tổng giao dịch và phân phối lợi nhuận</p></div>
                    </article>
                    <article>
                        <span><i class="fa-solid fa-scale-balanced"></i></span>
                        <div><h3>Quy định pháp lý</h3><p>Tuân thủ đầy đủ các quy định về kinh doanh và bảo vệ người tiêu dùng</p></div>
                    </article>
                    <article>
                        <span><i class="fa-solid fa-shield-halved"></i></span>
                        <div><h3>Bảo vệ dữ liệu</h3><p>Cam kết bảo mật thông tin cá nhân và tài chính của khách hàng</p></div>
                    </article>
                </div>
            </aside>
        </section>

        <section class="home-section home-ranks-section" aria-labelledby="home-ranks-title">
            <div class="home-section-heading">
                <div>
                    <span class="home-kicker">{{ __('home.ThanhVienAmazon') }}</span>
                    <h2 id="home-ranks-title">Thông tin gian hàng</h2>
                </div>
                <span class="home-section-heading__icon"><i class="fa-solid fa-layer-group"></i></span>
            </div>

            @php
                $rankIntro = optional($homeSections->get('tieu_de_lon_gioi_thieu_o_trang_chu'))->getTranslatedContent();
            @endphp
            @if(!empty($rankIntro))
                <div class="home-ranks-intro home-rich-content">{!! $rankIntro !!}</div>
            @endif

            @if($rank)
                <div class="home-rank-grid home-rank-grid--single">
                    <article class="home-rank-card is-current">
                        <div class="home-rank-card__header">
                            <span class="home-rank-card__icon"><i class="fa-solid fa-crown"></i></span>
                            <div>
                                <span class="home-kicker">Gian hàng hiện tại</span>
                                <h3>{{ $rank->name }}</h3>
                            </div>
                            <span class="home-rank-card__status"><i class="fa-solid fa-check"></i> Hiện tại</span>
                        </div>
                        <dl class="home-rank-card__metrics">
                            <div>
                                <dt>Phí nâng cấp</dt>
                                <dd>{{ format_money($rank->upgrade_fee ?? 0) }} USD</dd>
                            </div>
                            <div>
                                <dt>Chiết khấu</dt>
                                <dd>{{ $rank->commission_percentage ?? 0 }}%</dd>
                            </div>
                            <div>
                                <dt>Lượt phân phối</dt>
                                <dd>{{ $rank->spin_count ?? 0 }}</dd>
                            </div>
                            <div>
                                <dt>Giá trị</dt>
                                <dd>{{ format_money($rank->value ?? 0) }} USD</dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <div class="home-ranks-actions">
                    <a href="{{ route('vip') }}" class="home-ranks-button">
                        <span>Xem các gian hàng khác</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @else
                <div class="home-rank-empty">
                    <span class="home-rank-empty__icon"><i class="fa-solid fa-store"></i></span>
                    <div class="home-rank-empty__copy">
                        <span class="home-kicker">Chưa có gian hàng</span>
                        <h3>Bạn chưa sở hữu gian hàng nào</h3>
                        <p>Chọn gian hàng phù hợp để bắt đầu nhận quyền lợi và tham gia phân phối.</p>
                    </div>
                    <a href="{{ route('vip') }}" class="home-ranks-button home-ranks-button--primary">
                        <span>Xem các gian hàng</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @endif
        </section>

        <section class="home-social-surface home-social-surface--activity" aria-labelledby="home-social-title">
            <div class="home-social-surface__main">
                <div class="home-social-heading">
                    <div>
                        <span class="home-kicker">Nhịp hoạt động cộng đồng</span>
                        <h2 id="home-social-title">Hoạt động đang diễn ra</h2>
                    </div>
                    <span class="home-live-pill"><i></i> Giao dịch</span>
                </div>
                <p class="home-social-note">Dữ liệu bên dưới là các giao dịch đang diễn ra.</p>
                <div class="home-activity-stream" data-social-activity aria-live="polite" aria-label="Hoạt động hôm nay"></div>
            </div>
        </section>

        <section class="home-voices" aria-labelledby="home-voices-title" data-testimonials>
            <div class="home-voices__intro">
                <span class="home-kicker">Phản hồi minh họa</span>
                <h2 id="home-voices-title">Góc nhìn từ cộng đồng</h2>
                <p>Các nội dung bên dưới được giữ lại từ Home cũ như phần trình bày social-proof minh họa.</p>
                <div class="home-voices__controls">
                    <button type="button" data-testimonial-prev aria-label="Phản hồi trước"><i class="fa-solid fa-arrow-left"></i></button>
                    <button type="button" data-testimonial-next aria-label="Phản hồi tiếp theo"><i class="fa-solid fa-arrow-right"></i></button>
                </div>
            </div>

            <div class="home-voices__stage" data-testimonial-stage>
                @php
                    $homeTestimonials = [
                        ['avatar' => 'images/avatars/1.jpg', 'name' => 'Hà Phạm Thị', 'rank' => 'VIP Gold', 'text' => 'Mk kiếm cũng được kha khá tiền ở đây, nhưng hệ thống cho ít đơn thưởng quá săn mãi mới dc 1 đơn hicc'],
                        ['avatar' => 'images/avatars/2.jpg', 'name' => 'Nguyen Vu', 'rank' => 'VIP Platinum', 'text' => 'Làm được gần 1 năm thấy cũng ổn, ae làm mà nhận dc đơn thưởng thì bú luôn đi ko là ko đủ sống đâu, chịu khó đầu tư 1 tí'],
                        ['avatar' => 'images/avatars/3.jpg', 'name' => 'Phanhh Nguyễn', 'rank' => 'VIP Diamond', 'text' => 'Đội ngũ hỗ trợ chuyên nghiệp, giải quyết vấn đề nhanh chóng. Tôi tin tưởng và sẽ tiếp tục sử dụng dịch vụ lâu dài.'],
                        ['avatar' => 'images/avatars/4.jpg', 'name' => 'Trần Minh Tuấn', 'rank' => 'VIP Gold', 'text' => 'Hệ thống phân phối rất minh bạch và hiệu quả. Tôi đã kiếm được thu nhập ổn định từ đây. Giao diện dễ sử dụng và hỗ trợ khách hàng rất tốt.'],
                        ['avatar' => 'images/avatars/5.jpg', 'name' => 'Lê Thị Mai', 'rank' => 'VIP Platinum', 'text' => 'Tôi đã tham gia từ năm 2021 và rất hài lòng với dịch vụ. Hệ thống gian hàng phân cấp giúp tôi có thu nhập tăng dần theo thời gian.'],
                        ['avatar' => 'images/avatars/6.jpg', 'name' => 'Nguyễn Văn Đức', 'rank' => 'VIP Diamond', 'text' => 'Rất hài lòng với dịch vụ! Hệ thống hoạt động ổn định, không có lỗi gì. Thu nhập hàng tháng đều đặn, đúng như cam kết.'],
                    ];
                @endphp

                @foreach($homeTestimonials as $index => $testimonial)
                    <article class="home-testimonial {{ $index === 0 ? 'is-active' : '' }}" data-testimonial-slide aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                        <span class="home-testimonial__quote"><i class="fa-solid fa-quote-left"></i></span>
                        <blockquote>{{ $testimonial['text'] }}</blockquote>
                        <footer>
                            <img src="{{ asset($testimonial['avatar']) }}" alt="" loading="lazy">
                            <div><strong>{{ $testimonial['name'] }}</strong><span>{{ $testimonial['rank'] }}</span></div>
                            <small>Minh họa</small>
                        </footer>
                    </article>
                @endforeach

                <div class="home-voices__dots" aria-label="Chọn phản hồi">
                    @foreach($homeTestimonials as $index => $testimonial)
                        <button type="button" class="{{ $index === 0 ? 'is-active' : '' }}" data-testimonial-dot="{{ $index }}" aria-label="Phản hồi {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="home-section home-partners-section" aria-labelledby="home-partners-title">
            <div class="home-section-heading">
                <div>
                    <span class="home-kicker">Hệ sinh thái</span>
                    <h2 id="home-partners-title">{{ __('home.CacDoiTac') }}</h2>
                </div>
                <span class="home-section-heading__icon"><i class="fa-solid fa-handshake"></i></span>
            </div>

            <div class="home-partner-grid">
                @forelse($list_partners as $item)
                    <article class="home-partner-card">
                        <div class="home-partner-card__logo">
                            <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}" loading="lazy">
                        </div>
                        <div class="home-partner-card__body">
                            <h3>{{ $item->name }}</h3>
                            <a href="{{ $item->link }}" target="_blank" rel="noopener noreferrer">
                                {{ __('home.XemTrangWeb') }}
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="home-empty-state">
                        <i class="fa-regular fa-building"></i>
                        <span>{{ __('home.DangCapNhat') }}</span>
                    </div>
                @endforelse
            </div>
        </section>
    </main>

@endsection
