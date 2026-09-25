@extends('admin.layouts.master')

@section('title', 'Thông báo tính năng')

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
@php
    $authorization = app(\App\Services\AuthorizationService::class);
    $capabilities = config('authorization.capabilities');
    $currentUser = auth()->user();
    $canCreate = $authorization->can($currentUser, $capabilities['feature_announcements_create']);
    $canUpdate = $authorization->can($currentUser, $capabilities['feature_announcements_update']);
    $canToggle = $authorization->can($currentUser, $capabilities['feature_announcements_toggle']);
    $canDelete = $authorization->can($currentUser, $capabilities['feature_announcements_delete']);
    $canReport = $authorization->can($currentUser, $capabilities['feature_announcements_view_report']);
    $roleLabels = ['own' => 'Chủ hệ thống', 'admin' => 'Admin', 'staff' => 'Nhân viên'];
@endphp

<div class="container-fluid px-4 pb-5">
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon info"><i class="fas fa-bullhorn"></i></span>
                Thông báo tính năng
            </h1>
            <p class="page-subtitle">Quản lý thông báo bắt buộc xác nhận dành cho đội ngũ quản trị.</p>
        </div>
        @if($canCreate)
            <a href="{{ route('feature_announcements.create') }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-plus"></i> <span>Tạo thông báo</span>
            </a>
        @endif
    </div>

    <div class="table-card-modern">
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th>Thông báo</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Đối tượng</th>
                        <th>Đã xác nhận</th>
                        <th>Người tạo</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $announcement)
                        @php
                            $stats = $announcementStats[$announcement->id] ?? ['acknowledged_count' => 0, 'target_count' => 0];
                            $now = now();
                            if (!$announcement->is_active) {
                                $statusLabel = 'Đã tắt';
                                $statusClass = 'secondary';
                            } elseif ($announcement->starts_at->isAfter($now)) {
                                $statusLabel = 'Sắp diễn ra';
                                $statusClass = 'info';
                            } elseif ($announcement->ends_at && $announcement->ends_at->isBefore($now)) {
                                $statusLabel = 'Đã hết hạn';
                                $statusClass = 'secondary';
                            } else {
                                $statusLabel = 'Đang hiển thị';
                                $statusClass = 'success';
                            }
                            $priorityLabel = match($announcement->priority) {
                                'critical' => 'Khẩn cấp',
                                'important' => 'Quan trọng',
                                default => 'Bình thường',
                            };
                        @endphp
                        <tr>
                            <td style="min-width: 240px;">
                                <strong class="d-block">{{ $announcement->title }}</strong>
                                <small class="text-muted">{{ $priorityLabel }} · v{{ $announcement->version }}</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td style="min-width: 190px;">
                                <small class="d-block">Từ: {{ $announcement->starts_at->format('d/m/Y H:i') }}</small>
                                <small class="text-muted">Đến: {{ $announcement->ends_at?->format('d/m/Y H:i') ?? 'Không giới hạn' }}</small>
                            </td>
                            <td>
                                @if($announcement->target_type === \App\Models\FeatureAnnouncement::TARGET_TYPE_USERS)
                                    <span class="badge badge-info">
                                        {{ $announcement->targetedUsers->count() }} tài khoản cụ thể
                                    </span>
                                    @if($announcement->targetedUsers->isNotEmpty())
                                        <small class="text-muted d-block mt-1">
                                            {{ $announcement->targetedUsers->take(3)->map(fn ($user) => $user->full_name ?: $user->username)->join(', ') }}
                                            @if($announcement->targetedUsers->count() > 3)
                                                +{{ $announcement->targetedUsers->count() - 3 }}
                                            @endif
                                        </small>
                                    @endif
                                @else
                                    @foreach($announcement->target_roles ?? [] as $role)
                                        <span class="badge badge-light border">{{ $roleLabels[$role] ?? $role }}</span>
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                <strong>{{ $stats['acknowledged_count'] }} / {{ $stats['target_count'] }}</strong>
                                <small class="text-muted d-block">người đã xác nhận</small>
                            </td>
                            <td>
                                {{ $announcement->creator?->full_name ?: $announcement->creator?->username ?: '—' }}
                            </td>
                            <td class="text-center" style="white-space: nowrap;">
                                @if($canReport)
                                    <a href="{{ route('feature_announcements.show', $announcement) }}"
                                        class="btn btn-sm btn-outline-info" title="Chi tiết & báo cáo">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endif
                                @if($canUpdate)
                                    <a href="{{ route('feature_announcements.edit', $announcement) }}"
                                        class="btn btn-sm btn-outline-primary" title="Chỉnh sửa">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                @endif
                                @if($canToggle)
                                    <form action="{{ route('feature_announcements.toggle', $announcement) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            title="{{ $announcement->is_active ? 'Tắt' : 'Bật' }}">
                                            <i class="fas {{ $announcement->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                        </button>
                                    </form>
                                @endif
                                @if($canDelete)
                                    <form action="{{ route('feature_announcements.destroy', $announcement) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Xóa thông báo này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Chưa có thông báo tính năng nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $announcements->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection
