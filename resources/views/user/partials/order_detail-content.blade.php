@php
        $isHighValueOrder = $frozen_order->custom_price !== null;
        $status = $frozen_order->status ?: 'pending';
        $statusLabels = ['pending' => 'Chờ xử lý', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chuẩn bị', 'transit' => 'Đang trung chuyển', 'shipping' => 'Đang vận chuyển', 'delivered' => 'Đã giao hàng', 'completed' => 'Đã hoàn thành', 'cancelled' => 'Đã huỷ', 'canceled' => 'Đã huỷ'];
        $money = fn($value, $precision = 2) => $value === null ? '—' : format_money($value, $precision) . '$';
        $walletLabels = ['balance' => 'Số dư khả dụng', 'frozen_balance' => 'Số dư đóng băng'];
        $cancellationReason = $cancellation?->notes ?: $frozen_order->orderReport?->resolved_note ?: $frozen_order->orderReport?->reason;
        $cancellationActor = $cancellation?->changedBy?->full_name ?? $cancellation?->changedBy?->username ?? $frozen_order->orderReport?->resolver?->full_name;
        $typeLabels = ['order' => 'Trừ tiền đơn', 'profit' => 'Hoa hồng', 'penalty' => 'Tiền phạt', 'deposit' => 'Nạp tiền', 'withdraw' => 'Rút tiền'];
    @endphp
    <main class="od-page {{ $isHighValueOrder ? 'is-hvo' : '' }}">

        @if($isHighValueOrder)
            <section class="od-hvo-hero" aria-label="Đơn hàng giá trị cao">
                <div class="od-hvo-emblem"><i class="fas fa-gem"></i></div>
                <div class="od-hvo-copy">
                    <div class="od-hvo-kicker">High-Value Order</div>
                    <h2 class="od-hvo-title">Đơn hàng giá trị cao</h2>
                    <p class="od-hvo-note">Đơn hàng HVO được đánh dấu riêng để bạn dễ nhận biết và ưu tiên xử lý.</p>
                </div>
                <div class="od-hvo-seal"><i class="fas fa-crown"></i><span>HVO</span></div>
            </section>
        @endif
        <a class="od-back" href="{{ route('order') }}"><i class="fas fa-arrow-left"></i> Danh sách đơn hàng</a>
        @if($status === 'pending')
            <section class="od-card">
                <div class="od-actions"><button type="button" class="btn-confirm" id="btn_confirm_order"><i
                            class="fas fa-check me-1"></i> Xác nhận đơn
                        hàng</button>@if($frozen_order->custom_price !== null)<button type="button" class="btn-contact-cskh"
                        id="btn_contact_cskh"><i class="fas fa-headset me-1"></i> Liên hệ CSKH</button>@else<button
                            type="button" class="btn-report" id="btn_report_fake_order" @disabled($frozen_order->orderReport)><i
                                class="fas fa-flag me-1"></i>
                        {{ $frozen_order->orderReport ? 'Đã báo cáo - chờ xử lý' : 'Báo cáo đơn hàng' }}</button>@endif</div>
        </section>@endif

        <section class="od-card">
            <div class="od-header">
                <div>
                    <div class="od-eyebrow">Chi tiết đơn hàng</div>
                    <h1 class="od-title">{{ $frozen_order->display_order_code ?? '#' . $frozen_order->id }}</h1>
                    <div class="od-subtitle">Nhận lúc
                        {{ optional($frozen_order->order_date ?? $frozen_order->created_at)->format('d/m/Y H:i') }}</div>
                </div>
                <div class="od-badges">@if($financial['has_penalty'])<span class="od-badge penalty"><i
                class="fas fa-triangle-exclamation"></i> Có tiền phạt</span>@endif<span
                        class="od-badge {{ $status }}"><i class="fas fa-circle"></i>
                        {{ $currentStatus?->display_name ?? ($statusLabels[$status] ?? $status) }}</span></div>
            </div>
            <div class="od-metrics">
                <div class="od-metric">
                    <div class="od-metric-label">Giá trị lúc nhận</div>
                    <div class="od-metric-value">{{ $money($financial['order_amount']) }}</div>
                </div>
                <div class="od-metric">
                    <div class="od-metric-label">Đã trừ</div>
                    <div class="od-metric-value red">{{ $money($financial['deducted_amount']) }}</div>
                </div>
                <div class="od-metric">
                    <div class="od-metric-label">Hoa hồng thực nhận</div>
                    <div class="od-metric-value blue">{{ $money($financial['commission_paid'], 5) }}</div>
                </div>
                <div class="od-metric">
                    <div class="od-metric-label">Tiền phạt</div>
                    <div class="od-metric-value red">
                        {{ $money($financial['penalty_paid'] ?? ($financial['has_penalty'] ? $frozen_order->penalty_amount : 0)) }}
                    </div>
                </div>
                <div class="od-metric">
                    <div class="od-metric-label">Hoàn nhập / quyết toán</div>
                    <div class="od-metric-value green">{{ $money($financial['refund_amount'], 5) }}</div>
                </div>
            </div>
        </section>
        <div class="od-layout">
            <div>
                <section class="od-card">
                    <div class="od-section-head"><i class="fas fa-box"></i> Sản phẩm</div>
                    <div class="od-body od-product">@if($frozen_order->display_image)<img
                        src="{{ Storage::url($frozen_order->display_image) }}"
                    alt="{{ $frozen_order->display_name }}">@else<div></div>@endif<div>
                            <div class="od-product-name">{{ $frozen_order->display_name ?? 'Không có tên sản phẩm' }}</div>
                            <dl class="od-info-grid">
                                <div class="od-field">
                                    <dt>Đơn giá</dt>
                                    <dd>{{ $money($frozen_order->display_unit_price) }}</dd>
                                </div>
                                <div class="od-field">
                                    <dt>Số lượng</dt>
                                    <dd>{{ $frozen_order->display_quantity ?? '—' }}</dd>
                                </div>
                                <div class="od-field">
                                    <dt>Nền tảng</dt>
                                    <dd>{{ $frozen_order->display_partner_name ?? '—' }}</dd>
                                </div>
                                <div class="od-field">
                                    <dt>Thanh toán</dt>
                                    <dd>{{ $frozen_order->display_payment_method ?? '—' }} ·
                                        {{ $frozen_order->display_is_paid ? 'Đã thanh toán' : 'Chưa thanh toán' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </section>
                <section class="od-card">
                    <div class="od-section-head"><i class="fas fa-coins"></i> Tài chính và quyết toán</div>
                    <div class="od-body">
                        <div class="od-finance-row"><span>Giá trị đơn lúc
                                nhận</span><strong>{{ $money($financial['order_amount']) }}</strong></div>
                        <div class="od-finance-row"><span>Số tiền đã trừ trước
                                đó</span><strong>{{ $money($financial['deducted_amount']) }}</strong></div>
                        <div class="od-finance-row"><span>Hoa hồng dự kiến
                                ({{ format_money($frozen_order->display_commission_percentage ?? 0) }}%)</span><strong>{{ $money($financial['expected_commission'], 5) }}</strong>
                        </div>
                        <div class="od-finance-row"><span>Hoa hồng đã
                                nhận</span><strong>{{ $money($financial['commission_paid'], 5) }}</strong></div>
                        @if($financial['is_cancelled'])
                            <div class="od-finance-row"><span>Hoa hồng sau
                                    huỷ</span><strong>{{ $financial['commission_paid'] ? 'Đã ghi nhận ' . $money($financial['commission_paid'], 5) : 'Đã huỷ / chưa phát sinh' }}</strong>
                        </div>@endif
                        @if($financial['has_penalty'])
                            <div class="od-finance-row"><span>Tiền phạt ghi nhận</span><strong
                                    class="red">{{ $money($financial['penalty_paid'] ?? $frozen_order->penalty_amount) }}</strong>
                        </div>@endif
                        <div class="od-finance-row"><span>Ví nhận
                                tiền</span><strong>{{ $walletLabels[$financial['balance_destination']] ?? 'Không có dữ liệu' }}</strong>
                        </div>
                        <div class="od-finance-row"><span>Trạng thái quyết toán</span><strong><span
                                    class="od-badge {{ $financial['settlement_state'] }}">{{ $financial['settlement_label'] }}</span></strong>
                        </div>@if($financial['settled_at'])
                            <div class="od-finance-row"><span>Thời điểm quyết
                                    toán</span><strong>{{ \Carbon\Carbon::parse($financial['settled_at'])->format('d/m/Y H:i:s') }}</strong>
                        </div>@endif
                        @if($financial['refund_amount'] !== null)
                            <div class="od-highlight">
                                <span>{{ $financial['is_cancelled'] ? 'Số tiền hoàn nhập sau huỷ' : 'Số tiền quyết toán thực tế' }}</span><strong>{{ $money($financial['refund_amount'], 5) }}</strong>
                        </div>@endif
                    </div>@if($financial['settlement_reason'] && $financial['settlement_state'] === 'needs_review')
                    <div class="od-alert">{{ $financial['settlement_reason'] }}</div>@endif
                </section>
                @if($financial['is_cancelled'])
                    <section class="od-card">
                        <div class="od-section-head"><i class="fas fa-ban"></i> Thông tin huỷ đơn</div>
                        <div class="od-body">
                            <dl class="od-info-grid">
                                <div class="od-field">
                                    <dt>Trạng thái</dt>
                                    <dd><span class="od-badge cancelled">Đã huỷ</span></dd>
                                </div>
                                <div class="od-field">
                                    <dt>Thời điểm huỷ</dt>
                                    <dd>{{ optional($frozen_order->cancelled_at ?? $cancellation?->created_at)->format('d/m/Y H:i:s') ?? 'Không có dữ liệu' }}
                                    </dd>
                                </div>
                                <div class="od-field">
                                    <dt>Thực hiện bởi</dt>
                                    <dd>{{ $cancellationActor ?: 'Hệ thống / không có dữ liệu' }}</dd>
                                </div>
                                <div class="od-field">
                                    <dt>Nguồn xử lý</dt>
                                    <dd>{{ $frozen_order->orderReport?->status === 'approved' ? 'Admin duyệt báo cáo đơn hàng' : 'Huỷ trực tiếp' }}
                                    </dd>
                                </div>
                                <div class="od-field od-wide">
                                    <dt>Lý do huỷ</dt>
                                    <dd>{{ $cancellationReason ?: 'Không ghi nhận lý do' }}</dd>
                                </div>
                            </dl>
                        </div>@if($financial['deducted_amount'] && $financial['refund_amount'] === null)
                            <div class="od-alert danger">Đơn đã có giao dịch trừ tiền nhưng không có settlement snapshot hoặc giao
                        dịch hoàn nhập để xác nhận số tiền hoàn. Cần đối soát.</div>@endif
                </section>@endif
                @if($financial['transactions']->isNotEmpty())
                    <section class="od-card">
                        <div class="od-section-head"><i class="fas fa-receipt"></i> Giao dịch liên quan</div>
                        <div class="od-body">
                            <table class="od-transactions">
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Loại</th>
                                        <th>Số tiền</th>
                                        <th>Tham chiếu</th>
                                    </tr>
                                </thead>
                                <tbody>@foreach($financial['transactions'] as $transaction)
                                    <tr>
                                        <td>{{ optional($transaction->created_at)->format('d/m/Y H:i:s') }}</td>
                                        <td class="od-type {{ $transaction->type }}">
                                            {{ $typeLabels[$transaction->type] ?? $transaction->type }}</td>
                                        <td>{{ $money($transaction->value, 5) }}</td>
                                        <td>{{ $transaction->note ?: '—' }}</td>
                                </tr>@endforeach
                                </tbody>
                            </table>
                        </div>
                </section>@endif
            </div>
            <aside>
                <section class="od-card">
                    <div class="od-section-head"><i class="fas fa-location-dot"></i> Người nhận</div>
                    <div class="od-body">
                        <dl class="od-info-grid">
                            <div class="od-field od-wide">
                                <dt>Họ tên</dt>
                                <dd>{{ $frozen_order->display_customer_name ?? '—' }}</dd>
                            </div>
                            <div class="od-field od-wide">
                                <dt>Số điện thoại</dt>
                                <dd>{{ $frozen_order->display_customer_phone ?? '—' }}</dd>
                            </div>
                            <div class="od-field od-wide">
                                <dt>Địa chỉ</dt>
                                <dd>{{ $frozen_order->display_customer_address ?? '—' }}</dd>
                            </div>@if($frozen_order->display_customer_note)
                                <div class="od-field od-wide">
                                    <dt>Ghi chú</dt>
                                    <dd>{{ $frozen_order->display_customer_note }}</dd>
                            </div>@endif
                        </dl>
                    </div>
                </section>
                <section class="od-card">
                    <div class="od-section-head"><i class="fas fa-clock-rotate-left"></i> Lịch sử xử lý</div>
                    <div class="od-body">@forelse($statusHistory as $event)
                        <div class="od-event {{ $event->status?->name === $status ? 'current' : '' }}"><span
                                class="od-dot"></span>
                            <div class="od-event-title">
                                {{ $event->status?->display_name ?? ($statusLabels[$event->status?->name] ?? 'Cập nhật trạng thái') }}
                            </div>
                            <div class="od-event-meta">{{ optional($event->created_at)->format('d/m/Y H:i:s') }} ·
                                {{ $event->changedBy?->full_name ?? $event->changedBy?->username ?? 'Hệ thống' }}</div>
                            @if($event->notes)
                            <div class="od-event-note">{{ $event->notes }}</div>@endif
                    </div>@empty<div class="od-empty">Chưa có lịch sử trạng thái.</div>@endforelse
                    </div>
                </section>
                @if($frozen_order->tracking_number || $frozen_order->shipping_carrier)
                    <section class="od-card">
                        <div class="od-section-head"><i class="fas fa-truck"></i> Vận chuyển</div>
                        <div class="od-body">
                            <dl class="od-info-grid">
                                <div class="od-field od-wide">
                                    <dt>Mã vận đơn</dt>
                                    <dd>{{ $frozen_order->tracking_number ?? '—' }}</dd>
                                </div>
                                <div class="od-field od-wide">
                                    <dt>Đơn vị vận chuyển</dt>
                                    <dd>{{ $frozen_order->shipping_carrier ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                </section>@endif
                @if($frozen_order->display_api)
                    <section class="od-card">
                        <div class="od-section-head"><i class="fas fa-code"></i> API theo dõi</div>
                        <div class="od-body">
                            <div style="display:flex;gap:8px;align-items:center"><code
                                    style="word-break:break-all;flex:1">{{ $frozen_order->display_api }}</code><button
                                    class="btn-copy-api" type="button" data-api="{{ $frozen_order->display_api }}"><i
                                        class="fas fa-copy"></i></button></div>@if($apiUrl)<a href="{{ $apiUrl }}"
                                        target="_blank" rel="noopener" style="font-size:12px">Mở API theo dõi</a>@endif
                        </div>
                </section>@endif
            </aside>
        </div>
    </main>
