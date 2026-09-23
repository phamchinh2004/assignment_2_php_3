@extends('admin.layouts.master')

@section('title', 'Audit phân phối #' . $frozenOrder->id)

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
    @vite('resources/css/admin/order_distribution/index.css')
@endsection

@section('script-libs')
    @vite('resources/js/admin/order_distribution/show.js')
@endsection

@section('content')
@php
    $currentLabel = $statusLabels[$frozenOrder->status] ?? ($frozenOrder->status ?: 'Chưa ghi nhận');
    $nextLabel = $transition['next'] ? ($statusLabels[$transition['next']] ?? $transition['next']) : null;
    $assignee = $frozenOrder->user?->full_name ?: $frozenOrder->user?->username;
    $assigner = $frozenOrder->assignedBy?->full_name ?: $frozenOrder->assignedBy?->username;
    $backUrl = route('order_distributions.index', request()->query());
@endphp
<main class="container-fluid px-3 px-lg-4 pb-5 distribution-page">
    <header class="distribution-header distribution-audit-header">
        <div class="distribution-heading">
            <span class="distribution-heading-icon" aria-hidden="true"><i class="fas fa-clipboard-check"></i></span>
            <div><h1>Audit phân phối #{{ $frozenOrder->id }}</h1><p>Kiểm tra người nhận, tiến độ và thao tác được phép trên đơn hàng.</p></div>
        </div>
        <a href="{{ $backUrl }}" class="btn distribution-secondary-button"><i class="fas fa-arrow-left" aria-hidden="true"></i> Quay lại danh sách</a>
    </header>

    @foreach(['success' => 'success', 'error' => 'danger'] as $flash => $tone)
        @if(session($flash))<div class="alert alert-{{ $tone }}" role="alert">{{ session($flash) }}</div>@endif
    @endforeach

    <div class="distribution-audit-grid">
        <div class="distribution-audit-main">
            <section class="distribution-panel" aria-labelledby="distribution-summary-title">
                <div class="distribution-panel-heading"><div><h2 id="distribution-summary-title">Thông tin phân phối</h2><p>Bản ghi phân phối và dữ liệu đã lưu tại thời điểm tạo.</p></div><span class="distribution-status distribution-status-{{ $frozenOrder->status ?: 'unknown' }}">{{ $currentLabel }}</span></div>
                <div class="distribution-audit-content">
                    <div class="distribution-audit-order">
                        @if($frozenOrder->snapshot_image)<img src="{{ Storage::url($frozenOrder->snapshot_image) }}" alt="" class="distribution-audit-thumb">@endif
                        <div><span class="distribution-eyebrow">Đơn phân phối #{{ $frozenOrder->id }}</span><h3>{{ $frozenOrder->snapshot_order_code ?: 'Chưa ghi nhận mã đơn' }}</h3><p>{{ $frozenOrder->snapshot_name ?: 'Tên sản phẩm chưa được ghi nhận trong snapshot' }}</p><span class="distribution-type">{{ $frozenOrder->custom_price !== null ? 'Đơn giá trị cao' : 'Đơn thường' }}</span></div>
                    </div>
                    <dl class="distribution-detail-grid">
                        <div><dt>Người nhận phân phối</dt><dd>{{ $assignee ?: 'Tài khoản không còn tồn tại' }} <small>User #{{ $frozenOrder->user_id }}</small></dd></div>
                        <div><dt>Người phân phối</dt><dd>{{ $assigner ?: ($frozenOrder->assignment_source === 'spin' ? 'Hệ thống tự phân phối' : ($frozenOrder->assignment_source === 'admin' ? 'Không còn thông tin người phân phối' : 'Chưa ghi nhận')) }} <small>{{ $frozenOrder->assignment_source === 'admin' ? 'Giao thủ công' : ($frozenOrder->assignment_source === 'spin' ? 'Người dùng tự nhận' : 'Không có dữ liệu nguồn lịch sử') }}</small></dd></div>
                        <div><dt>Thời gian phân phối</dt><dd>{{ $frozenOrder->created_at?->format('d/m/Y H:i:s') ?: '—' }}</dd></div>
                        <div><dt>Cập nhật bản ghi</dt><dd>{{ $frozenOrder->updated_at?->format('d/m/Y H:i:s') ?: '—' }}</dd></div>
                        <div><dt>Order nguồn</dt><dd>@if($frozenOrder->order && $canViewOrder)<a href="{{ route('order.show', $frozenOrder->order) }}">Order #{{ $frozenOrder->order_id }} <i class="fas fa-external-link-alt" aria-hidden="true"></i></a>@else Order #{{ $frozenOrder->order_id }} · {{ $frozenOrder->order ? 'Chỉ xem mã tham chiếu' : 'Đã xóa' }} @endif</dd></div>
                        <div><dt>Tình trạng nhận đơn</dt><dd>{{ $frozenOrder->spun ? 'Đã nhận đơn' : 'Chưa nhận đơn' }}</dd></div>
                    </dl>
                </div>
            </section>

            <section class="distribution-panel" aria-labelledby="distribution-history-title">
                <div class="distribution-panel-heading"><div><h2 id="distribution-history-title">Lịch sử trạng thái</h2><p>Nhật ký hiện có từ status_orders, sắp xếp theo thời điểm ghi nhận.</p></div><span class="distribution-page-count">{{ $frozenOrder->statusOrders->count() }} sự kiện</span></div>
                <div class="distribution-audit-content">
                    @forelse($frozenOrder->statusOrders->sortBy('id') as $event)
                        <div class="distribution-history-item">
                            <span class="distribution-history-marker" aria-hidden="true"></span>
                            <div class="distribution-history-body">
                                <div class="distribution-history-top"><strong>{{ $event->status?->display_name ?: ($event->status?->name ?: 'Trạng thái không còn tồn tại') }}</strong><time datetime="{{ $event->created_at?->toISOString() }}">{{ $event->created_at?->format('d/m/Y H:i:s') ?: '—' }}</time></div>
                                @php $legacyActor = $frozenOrder->assignment_source === null && $event->status?->name === 'pending' && $event->changed_by !== null && (int) $event->changed_by === (int) $frozenOrder->user_id; @endphp
                                <p>{{ $legacyActor ? 'Tài khoản trong lịch sử cũ (chưa xác minh người thao tác)' : 'Người ghi nhận' }}: {{ $event->changedBy?->full_name ?: $event->changedBy?->username ?: 'Hệ thống / chưa ghi nhận' }}</p>
                                @if($event->notes)<p class="distribution-history-note">{{ $event->notes }}</p>@endif
                            </div>
                        </div>
                    @empty
                        <div class="distribution-empty distribution-history-empty"><i class="fas fa-clock" aria-hidden="true"></i><strong>Chưa có lịch sử trạng thái</strong><p>Bản ghi cũ có thể chưa được ghi vào nhật ký.</p></div>
                    @endforelse
                </div>
            </section>

            <section class="distribution-panel" aria-labelledby="distribution-snapshot-title">
                <div class="distribution-panel-heading"><div><h2 id="distribution-snapshot-title">Dữ liệu snapshot</h2><p>Thông tin lịch sử đã lưu; không lấy giá trị thay thế từ Order hiện tại.</p></div><span class="distribution-snapshot-label">{{ $frozenOrder->snapshot_state === 'complete' ? 'Đầy đủ' : 'Cần đối chiếu' }}</span></div>
                <div class="distribution-audit-content">
                    @if($frozenOrder->snapshot_state !== 'complete')<p class="distribution-notice">Snapshot này chưa đầy đủ hoặc có dữ liệu bất thường. Các giá trị chưa ghi nhận được để trống, không suy đoán từ Order nguồn.</p>@endif
                    <details class="distribution-snapshot-details"><summary>Xem chi tiết snapshot và nguồn dữ liệu</summary>
                        <dl class="distribution-detail-grid">
                            <div><dt>Số lượng</dt><dd>{{ $frozenOrder->snapshot_quantity ?? '—' }}</dd></div>
                            <div><dt>Đơn giá snapshot</dt><dd>{{ $frozenOrder->snapshot_unit_price !== null ? format_money($frozenOrder->snapshot_unit_price, 2) . '$' : '—' }}</dd></div>
                            <div><dt>Nguồn snapshot</dt><dd>{{ $frozenOrder->snapshot_source ?: 'Chưa ghi nhận' }}</dd></div>
                            <div><dt>Ngày chụp snapshot</dt><dd>{{ $frozenOrder->snapshot_captured_at?->format('d/m/Y H:i') ?: '—' }}</dd></div>
                            <div><dt>Tình trạng dữ liệu</dt><dd>{{ $frozenOrder->snapshot_state }}</dd></div>
                            <div><dt>Ngày bổ sung dữ liệu cũ</dt><dd>{{ $frozenOrder->snapshot_restored_at?->format('d/m/Y H:i') ?: '—' }}</dd></div>
                        </dl>
                    </details>
                </div>
            </section>
        </div>

        <aside class="distribution-audit-aside" aria-label="Xử lý đơn phân phối">
            <section class="distribution-panel" aria-labelledby="distribution-action-title">
                <div class="distribution-panel-heading"><div><h2 id="distribution-action-title">Xử lý trạng thái</h2><p>Thao tác theo đúng bước tiếp theo của quy trình.</p></div></div>
                <div class="distribution-audit-content">
                    <span class="distribution-eyebrow">Trạng thái hiện tại</span>
                    <div class="distribution-current-state"><span class="distribution-status distribution-status-{{ $frozenOrder->status ?: 'unknown' }}">{{ $currentLabel }}</span></div>
                    @if($transition['next'])
                        <div class="distribution-next-state"><i class="fas fa-arrow-down" aria-hidden="true"></i><div><small>Bước tiếp theo</small><strong>{{ $nextLabel }}</strong></div></div>
                    @endif
                    @if($transition['ready'] && $canAdvance)
                        <form id="distribution-transition-form" method="POST" action="{{ route('order_distributions.transition', array_merge(['frozenOrder' => $frozenOrder->id], request()->query())) }}" data-confirm="{{ $transition['next'] === 'completed' ? 'Xác nhận hoàn thành và quyết toán số dư, hoa hồng cho đơn hàng này?' : '' }}">
                            @csrf
                            <input type="hidden" name="expected_status" value="{{ $frozenOrder->status }}">
                            <input type="hidden" name="expected_updated_at" value="{{ $frozenOrder->updated_at?->toISOString() }}">
                            <button type="submit" class="btn distribution-primary-button distribution-transition-button"><span class="distribution-button-label">Chuyển sang {{ $nextLabel }}</span><span class="distribution-button-spinner" aria-hidden="true"></span></button>
                            <div id="transition-feedback" class="distribution-transition-feedback" role="alert" aria-live="assertive" hidden></div>
                            <a id="distribution-conflict-reload" class="btn distribution-secondary-button mt-2" href="{{ url()->current() . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}" hidden>Tải lại Audit</a>
                        </form>
                    @elseif(!$canAdvance && $transition['next'])
                        <p class="distribution-notice mb-0">Tài khoản của bạn chỉ có quyền xem. Bước này yêu cầu quyền quản lý đơn hàng{{ $transition['next'] === 'completed' ? ' và quản lý giao dịch người dùng' : '' }}.</p>
                    @else
                        <p class="distribution-notice mb-0">{{ $transition['reason'] }}</p>
                        @if($transition['available_at'])<p class="distribution-available-at">Có thể xử lý từ {{ $transition['available_at']->format('d/m/Y H:i') }}.</p>@endif
                    @endif
                </div>
            </section>

            <section class="distribution-panel" aria-labelledby="distribution-financial-title">
                <div class="distribution-panel-heading"><div><h2 id="distribution-financial-title">Tình trạng tài chính</h2><p>Chỉ dùng số liệu snapshot đã lưu trên đơn.</p></div></div>
                <div class="distribution-audit-content">
                    <dl class="distribution-financial-list">
                        <div><dt>Giá trị đơn</dt><dd>{{ $frozenOrder->snapshot_order_value !== null ? format_money($frozenOrder->snapshot_order_value, 2) . '$' : 'Chưa có snapshot' }}</dd></div>
                        <div><dt>Hoa hồng</dt><dd>{{ $frozenOrder->snapshot_commission_value !== null ? format_money($frozenOrder->snapshot_commission_value, 5) . '$' : 'Chưa có snapshot' }}</dd></div>
                        <div><dt>Đã trả hoa hồng</dt><dd>{{ $frozenOrder->commission_paid ? 'Đã trả' : 'Chưa trả' }}</dd></div>
                        <div><dt>Thời điểm quyết toán</dt><dd>{{ $frozenOrder->settled_at?->format('d/m/Y H:i') ?: 'Chưa quyết toán' }}</dd></div>
                    </dl>
                </div>
            </section>
        </aside>
    </div>
</main>
@endsection
