@extends('admin.layouts.master')

@section('title', 'Audit Frozen Order #' . $frozenOrder->id)

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
    @vite('resources/css/admin/order_distribution/index.css')
@endsection

@section('content')
@php
    $order = $frozenOrder->order;
    $stateLabels = ['complete' => 'Đầy đủ', 'legacy' => 'Legacy', 'incomplete' => 'Thiếu dữ liệu', 'invalid' => 'Bất thường'];
    $rows = [
        ['Mã đơn', $frozenOrder->snapshot_order_code, $order?->order_code],
        ['Tên sản phẩm', $frozenOrder->snapshot_name, $order?->name],
        ['Ảnh', $frozenOrder->snapshot_image, $order?->image],
        ['Số lượng', $frozenOrder->snapshot_quantity, $order?->quantity],
        ['Đơn giá', $frozenOrder->snapshot_unit_price, $order?->price],
        ['Tổng giá trị', $frozenOrder->snapshot_order_amount, $order ? $order->price * $order->quantity : null],
        ['Commission rate', $frozenOrder->commission_percentage, $order?->commission_percentage],
        ['Commission amount', $frozenOrder->snapshot_commission_amount, $order ? ($order->price * $order->quantity * (($order->commission_percentage ?? 0) / 100)) : null],
        ['Người nhận', $frozenOrder->snapshot_customer_name, $order?->customer_name],
        ['Số điện thoại', $frozenOrder->snapshot_customer_phone, $order?->customer_phone],
        ['Địa chỉ', $frozenOrder->snapshot_customer_address, $order?->customer_address],
        ['Ghi chú', $frozenOrder->snapshot_customer_note, $order?->customer_note],
        ['Đối tác', $frozenOrder->snapshot_partner_name, $order?->partner?->name],
        ['Thanh toán', $frozenOrder->snapshot_payment_method, $order?->payment_method],
        ['Đã thanh toán', $frozenOrder->snapshot_is_paid === null ? null : ($frozenOrder->snapshot_is_paid ? 'Có' : 'Không'), $order ? ($order->is_paid ? 'Có' : 'Không') : null],
        ['API', $frozenOrder->snapshot_api, $order?->api],
    ];
@endphp
<div class="container-fluid px-4 pb-5 distribution-page">
    <div class="distribution-header d-flex justify-content-between align-items-center flex-wrap">
        <div><h1>Audit Frozen Order #{{ $frozenOrder->id }}</h1><p>So sánh snapshot lịch sử với Order nguồn hiện tại.</p></div>
        <a href="{{ route('order_distributions.index') }}" class="btn btn-light mt-2 mt-md-0"><i class="fas fa-arrow-left mr-1"></i>Danh sách</a>
    </div>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="distribution-panel">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div><strong>Đối chiếu dữ liệu</strong><div class="text-muted small">Cột Order hiện tại chỉ là tham khảo, không phải lịch sử đã xác minh.</div></div>
                    <span class="snapshot-badge snapshot-{{ $frozenOrder->snapshot_state }}">{{ $stateLabels[$frozenOrder->snapshot_state] }}</span>
                </div>
                <div class="compare-grid">
                    <div class="compare-head">Field</div><div class="compare-head">Snapshot</div><div class="compare-head">Order hiện tại</div>
                    @foreach($rows as [$label, $snapshot, $current])
                        @php $different = (string) $snapshot !== (string) $current; @endphp
                        <div><strong>{{ $label }}</strong></div>
                        <div>{{ filled($snapshot) || $snapshot === 0 ? $snapshot : '—' }} @if($snapshot === null)<span class="badge badge-warning">thiếu</span>@endif</div>
                        <div class="{{ $different ? 'value-fallback' : '' }}">{{ filled($current) || $current === 0 ? $current : '—' }} @if($different)<span class="badge badge-light border">khác</span>@endif</div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="distribution-panel mb-4 p-3">
                <h6 class="font-weight-bold">Tình trạng snapshot</h6>
                <dl class="row small mb-0">
                    <dt class="col-5">Nguồn</dt><dd class="col-7">{{ $frozenOrder->snapshot_source ?: ($frozenOrder->snapshot_state === 'complete' ? 'captured (pre-metadata)' : 'legacy / chưa ghi nhận') }}</dd>
                    <dt class="col-5">Captured</dt><dd class="col-7">{{ optional($frozenOrder->snapshot_captured_at)->format('d/m/Y H:i') ?: '—' }}</dd>
                    <dt class="col-5">Restored</dt><dd class="col-7">{{ optional($frozenOrder->snapshot_restored_at)->format('d/m/Y H:i') ?: '—' }}</dd>
                    <dt class="col-5">Người restore</dt><dd class="col-7">{{ $frozenOrder->snapshotRestoredBy?->full_name ?: '—' }}</dd>
                    <dt class="col-5">Field thiếu</dt><dd class="col-7">{{ $frozenOrder->snapshot_missing_fields ? count($frozenOrder->snapshot_missing_fields) : 'Không' }}</dd>
                </dl>
                @if($missingDetails)
                    <div class="missing-audit mt-3">
                        @foreach($missingDetails as $detail)
                            <div class="missing-audit-item">
                                <strong>{{ $detail['label'] }}</strong>
                                <code>{{ $detail['field'] }}</code>
                                <div>Nguồn: <code class="d-inline">{{ $detail['source_field'] }}</code></div>
                                <div>Giá trị hiện tại: {{ $detail['source_available'] ? $detail['source'] : '—' }}</div>
                                <div class="{{ $detail['can_restore'] ? 'text-success' : 'text-danger' }}">{{ $detail['reason'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="distribution-panel mb-4 p-3">
                <h6 class="font-weight-bold">Phân phối & tài chính</h6>
                <dl class="row small mb-0">
                    <dt class="col-5">User</dt><dd class="col-7"><a href="{{ route('user.show', $frozenOrder->user_id) }}">{{ $frozenOrder->user?->full_name ?: $frozenOrder->user?->username }}</a></dd>
                    <dt class="col-5">Order nguồn</dt><dd class="col-7">@if($order)<a href="{{ route('order.show', $order) }}">#{{ $order->id }}</a>@else Đã xóa @endif</dd>
                    <dt class="col-5">Status</dt><dd class="col-7">{{ $frozenOrder->status ?: 'legacy' }}</dd>
                    <dt class="col-5">Spun</dt><dd class="col-7">{{ $frozenOrder->spun ? 'Có' : 'Không' }}</dd>
                    <dt class="col-5">Commission paid</dt><dd class="col-7">{{ $frozenOrder->commission_paid ? 'Có' : 'Không' }}</dd>
                    <dt class="col-5">Giá trị hiển thị</dt><dd class="col-7 money">{{ format_money($frozenOrder->display_order_amount ?? 0, 2) }}$</dd>
                    <dt class="col-5">Commission</dt><dd class="col-7 money">{{ format_money($frozenOrder->display_commission_amount ?? 0, 5) }}$</dd>
                </dl>
                @if($frozenOrder->uses_snapshot_fallback)<div class="alert alert-warning small mt-3 mb-0">Giá trị hiển thị có fallback từ Order hiện tại. Không dùng làm bằng chứng lịch sử.</div>@endif
            </div>

            <div class="distribution-panel p-3">
                <h6 class="font-weight-bold">Phục hồi field trống</h6>
                <p class="small text-muted">Không ghi đè snapshot có sẵn. Dữ liệu lấy từ Order hiện tại được đánh dấu chưa xác minh.</p>
                @if(!$order)
                    <div class="alert alert-danger small mb-0">Order nguồn không còn tồn tại. Không thể phục hồi tự động.</div>
                @else
                    <form method="POST" action="{{ route('order_distributions.restore', $frozenOrder) }}" onsubmit="return confirm('Dữ liệu phục hồi lấy từ Order hiện tại, không phải lịch sử đã xác minh. Tiếp tục?')">
                        @csrf
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="include_financials" name="include_financials" value="1" @checked($canRestoreFinancials) @disabled(!$canRestoreFinancials)>
                            <label class="custom-control-label" for="include_financials">Bổ sung field tài chính</label>
                        </div>
                        @if(!$canRestoreFinancials)<div class="small text-danger mb-3">Đơn đã xác nhận/hoàn thành hoặc commission đã trả: khóa phục hồi tài chính.</div>@endif
                        <button class="btn btn-warning btn-block font-weight-bold"><i class="fas fa-wand-magic-sparkles mr-1"></i>Phục hồi field trống</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
