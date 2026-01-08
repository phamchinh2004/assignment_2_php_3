@extends('admin.layouts.master')
@section('title')
    Chi tiết báo cáo đơn hàng
@endsection

@section('style-libs')
<style>
    .order-detail-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    .order-detail-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 24px;
        margin-bottom: 20px;
    }
    .order-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f0f0f0;
    }
    .order-status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
    }
    .status-pending { background: #ffc10720; color: #ffc107; }
    .status-confirmed { background: #0d6efd20; color: #0d6efd; }
    .status-preparing { background: #17a2b820; color: #17a2b8; }
    .status-transit { background: #6f42c120; color: #6f42c1; }
    .status-shipping { background: #fd7e1420; color: #fd7e14; }
    .status-delivered { background: #19875420; color: #198754; }
    .status-completed { background: #19875420; color: #198754; }
    .status-cancelled { background: #dc354520; color: #dc3545; }
    
    .info-section {
        margin-bottom: 24px;
    }
    .info-section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 16px;
        color: #333;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-label {
        width: 200px;
        font-weight: 500;
        color: #666;
        flex-shrink: 0;
    }
    .info-value {
        flex: 1;
        color: #333;
    }
    .product-image {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    .customer-avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 120px;
        height: 120px;
        flex-shrink: 0;
    }
    .timeline-container {
        position: relative;
        padding-left: 30px;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 24px;
    }
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    .timeline-dot {
        position: absolute;
        left: -37px;
        top: 4px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #dee2e6;
        border: 3px solid white;
        z-index: 2;
    }
    .timeline-item.active .timeline-dot {
        background: #0d6efd;
        box-shadow: 0 0 0 4px #0d6efd20;
    }
    .timeline-item.completed .timeline-dot {
        background: #198754;
    }
    .timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: -31px;
        top: 18px;
        width: 2px;
        height: calc(100% - 4px);
        background: #dee2e6;
    }
    .timeline-item.completed:not(:last-child)::before,
    .timeline-item.active:not(:last-child)::before {
        background: #198754;
    }
    .timeline-item.is-muted {
        opacity: 0.5;
    }
    .timeline-content {
        font-size: 14px;
    }
    .timeline-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }
    .timeline-label.is-muted {
        color: #999;
    }
    .timeline-label.is-waiting {
        color: #ffc107;
    }
    .timeline-time {
        font-size: 12px;
        color: #999;
    }
    .btn-copy-api {
        background: #0d6efd;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
    }
    .btn-copy-api:hover {
        background: #0b5ed7;
        transform: translateY(-1px);
    }
    .btn-copy-api.copied {
        background: #198754;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0 text-gray-800">Chi tiết báo cáo đơn hàng</h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('order_reports.index') }}">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại danh sách
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @php
        $frozen = $frozenOrder ?? $orderReport->frozenOrder;
        $order = $frozen?->order;
        $owner = $frozen?->user;
    @endphp

    @if(!$frozen)
        <div class="alert alert-danger">Không tìm thấy thông tin đơn hàng!</div>
    @else
    <div class="row">
        <!-- Cột phải: Xử lý báo cáo -->
        <div class="col-lg-4 order-lg-2">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tasks mr-2"></i>Xử lý báo cáo
                    </h6>
                </div>
                <div class="card-body">
                    @if ($orderReport->status !== 'pending')
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle mr-2"></i>Báo cáo này đã được xử lý.
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Chọn 1 trong 2 hành động:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Xác nhận đơn</strong>: đơn thật → bác báo cáo</li>
                                <li><strong>Hủy đơn</strong>: đơn ảo → xác nhận báo cáo đúng</li>
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('order_reports.confirm', $orderReport) }}" class="mb-3"
                            onsubmit="return confirm('Xác nhận đơn này là đơn thật?');">
                            @csrf
                            <div class="form-group">
                                <label for="resolved_note_confirm">Ghi chú (tuỳ chọn)</label>
                                <textarea class="form-control" id="resolved_note_confirm" name="resolved_note" rows="3"
                                    placeholder="Nhập ghi chú..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-check mr-1"></i> Xác nhận đơn (bác báo cáo)
                            </button>
                        </form>

                        <form method="POST" action="{{ route('order_reports.cancel', $orderReport) }}"
                            onsubmit="return confirm('Hủy đơn này (xác nhận báo cáo đúng)?');">
                            @csrf
                            <div class="form-group">
                                <label for="resolved_note_cancel">Ghi chú (tuỳ chọn)</label>
                                <textarea class="form-control" id="resolved_note_cancel" name="resolved_note" rows="3"
                                    placeholder="Nhập ghi chú..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-times mr-1"></i> Hủy đơn (xác nhận báo cáo đúng)
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Cột trái: Thông tin chi tiết đơn hàng -->
        <div class="col-lg-8 order-lg-1">
            <!-- Thông tin báo cáo -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Thông tin báo cáo
                    </h6>
                    <div>
                        @if ($orderReport->status === 'pending')
                            <span class="badge badge-warning">Chờ xử lý</span>
                        @elseif ($orderReport->status === 'approved')
                            <span class="badge badge-success">Đã hủy (đơn ảo)</span>
                        @else
                            <span class="badge badge-secondary">Đã xác nhận (đơn thật)</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3"><strong>ID báo cáo:</strong> #{{ $orderReport->id }}</div>
                            <div class="mb-3"><strong>Mã đơn:</strong> {{ $order?->order_code ?? 'N/A' }}</div>
                            <div class="mb-3"><strong>Frozen Order ID:</strong> #{{ $orderReport->frozen_order_id }}</div>
                            <div class="mb-3">
                                <strong>Người báo cáo:</strong><br>
                                <div class="mt-1">
                                    <i class="fas fa-user mr-1"></i>
                                    {{ $orderReport->reporter?->full_name ?? $orderReport->reporter?->username ?? ('#' . $orderReport->reported_by) }}
                                </div>
                                @if($orderReport->reporter)
                                <div class="text-muted small mt-1">
                                    @if($orderReport->reporter->email)
                                        <i class="fas fa-envelope mr-1"></i>{{ $orderReport->reporter->email }}<br>
                                    @endif
                                    @if($orderReport->reporter->phone)
                                        <i class="fas fa-phone mr-1"></i>{{ $orderReport->reporter->phone }}
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <strong>Người đặt hàng:</strong><br>
                                <div class="mt-1">
                                    <i class="fas fa-user mr-1"></i>
                                    {{ $owner?->full_name ?? $owner?->username ?? ('#' . ($owner?->id ?? 'N/A')) }}
                                </div>
                                @if($owner)
                                <div class="text-muted small mt-1">
                                    @if($owner->email)
                                        <i class="fas fa-envelope mr-1"></i>{{ $owner->email }}<br>
                                    @endif
                                    @if($owner->phone)
                                        <i class="fas fa-phone mr-1"></i>{{ $owner->phone }}
                                    @endif
                                </div>
                                @endif
                            </div>
                            <div class="mb-3"><strong>Thời gian báo cáo:</strong> {{ $orderReport->created_at?->format('d/m/Y H:i:s') }}</div>
                            @if ($orderReport->resolved_at)
                                <div class="mb-3">
                                    <strong>Người xử lý:</strong><br>
                                    <div class="mt-1">
                                        <i class="fas fa-user-check mr-1"></i>
                                        {{ $orderReport->resolver?->full_name ?? $orderReport->resolver?->username ?? ('#' . $orderReport->resolved_by) }}
                                    </div>
                                </div>
                                <div class="mb-3"><strong>Thời gian xử lý:</strong> {{ $orderReport->resolved_at?->format('d/m/Y H:i:s') }}</div>
                            @endif
                        </div>
                    </div>
                    <hr>
                    <div class="mb-2">
                        <strong><i class="fas fa-comment-alt mr-2"></i>Lý do báo cáo:</strong>
                        <div class="mt-2 p-3 bg-light rounded">
                            {{ $orderReport->reason ?: '—' }}
                        </div>
                    </div>
                    @if ($orderReport->resolved_note)
                    <div class="mt-3">
                        <strong><i class="fas fa-sticky-note mr-2"></i>Ghi chú xử lý:</strong>
                        <div class="mt-2 p-3 bg-light rounded">
                            {{ $orderReport->resolved_note }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Thông tin đơn hàng -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shopping-cart mr-2"></i>Thông tin đơn hàng
                    </h6>
                </div>
                <div class="card-body">
                    <div class="order-detail-header">
                        <div>
                            <h5 style="margin: 0; font-size: 20px; font-weight: 600;">Chi tiết đơn hàng</h5>
                            <p style="margin: 8px 0 0 0; color: #666;">Mã đơn: {{ $order?->order_code ?? 'N/A' }}</p>
                        </div>
                        <div class="order-status-badge status-{{ $frozen->status ?? 'pending' }}" 
                             @if($currentStatus && $currentStatus->color) 
                             style="background: {{ $currentStatus->color }}20; color: {{ $currentStatus->color }}; border: 1px solid {{ $currentStatus->color }}40;"
                             @endif>
                            @php
                                $statusLabels = [
                                    'pending' => 'Chờ xử lý',
                                    'confirmed' => 'Đã xác nhận',
                                    'preparing' => 'Đang chuẩn bị hàng hóa',
                                    'transit' => 'Đang trung chuyển',
                                    'shipping' => 'Đang vận chuyển đến khách hàng',
                                    'delivered' => 'Đã giao hàng',
                                    'completed' => 'Đã hoàn thành',
                                    'cancelled' => 'Đã hủy'
                                ];
                            @endphp
                            {{ $currentStatus ? $currentStatus->display_name : ($statusLabels[$frozen->status ?? 'pending'] ?? 'Chờ xử lý') }}
                        </div>
                    </div>

                    <!-- Product Information -->
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="fas fa-box"></i>
                            Thông tin sản phẩm
                        </div>
                        <div class="d-flex gap-3">
                            @if($order && $order->image)
                            <img src="{{ Storage::url($order->image) }}" alt="{{ $order->name }}" class="product-image">
                            @endif
                            <div style="flex: 1;">
                                @if($order)
                                <h5 style="margin: 0 0 12px 0; font-weight: 600;">{{ $order->name }}</h5>
                                <div class="info-row">
                                    <div class="info-label">Giá:</div>
                                    <div class="info-value">{{ format_money($frozen->custom_price ? ($frozen->custom_price / $order->quantity) : $order->price) }}$</div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Số lượng:</div>
                                    <div class="info-value">{{ $order->quantity }}</div>
                                </div>
                                @php
                                    $commission_percentage = $frozen->custom_price != null 
                                        ? ($frozen->commission_percentage ?? $order->commission_percentage ?? 0)
                                        : ($order->commission_percentage ?? 0);
                                    
                                    $total_order_value = $frozen->custom_price 
                                        ? $frozen->custom_price 
                                        : ($order->price * $order->quantity);
                                    
                                    $commission_amount = $total_order_value * ($commission_percentage / 100);
                                @endphp
                                <div class="info-row">
                                    <div class="info-label">Tổng giá trị:</div>
                                    <div class="info-value" style="font-weight: 600; color: #198754;">
                                        {{ format_money($total_order_value) }}$
                                    </div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Hoa hồng ({{ $commission_percentage }}%):</div>
                                    <div class="info-value" style="font-weight: 600; color: #0d6efd;">
                                        {{ format_money($commission_amount) }}$
                                    </div>
                                </div>
                                @if($frozen->penalty_amount && $frozen->penalty_amount > 0)
                                <div class="info-row">
                                    <div class="info-label">Tiền phạt:</div>
                                    <div class="info-value" style="font-weight: 600; color: #dc3545;">
                                        -{{ format_money($frozen->penalty_amount) }}$
                                    </div>
                                </div>
                                @endif
                                @if($frozen->custom_price != null)
                                <div class="info-row" style="border-bottom: none;">
                                    <div class="info-label">Loại đơn:</div>
                                    <div class="info-value" style="font-weight: 600; color: #fd7e14;">
                                        <i class="fas fa-star mr-1"></i>Đơn đặc biệt
                                    </div>
                                </div>
                                @endif
                                @else
                                <div class="text-muted">Không có thông tin sản phẩm</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="fas fa-user"></i>
                            Thông tin người đặt hàng
                        </div>
                        <div class="d-flex gap-3">
                            <div class="customer-avatar">
                                <i class="fas fa-user-circle" style="font-size: 80px; color: #0d6efd;"></i>
                            </div>
                            <div style="flex: 1;">
                                @if($order && $order->customer_name)
                                <h5 style="margin: 0 0 12px 0; font-weight: 600;">{{ $order->customer_name }}</h5>
                                @endif
                                @if($order && $order->customer_phone)
                                <div class="info-row">
                                    <div class="info-label">Số điện thoại:</div>
                                    <div class="info-value">{{ $order->customer_phone }}</div>
                                </div>
                                @endif
                                @if($order && $order->customer_address)
                                <div class="info-row">
                                    <div class="info-label">Địa chỉ nhận hàng:</div>
                                    <div class="info-value">{{ $order->customer_address }}</div>
                                </div>
                                @endif
                                @if($order && $order->customer_note)
                                <div class="info-row" style="border-bottom: none;">
                                    <div class="info-label">Ghi chú:</div>
                                    <div class="info-value" style="font-style: italic; color: #666;">{{ $order->customer_note }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Platform & Payment Information -->
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="fas fa-store"></i>
                            Nền tảng & Thanh toán
                        </div>
                        @if($order && $order->partner)
                        <div class="info-row">
                            <div class="info-label">Nền tảng:</div>
                            <div class="info-value" style="font-weight: 600;">{{ $order->partner->name }}</div>
                        </div>
                        @elseif($frozen->platform)
                        <div class="info-row">
                            <div class="info-label">Nền tảng:</div>
                            <div class="info-value" style="font-weight: 600;">{{ $frozen->platform }}</div>
                        </div>
                        @endif
                        @if($frozen->order_date)
                        <div class="info-row">
                            <div class="info-label">Ngày đặt hàng:</div>
                            <div class="info-value">{{ $frozen->order_date->format('d/m/Y H:i') }}</div>
                        </div>
                        @endif
                        @if($order && $order->payment_method)
                        <div class="info-row">
                            <div class="info-label">Hình thức thanh toán:</div>
                            <div class="info-value" style="font-weight: 600;">
                                {{ $order->payment_method }}
                                @if($order->is_paid)
                                    <span class="badge badge-success ml-2">Đã thanh toán</span>
                                @else
                                    <span class="badge badge-warning ml-2">Chưa thanh toán</span>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Tracking Information -->
                    @if($frozen->tracking_number)
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="fas fa-truck"></i>
                            Thông tin vận chuyển
                        </div>
                        <div class="info-row">
                            <div class="info-label">Mã vận đơn:</div>
                            <div class="info-value" style="font-family: monospace; font-weight: 600;">{{ $frozen->tracking_number }}</div>
                        </div>
                        @if($frozen->shipping_carrier)
                        <div class="info-row" style="border-bottom: none;">
                            <div class="info-label">Đơn vị vận chuyển:</div>
                            <div class="info-value">{{ $frozen->shipping_carrier }}</div>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- API Information -->
                    @if($order && $order->api)
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="fas fa-code"></i>
                            API theo dõi đơn hàng
                        </div>
                        <div class="info-row">
                            <div class="info-label">API Key:</div>
                            <div class="info-value" style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-family: monospace; font-weight: 600; word-break: break-all; background: #f8f9fa; padding: 8px; border-radius: 4px; flex: 1;">
                                    {{ $order->api }}
                                </span>
                                <button type="button" class="btn-copy-api" data-api="{{ $order->api }}" title="Sao chép API Key">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Order Timeline -->
                    @if(isset($allStatusesWithHistory) && count($allStatusesWithHistory) > 0)
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="fas fa-history"></i>
                            Lịch sử thay đổi trạng thái
                        </div>
                        <div class="timeline-container">
                            @foreach($allStatusesWithHistory as $index => $statusData)
                                @php
                                    $isSpecial = $statusData['isSpecial'] ?? false;
                                    
                                    if ($isSpecial && ($statusData['specialType'] ?? '') === 'commission_paid') {
                                        $isReached = $statusData['isReached'] ?? false;
                                        $commissionPaid = $statusData['commissionPaid'] ?? false;
                                        $isOrderCompleted = $statusData['isOrderCompleted'] ?? false;
                                    } else {
                                        $status = $statusData['status'];
                                        $statusOrder = $statusData['statusOrder'];
                                        $isReached = $statusData['isReached'];
                                        $isCurrent = $isReached && $frozen->status === $status->name;
                                        $isCompleted = $isReached && !$isCurrent;
                                    }
                                @endphp
                                
                                @if($isSpecial && ($statusData['specialType'] ?? '') === 'commission_paid')
                                    @php
                                        $isPending = !$isOrderCompleted;
                                        $isWaiting = $isOrderCompleted && !$isReached;
                                        $isPaid = $isReached;
                                    @endphp
                                    <div class="timeline-item {{ $isPaid ? 'completed' : 'pending' }} {{ $isPending ? 'is-muted' : '' }}">
                                        <div class="timeline-dot" 
                                             @if($isPaid)
                                             style="background: #198754; border-color: #198754;"
                                             @elseif($isWaiting)
                                             style="background: #ffc107; border-color: #ffc107;"
                                             @else
                                             style="background: #dee2e6; border-color: #dee2e6;"
                                             @endif></div>
                                        <div class="timeline-content">
                                            <div class="timeline-label {{ $isPending ? 'is-muted' : ($isWaiting ? 'is-waiting' : '') }}">
                                                {{ $isPaid ? 'Đã cộng tiền' : 'Chưa cộng tiền' }}
                                                <span style="color: #666; font-size: 12px; font-weight: normal;">
                                                    (Bởi hệ thống)
                                                </span>
                                            </div>
                                            @if($isPending)
                                            <div class="timeline-time" style="color: #999;">
                                                <i class="fas fa-clock"></i> Đơn hàng chưa hoàn thành
                                            </div>
                                            @elseif($isWaiting)
                                            <div class="timeline-time" style="color: #ffc107; font-weight: 600;">
                                                <i class="fas fa-hourglass-half"></i> Đang chờ cộng tiền
                                            </div>
                                            @else
                                            <div class="timeline-time" style="color: #198754; font-weight: 600;">
                                                <i class="fas fa-check-circle"></i> Đã cộng tiền hoa hồng
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="timeline-item {{ $isCompleted ? 'completed' : ($isCurrent ? 'active' : ($isReached ? '' : 'pending')) }} {{ !$isReached ? 'is-muted' : '' }}">
                                        <div class="timeline-dot" 
                                             @if($isReached && $status && $status->color)
                                             style="background: {{ $status->color }}; border-color: {{ $status->color }};"
                                             @else
                                             style="background: #dee2e6; border-color: #dee2e6;"
                                             @endif></div>
                                        <div class="timeline-content">
                                            <div class="timeline-label {{ !$isReached ? 'is-muted' : '' }}">
                                                {{ $status ? $status->display_name : 'N/A' }}
                                                @if($isReached && $statusOrder)
                                                    @if($statusOrder->changedBy)
                                                        <span style="color: #666; font-size: 12px; font-weight: normal;">
                                                            (bởi {{ $statusOrder->changedBy->full_name ?? $statusOrder->changedBy->username ?? 'Hệ thống' }})
                                                        </span>
                                                    @else
                                                        <span style="color: #666; font-size: 12px; font-weight: normal;">
                                                            (Hệ thống)
                                                        </span>
                                                    @endif
                                                @endif
                                            </div>
                                            @if($isReached && $statusOrder)
                                            <div class="timeline-time">{{ $statusOrder->created_at->format('d/m/Y H:i:s') }}</div>
                                            @if($statusOrder->notes)
                                            <div style="margin-top: 4px; font-size: 12px; color: #666; font-style: italic;">
                                                <i class="fas fa-comment-alt"></i> {{ $statusOrder->notes }}
                                            </div>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                    <div class="info-section">
                        <div class="info-section-title">
                            <i class="fas fa-history"></i>
                            Lịch sử thay đổi trạng thái
                        </div>
                        <div style="text-align: center; padding: 20px; color: #999;">
                            <i class="fas fa-info-circle"></i> Chưa có lịch sử thay đổi trạng thái
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Xử lý nút sao chép API
    document.addEventListener('DOMContentLoaded', function() {
        const btnCopyApi = document.querySelector('.btn-copy-api');
        if (btnCopyApi) {
            btnCopyApi.addEventListener('click', function() {
                const apiKey = this.getAttribute('data-api');
                if (!apiKey) {
                    alert('Không tìm thấy API Key');
                    return;
                }
                
                navigator.clipboard.writeText(apiKey).then(function() {
                    const icon = btnCopyApi.querySelector('i');
                    const originalClass = icon.className;
                    icon.className = 'fas fa-check';
                    btnCopyApi.classList.add('copied');
                    btnCopyApi.title = 'Đã sao chép!';
                    
                    if (typeof notification !== 'undefined') {
                        notification('success', 'Đã sao chép API Key vào clipboard!', 'Thành công');
                    } else {
                        alert('Đã sao chép API Key vào clipboard!');
                    }
                    
                    setTimeout(function() {
                        icon.className = originalClass;
                        btnCopyApi.classList.remove('copied');
                        btnCopyApi.title = 'Sao chép API Key';
                    }, 2000);
                }).catch(function(err) {
                    console.error('Lỗi khi sao chép:', err);
                    const textArea = document.createElement('textarea');
                    textArea.value = apiKey;
                    textArea.style.position = 'fixed';
                    textArea.style.opacity = '0';
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        alert('Đã sao chép API Key vào clipboard!');
                    } catch (err) {
                        alert('Không thể sao chép. Vui lòng sao chép thủ công.');
                    }
                    document.body.removeChild(textArea);
                });
            });
        }
    });
</script>
@endpush
@endsection
