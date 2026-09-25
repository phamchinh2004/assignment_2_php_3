@extends('admin.layouts.master')

@section('title')
    Chi tiết cấp độ — {{ $rank->name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
@php
    $authorization = app(\App\Services\AuthorizationService::class);
    $canUpdateRank = $authorization->can(auth()->user(), config('authorization.capabilities.ranks_update'));
    $canViewCustomerDetail = $authorization->can(auth()->user(), config('authorization.capabilities.customers_view_detail'));
@endphp
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('rank.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách cấp độ
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="entity-thumbnail" style="width: 56px; height: 56px; background: #fffbeb; border-color: #fde68a;">
                @if($rank->image)
                    <img src="{{ Storage::url($rank->image) }}" alt="{{ $rank->name }}">
                @else
                    <i class="fas fa-crown text-warning" style="font-size: 1.6rem;"></i>
                @endif
            </div>
            <div>
                <h1 class="page-title-main" style="font-size: 1.35rem;">
                    {{ $rank->name }}
                    <span class="id-chip">ID: {{ $rank->id }}</span>
                </h1>
                <p class="page-subtitle">
                    Hoa hồng: <b>{{ $rank->commission_percentage }}%</b> • Phí nâng cấp: <b>{{ format_money($rank->upgrade_fee, 2) }}$</b>
                </p>
            </div>
        </div>

        @if ($canUpdateRank)
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('rank.edit', ['rank' => $rank->id]) }}" class="btn-create-modern">
                <i class="fas fa-pen mr-1"></i> Chỉnh sửa cấp độ
            </a>
        </div>
        @endif
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern warning">
            <div class="stat-content">
                <span class="stat-label">Hoa hồng nhận được</span>
                <span class="stat-number text-warning">{{ $rank->commission_percentage }}%</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-percent text-warning"></i> Tính theo giá trị đơn
                </span>
            </div>
            <div class="stat-icon-wrapper warning">
                <i class="fas fa-award"></i>
            </div>
        </div>

        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Số đơn / vòng quay</span>
                <span class="stat-number text-primary">{{ $rank->spin_count }} đơn</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-layer-group text-primary"></i> Tổng giá: {{ format_money($rank->value, 2) }}$
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-boxes-stacked"></i>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Thành viên ở cấp này</span>
                <span class="stat-number text-success">{{ number_format($users->total() ?? 0) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-users text-success"></i> Người dùng đang sở hữu
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
    </div>

    {{-- 2-Column Detail Cards --}}
    <div class="detail-grid-modern mb-4">
        {{-- Card 1: Thông số đơn hàng & Phí --}}
        <div class="detail-card-modern">
            <h5 class="detail-card-title">
                <i class="fas fa-sliders"></i> Thông số đơn hàng & Phí kích hoạt
            </h5>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Tên cấp bậc</span>
                <span class="detail-value-modern">{{ $rank->name }}</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Phí nâng cấp</span>
                <span class="detail-value-modern font-weight-bold text-primary">{{ format_money($rank->upgrade_fee, 2) }}$</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Hoa hồng</span>
                <span class="detail-value-modern font-weight-bold text-success">{{ $rank->commission_percentage }}%</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Số đơn hàng cho phép</span>
                <span class="detail-value-modern">{{ $rank->spin_count }} đơn hàng</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Tổng giá trị đơn hàng</span>
                <span class="detail-value-modern">{{ format_money($rank->value, 2) }}$</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Số đơn mẫu đã tạo trong kho</span>
                <span class="detail-value-modern"><span class="id-chip">{{ number_format($rank->orders_count ?? 0) }} đơn</span></span>
            </div>
        </div>

        {{-- Card 2: Quy định rút tiền & Hệ thống --}}
        <div class="detail-card-modern">
            <h5 class="detail-card-title">
                <i class="fas fa-building-columns"></i> Hạn mức rút tiền & Thời gian
            </h5>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Số lần rút tối đa / ngày</span>
                <span class="detail-value-modern">{{ $rank->maximum_number_of_withdrawals }} lần</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Số tiền rút tối đa / lượt</span>
                <span class="detail-value-modern font-weight-bold text-danger">{{ format_money($rank->maximum_withdrawal_amount, 2) }}$</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Ngày tạo</span>
                <span class="detail-value-modern">{{ $rank->created_at ? $rank->created_at->format('d/m/Y H:i') : '—' }}</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Cập nhật cuối</span>
                <span class="detail-value-modern">{{ $rank->updated_at ? $rank->updated_at->format('d/m/Y H:i') : '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Bảng danh sách thành viên ở cấp độ này --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-users"></i> Danh sách thành viên đang ở cấp độ này
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>Khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Số dư khả dụng</th>
                        <th class="text-center">Trạng thái</th>
                        <th>Ngày tham gia</th>
                        <th class="text-center">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $u)
                        <tr>
                            <td class="text-center"><span class="id-chip">#{{ $index + 1 }}</span></td>
                            <td>
                                <div class="entity-identity-cell">
                                    <div class="user-avatar-circle" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                        <span>{{ mb_strtoupper(mb_substr($u->full_name ?: ($u->username ?: 'U'), 0, 2)) }}</span>
                                    </div>
                                    <div class="entity-details">
                                        <span class="font-weight-bold text-dark">{{ $u->full_name ?: 'Chưa đặt tên' }}</span>
                                        <span class="entity-subtitle">@<span>{{ $u->username }}</span></span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $u->phone ?: '—' }}</td>
                            <td><strong class="text-primary">{{ format_money($u->balance, 2) }}$</strong></td>
                            <td class="text-center">
                                @if($u->status === 'activated')
                                    <span class="badge-status-modern success">Hoạt động</span>
                                @else
                                    <span class="badge-status-modern danger">Bị khóa</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $u->created_at ? $u->created_at->format('d/m/Y') : '—' }}</td>
                            <td class="text-center">
                                @if ($canViewCustomerDetail)
                                <a href="{{ route('user.show', ['user' => $u->id]) }}" class="btn-action-icon view" title="Xem hồ sơ">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="fas fa-users-slash fa-2x mb-2 d-block" style="opacity: 0.4;"></i>
                                Chưa có thành viên nào đang ở cấp bậc này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="p-3">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
