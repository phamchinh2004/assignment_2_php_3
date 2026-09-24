@extends('admin.layouts.master')

@section('title')
    Chi tiết người dùng — {{ $user->full_name ?: $user->username }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back link --}}
    <a href="{{ route('user.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="user-avatar-circle" style="width: 56px; height: 56px; font-size: 1.4rem;">
                @if(!empty($user->avatar))
                    <img src="{{ get_user_avatar($user) }}" alt="{{ $user->username }}" class="user-avatar-img">
                @else
                    <span>{{ mb_strtoupper(mb_substr($user->full_name ?: ($user->username ?: 'U'), 0, 2)) }}</span>
                @endif
            </div>
            <div>
                <h1 class="page-title-main" style="font-size: 1.35rem;">
                    {{ $user->full_name ?: 'Chưa đặt tên' }}
                    <span class="id-chip">ID: {{ $user->id }}</span>
                    @if($user->status === 'activated')
                        <span class="badge-status-modern success"><span class="status-dot"></span> Đã kích hoạt</span>
                    @elseif($user->status === 'inactivated')
                        <span class="badge-status-modern warning"><span class="status-dot"></span> Chưa kích hoạt</span>
                    @else
                        <span class="badge-status-modern danger"><span class="status-dot"></span> Bị khóa</span>
                    @endif
                </h1>
                <p class="page-subtitle">
                    <span><i class="fas fa-at"></i> {{ $user->username }}</span> •
                    <span><i class="fas fa-phone"></i> {{ $user->phone ?: 'Chưa cập nhật' }}</span> •
                    <span><i class="fas fa-calendar-alt"></i> Ngày tham gia: {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '—' }}</span>
                </p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('user.edit', ['user' => $user->id]) }}" class="btn-create-modern">
                <i class="fas fa-pen-to-square"></i>
                <span>Chỉnh sửa hồ sơ</span>
            </a>
        </div>
    </div>

    {{-- KPI Cards: Tài chính & Tiến độ --}}
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Số dư khả dụng</span>
                <span class="stat-number text-primary">{{ format_money($user->balance, 2) }}$</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-wallet text-primary"></i> Khả dụng giao dịch
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>

        <div class="stat-card-modern info">
            <div class="stat-content">
                <span class="stat-label">Số dư đóng băng</span>
                <span class="stat-number text-info">{{ format_money($user->frozen_balance, 2) }}$</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-snowflake text-info"></i> Tạm giữ theo đơn
                </span>
            </div>
            <div class="stat-icon-wrapper info">
                <i class="fas fa-lock"></i>
            </div>
        </div>

        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Cấp độ (Rank)</span>
                <span class="stat-number" style="font-size: 1.35rem; color: #b45309;">
                    {{ $user->rank->name ?? 'Chưa có cấp' }}
                </span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-percent text-warning"></i> Hoa hồng: {{ $user->rank->commission_percentage ?? 0 }}%
                </span>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-crown"></i>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Tiến độ đơn hàng</span>
                <span class="stat-number text-success">
                    {{ $user->user_spin_progress->current_spin ?? 0 }} / {{ $user->rank->spin_count ?? 0 }}
                </span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-spinner text-success"></i> Vòng quay hiện tại
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-shopping-bag"></i>
            </div>
        </div>
    </div>

    {{-- 2-Column Detail Cards --}}
    <div class="detail-grid-modern mb-4">
        {{-- Card 1: Thông tin tài khoản --}}
        <div class="detail-card-modern">
            <h5 class="detail-card-title">
                <i class="fas fa-user-shield"></i> Thông tin tài khoản & Bảo mật
            </h5>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Họ và tên</span>
                <span class="detail-value-modern">{{ $user->full_name ?: 'Chưa cập nhật' }}</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Tên đăng nhập</span>
                <span class="detail-value-modern">@<span>{{ $user->username }}</span></span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Số điện thoại</span>
                <span class="detail-value-modern">{{ $user->phone ?: 'Chưa cập nhật' }}</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Email</span>
                <span class="detail-value-modern">{{ $user->email ?: 'Chưa cập nhật' }}</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Mã giới thiệu</span>
                <span class="detail-value-modern"><span class="id-chip">{{ $user->referral_code ?: '—' }}</span></span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Người giới thiệu</span>
                <span class="detail-value-modern">
                    @if($user->referrer)
                        <a href="{{ route('user.show', ['user' => $user->referrer->id]) }}" class="text-primary font-weight-bold text-decoration-none">
                            {{ $user->referrer->full_name ?: $user->referrer->username }} (#{{ $user->referrer->id }})
                        </a>
                    @else
                        <span class="text-muted">Không có</span>
                    @endif
                </span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Loại tài khoản</span>
                <span class="detail-value-modern">
                    @if($user->clone_account)
                        <span class="badge-status-modern secondary">Tài khoản Clone</span>
                    @else
                        <span class="badge-status-modern info">Thành viên thực</span>
                    @endif
                </span>
            </div>
        </div>

        {{-- Card 2: Thông tin giao dịch & Vị trí --}}
        @php
            $displayIp = $user->last_login_ip ?: $user->register_ip;
            $displayCountryCode = $user->location_country_code ?: $user->approx_location_country_code;
            $displayCountry = $user->location_country ?: $user->approx_location_country;
            $displayCity = $user->location_city;
            $displayLastLoginAt = $user->last_login_at ?: $user->last_seen;
        @endphp
        <div class="detail-card-modern">
            <h5 class="detail-card-title">
                <i class="fas fa-map-marker-alt"></i> Vị trí & Thiết bị đăng nhập
            </h5>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Địa chỉ IP gần nhất</span>
                <span class="detail-value-modern"><code>{{ $displayIp ?: '—' }}</code></span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Quốc gia / Khu vực</span>
                <span class="detail-value-modern">
                    @if($displayCountry || $displayCountryCode || $displayCity)
                        {{ $displayCountry ?: strtoupper((string) $displayCountryCode) }}
                        @if($displayCountry && $displayCountryCode)
                            ({{ strtoupper($displayCountryCode) }})
                        @endif
                        @if($displayCity)
                            · {{ $displayCity }}
                        @endif
                    @else
                        —
                    @endif
                </span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Thời gian đăng nhập cuối</span>
                <span class="detail-value-modern">{{ $displayLastLoginAt?->format('d/m/Y H:i:s') ?: '—' }}</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Ngày đăng ký</span>
                <span class="detail-value-modern">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i:s') : '—' }}</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Cập nhật gần nhất</span>
                <span class="detail-value-modern">{{ $user->updated_at ? $user->updated_at->format('d/m/Y H:i:s') : '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Card 3: Lịch sử biến động số dư gần nhất --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-receipt"></i> Lịch sử biến động số dư gần nhất (10 giao dịch)
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Loại giao dịch</th>
                        <th>Số tiền</th>
                        <th>Số dư trước</th>
                        <th>Số dư sau</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($user->wallet_balance_histories as $index => $history)
                        <tr>
                            <td><span class="id-chip">#{{ $index + 1 }}</span></td>
                            <td>
                                @if($history->type === 'deposit')
                                    <span class="badge-status-modern success"><i class="fas fa-arrow-down"></i> Nạp tiền</span>
                                @elseif($history->type === 'withdraw')
                                    <span class="badge-status-modern danger"><i class="fas fa-arrow-up"></i> Rút tiền</span>
                                @else
                                    <span class="badge-status-modern info">{{ $history->type }}</span>
                                @endif
                            </td>
                            <td>
                                <strong class="{{ $history->value >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $history->value >= 0 ? '+' : '' }}{{ format_money($history->value, 2) }}$
                                </strong>
                            </td>
                            <td>{{ format_money($history->initial_balance, 2) }}$</td>
                            <td><strong>{{ format_money($history->final_balance, 2) }}$</strong></td>
                            <td class="text-muted">{{ $history->created_at ? $history->created_at->format('d/m/Y H:i') : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block text-muted" style="opacity: 0.5;"></i>
                                Chưa có lịch sử biến động số dư nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
