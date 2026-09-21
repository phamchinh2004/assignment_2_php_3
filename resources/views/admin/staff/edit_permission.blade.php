@extends('admin.layouts.master')
@section('title')
    Phân quyền nhân viên — {{ $get_user->full_name }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
    <style>
        .permission-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
            margin-bottom: 1rem;
        }
        .permission-card:hover {
            border-color: #c7d2fe;
            box-shadow: var(--shadow-subtle);
            background: #fafafa;
        }
        .permission-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .permission-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: rgba(79, 70, 229, 0.1);
            color: #4f46e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .permission-name {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
        }
        .permission-code {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-family: monospace;
        }
        .toggle-icon {
            font-size: 2.2rem;
            cursor: pointer;
            transition: color 0.15s ease;
        }
        .toggle-icon.fa-toggle-on {
            color: #10b981;
        }
        .toggle-icon.fa-toggle-off {
            color: #cbd5e1;
        }
        .permission-bulk-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 0.875rem 1rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: #f8fafc;
        }
        .permission-bulk-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .permission-select {
            width: 1rem;
            height: 1rem;
            cursor: pointer;
            flex-shrink: 0;
        }
    </style>
@endsection

@section('script-libs')
    @vite('resources/js/admin/staff/edit_permission.js')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('staff.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách nhân viên
    </a>

    {{-- Page Header --}}
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
                    Tài khoản: <b>@<span>{{ $get_user->username }}</span></b> • Bật / tắt các quyền quản trị trên hệ thống
                </p>
            </div>
        </div>
    </div>

    {{-- Permission Grid Card --}}
    <div class="card-modern">
        <div class="card-header-modern">
            <h6 class="title-header">
                <i class="fas fa-shield-halved"></i> Danh mục quyền hạn quản lý
            </h6>
            <span class="text-muted" style="font-size: 0.8125rem;">
                <i class="fas fa-info-circle text-primary mr-1"></i> Bấm vào công tắc để bật / tắt quyền ngay lập tức
            </span>
        </div>

        <div class="card-body p-4">
            <form method="POST" action="{{ route('staff.change.status.permissions') }}">
                @csrf
                <input type="hidden" name="staff_id" value="{{ $get_user->id }}">
            <div class="permission-bulk-toolbar">
                <label class="d-flex align-items-center gap-2 mb-0" for="select_all_permissions">
                    <input type="checkbox" id="select_all_permissions" class="permission-select">
                    <span class="font-weight-bold">Chọn tất cả</span>
                </label>
                <div class="permission-bulk-actions">
                    <span id="selected_permission_count" class="text-muted mr-1">0 quyền đã chọn</span>
                    <button type="submit" name="is_active" value="1" class="btn btn-success btn-sm">
                        <i class="fas fa-check mr-1"></i> Cấp quyền đã chọn
                    </button>
                    <button type="submit" name="is_active" value="0" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-ban mr-1"></i> Bỏ quyền đã chọn
                    </button>
                </div>
            </div>

            <div class="row"
                 id="list_permissions"
                 data-staff-id="{{ $get_user->id }}"
                 data-bulk-url="{{ route('staff.change.status.permissions') }}">
                @foreach($list_manager_settings as $item)
                    @php
                        $sub = $get_user_manager_setting->where('manager_setting_id', $item->id)->first();
                        $isActive = $sub && $sub->is_active;
                    @endphp
                    <div class="col-12 col-md-6">
                        <div class="permission-card">
                            <div class="permission-info">
                                @if($sub)
                                    <input type="checkbox"
                                           class="permission-select permission-item-select"
                                           name="assignment_ids[]"
                                           value="{{ $sub->id }}"
                                           aria-label="Chọn quyền {{ $item->manager_name }}">
                                @endif
                                <div class="permission-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div>
                                    <h6 class="permission-name">{{ $item->manager_name }}</h6>
                                    <span class="permission-code">{{ $item->manager_code }}</span>
                                </div>
                            </div>
                            <div>
                                @if($sub)
                                    <i class="fa-solid {{ $isActive ? 'fa-toggle-on' : 'fa-toggle-off' }} toggle-icon change_status_permission"
                                       data-id="{{ $sub->id }}"
                                       title="Bấm để {{ $isActive ? 'hủy quyền' : 'cấp quyền' }}"></i>
                                @else
                                    <i class="fa-solid fa-toggle-off toggle-icon text-muted" style="opacity: 0.4;" title="Chưa khởi tạo"></i>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            </form>
        </div>
    </div>

</div>
@endsection
