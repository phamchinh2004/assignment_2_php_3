@extends('admin.layouts.master')

@section('title', 'Quản lý phân phối đơn hàng')

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
    @vite('resources/css/admin/order_distribution/index.css')
@endsection

@section('content')
<div class="container-fluid px-4 pb-5 distribution-page">
    <div class="distribution-header">
        <h1><i class="fas fa-route mr-2"></i>Quản lý phân phối đơn hàng</h1>
        <p>Audit FrozenOrder, nguồn snapshot, dữ liệu fallback và an toàn tài chính.</p>
    </div>

    <div class="distribution-stats">
        <div class="distribution-stat"><span>Tổng phân phối</span><strong>{{ number_format($stats['total']) }}</strong></div>
        <div class="distribution-stat"><span>Đang xử lý</span><strong class="text-primary">{{ number_format($stats['open']) }}</strong></div>
        <div class="distribution-stat"><span>Hoàn thành</span><strong class="text-success">{{ number_format($stats['completed']) }}</strong></div>
        <div class="distribution-stat"><span>Legacy</span><strong class="text-warning">{{ number_format($stats['legacy']) }}</strong></div>
        <div class="distribution-stat"><span>Thiếu snapshot</span><strong class="text-danger">{{ number_format($stats['incomplete']) }}</strong></div>
        <div class="distribution-stat"><span>Snapshot lỗi</span><strong class="text-danger">{{ number_format($stats['invalid']) }}</strong></div>
    </div>

    <div class="distribution-panel mb-4">
        <form method="GET" class="distribution-filters">
            <div class="form-row">
                <div class="col-lg-3 mb-2"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="ID, mã đơn, sản phẩm, user..."></div>
                <div class="col-lg-2 mb-2">
                    <select name="snapshot" class="form-control">
                        <option value="">Mọi snapshot</option>
                        <option value="complete" @selected(request('snapshot') === 'complete')>Đầy đủ</option>
                        <option value="legacy" @selected(request('snapshot') === 'legacy')>Legacy</option>
                        <option value="incomplete" @selected(request('snapshot') === 'incomplete')>Thiếu dữ liệu</option>
                        <option value="invalid" @selected(request('snapshot') === 'invalid')>Dữ liệu bất thường</option>
                        <option value="restored" @selected(request('snapshot') === 'restored')>Đã phục hồi</option>
                    </select>
                </div>
                <div class="col-lg-2 mb-2">
                    <select name="status" class="form-control">
                        <option value="">Mọi trạng thái</option>
                        @foreach(['pending','confirmed','preparing','transit','shipping','delivered','completed','cancelled'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 mb-2">
                    <select name="user_id" class="form-control">
                        <option value="">Mọi user</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) request('user_id') === (string) $user->id)>{{ $user->full_name ?: $user->username }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 mb-2">
                    <select name="order_id" class="form-control">
                        <option value="">Mọi Order</option>
                        @foreach($orders as $order)
                            <option value="{{ $order->id }}" @selected((string) request('order_id') === (string) $order->id)>#{{ $order->id }} {{ $order->order_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-1 mb-2"><button class="btn btn-primary btn-block"><i class="fas fa-filter"></i></button></div>
            </div>
            <div class="form-row mt-1">
                <div class="col-md-2 mb-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
                <div class="col-md-2 mb-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
                <div class="col-md-2 mb-2">
                    <select name="sort" class="form-control">
                        <option value="created_at" @selected(request('sort', 'created_at') === 'created_at')>Ngày phân phối</option>
                        <option value="updated_at" @selected(request('sort') === 'updated_at')>Ngày cập nhật</option>
                        <option value="status" @selected(request('sort') === 'status')>Trạng thái</option>
                        <option value="id" @selected(request('sort') === 'id')>ID</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="direction" class="form-control">
                        <option value="desc" @selected(request('direction', 'desc') === 'desc')>Giảm dần</option>
                        <option value="asc" @selected(request('direction') === 'asc')>Tăng dần</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2"><a href="{{ route('order_distributions.index') }}" class="btn btn-light border btn-block">Xóa lọc</a></div>
            </div>
        </form>

        <form method="POST" action="{{ route('order_distributions.bulk_restore') }}" onsubmit="return confirm('Chỉ bổ sung field trình bày đang trống từ Order hiện tại. Dữ liệu sẽ được đánh dấu chưa xác minh. Tiếp tục?')">
            @csrf
            <div class="bulk-bar">
                <span class="text-muted"><i class="fas fa-shield-halved mr-1"></i>Field tài chính chỉ được bổ sung khi đơn còn pending và chưa trả commission.</span>
                <button class="btn btn-warning btn-sm font-weight-bold"><i class="fas fa-wand-magic-sparkles mr-1"></i>Bổ sung snapshot đã chọn</button>
            </div>
            <div class="table-responsive">
                <table class="table distribution-table mb-0">
                    <thead><tr><th><input type="checkbox" id="select-all"></th><th>Frozen Order</th><th>User</th><th>Trạng thái</th><th>Snapshot</th><th>Tài chính</th><th>Phân phối</th><th></th></tr></thead>
                    <tbody>
                    @forelse($frozenOrders as $item)
                        @php
                            $labels = ['complete' => 'Đầy đủ', 'legacy' => 'Legacy', 'incomplete' => 'Thiếu', 'invalid' => 'Bất thường'];
                        @endphp
                        <tr>
                            <td><input type="checkbox" class="row-check" name="frozen_order_ids[]" value="{{ $item->id }}"></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($item->display_image)<img src="{{ Storage::url($item->display_image) }}" class="order-thumb mr-2" alt="">@endif
                                    <div><strong>#{{ $item->id }} · {{ $item->display_order_code ?: 'Không mã' }}</strong><br><span class="text-muted">{{ Str::limit($item->display_name ?: 'Không tên', 34) }}</span></div>
                                </div>
                            </td>
                            <td><a href="{{ route('user.show', $item->user_id) }}">{{ $item->user?->full_name ?: $item->user?->username }}</a><br><small class="text-muted">{{ $item->user?->phone }}</small></td>
                            <td><span class="badge badge-secondary">{{ $item->status ?: 'legacy' }}</span><br><small>spun: {{ $item->spun ? 'yes' : 'no' }}</small></td>
                            <td>
                                <span class="snapshot-badge snapshot-{{ $item->snapshot_state }}">{{ $labels[$item->snapshot_state] }}</span>
                                @if($item->snapshot_source === 'restored_current')<div class="source-note">Phục hồi từ Order hiện tại</div>@endif
                                @if($item->snapshot_missing_fields)
                                    <details class="missing-details mt-1">
                                        <summary>Thiếu {{ count($item->snapshot_missing_fields) }} field</summary>
                                        <ul class="mb-0 pl-3">
                                            @foreach($missingDetails->get($item->id, []) as $detail)
                                                <li title="{{ $detail['reason'] }}">
                                                    <code>{{ $detail['field'] }}</code> · {{ $detail['label'] }}
                                                    <span class="d-block text-muted">Nguồn: {{ $detail['source_field'] }}</span>
                                                    <span class="d-block {{ $detail['can_restore'] ? 'text-success' : 'text-danger' }}">{{ $detail['reason'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </td>
                            <td><span class="money">{{ format_money($item->display_order_amount ?? 0, 2) }}$</span><br><small>{{ $item->display_commission_percentage ?? 0 }}% · {{ format_money($item->display_commission_amount ?? 0, 5) }}$</small>@if($item->uses_snapshot_fallback)<div class="source-note">Có fallback hiện tại</div>@endif</td>
                            <td>{{ optional($item->created_at)->format('d/m/Y H:i') }}<br><small class="text-muted">Order #{{ $item->order_id }}</small></td>
                            <td class="text-right"><a href="{{ route('order_distributions.show', $item) }}" class="btn btn-sm btn-outline-primary">Audit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-5 text-muted">Không có frozen order phù hợp.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </form>
        <div class="p-3">{{ $frozenOrders->links('pagination::bootstrap-4') }}</div>
    </div>
</div>
<script>
document.getElementById('select-all')?.addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach((item) => item.checked = this.checked);
});
</script>
@endsection
