@extends('admin.layouts.master')

@section('title')
    Chi tiết nhân viên — {{ $staff->full_name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
@php
    $activePermissions = $staff->user_manager_settings ? $staff->user_manager_settings->where('is_active', true) : collect();
@endphp

<div class="container-fluid px-4 pb-5">

    {{-- Back link --}}
    <a href="{{ route('staff.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách nhân viên
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="user-avatar-circle" style="width: 56px; height: 56px; font-size: 1.4rem;">
                <span>{{ mb_strtoupper(mb_substr($staff->full_name ?: ($staff->username ?: 'S'), 0, 2)) }}</span>
            </div>
            <div>
                <h1 class="page-title-main" style="font-size: 1.35rem;">
                    {{ $staff->full_name ?: 'Chưa đặt tên' }}
                    <span class="id-chip">ID: {{ $staff->id }}</span>
                    @if($staff->status === 'activated')
                        <span class="badge-status-modern success"><span class="status-dot"></span> Đang hoạt động</span>
                    @elseif($staff->status === 'inactivated')
                        <span class="badge-status-modern warning"><span class="status-dot"></span> Chưa kích hoạt</span>
                    @else
                        <span class="badge-status-modern danger"><span class="status-dot"></span> Đã bị khóa</span>
                    @endif
                </h1>
                <p class="page-subtitle">
                    <span><i class="fas fa-at"></i> {{ $staff->username }}</span> •
                    <span><i class="fas fa-phone"></i> {{ $staff->phone ?: 'Chưa có SĐT' }}</span> •
                    <span><i class="fas fa-calendar-alt"></i> Ngày tạo: {{ $staff->created_at ? $staff->created_at->format('d/m/Y H:i') : '—' }}</span>
                </p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('staff.edit.permissions', ['id' => $staff->id]) }}" class="btn btn-outline-primary btn-sm px-3" style="border-radius: 8px; font-weight: 600;">
                <i class="fas fa-shield-halved mr-1"></i> Phân quyền
            </a>
            <a href="{{ route('staff.edit', ['staff' => $staff->id]) }}" class="btn-create-modern">
                <i class="fas fa-pen mr-1"></i> Chỉnh sửa
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern primary">
            <div class="stat-content">
                <span class="stat-label">Tổng doanh số nạp</span>
                <span class="stat-number text-primary">{{ format_money($staff->total_deposit ?? 0, 2) }}$</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-money-bill-transfer text-primary"></i> Giao dịch thành công
                </span>
            </div>
            <div class="stat-icon-wrapper primary">
                <i class="fas fa-sack-dollar"></i>
            </div>
        </div>

        <div class="stat-card-modern teal">
            <div class="stat-content">
                <span class="stat-label">Thành viên quản lý</span>
                <span class="stat-number">{{ number_format($referrals->total() ?? 0) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-users text-teal"></i> Tài khoản đã tạo / mời
                </span>
            </div>
            <div class="stat-icon-wrapper teal">
                <i class="fas fa-user-group"></i>
            </div>
        </div>

        <div class="stat-card-modern success">
            <div class="stat-content">
                <span class="stat-label">Quyền hạn được cấp</span>
                <span class="stat-number text-success">{{ number_format($activePermissions->count()) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-check-shield text-success"></i> Chức năng đang bật
                </span>
            </div>
            <div class="stat-icon-wrapper success">
                <i class="fas fa-shield-check"></i>
            </div>
        </div>
    </div>

    {{-- 2-Column Detail Cards --}}
    <div class="detail-grid-modern mb-4">
        {{-- Card 1: Thông tin nhân sự --}}
        <div class="detail-card-modern">
            <h5 class="detail-card-title">
                <i class="fas fa-id-card"></i> Thông tin hồ sơ nhân sự
            </h5>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Họ và tên</span>
                <span class="detail-value-modern">{{ $staff->full_name }}</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Tên đăng nhập</span>
                <span class="detail-value-modern">@<span>{{ $staff->username }}</span></span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Số điện thoại</span>
                <span class="detail-value-modern">{{ $staff->phone ?: 'Chưa cập nhật' }}</span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Mã giới thiệu</span>
                <span class="detail-value-modern"><span class="id-chip">{{ $staff->referral_code ?: '—' }}</span></span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Người quản lý / Tạo</span>
                <span class="detail-value-modern">
                    @if($staff->referrer)
                        {{ $staff->referrer->full_name }} (@<span>{{ $staff->referrer->username }}</span>)
                    @else
                        <span class="text-muted">Quản trị viên tối cao</span>
                    @endif
                </span>
            </div>
            <div class="detail-row-modern">
                <span class="detail-label-modern">Ngày khởi tạo</span>
                <span class="detail-value-modern">{{ $staff->created_at ? $staff->created_at->format('d/m/Y H:i:s') : '—' }}</span>
            </div>
        </div>

        {{-- Card 2: Danh sách quyền đang sở hữu --}}
        <div class="detail-card-modern">
            <h5 class="detail-card-title">
                <i class="fas fa-shield-halved"></i> Quyền hạn hệ thống đang hoạt động
            </h5>
            @if($activePermissions->count() > 0)
                <div class="d-flex flex-wrap" style="gap: 8px;">
                    @foreach($activePermissions as $perm)
                        <div class="badge-status-modern success" style="padding: 6px 12px; font-size: 0.8125rem;">
                            <i class="fas fa-check-circle mr-1"></i> {{ $perm->manager_setting->manager_name ?? $perm->manager_setting_id }}
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-lock fa-2x mb-2 d-block" style="opacity: 0.4;"></i>
                    Chưa được cấp quyền hạn nào.
                </div>
            @endif
        </div>
    </div>

    {{-- Bảng danh sách thành viên giới thiệu --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-users"></i> Danh sách thành viên do nhân viên quản lý
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>Khách hàng</th>
                        <th>Số điện thoại</th>
                        <th>Cấp độ</th>
                        <th>Số dư</th>
                        <th class="text-center">Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="text-center">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($referrals as $index => $ref)
                        <tr>
                            <td class="text-center"><span class="id-chip">#{{ $index + 1 }}</span></td>
                            <td>
                                <div class="entity-identity-cell">
                                    <div class="user-avatar-circle" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                        <span>{{ mb_strtoupper(mb_substr($ref->full_name ?: ($ref->username ?: 'U'), 0, 2)) }}</span>
                                    </div>
                                    <div class="entity-details">
                                        <span class="font-weight-bold text-dark">{{ $ref->full_name ?: 'Chưa đặt tên' }}</span>
                                        <span class="entity-subtitle">@<span>{{ $ref->username }}</span></span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $ref->phone ?: '—' }}</td>
                            <td><span class="badge-status-modern info" style="font-size: 0.75rem;">{{ $ref->rank->name ?? 'Mặc định' }}</span></td>
                            <td><strong class="text-primary">{{ format_money($ref->balance, 2) }}$</strong></td>
                            <td class="text-center">
                                @if($ref->status === 'activated')
                                    <span class="badge-status-modern success">Hoạt động</span>
                                @else
                                    <span class="badge-status-modern danger">Bị khóa</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $ref->created_at ? $ref->created_at->format('d/m/Y') : '—' }}</td>
                            <td class="text-center">
                                <a href="{{ route('user.show', ['user' => $ref->id]) }}" class="btn-action-icon view" title="Xem người dùng">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-user-slash fa-2x mb-2 d-block" style="opacity: 0.4;"></i>
                                Chưa có thành viên nào được quản lý bởi nhân viên này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($referrals->hasPages())
            <div class="p-3">
                {{ $referrals->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
