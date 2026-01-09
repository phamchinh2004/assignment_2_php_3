@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/order.css')
<style>
    .order-detail-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    .order-detail-card {
        background: white;
        border-radius: 12px;
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
    }
    .info-value {
        flex: 1;
        color: #333;
    }
    .product-info-container,
    .customer-info-container {
        align-items: center;
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
    .timeline-item.is-muted {
        opacity: 0.5;
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
    /* Đường line từ trạng thái đã đạt đến đến trạng thái chưa đạt đến vẫn là xám */
    .timeline-item.completed + .timeline-item.pending::before,
    .timeline-item.active + .timeline-item.pending::before {
        background: #dee2e6;
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
    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }
    .btn-confirm {
        background: #198754;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-confirm:hover {
        background: #157347;
        transform: translateY(-2px);
    }
    .btn-cancel {
        background: #dc3545;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-cancel:hover {
        background: #bb2d3b;
        transform: translateY(-2px);
    }
    .btn-report {
        background: #fd7e14;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-report:hover {
        background: #e36d0b;
        transform: translateY(-2px);
    }
    .btn-contact-cskh {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    .btn-contact-cskh:hover {
        background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    .btn-back {
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin-bottom: 20px;
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
    .btn-copy-api:active {
        transform: translateY(0);
    }
    .btn-copy-api.copied {
        background: #198754;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .order-detail-container {
            padding: 10px;
        }
        .order-detail-card {
            padding: 16px;
        }
        .order-detail-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }
        .product-info-container,
        .customer-info-container {
            flex-direction: column;
            gap: 16px !important;
            align-items: center;
        }
        .product-image {
            width: 100%;
            max-width: 200px;
            height: auto;
            aspect-ratio: 1;
        }
        .customer-avatar {
            width: 100%;
            max-width: 120px;
        }
        .customer-avatar i {
            font-size: 80px !important;
        }
        .info-row {
            flex-wrap: nowrap;
            gap: 8px;
            padding: 10px 0;
        }
        .info-label {
            min-width: 120px;
            max-width: 50%;
            font-size: 14px;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
            min-width: 0;
            word-break: break-word;
            font-size: 14px;
        }
        .info-section-title {
            font-size: 16px;
        }
        h2 {
            font-size: 20px !important;
        }
        h4 {
            font-size: 16px !important;
        }
    }
    
    @media (max-width: 480px) {
        .order-detail-container {
            padding: 8px;
        }
        .order-detail-card {
            padding: 12px;
        }
        .product-image {
            max-width: 150px;
        }
        .info-label {
            font-size: 13px;
        }
        .info-value {
            font-size: 13px;
        }
        .info-value small {
            font-size: 11px !important;
        }
    }
</style>
@endsection
@section('script-libs')
@php
    $frozenOrderId = $frozen_order->id ?? null;
    $routeConfirmOrder = $frozenOrderId ? route('order.confirm', ['frozen_order' => $frozenOrderId]) : '';
    $routeCancelOrder = $frozenOrderId ? route('order.cancel', ['frozen_order' => $frozenOrderId]) : '';
    $routeReportOrder = $frozenOrderId ? route('order.report', ['frozen_order' => $frozenOrderId]) : '';

    $orderDetailConfig = [
        'trans' => [
            'XacNhanDonHang' => __('order.XacNhanDonHang') ?? 'Xác nhận đơn hàng',
            'HuyDonHang' => __('order.HuyDonHang') ?? 'Hủy đơn hàng',
            'ThanhCong' => __('order.ThanhCong') ?? 'Thành công',
            'Loi' => __('order.Loi') ?? 'Lỗi',
            'CanhBao' => __('order.CanhBao') ?? 'Cảnh báo',
        ],
        'routes' => [
            'confirm' => $routeConfirmOrder,
            'cancel' => $routeCancelOrder,
            'report' => $routeReportOrder,
            'order' => route('order'),
        ],
        'csrf' => csrf_token(),
        'frozen_order_id' => $frozenOrderId,
    ];
@endphp
<div id="order-detail-config" data-config='@json($orderDetailConfig)' style="display:none;"></div>
@vite('resources/js/user/order_detail.js')
@endsection
@section('content')

<div class="order-detail-container">
    <a href="{{ route('order') }}" class="btn-back">
        <i class="fas fa-arrow-left me-2"></i>Quay lại danh sách
    </a>

    <!-- Order Status Card -->
    <div class="order-detail-card">
        <div class="order-detail-header">
<div>
                <h2 style="margin: 0; font-size: 24px; font-weight: 600;">Chi tiết đơn hàng</h2>
                <p style="margin: 8px 0 0 0; color: #666;">Mã đơn: {{ $frozen_order->order->order_code ?? 'N/A' }}</p>
            </div>
            <div class="order-status-badge status-{{ $frozen_order->status ?? 'pending' }}" 
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
                {{ $currentStatus ? $currentStatus->display_name : ($statusLabels[$frozen_order->status ?? 'pending'] ?? 'Chờ xử lý') }}
            </div>
        </div>

        <!-- Product Information -->
        <div class="info-section">
            <div class="info-section-title">
                <i class="fas fa-box"></i>
                Thông tin sản phẩm
            </div>
            <div class="d-flex gap-3 product-info-container">
                <img src="{{ Storage::url($frozen_order->order->image) }}" alt="{{ $frozen_order->order->name }}" class="product-image">
                <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;" class="w-100">
                    <h4 style="margin: 0 0 12px 0; font-weight: 600;" class="text-center">{{ $frozen_order->order->name }}</h4>
                    <div class="info-row">
                        <div class="info-label">Giá:</div>
                        <div class="info-value">{{ format_money($frozen_order->custom_price ? ($frozen_order->custom_price / $frozen_order->order->quantity) : $frozen_order->order->price) }}$</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Số lượng:</div>
                        <div class="info-value">{{ $frozen_order->order->quantity }}</div>
                    </div>
                    @php
                        // Lấy phần trăm hoa hồng: đơn đặc biệt từ frozen_order, đơn thường từ order
                        $commission_percentage = $frozen_order->custom_price != null 
                            ? ($frozen_order->commission_percentage ?? $frozen_order->order->commission_percentage ?? 0)
                            : ($frozen_order->order->commission_percentage ?? 0);
                        
                        // Tính tổng giá trị đơn hàng
                        $total_order_value = $frozen_order->custom_price 
                            ? $frozen_order->custom_price 
                            : ($frozen_order->order->price * $frozen_order->order->quantity);
                        
                        // Tính hoa hồng
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
                    @if($frozen_order->penalty_amount && $frozen_order->penalty_amount > 0)
                    <div class="info-row">
                        <div class="info-label">Tiền phạt:</div>
                        <div class="info-value" style="font-weight: 600; color: #dc3545;">
                            -{{ format_money($frozen_order->penalty_amount) }}$
                        </div>
                    </div>
                    <div class="info-row" style="border-bottom: none;">
                        <div class="info-label">Số tiền cần xử lý đơn hàng này:</div>
                        <div class="info-value" style="font-weight: 600; color: #dc3545;">
                            @php
                                $totalOrderValue = $frozen_order->custom_price ? $frozen_order->custom_price : ($frozen_order->order->price * $frozen_order->order->quantity);
                                $penaltyAmount = $frozen_order->penalty_amount ?? 0;
                                $totalAmount = $totalOrderValue + $penaltyAmount;
                            @endphp
                            {{ format_money($totalAmount) }}$
                            <small style="display: block; font-size: 12px; color: #666; margin-top: 4px;">
                                (Tổng giá trị: {{ format_money($totalOrderValue) }}$ + Tiền phạt: {{ format_money($penaltyAmount) }}$)
                            </small>
                        </div>
                    </div>
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
            <div class="d-flex gap-3 customer-info-container">
                <div class="customer-avatar">
                    <i class="fas fa-user-circle" style="font-size: 80px; color: #0d6efd;"></i>
                </div>
                <div style="flex: 1; display: flex; flex-direction: column; justify-content: center;">
                    @if($frozen_order->order->customer_name)
                    <h4 style="margin: 0 0 12px 0; font-weight: 600; text-align: center;">{{ $frozen_order->order->customer_name }}</h4>
                    @endif
                    @if($frozen_order->order->customer_phone)
                    <div class="info-row">
                        <div class="info-label">Số điện thoại:</div>
                        <div class="info-value">{{ $frozen_order->order->customer_phone }}</div>
                    </div>
                    @endif
                    @if($frozen_order->order->customer_address)
                    <div class="info-row">
                        <div class="info-label">Địa chỉ nhận hàng:</div>
                        <div class="info-value">{{ $frozen_order->order->customer_address }}</div>
                    </div>
                    @endif
                    @if($frozen_order->order->customer_note)
                    <div class="info-row">
                        <div class="info-label">Ghi chú:</div>
                        <div class="info-value" style="font-style: italic; color: #666;">{{ $frozen_order->order->customer_note }}</div>
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
            @if($frozen_order->order->partner)
            <div class="info-row">
                <div class="info-label">Nền tảng:</div>
                <div class="info-value" style="font-weight: 600;">{{ $frozen_order->order->partner->name }}</div>
            </div>
            @elseif($frozen_order->platform)
            <div class="info-row">
                <div class="info-label">Nền tảng:</div>
                <div class="info-value" style="font-weight: 600;">{{ $frozen_order->platform }}</div>
            </div>
            @endif
            @if($frozen_order->order_date)
            <div class="info-row">
                <div class="info-label">Ngày đặt hàng:</div>
                <div class="info-value">{{ $frozen_order->order_date->format('d/m/Y H:i') }}</div>
            </div>
            @endif
            @if($frozen_order->order->payment_method)
            <div class="info-row">
                <div class="info-label">Hình thức thanh toán:</div>
                <div class="info-value" style="font-weight: 600;">
                    {{ $frozen_order->order->payment_method }}
                    @if($frozen_order->order->is_paid)
                        <span style="background: #198754; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">Đã thanh toán</span>
                    @else
                        <span style="background: #ffc107; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin-left: 8px;">Chưa thanh toán</span>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Tracking Information -->
        @if($frozen_order->tracking_number)
        <div class="info-section">
            <div class="info-section-title">
                <i class="fas fa-truck"></i>
                Thông tin vận chuyển
            </div>
            <div class="info-row">
                <div class="info-label">Mã vận đơn:</div>
                <div class="info-value" style="font-family: monospace; font-weight: 600;">{{ $frozen_order->tracking_number }}</div>
            </div>
            @if($frozen_order->shipping_carrier)
            <div class="info-row">
                <div class="info-label">Đơn vị vận chuyển:</div>
                <div class="info-value">{{ $frozen_order->shipping_carrier }}</div>
            </div>
            @endif
        </div>
        @endif

        <!-- API Information -->
        @if($frozen_order->order->api)
        <div class="info-section">
            <div class="info-section-title">
                <i class="fas fa-code"></i>
                API theo dõi đơn hàng
            </div>
            <div class="info-row">
                <div class="info-label">API Key:</div>
                <div class="info-value" style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-family: monospace; font-weight: 600; word-break: break-all; background: #f8f9fa; padding: 8px; border-radius: 4px; flex: 1;">
                        {{ $frozen_order->order->api }}
                    </span>
                    <button type="button" class="btn-copy-api" data-api="{{ $frozen_order->order->api }}" title="Sao chép API Key">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            <div class="info-row" style="border-bottom: none;">
                <div class="info-label"></div>
                <div class="info-value" style="color: #666; font-size: 14px; font-style: italic;">
                    <i class="fas fa-info-circle me-2"></i>
                    Bạn có thể dùng API này ở nền tảng quản lý đơn hàng này để theo dõi đơn hàng!
                </div>
            </div>
        </div>
        @endif

        <!-- Order Timeline -->
        <div class="info-section">
            <div class="info-section-title">
                <i class="fas fa-history"></i>
                Lịch sử thay đổi trạng thái
            </div>
            @if(isset($allStatusesWithHistory) && count($allStatusesWithHistory) > 0)
            <div class="timeline-container">
                @foreach($allStatusesWithHistory as $index => $statusData)
                    @php
                        $isSpecial = $statusData['isSpecial'] ?? false;
                        
                        if ($isSpecial && ($statusData['specialType'] ?? '') === 'commission_paid') {
                            // Xử lý mục đặc biệt "Đã cộng tiền"
                            $isReached = $statusData['isReached'] ?? false;
                            $commissionPaid = $statusData['commissionPaid'] ?? false;
                            $isOrderCompleted = $statusData['isOrderCompleted'] ?? false;
                        } else {
                            // Xử lý trạng thái bình thường
                            $status = $statusData['status'];
                            $statusOrder = $statusData['statusOrder'];
                            $isReached = $statusData['isReached'];
                            // Trạng thái hiện tại là trạng thái có name trùng với frozen_order->status
                            $isCurrent = $isReached && $frozen_order->status === $status->name;
                            $isCompleted = $isReached && !$isCurrent;
                        }
                    @endphp
                    
                    @if($isSpecial && ($statusData['specialType'] ?? '') === 'commission_paid')
                        {{-- Hiển thị mục "Đã cộng tiền" --}}
                        @php
                            // Xác định style dựa trên trạng thái
                            $isPending = !$isOrderCompleted; // Chưa completed
                            $isWaiting = $isOrderCompleted && !$isReached; // Đã completed nhưng chưa cộng tiền
                            $isPaid = $isReached; // Đã completed và đã cộng tiền
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
                        {{-- Hiển thị trạng thái bình thường --}}
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
                                                @if($status && $status->name === 'pending')
                                                    (Bởi nhân viên)
                                                @elseif($status && $status->name === 'confirmed')
                                                    (Bởi người bán)
                                                @elseif($status && $status->name === 'preparing')
                                                    (Bởi người bán)
                                                @elseif($status && $status->name === 'transit')
                                                    (CÔNG TY TNHH DỊCH VỤ TIẾP VẬN TOÀN CẦU (GLS))
                                                @elseif($status && $status->name === 'shipping')
                                                    (Bởi đơn vị vận chuyển)
                                                @elseif($status && $status->name === 'delivered')
                                                    (Bởi người giao hàng)
                                                @elseif($status && $status->name === 'completed')
                                                    (Bởi khách hàng)
                                                @elseif($status && $status->name === 'cancelled')
                                                    (Bởi người bán hoặc khách hàng)
                                                @else
                                                    (Hệ thống)
                                                @endif
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
            <div style="text-align: center; padding: 20px; color: #999;">
                <i class="fas fa-info-circle"></i> Chưa có lịch sử thay đổi trạng thái
            </div>
            @endif
        </div>

        <!-- Action Buttons -->
        @if(($frozen_order->status ?? 'pending') === 'pending')
        <div class="action-buttons">
            <button type="button" class="btn-confirm" id="btn_confirm_order">
                <i class="fas fa-check-circle me-2"></i>{{ __('order.XacNhanDonHang') ?? 'Xác nhận đơn hàng' }}
            </button>
            @if($frozen_order->custom_price != null)
                <!-- Đơn đặc biệt: Hiển thị nút Liên hệ CSKH thay vì Hủy đơn hàng -->
                <button type="button" class="btn-contact-cskh" id="btn_contact_cskh">
                    <i class="fas fa-headset me-2"></i>Liên hệ CSKH
                </button>
            @else
                <!-- Đơn thường: Thay nút Hủy bằng nút Báo cáo đơn hàng -->
                <button type="button" class="btn-report" id="btn_report_fake_order"
                    @if($frozen_order->orderReport) disabled @endif
                    title="{{ $frozen_order->orderReport ? 'Đơn hàng đã được báo cáo và đang chờ admin xử lý' : 'Báo cáo đơn hàng' }}">
                    <i class="fas fa-flag me-2"></i>
                    {{ $frozen_order->orderReport ? 'Đã báo cáo - chờ xử lý' : 'Báo cáo đơn hàng' }}
                </button>
            @endif
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Test script để kiểm tra button có tồn tại không
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== ORDER DETAIL DEBUG ===');
        const btnConfirm = document.getElementById('btn_confirm_order');
        const btnReport = document.getElementById('btn_report_fake_order');
        
        console.log('Button elements:', {
            btnConfirm: btnConfirm,
            btnReport: btnReport,
            btnConfirmExists: !!btnConfirm,
            btnReportExists: !!btnReport
        });
        
        console.log('Window variables:', {
            trans: window.trans,
            route_confirm_order: window.route_confirm_order,
            route_report_order: window.route_report_order,
            csrf: window.csrf ? 'exists' : 'missing'
        });
        
        // Event listener đã được xử lý trong order_detail.js
        // Không cần thêm event listener ở đây nữa để tránh double request
        
        // Xử lý nút sao chép API
        const btnCopyApi = document.querySelector('.btn-copy-api');
        if (btnCopyApi) {
            btnCopyApi.addEventListener('click', function() {
                const apiKey = this.getAttribute('data-api');
                if (!apiKey) {
                    alert('Không tìm thấy API Key');
                    return;
                }
                
                // Copy vào clipboard
                navigator.clipboard.writeText(apiKey).then(function() {
                    // Thay đổi icon và màu nút
                    const icon = btnCopyApi.querySelector('i');
                    const originalClass = icon.className;
                    icon.className = 'fas fa-check';
                    btnCopyApi.classList.add('copied');
                    btnCopyApi.title = 'Đã sao chép!';
                    
                    // Hiển thị thông báo
                    if (typeof notification !== 'undefined') {
                        notification('success', 'Đã sao chép API Key vào clipboard!', 'Thành công');
                    } else {
                        alert('Đã sao chép API Key vào clipboard!');
                    }
                    
                    // Khôi phục sau 2 giây
                    setTimeout(function() {
                        icon.className = originalClass;
                        btnCopyApi.classList.remove('copied');
                        btnCopyApi.title = 'Sao chép API Key';
                    }, 2000);
                }).catch(function(err) {
                    console.error('Lỗi khi sao chép:', err);
                    // Fallback: sử dụng cách cũ
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
