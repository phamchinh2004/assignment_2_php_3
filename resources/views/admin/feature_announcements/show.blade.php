@extends('admin.layouts.master')

@section('title', 'Chi tiết thông báo tính năng')

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
@endsection

@section('content')
@php
    $authorization = app(\App\Services\AuthorizationService::class);
    $canUpdate = $authorization->can(auth()->user(), config('authorization.capabilities.feature_announcements_update'));
    $roleLabels = ['own' => 'Chủ hệ thống', 'admin' => 'Admin', 'staff' => 'Nhân viên'];
@endphp

<div class="container-fluid px-4 pb-5">
    <a href="{{ route('feature_announcements.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách
    </a>

    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
            <h1 class="page-title-main">
                <span class="page-title-icon info"><i class="fas fa-bullhorn"></i></span>
                {{ $featureAnnouncement->title }}
            </h1>
            <p class="page-subtitle">
                Version {{ $featureAnnouncement->version }} ·
                {{ $stats['acknowledged_count'] }} / {{ $stats['target_count'] }} người đã xác nhận
            </p>
        </div>
        @if($canUpdate)
            <a href="{{ route('feature_announcements.edit', $featureAnnouncement) }}" class="btn-create-modern text-decoration-none">
                <i class="fas fa-pen"></i> <span>Chỉnh sửa</span>
            </a>
        @endif
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8">
                    <div style="white-space: pre-wrap;">{{ $featureAnnouncement->content }}</div>

                    @if($featureAnnouncement->image_path)
                        <img src="{{ Storage::url($featureAnnouncement->image_path) }}" alt="Ảnh thông báo"
                            class="img-fluid rounded border mt-3" style="max-height: 420px;">
                    @endif

                    @if($featureAnnouncement->action_text && $featureAnnouncement->action_url)
                        <div class="mt-3">
                            <a href="{{ $featureAnnouncement->action_url }}" target="_blank" rel="noopener noreferrer"
                                class="btn btn-outline-primary">
                                <i class="fas fa-arrow-up-right-from-square"></i>
                                {{ $featureAnnouncement->action_text }}
                            </a>
                        </div>
                    @endif
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <dl class="mb-0">
                        <dt>Trạng thái</dt>
                        <dd>{{ $featureAnnouncement->is_active ? 'Đang bật' : 'Đã tắt' }}</dd>
                        <dt>Ưu tiên</dt>
                        <dd>{{ $featureAnnouncement->priority }}</dd>
                        <dt>Hiệu lực</dt>
                        <dd>
                            {{ $featureAnnouncement->starts_at->format('d/m/Y H:i') }}<br>
                            đến {{ $featureAnnouncement->ends_at?->format('d/m/Y H:i') ?? 'không giới hạn' }}
                        </dd>
                        <dt>Đối tượng</dt>
                        <dd>
                            @foreach($featureAnnouncement->target_roles ?? [] as $role)
                                <span class="badge badge-light border">{{ $roleLabels[$role] ?? $role }}</span>
                            @endforeach
                        </dd>
                        <dt>Người tạo</dt>
                        <dd>{{ $featureAnnouncement->creator?->full_name ?: $featureAnnouncement->creator?->username ?: '—' }}</dd>
                        <dt>Tạo lúc</dt>
                        <dd>{{ $featureAnnouncement->created_at->format('d/m/Y H:i') }}</dd>
                        <dt>Cập nhật lúc</dt>
                        <dd>{{ $featureAnnouncement->updated_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="table-card-modern">
        <div class="p-3 border-bottom">
            <h5 class="mb-1">Báo cáo xác nhận</h5>
            <small class="text-muted">Trạng thái được tính theo role hiện tại của người dùng và version hiện tại của thông báo.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th>Người dùng</th>
                        <th>Role</th>
                        <th>Trạng thái</th>
                        <th>Thời gian xác nhận</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stats['users'] as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['user']->full_name ?: $row['user']->username }}</strong>
                                <small class="text-muted d-block">{{ $row['user']->username }} · {{ $row['user']->email }}</small>
                            </td>
                            <td>{{ $roleLabels[$row['user']->role] ?? $row['user']->role }}</td>
                            <td>
                                @if($row['acknowledged_at'])
                                    <span class="badge badge-success">Đã nắm rõ</span>
                                @else
                                    <span class="badge badge-warning">Chưa xác nhận</span>
                                @endif
                            </td>
                            <td>{{ $row['acknowledged_at']?->format('d/m/Y H:i:s') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Không có người dùng thuộc nhóm nhận hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
