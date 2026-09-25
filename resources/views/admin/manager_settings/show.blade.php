@extends('admin.layouts.master')

@section('title')
    Chi tiết chức năng — {{ $manager_setting->manager_name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('manager_setting.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách chức năng
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-title-icon teal" style="width: 52px; height: 52px; font-size: 1.3rem;">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <h1 class="page-title-main" style="font-size: 1.35rem;">
                    {{ $manager_setting->manager_name }}
                    <span class="id-chip">Code: <code>{{ $manager_setting->manager_code }}</code></span>
                </h1>
                <p class="page-subtitle">
                    ID hệ thống: #{{ $manager_setting->id }} • Ngày thiết lập: {{ $manager_setting->created_at ? $manager_setting->created_at->format('d/m/Y H:i') : '—' }}
                </p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('manager_setting.edit', ['manager_setting' => $manager_setting->id]) }}" class="btn-create-modern">
                <i class="fas fa-pen mr-1"></i> Chỉnh sửa chức năng
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="stats-grid">
        <div class="stat-card-modern teal">
            <div class="stat-content">
                <span class="stat-label">Nhân viên sở hữu quyền</span>
                <span class="stat-number text-teal">{{ number_format($users_with_permission->count()) }}</span>
                <span class="stat-subtext text-muted">
                    <i class="fas fa-user-check text-teal"></i> Tài khoản nhân viên đang bật
                </span>
            </div>
            <div class="stat-icon-wrapper teal">
                <i class="fas fa-users-gear"></i>
            </div>
        </div>
    </div>

    {{-- Bảng nhân viên đang được cấp quyền --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-users"></i> Danh sách nhân viên đang được phân quyền này
            </h6>
        </div>

        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>Nhân viên</th>
                        <th>Số điện thoại</th>
                        <th class="text-center">Trạng thái nhân viên</th>
                        <th>Thời gian cấp quyền</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users_with_permission as $index => $item)
                        @php($staffUser = $item->user)
                        <tr>
                            <td class="text-center"><span class="id-chip">#{{ $index + 1 }}</span></td>
                            <td>
                                @if($staffUser)
                                    <div class="entity-identity-cell">
                                        <div class="user-avatar-circle" style="width: 34px; height: 34px; font-size: 0.8rem;">
                                            <span>{{ mb_strtoupper(mb_substr($staffUser->full_name ?: ($staffUser->username ?: 'S'), 0, 2)) }}</span>
                                        </div>
                                        <div class="entity-details">
                                            <a class="entity-title" href="{{ route('staff.show', ['staff' => $staffUser->id]) }}">
                                                {{ $staffUser->full_name }}
                                            </a>
                                            <span class="entity-subtitle">@<span>{{ $staffUser->username }}</span></span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted font-italic">Tài khoản đã xóa</span>
                                @endif
                            </td>
                            <td>{{ $staffUser->phone ?? '—' }}</td>
                            <td class="text-center">
                                @if(($staffUser->status ?? '') === 'activated')
                                    <span class="badge-status-modern success">Hoạt động</span>
                                @else
                                    <span class="badge-status-modern danger">Bị khóa</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $item->updated_at ? $item->updated_at->format('d/m/Y H:i') : '—' }}</td>
                            <td class="text-center">
                                @if($staffUser && app(\App\Services\AuthorizationService::class)->canManageOperatorPermissions(auth()->user(), $staffUser)
                                    && app(\App\Services\AuthorizationService::class)->can(auth()->user(), config('authorization.capabilities.staff_permissions_view')))
                                    <a href="{{ route('staff.edit.permissions', ['id' => $staffUser->id]) }}" class="btn-action-icon edit" title="Chỉnh sửa quyền nhân viên này">
                                        <i class="fas fa-shield-halved"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-user-lock fa-2x mb-2 d-block" style="opacity: 0.4;"></i>
                                Chưa có nhân viên nào được cấp chức năng này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
