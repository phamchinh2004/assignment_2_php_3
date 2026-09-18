@extends('admin.layouts.master')

@section('title')
Chi tiết người dùng
@endsection

@section('style-libs')
@vite('resources/css/admin/user/index.css')
<style>
    .user-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .user-detail-section {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1rem;
    }

    .user-detail-section.full-width {
        grid-column: 1 / -1;
    }

    .user-detail-section h5 {
        margin: 0 0 1rem;
        font-weight: 700;
        color: #2d3748;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.55rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-row:last-child {
        border-bottom: 0;
    }

    .detail-label {
        color: #6c757d;
        font-weight: 500;
    }

    .detail-value {
        color: #2d3748;
        font-weight: 600;
        text-align: right;
        word-break: break-word;
    }

    .balance-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .balance-detail-item {
        border-radius: 8px;
        padding: 0.9rem;
        background: #f8f9fa;
    }

    .balance-detail-item strong {
        display: block;
        margin-top: 0.35rem;
        font-size: 1.15rem;
    }

    .detail-table {
        width: 100%;
        margin: 0;
    }

    .detail-table th,
    .detail-table td {
        padding: 0.65rem;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }

    @media (max-width: 768px) {
        .user-detail-grid,
        .balance-detail-grid {
            grid-template-columns: 1fr;
        }

        .user-detail-section.full-width {
            grid-column: auto;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Chi tiết người dùng</h1>
            <p class="mb-0 text-muted">Thông tin tài khoản #{{ $user->id }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('user.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
            <a href="{{ route('user.edit', ['user' => $user->id]) }}" class="btn btn-warning">
                <i class="fas fa-pen mr-1"></i> Chỉnh sửa
            </a>
        </div>
    </div>

    <div class="user-detail-grid">
        <section class="user-detail-section">
            <h5><i class="fas fa-user mr-2"></i>Thông tin tài khoản</h5>
            <div class="detail-row"><span class="detail-label">ID</span><span class="detail-value">{{ $user->id }}</span></div>
            <div class="detail-row"><span class="detail-label">Họ và tên</span><span class="detail-value">{{ $user->full_name }}</span></div>
            <div class="detail-row"><span class="detail-label">Username</span><span class="detail-value">{{ $user->username }}</span></div>
            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">{{ $user->email ?: 'Chưa có' }}</span></div>
            <div class="detail-row"><span class="detail-label">Số điện thoại</span><span class="detail-value">{{ $user->phone ?: 'Chưa có' }}</span></div>
            <div class="detail-row"><span class="detail-label">Trạng thái</span><span class="detail-value">{{ $user->status }}</span></div>
        </section>

        <section class="user-detail-section">
            <h5><i class="fas fa-wallet mr-2"></i>Số dư và cấp bậc</h5>
            <div class="balance-detail-grid">
                <div class="balance-detail-item">
                    <span class="detail-label">Số dư</span>
                    <strong class="text-success">{{ format_money($user->balance ?? 0, 5) }}$</strong>
                </div>
                <div class="balance-detail-item">
                    <span class="detail-label">Số dư đóng băng</span>
                    <strong class="text-primary">{{ format_money($user->frozen_balance ?? 0, 5) }}$</strong>
                </div>
            </div>
            <div class="detail-row"><span class="detail-label">Cấp bậc</span><span class="detail-value">{{ optional($user->rank)->name ?: 'Chưa có cấp bậc' }}</span></div>
            <div class="detail-row"><span class="detail-label">Phân phối hôm nay</span><span class="detail-value">{{ $user->distribution_today ?? 0 }}</span></div>
            <div class="detail-row"><span class="detail-label">Hoa hồng hôm nay</span><span class="detail-value">{{ format_money($user->todays_discount ?? 0, 5) }}$</span></div>
            <div class="detail-row"><span class="detail-label">Mã giới thiệu</span><span class="detail-value">{{ $user->referral_code ?: 'Chưa có' }}</span></div>
        </section>

        <section class="user-detail-section full-width">
            <h5><i class="fas fa-location-dot mr-2"></i>Vị trí hiện tại</h5>
            <div class="detail-row">
                <span class="detail-label">Quyền truy cập</span>
                <span class="detail-value">
                    @if($user->location_permission === 'granted')
                        <span class="text-success"><i class="fas fa-check-circle mr-1"></i>Đã cấp quyền</span>
                    @elseif($user->location_permission === 'denied')
                        <span class="text-danger"><i class="fas fa-ban mr-1"></i>Đã từ chối</span>
                    @else
                        <span class="text-warning"><i class="fas fa-clock mr-1"></i>Chưa hỏi quyền</span>
                    @endif
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Khu vực</span>
                <span class="detail-value">
                    @if($user->location_country_code)
                        <span class="fs-5 mr-1">{{ country_flag($user->location_country_code) }}</span>
                    @endif
                    {{ $user->location_city ?: 'Chưa xác định thành phố' }}{{ $user->location_country ? ', ' . $user->location_country : '' }}
                </span>
            </div>
            <div class="detail-row"><span class="detail-label">Tọa độ</span><span class="detail-value">{{ $user->location_latitude !== null && $user->location_longitude !== null ? $user->location_latitude . ', ' . $user->location_longitude : 'Chưa có' }}</span></div>
            <div class="detail-row"><span class="detail-label">Độ chính xác</span><span class="detail-value">{{ $user->location_accuracy !== null ? number_format($user->location_accuracy, 2) . ' m' : 'Chưa có' }}</span></div>
            <div class="detail-row"><span class="detail-label">Cập nhật lần cuối</span><span class="detail-value">{{ $user->location_updated_at ? $user->location_updated_at->format('d/m/Y H:i:s') : 'Chưa có' }}</span></div>
        </section>

        <section class="user-detail-section">
            <h5><i class="fas fa-building-columns mr-2"></i>Thông tin ngân hàng</h5>
            <div class="detail-row"><span class="detail-label">Tên tài khoản</span><span class="detail-value">{{ $user->username_bank ?: 'Chưa liên kết' }}</span></div>
            <div class="detail-row"><span class="detail-label">Số tài khoản</span><span class="detail-value">{{ $user->account_number ?: 'Chưa liên kết' }}</span></div>
            <div class="detail-row"><span class="detail-label">Ngân hàng</span><span class="detail-value">{{ $user->bank_name ?: 'Chưa liên kết' }}</span></div>
            <div class="detail-row"><span class="detail-label">Người giới thiệu</span><span class="detail-value">{{ optional($user->referrer)->full_name ?: 'Không có' }}</span></div>
        </section>

        <section class="user-detail-section">
            <h5><i class="fas fa-chart-line mr-2"></i>Tiến trình</h5>
            @if($user->user_spin_progress)
                <div class="detail-row"><span class="detail-label">Cấp tiến trình</span><span class="detail-value">{{ $user->user_spin_progress->rank_id }}</span></div>
                <div class="detail-row"><span class="detail-label">Đã xử lý</span><span class="detail-value">{{ $user->user_spin_progress->current_spin }}</span></div>
            @else
                <p class="text-muted mb-0">Chưa có tiến trình phân phối.</p>
            @endif
        </section>

        <section class="user-detail-section full-width">
            <h5><i class="fas fa-box mr-2"></i>Đơn hàng gần đây</h5>
            <div class="table-responsive">
                <table class="detail-table">
                    <thead>
                        <tr><th>Mã đơn</th><th>Loại</th><th>Trạng thái</th><th>Số tiền</th><th>Ngày cập nhật</th></tr>
                    </thead>
                    <tbody>
                        @forelse($user->frozen_orders->sortByDesc('updated_at')->take(10) as $frozenOrder)
                            <tr>
                                <td>{{ $frozenOrder->order->order_code ?? 'N/A' }}</td>
                                <td>{{ $frozenOrder->custom_price !== null ? 'Đặc biệt' : 'Bình thường' }}</td>
                                <td>{{ $frozenOrder->status ?: 'Chưa có' }}</td>
                                <td>{{ format_money($frozenOrder->custom_price ?? (($frozenOrder->order->price ?? 0) * ($frozenOrder->order->quantity ?? 0)), 5) }}$</td>
                                <td>{{ optional($frozenOrder->updated_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Chưa có đơn hàng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
