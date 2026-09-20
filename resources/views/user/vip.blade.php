@extends('user.layouts.master')

@section('css-libs')
    @vite('resources/css/user/vip.css')
@endsection

@section('content')
<main class="membership-page">
    <header class="membership-header">
        <a href="{{ route('me') }}" class="membership-back" aria-label="Quay lại tài khoản">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <p class="membership-eyebrow">Membership</p>
            <h1>{{ __('vip.CapDoThanhVien') }}</h1>
            <p>Khám phá quyền lợi và giới hạn thực tế của từng cấp độ.</p>
        </div>
    </header>

    <section class="membership-overview" aria-labelledby="current-membership-title">
        <div class="overview-orb overview-orb--one" aria-hidden="true"></div>
        <div class="overview-orb overview-orb--two" aria-hidden="true"></div>
        <div class="overview-main">
            <div class="member-identity">
                <div class="member-avatar">
                    <img src="{{ get_user_avatar($user) }}" alt="{{ $user->full_name }}"
                         onerror="this.src='{{ asset('images/default-avatar-gray.svg') }}'">
                    <span><i class="fa-solid fa-crown"></i></span>
                </div>
                <div class="member-copy">
                    <p>Cấp độ hiện tại</p>
                    <h2 id="current-membership-title">{{ $rank?->name ?? __('vip.BanChuaCoGianHang') }}</h2>
                    <span>{{ $user->full_name }} · {{ '@' . $user->username }}</span>
                </div>
            </div>

            @if ($rank)
                <div class="current-benefits">
                    <div><span>Hoa hồng</span><strong>{{ format_money($rank->commission_percentage) }}%</strong></div>
                    <div><span>Nhiệm vụ/ngày</span><strong>{{ $rank->spin_count }}</strong></div>
                    <div><span>Rút tiền/ngày</span><strong>{{ $rank->maximum_number_of_withdrawals }} lượt</strong></div>
                    <div><span>Hạn mức/lần</span><strong>{{ format_money($rank->maximum_withdrawal_amount) }}$</strong></div>
                </div>
            @else
                <div class="membership-empty">
                    <i class="fa-regular fa-gem"></i>
                    <span>Tài khoản chưa được gán cấp độ thành viên.</span>
                </div>
            @endif
        </div>

        @if ($nextRank)
            @php
                $commissionGain = $rank ? $nextRank->commission_percentage - $rank->commission_percentage : null;
                $taskGain = $rank ? $nextRank->spin_count - $rank->spin_count : null;
                $withdrawalGain = $rank ? $nextRank->maximum_number_of_withdrawals - $rank->maximum_number_of_withdrawals : null;
                $withdrawalAmountGain = $rank ? $nextRank->maximum_withdrawal_amount - $rank->maximum_withdrawal_amount : null;
            @endphp
            <aside class="next-tier-panel">
                <div class="next-tier-heading">
                    <span>Cấp tiếp theo</span>
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
                <h3>{{ $nextRank->name }}</h3>
                <p class="next-tier-price"><small>Điều kiện cấp độ</small><strong>{{ format_money($nextRank->upgrade_fee) }}$</strong></p>
                @if ($rank)
                    <div class="upgrade-gains">
                        @if ($commissionGain > 0)<span><i class="fa-solid fa-plus"></i>{{ format_money($commissionGain) }}% hoa hồng</span>@endif
                        @if ($taskGain > 0)<span><i class="fa-solid fa-plus"></i>{{ $taskGain }} nhiệm vụ/ngày</span>@endif
                        @if ($withdrawalGain > 0)<span><i class="fa-solid fa-plus"></i>{{ $withdrawalGain }} lượt rút/ngày</span>@endif
                        @if ($withdrawalAmountGain > 0)<span><i class="fa-solid fa-plus"></i>{{ format_money($withdrawalAmountGain) }}$ hạn mức/lần</span>@endif
                    </div>
                @else
                    <p class="next-tier-note">Cấp độ đầu tiên trong hệ thống.</p>
                @endif
            </aside>
        @else
            <aside class="next-tier-panel next-tier-panel--max">
                <div class="max-tier-icon"><i class="fa-solid fa-trophy"></i></div>
                <span>Cấp cao nhất</span>
                <h3>Bạn đang ở cấp độ cao nhất</h3>
                <p>Toàn bộ giới hạn của cấp hiện tại đang được áp dụng.</p>
            </aside>
        @endif
    </section>

    <section class="benefit-section" aria-labelledby="benefit-title">
        <div class="section-heading">
            <div><p class="membership-eyebrow">Quyền lợi cốt lõi</p><h2 id="benefit-title">Giá trị theo từng cấp</h2></div>
        </div>
        <div class="benefit-grid">
            <article class="benefit-item">
                <span class="benefit-icon"><i class="fa-solid fa-percent"></i></span>
                <div><h3>Hoa hồng theo cấp</h3><p>Tỷ lệ hoa hồng của đơn hoàn thành được cấu hình riêng cho từng cấp.</p></div>
            </article>
            <article class="benefit-item">
                <span class="benefit-icon"><i class="fa-solid fa-list-check"></i></span>
                <div><h3>Nhiệm vụ mỗi ngày</h3><p>Số lượt phân phối trong ngày phụ thuộc trực tiếp vào cấp hiện tại.</p></div>
            </article>
            <article class="benefit-item">
                <span class="benefit-icon"><i class="fa-solid fa-wallet"></i></span>
                <div><h3>Giới hạn rút tiền</h3><p>Số lượt và số tiền tối đa mỗi lần rút được áp dụng theo cấu hình cấp.</p></div>
            </article>
        </div>
    </section>

    <section class="tiers-section" aria-labelledby="tiers-title">
        <div class="section-heading">
            <div><p class="membership-eyebrow">Danh sách cấp độ</p><h2 id="tiers-title">So sánh quyền lợi</h2></div>
            <span>{{ $list_ranks->count() }} cấp độ</span>
        </div>

        @if ($list_ranks->isEmpty())
            <div class="tiers-empty"><i class="fa-regular fa-folder-open"></i><strong>Chưa có cấp độ</strong><span>Hệ thống chưa cấu hình dữ liệu thành viên.</span></div>
        @else
            <div class="tier-grid">
                @foreach ($list_ranks as $index => $item)
                    @php
                        $isCurrent = $rank && (int) $item->id === (int) $rank->id;
                        $isHigher = $currentRankIndex !== false && $index > $currentRankIndex;
                        $commissionDelta = $isHigher ? $item->commission_percentage - $rank->commission_percentage : 0;
                        $taskDelta = $isHigher ? $item->spin_count - $rank->spin_count : 0;
                        $withdrawalDelta = $isHigher ? $item->maximum_number_of_withdrawals - $rank->maximum_number_of_withdrawals : 0;
                        $limitDelta = $isHigher ? $item->maximum_withdrawal_amount - $rank->maximum_withdrawal_amount : 0;
                        $tierIcon = match ($index % 4) { 0 => 'fa-star', 1 => 'fa-gem', 2 => 'fa-crown', default => 'fa-trophy' };
                    @endphp
                    <article class="tier-card tier-card--{{ ($index % 4) + 1 }} {{ $isCurrent ? 'is-current' : '' }}">
                        <div class="tier-card__header">
                            <div class="tier-mark">
                                @if ($item->image)
                                    <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}">
                                @else
                                    <i class="fa-solid {{ $tierIcon }}"></i>
                                @endif
                            </div>
                            <div class="tier-title">
                                <span>Cấp {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <h3>{{ $item->name }}</h3>
                            </div>
                            @if ($isCurrent)<span class="current-tier-badge"><i class="fa-solid fa-check"></i>Cấp hiện tại</span>@endif
                        </div>

                        <div class="tier-price">
                            <span>Điều kiện cấp độ</span>
                            <strong>{{ format_money($item->upgrade_fee) }}<small>$</small></strong>
                        </div>

                        <div class="commission-highlight">
                            <span>Hoa hồng</span>
                            <strong>{{ format_money($item->commission_percentage) }}%</strong>
                            @if ($commissionDelta > 0)<small>+{{ format_money($commissionDelta) }}% so với cấp hiện tại</small>@endif
                        </div>

                        <div class="tier-features">
                            <div class="tier-feature">
                                <span class="feature-icon"><i class="fa-solid fa-list-check"></i></span>
                                <span><small>Nhiệm vụ mỗi ngày</small><strong>{{ $item->spin_count }} nhiệm vụ</strong></span>
                                @if ($taskDelta > 0)<em>+{{ $taskDelta }}</em>@endif
                            </div>
                            <div class="tier-feature">
                                <span class="feature-icon"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                                <span><small>Lượt rút mỗi ngày</small><strong>{{ $item->maximum_number_of_withdrawals }} lượt</strong></span>
                                @if ($withdrawalDelta > 0)<em>+{{ $withdrawalDelta }}</em>@endif
                            </div>
                            <div class="tier-feature">
                                <span class="feature-icon"><i class="fa-solid fa-coins"></i></span>
                                <span><small>Hạn mức mỗi lần rút</small><strong>{{ format_money($item->maximum_withdrawal_amount) }}$</strong></span>
                                @if ($limitDelta > 0)<em>+{{ format_money($limitDelta) }}$</em>@endif
                            </div>
                            <div class="tier-feature">
                                <span class="feature-icon"><i class="fa-solid fa-layer-group"></i></span>
                                <span><small>Giá trị cấp độ</small><strong>{{ format_money($item->value) }}$</strong></span>
                            </div>
                            <div class="tier-feature">
                                <span class="feature-icon"><i class="fa-regular fa-calendar"></i></span>
                                <span><small>Thời hạn</small><strong>Vĩnh viễn</strong></span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</main>
@endsection
