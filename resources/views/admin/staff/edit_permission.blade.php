@extends('admin.layouts.master')

@section('title')
    Phân quyền nhân viên — {{ $get_user->full_name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
    @include('admin.manager_settings.group-styles')
    <style>
        .permission-toolbar {
            display: flex;
            gap: .75rem;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: #f8fafc;
            margin-bottom: 1rem;
        }
        .permission-search {
            min-width: min(100%, 320px);
            flex: 1 1 320px;
        }
        .permission-bulk-actions,
        .permission-selection-actions {
            display: flex;
            gap: .5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .permission-card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: .9rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .permission-card:hover {
            border-color: #c7d2fe;
            box-shadow: var(--shadow-subtle);
        }
        .permission-info {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            min-width: 0;
        }
        .permission-name {
            margin: 0 0 .2rem;
            font-size: .92rem;
            font-weight: 700;
            color: var(--text-main);
        }
        .permission-code {
            font: .75rem/1.35 monospace;
            color: var(--text-muted);
            word-break: break-word;
        }
        .permission-select {
            width: 1.05rem;
            height: 1.05rem;
            margin-top: .15rem;
            cursor: pointer;
            flex-shrink: 0;
        }
        .toggle-icon {
            font-size: 2rem;
            cursor: pointer;
            transition: color .15s ease;
            flex-shrink: 0;
        }
        .toggle-icon.fa-toggle-on { color: #10b981; }
        .toggle-icon.fa-toggle-off { color: #cbd5e1; }
        .permission-empty {
            padding: 2.5rem 1rem;
            text-align: center;
            color: var(--text-muted);
        }
        @media (max-width: 900px) {
            .permission-module__body { grid-template-columns: 1fr; }
        }
    </style>
@endsection

@section('script-libs')
    @vite('resources/js/admin/staff/edit_permission.js')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">
    <a href="{{ route('staff.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách nhân viên
    </a>

    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="user-avatar-circle" style="width: 52px; height: 52px; font-size: 1.25rem;">
                <span>{{ mb_strtoupper(mb_substr($get_user->full_name ?: ($get_user->username ?: 'S'), 0, 2)) }}</span>
            </div>
            <div>
                <h1 class="page-title-main" style="font-size: 1.35rem;">
                    Phân quyền nhân viên: {{ $get_user->full_name }}
                    <span class="id-chip">ID: {{ $get_user->id }}</span>
                </h1>
                <p class="page-subtitle">
                    Tài khoản: <b>@<span>{{ $get_user->username }}</span></b>
                    • Quyền được nhóm theo chức năng thực tế trong Admin Panel
                </p>
            </div>
        </div>
    </div>

    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-shield-halved"></i> Danh mục quyền hạn
            </h6>
            <span class="text-muted" style="font-size: .8125rem;">
                Checkbox dùng để chọn hàng loạt; công tắc cập nhật ngay lập tức.
            </span>
        </div>

        <div class="card-body p-4">
            <form method="POST" action="{{ route('staff.change.status.permissions') }}" id="permission_bulk_form">
                @csrf
                <input type="hidden" name="staff_id" value="{{ $get_user->id }}">

                <div class="permission-toolbar">
                    <input
                        type="search"
                        id="permission_search"
                        class="form-control permission-search"
                        placeholder="Tìm theo module, tên quyền hoặc mã quyền..."
                        autocomplete="off">

                    <div class="permission-selection-actions">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="select_all_permissions">
                            <i class="fas fa-check-double mr-1"></i> Chọn tất cả
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="deselect_all_permissions">
                            <i class="fas fa-xmark mr-1"></i> Bỏ chọn tất cả
                        </button>
                    </div>

                    <div class="permission-bulk-actions">
                        <span id="selected_permission_count" class="text-muted">0 quyền đã chọn</span>
                        <button type="submit" name="is_active" value="1" class="btn btn-success btn-sm">
                            <i class="fas fa-check mr-1"></i> Cấp quyền đã chọn
                        </button>
                        <button type="submit" name="is_active" value="0" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-ban mr-1"></i> Bỏ quyền đã chọn
                        </button>
                    </div>
                </div>

                <div id="list_permissions">
                    @forelse($permissionGroups as $group)
                        <section
                            class="permission-module"
                            data-module="{{ $group['key'] }}"
                            data-search="{{ mb_strtolower($group['label'] . ' ' . $group['key']) }}">
                            <div class="permission-module__header">
                                <label class="permission-module__title mb-0">
                                    <input
                                        type="checkbox"
                                        class="permission-select permission-module-select"
                                        data-module="{{ $group['key'] }}"
                                        aria-label="Chọn toàn bộ quyền {{ $group['label'] }}">
                                    <i class="fas fa-layer-group text-primary"></i>
                                    <span>{{ $group['label'] }}</span>
                                </label>
                                <span class="text-muted small">{{ count($group['permissions']) }} quyền</span>
                            </div>

                            <div class="permission-module__body">
                                @foreach($group['permissions'] as $permission)
                                    @php($assignment = $permission['assignment'])
                                    <div
                                        class="permission-card"
                                        data-search="{{ mb_strtolower($group['label'] . ' ' . $permission['label'] . ' ' . $permission['code']) }}">
                                        <div class="permission-info">
                                            <input
                                                type="checkbox"
                                                class="permission-select permission-item-select"
                                                name="assignment_ids[]"
                                                value="{{ $assignment->id }}"
                                                data-module="{{ $group['key'] }}"
                                                aria-label="Chọn quyền {{ $permission['label'] }}">
                                            <div>
                                                <h6 class="permission-name">{{ $permission['label'] }}</h6>
                                                <span class="permission-code">{{ $permission['code'] }}</span>
                                            </div>
                                        </div>
                                        <i
                                            class="fa-solid {{ $permission['active'] ? 'fa-toggle-on' : 'fa-toggle-off' }} toggle-icon change_status_permission"
                                            data-id="{{ $assignment->id }}"
                                            role="button"
                                            tabindex="0"
                                            aria-label="{{ $permission['active'] ? 'Thu hồi' : 'Cấp' }} quyền {{ $permission['label'] }}"
                                            title="{{ $permission['active'] ? 'Thu hồi quyền' : 'Cấp quyền' }}"></i>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="permission-empty">
                            <i class="fas fa-shield-halved fa-2x mb-2"></i>
                            <div>Chưa có permission granular trong registry.</div>
                        </div>
                    @endforelse
                </div>

                <div id="permission_search_empty" class="permission-empty" hidden>
                    Không tìm thấy quyền phù hợp với từ khóa.
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
