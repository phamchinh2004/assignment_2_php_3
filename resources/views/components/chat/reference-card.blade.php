@props(['message', 'audience' => 'user'])

@php
    $kind = $message['kind'] ?? $message['type'] ?? 'text';
    $payload = $message['reference_payload'] ?? [];
    $isOrder = $kind === 'order_reference';
    $statusLabels = [
        'pending' => 'Chờ xử lý',
        'confirmed' => 'Đã xác nhận',
        'preparing' => 'Đang chuẩn bị',
        'transit' => 'Đang trung chuyển',
        'shipping' => 'Đang giao',
        'delivered' => 'Đã giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã huỷ',
        'processing' => 'Đang xử lý',
        'recorded' => 'Đã ghi nhận',
    ];
    $typeLabels = [
        'deposit' => 'Nạp tiền',
        'withdraw' => 'Rút tiền',
        'order' => 'Thanh toán đơn',
        'profit' => 'Hoa hồng',
        'penalty' => 'Tiền phạt',
    ];
    $status = $payload['status'] ?? null;
    $statusClass = $status ? preg_replace('/[^a-z0-9_-]+/', '-', strtolower((string) $status)) : null;
    $createdAt = !empty($payload['created_at'])
        ? \Carbon\Carbon::parse($payload['created_at'])->setTimezone('Asia/Ho_Chi_Minh')
        : null;
    $referenceCode = $isOrder
        ? ($payload['code'] ?? '#' . ($payload['id'] ?? ''))
        : strtoupper($payload['source'] ?? 'TX') . '-' . ($payload['id'] ?? '');
    $transactionLabel = $typeLabels[$payload['type'] ?? ''] ?? ($payload['type'] ?? 'Giao dịch');
    $link = null;

    if ($isOrder && isset($payload['id'])) {
        if ($audience === 'user') {
            $link = route('order.show', $payload['id']);
        } elseif (auth()->user() && app(\App\Services\AuthorizationService::class)->can(
            auth()->user(),
            config('authorization.capabilities.order_distributions')
        )) {
            $link = route('order_distributions.show', $payload['id']);
        }
    }
@endphp

<article class="chat-reference-card {{ $isOrder ? 'is-order' : 'is-transaction' }}">
    <header class="chat-reference-header">
        <div class="chat-reference-heading">
            <span class="chat-reference-icon" aria-hidden="true">
                <i class="fas {{ $isOrder ? 'fa-box-open' : 'fa-receipt' }}"></i>
            </span>
            <span>{{ $isOrder ? 'Đơn hàng liên quan' : 'Giao dịch liên quan' }}</span>
        </div>

        @if($status)
            <span class="chat-reference-status status-{{ $statusClass }}">{{ $statusLabels[$status] ?? $status }}</span>
        @endif
    </header>

    <section class="chat-reference-identity">
        <span class="chat-reference-eyebrow">{{ $isOrder ? 'Mã đơn hàng' : $transactionLabel }}</span>
        <div class="chat-reference-code-row">
            <strong class="chat-reference-code">{{ $referenceCode }}</strong>

            @if(!$isOrder && array_key_exists('amount', $payload) && $payload['amount'] !== null)
                <strong class="chat-reference-transaction-amount">{{ format_money((float) $payload['amount'], 5) }}$</strong>
            @endif
        </div>

        @if($createdAt)
            <time datetime="{{ $createdAt->toIso8601String() }}">
                {{ $isOrder ? 'Đặt lúc' : 'Ghi nhận lúc' }} {{ $createdAt->format('d/m/Y · H:i') }}
            </time>
        @endif
    </section>

    @if($isOrder)
        <section class="chat-reference-product">
            <div class="chat-reference-media">
                @if(!empty($payload['image_path']))
                    <img
                        src="{{ Storage::disk('public')->url($payload['image_path']) }}"
                        alt="{{ $payload['name'] ?? 'Sản phẩm' }}"
                        class="chat-reference-image"
                    >
                @else
                    <span class="chat-reference-image-placeholder" aria-hidden="true"><i class="fas fa-box"></i></span>
                @endif
            </div>

            <div class="chat-reference-product-copy">
                <strong class="chat-reference-product-name">{{ $payload['name'] ?: 'Sản phẩm trong đơn hàng' }}</strong>
                <div class="chat-reference-product-meta">
                    @if(array_key_exists('quantity', $payload) && $payload['quantity'] !== null)
                        <span>SL {{ $payload['quantity'] }}</span>
                    @endif
                    @if(array_key_exists('unit_price', $payload) && $payload['unit_price'] !== null)
                        <span>{{ format_money((float) $payload['unit_price'], 5) }}$/sp</span>
                    @endif
                    @if(!empty($payload['partner_name']))
                        <span>{{ $payload['partner_name'] }}</span>
                    @endif
                </div>
            </div>
        </section>

        @if(
            (array_key_exists('amount', $payload) && $payload['amount'] !== null)
            || (array_key_exists('commission_amount', $payload) && $payload['commission_amount'] !== null)
            || (array_key_exists('refund_amount', $payload) && $payload['refund_amount'] !== null)
            || (array_key_exists('penalty_amount', $payload) && $payload['penalty_amount'] !== null && (float) $payload['penalty_amount'] != 0.0)
        )
            <section class="chat-reference-finance" aria-label="Tóm tắt tài chính">
                @if(array_key_exists('amount', $payload) && $payload['amount'] !== null)
                    <div class="chat-reference-finance-row is-primary">
                        <span>Tổng tiền đơn hàng</span>
                        <strong>{{ format_money((float) $payload['amount'], 5) }}$</strong>
                    </div>
                @endif

                @if(array_key_exists('commission_amount', $payload) && $payload['commission_amount'] !== null)
                    <div class="chat-reference-finance-row">
                        <span>
                            Hoa hồng
                            @if(array_key_exists('commission_percentage', $payload) && $payload['commission_percentage'] !== null)
                                ({{ format_money((float) $payload['commission_percentage']) }}%)
                            @endif
                        </span>
                        <strong>{{ format_money((float) $payload['commission_amount'], 5) }}$</strong>
                    </div>
                @endif

                @if(array_key_exists('refund_amount', $payload) && $payload['refund_amount'] !== null)
                    <div class="chat-reference-finance-row is-positive">
                        <span>Đã hoàn nhập</span>
                        <strong>{{ format_money((float) $payload['refund_amount'], 5) }}$</strong>
                    </div>
                @endif

                @if(array_key_exists('penalty_amount', $payload) && $payload['penalty_amount'] !== null && (float) $payload['penalty_amount'] != 0.0)
                    <div class="chat-reference-finance-row is-danger">
                        <span>Tiền phạt</span>
                        <strong>{{ format_money((float) $payload['penalty_amount'], 5) }}$</strong>
                    </div>
                @endif
            </section>
        @endif
    @elseif(!empty($payload['detail']) || !empty($payload['note']))
        <section class="chat-reference-transaction-detail">
            @if(!empty($payload['detail']))
                <span class="chat-reference-detail-label">Loại giao dịch</span>
                <strong>{{ $payload['detail'] }}</strong>
            @endif

            @if(!empty($payload['note']))
                <p>{{ $payload['note'] }}</p>
            @endif
        </section>
    @endif

    @if($link)
        <a href="{{ $link }}" class="chat-reference-link" target="_blank" rel="noopener">
            <span>Xem chi tiết đơn hàng</span>
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    @endif
</article>
