@extends('admin.layouts.master')

@section('title', 'Phần thưởng vòng quay')

@section('style-libs')
    @vite(['resources/css/admin/common-modern.css', 'resources/css/admin/lucky-wheel-rewards.css'])
@endsection

@section('content')
<div class="container-fluid px-4 pb-5 reward-page">
    <header class="reward-hero">
        <div>
            <span class="reward-kicker"><i class="fas fa-gift"></i> Vòng quay may mắn</span>
            <h1>Quản lý phần thưởng</h1>
            <p>Duyệt phần thưởng tiền mặt và cộng vào tài khoản dưới dạng giao dịch tiền thưởng.</p>
        </div>
        <form class="auto-approval-card" method="POST" action="{{ route('lucky_wheel_rewards.auto_approval') }}">
            @csrf
            <input type="hidden" name="enabled" value="{{ $setting->auto_approve_rewards ? 0 : 1 }}">
            <div>
                <small>Tự động duyệt</small>
                <strong>{{ $setting->auto_approve_rewards ? 'Đang bật' : 'Đang tắt' }}</strong>
                <span>{{ $setting->auto_approve_rewards ? 'Lượt quay mới được cộng tiền ngay.' : 'Lượt quay mới sẽ chờ quản trị viên duyệt.' }}</span>
            </div>
            <button type="submit" class="reward-switch {{ $setting->auto_approve_rewards ? 'is-on' : '' }}"
                aria-label="{{ $setting->auto_approve_rewards ? 'Tắt tự động duyệt' : 'Bật tự động duyệt' }}"><span></span></button>
        </form>
    </header>

    @if(session('success'))
        <div class="reward-alert is-success" role="status"><i class="fas fa-circle-check"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="reward-alert is-danger" role="alert"><i class="fas fa-circle-exclamation"></i>{{ $errors->first() }}</div>
    @endif

    <section class="reward-stats" aria-label="Tổng quan phần thưởng">
        <article><span><i class="fas fa-receipt"></i></span><div><small>Tổng phần thưởng</small><strong>{{ number_format($counts['total']) }}</strong></div></article>
        <article><span><i class="fas fa-clock"></i></span><div><small>Chờ duyệt</small><strong>{{ number_format($counts['pending']) }}</strong></div></article>
        <article><span><i class="fas fa-circle-check"></i></span><div><small>Đã duyệt</small><strong>{{ number_format($counts['approved']) }}</strong></div></article>
        <article><span><i class="fas fa-circle-xmark"></i></span><div><small>Đã từ chối</small><strong>{{ number_format($counts['rejected']) }}</strong></div></article>
        <article><span><i class="fas fa-dollar-sign"></i></span><div><small>Đã trả thưởng</small><strong>${{ number_format($counts['paid_amount'], 2) }}</strong></div></article>
    </section>

    <section class="reward-card">
        <div class="reward-card__head">
            <div><span class="reward-kicker">Reward queue</span><h2>Danh sách phần thưởng</h2></div>
            <nav class="reward-filters" aria-label="Lọc trạng thái">
                <a class="{{ !$selectedStatus ? 'is-active' : '' }}" href="{{ route('lucky_wheel_rewards.index') }}">Tất cả</a>
                <a class="{{ $selectedStatus === \App\Models\LuckyWheelSpin::STATUS_PENDING ? 'is-active' : '' }}" href="{{ route('lucky_wheel_rewards.index', ['status' => 'pending']) }}">Chờ duyệt <span>{{ $counts['pending'] }}</span></a>
                <a class="{{ $selectedStatus === \App\Models\LuckyWheelSpin::STATUS_APPROVED ? 'is-active' : '' }}" href="{{ route('lucky_wheel_rewards.index', ['status' => 'approved']) }}">Đã duyệt</a>
                <a class="{{ $selectedStatus === \App\Models\LuckyWheelSpin::STATUS_REJECTED ? 'is-active' : '' }}" href="{{ route('lucky_wheel_rewards.index', ['status' => 'rejected']) }}">Đã từ chối</a>
            </nav>
        </div>

        <div class="reward-table-wrap">
            <table class="reward-table">
                <thead><tr><th>Người dùng</th><th>Phần thưởng</th><th>Nguồn</th><th>Trạng thái</th><th>Thời gian</th><th>Xử lý bởi</th><th>Thao tác</th></tr></thead>
                <tbody>
                @forelse($rewards as $reward)
                    @php
                        $displayName = $reward->user?->full_name ?: ($reward->user?->username ?: 'Người dùng #' . $reward->user_id);
                        $initial = mb_strtoupper(mb_substr($displayName, 0, 1));
                    @endphp
                    <tr>
                        <td data-label="Người dùng"><a class="reward-user" href="{{ $reward->user ? route('user.show', $reward->user) : '#' }}"><b>{{ $initial }}</b><span><strong>{{ $displayName }}</strong><small>#{{ $reward->user_id }}</small></span></a></td>
                        <td data-label="Phần thưởng"><div class="reward-amount"><strong>+${{ number_format((float)$reward->reward_amount, 2) }}</strong><small>{{ $reward->prize }}</small></div></td>
                        <td data-label="Nguồn"><span class="reward-source"><i class="fas {{ $reward->spin_type === \App\Models\LuckyWheelSpin::TYPE_ADMIN_BONUS ? 'fa-ticket' : 'fa-rotate' }}"></i>{{ $reward->spin_type === \App\Models\LuckyWheelSpin::TYPE_ADMIN_BONUS ? 'Lượt admin cấp' : 'Hoàn thành hằng ngày' }}</span></td>
                        <td data-label="Trạng thái"><span class="reward-status is-{{ $reward->reward_status }}"><i class="fas {{ $reward->reward_status === 'approved' ? 'fa-circle-check' : ($reward->reward_status === 'rejected' ? 'fa-circle-xmark' : 'fa-clock') }}"></i>{{ $reward->rewardStatusLabel() }}</span></td>
                        <td data-label="Thời gian"><time><strong>{{ $reward->created_at?->format('d/m/Y') }}</strong><small>{{ $reward->created_at?->format('H:i') }}</small></time></td>
                        <td data-label="Xử lý bởi">
                            @if(in_array($reward->reward_status, [\App\Models\LuckyWheelSpin::STATUS_APPROVED, \App\Models\LuckyWheelSpin::STATUS_REJECTED], true))
                                <div class="reward-approved-by"><strong>{{ $reward->approval_method === 'automatic' ? 'Hệ thống' : ($reward->handledBy?->full_name ?: $reward->handledBy?->username) }}</strong><small>{{ $reward->reward_status === \App\Models\LuckyWheelSpin::STATUS_REJECTED ? 'Từ chối thủ công' : ($reward->approval_method === 'automatic' ? 'Tự động duyệt' : 'Duyệt thủ công') }}</small></div>
                            @else
                                <span class="reward-muted">—</span>
                            @endif
                        </td>
                        <td data-label="Thao tác">
                            @if($reward->reward_status === \App\Models\LuckyWheelSpin::STATUS_PENDING)
                                <div class="reward-actions">
                                    <form method="POST" action="{{ route('lucky_wheel_rewards.approve', $reward) }}">@csrf<button class="reward-approve" type="submit"><i class="fas fa-check"></i>Duyệt & cộng ${{ number_format((float)$reward->reward_amount, 2) }}</button></form>
                                    <form method="POST" action="{{ route('lucky_wheel_rewards.reject', $reward) }}">@csrf<button class="reward-reject" type="submit"><i class="fas fa-xmark"></i>Từ chối</button></form>
                                </div>
                            @else
                                <span class="reward-done"><i class="fas {{ $reward->reward_status === 'rejected' ? 'fa-ban' : 'fa-check-double' }}"></i> Hoàn tất</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="reward-empty"><i class="fas fa-gift"></i><strong>Chưa có phần thưởng phù hợp</strong><p>Các phần thưởng tiền mặt mới sẽ xuất hiện tại đây.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($rewards->hasPages())<div class="reward-pagination">{{ $rewards->links() }}</div>@endif
    </section>
    <p class="reward-note"><i class="fas fa-circle-info"></i> Tự động duyệt chỉ áp dụng cho lượt quay mới; các phần thưởng đang chờ vẫn cần duyệt thủ công.</p>
</div>
@endsection
