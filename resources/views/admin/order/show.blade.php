@extends('admin.layouts.master')
@section('title')
    Chi tiết đơn hàng #{{ $order->order_code }}
@endsection

@section('style-libs')
    @vite('resources/css/admin/common-modern.css')
    <style>
        .order-image-box {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--border-color);
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .order-image-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .order-code-mono {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 0.85rem;
            padding: 3px 8px;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            display: inline-block;
        }
        .user-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 8px;
            background: #f1f5f9;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #334155;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid px-4 pb-5">

    {{-- Back Link --}}
    <a href="{{ route('order.index') }}" class="btn-back-modern">
        <i class="fas fa-arrow-left"></i> Quay lại danh sách đơn hàng
    </a>

    {{-- Page Header --}}
    <div class="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="order-image-box">
                @if($order->image)
                    <img src="{{ Storage::url($order->image) }}" alt="{{ $order->name }}">
                @else
                    <i class="fas fa-box-open text-muted" style="font-size: 32px;"></i>
                @endif
            </div>
            <div>
                <h1 class="page-title-main">
                    {{ $order->name }}
                </h1>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                    <span class="order-code-mono"><i class="fas fa-barcode mr-1"></i>{{ $order->order_code }}</span>
                    
                    @if($order->status == 1)
                        <span class="badge-status-modern success"><span class="status-dot"></span> Hoạt động</span>
                    @else
                        <span class="badge-status-modern danger"><span class="status-dot"></span> Đã ẩn</span>
                    @endif

                    @if($order->is_paid)
                        <span class="badge-status-modern success"><i class="fas fa-check-circle mr-1"></i> Đã thanh toán</span>
                    @else
                        <span class="badge-status-modern warning"><i class="fas fa-clock mr-1"></i> Chưa thanh toán</span>
                    @endif

                    @if($order->partner)
                        <span class="badge-status-modern info">
                            <i class="fas fa-store mr-1"></i> {{ $order->partner->name }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('order.edit', ['order' => $order->id]) }}" class="btn-create-modern">
                <i class="fas fa-pen mr-1"></i> Chỉnh sửa đơn hàng
            </a>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="stats-grid-modern">
        <div class="stat-card-modern">
            <div class="stat-header-modern">
                <span class="stat-title-modern">Đơn giá sản phẩm</span>
                <span class="stat-icon-wrap-modern primary"><i class="fas fa-dollar-sign"></i></span>
            </div>
            <div class="stat-value-modern">${{ number_format($order->price, 2) }}</div>
            <div class="stat-hint-modern">
                @if($order->fake_price)
                    <span class="text-muted text-decoration-line-through">${{ number_format($order->fake_price, 2) }}</span>
                @else
                    Giá niêm yết
                @endif
            </div>
        </div>

        <div class="stat-card-modern">
            <div class="stat-header-modern">
                <span class="stat-title-modern">Số lượng</span>
                <span class="stat-icon-wrap-modern info"><i class="fas fa-cubes"></i></span>
            </div>
            <div class="stat-value-modern">{{ number_format($order->quantity) }}</div>
            <div class="stat-hint-modern">Đơn vị sản phẩm</div>
        </div>

        <div class="stat-card-modern">
            <div class="stat-header-modern">
                <span class="stat-title-modern">Tổng giá trị đơn</span>
                <span class="stat-icon-wrap-modern success"><i class="fas fa-calculator"></i></span>
            </div>
            <div class="stat-value-modern text-success">${{ number_format($order->price * $order->quantity, 2) }}</div>
            <div class="stat-hint-modern">Thành tiền thực nhận</div>
        </div>

        <div class="stat-card-modern">
            <div class="stat-header-modern">
                <span class="stat-title-modern">Tỷ lệ hoa hồng</span>
                <span class="stat-icon-wrap-modern warning"><i class="fas fa-percent"></i></span>
            </div>
            <div class="stat-value-modern text-warning">{{ $order->commission_percentage ?? 0 }}%</div>
            <div class="stat-hint-modern">Cấp: {{ $order->rank ? $order->rank->name : 'Mặc định' }}</div>
        </div>
    </div>

    {{-- Detailed Info Section --}}
    <div class="row mt-4">
        {{-- Left: Product & Assigned Members Info --}}
        <div class="col-12 col-lg-7">
            {{-- Product details --}}
            <div class="card-modern mb-4">
                <div class="card-header-modern">
                    <h6 class="title-header">
                        <i class="fas fa-box-open"></i> Chi tiết mặt hàng & Cấu hình
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="detail-row-modern">
                        <span class="detail-label-modern">Tên sản phẩm</span>
                        <span class="detail-value-modern font-weight-bold">{{ $order->name }}</span>
                    </div>
                    <div class="detail-row-modern">
                        <span class="detail-label-modern">Mã hệ thống</span>
                        <span class="detail-value-modern"><code>{{ $order->order_code }}</code></span>
                    </div>
                    <div class="detail-row-modern">
                        <span class="detail-label-modern">Hạng thành viên áp dụng</span>
                        <span class="detail-value-modern">
                            @if($order->rank)
                                <span class="badge-status-modern info">
                                    <i class="fas fa-crown mr-1"></i> {{ $order->rank->name }}
                                </span>
                            @else
                                <span class="text-muted">Áp dụng tất cả</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-row-modern">
                        <span class="detail-label-modern">Mã theo dõi (API)</span>
                        <span class="detail-value-modern">
                            @if($order->api)
                                <code>{{ $order->api }}</code>
                            @else
                                <span class="text-muted font-italic">Không có mã API</span>
                            @endif
                        </span>
                    </div>
                    <div class="detail-row-modern">
                        <span class="detail-label-modern">Trạng thái hiển thị</span>
                        <span class="detail-value-modern">
                            @if($order->status == 1)
                                <span class="badge-status-modern success">Hiển thị trong hệ thống</span>
                            @else
                                <span class="badge-status-modern danger">Đang tạm ẩn</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Assigned Members (Frozen Orders) --}}
            <div class="card-modern">
                <div class="card-header-modern d-flex justify-content-between align-items-center">
                    <h6 class="title-header">
                        <i class="fas fa-users"></i> Lịch sử gán cho thành viên ({{ $order->frozen_orders->count() }})
                    </h6>
                </div>
                <div class="table-responsive">
                    @if($order->frozen_orders->count() > 0)
                        <table class="table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Thành viên</th>
                                    <th>Trạng thái gán</th>
                                    <th>Hoa hồng</th>
                                    <th>Thời điểm gán</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->frozen_orders as $fo)
                                    <tr>
                                        <td>#{{ $fo->id }}</td>
                                        <td>
                                            @if($fo->user)
                                                <a href="{{ route('user.edit', ['user' => $fo->user->id]) }}" class="user-tag text-decoration-none">
                                                    <i class="fas fa-user-circle text-primary"></i>
                                                    {{ $fo->user->name ?? $fo->user->phone_number }}
                                                </a>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($fo->is_frozen)
                                                <span class="badge-status-modern warning"><span class="status-dot"></span> Đang chờ xử lý</span>
                                            @else
                                                <span class="badge-status-modern success"><span class="status-dot"></span> Đã hoàn thành</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($fo->commission_paid)
                                                <span class="text-success font-weight-bold"><i class="fas fa-check mr-1"></i> Đã trả</span>
                                            @else
                                                <span class="text-muted">Chưa trả</span>
                                            @endif
                                        </td>
                                        <td>{{ $fo->created_at ? $fo->created_at->format('d/m/Y H:i') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state-modern py-4 text-center">
                            <div class="empty-icon-wrap"><i class="fas fa-user-clock"></i></div>
                            <h6 class="empty-title">Chưa có thành viên nào nhận đơn này</h6>
                            <p class="empty-description">Đơn hàng này chưa được phân bổ vào danh sách nhiệm vụ của thành viên nào.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Customer & Payment Details --}}
        <div class="col-12 col-lg-5">
            {{-- Customer Card --}}
            <div class="detail-card-modern mb-4">
                <h5 class="detail-card-title">
                    <i class="fas fa-user-tag"></i> Thông tin khách hàng nhận
                </h5>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Người nhận</span>
                    <span class="detail-value-modern font-weight-bold">{{ $order->customer_name ?: '—' }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Số điện thoại</span>
                    <span class="detail-value-modern">
                        @if($order->customer_phone)
                            <a href="tel:{{ $order->customer_phone }}" class="text-decoration-none font-weight-bold">
                                <i class="fas fa-phone mr-1 text-primary"></i>{{ $order->customer_phone }}
                            </a>
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Địa chỉ giao</span>
                    <span class="detail-value-modern">{{ $order->customer_address ?: '—' }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Ghi chú giao hàng</span>
                    <span class="detail-value-modern text-muted font-italic">{{ $order->customer_note ?: 'Không có ghi chú' }}</span>
                </div>
            </div>

            {{-- Payment & Channel Card --}}
            <div class="detail-card-modern">
                <h5 class="detail-card-title">
                    <i class="fas fa-receipt"></i> Thanh toán & Nền tảng
                </h5>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Nền tảng bán</span>
                    <span class="detail-value-modern">
                        @if($order->partner)
                            <span class="badge-status-modern info font-weight-bold">
                                <i class="fas fa-store mr-1"></i> {{ $order->partner->name }}
                            </span>
                        @else
                            <span class="text-muted">Hệ thống nội bộ</span>
                        @endif
                    </span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Hình thức thanh toán</span>
                    <span class="detail-value-modern font-weight-bold">
                        @switch(strtoupper($order->payment_method ?? ''))
                            @case('COD')
                                <span class="badge-status-modern warning"><i class="fas fa-hand-holding-dollar mr-1"></i> COD (Khi nhận hàng)</span>
                                @break
                            @case('VNPAY')
                                <span class="badge-status-modern info"><i class="fas fa-building-columns mr-1"></i> VNPAY</span>
                                @break
                            @case('MOMO')
                                <span class="badge-status-modern danger"><i class="fas fa-wallet mr-1"></i> MoMo</span>
                                @break
                            @case('PAYPAL')
                                <span class="badge-status-modern primary"><i class="fab fa-paypal mr-1"></i> PayPal</span>
                                @break
                            @case('BANK_TRANSFER')
                                <span class="badge-status-modern primary"><i class="fas fa-university mr-1"></i> Chuyển khoản ngân hàng</span>
                                @break
                            @default
                                <span class="badge-status-modern secondary">{{ $order->payment_method ?: 'Chưa xác định' }}</span>
                        @endswitch
                    </span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Trạng thái thanh toán</span>
                    <span class="detail-value-modern">
                        @if($order->is_paid)
                            <span class="badge-status-modern success"><span class="status-dot"></span> Đã thanh toán</span>
                        @else
                            <span class="badge-status-modern warning"><span class="status-dot"></span> Chưa thanh toán</span>
                        @endif
                    </span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Thời gian tạo</span>
                    <span class="detail-value-modern">{{ $order->created_at ? $order->created_at->format('d/m/Y H:i:s') : '—' }}</span>
                </div>
                <div class="detail-row-modern">
                    <span class="detail-label-modern">Cập nhật cuối</span>
                    <span class="detail-value-modern">{{ $order->updated_at ? $order->updated_at->format('d/m/Y H:i:s') : '—' }}</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
