<section class="chat-shared-images">
    <h4>Hình ảnh đã gửi <span class="chat-context-count">{{ count($context['images']) }}</span></h4>
    <div class="chat-image-grid">
        @forelse($context['images'] as $image)
            <button type="button" class="chat-shared-image" onclick="this.querySelector('img').click()" aria-label="Xem ảnh đã gửi">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image['image_path']) }}" alt="Ảnh đã gửi" class="zoomable-image" onclick="event.stopPropagation()" loading="lazy" decoding="async">
            </button>
        @empty
            <p class="chat-context-empty">Chưa có hình ảnh.</p>
        @endforelse
    </div>
    @if(count($context['images']) === 24)<p class="chat-details-hint">24 hình ảnh gần nhất</p>@endif
</section>
@if($context['can_view_context'])
    <section class="chat-context-orders">
        <h4>Đơn hàng liên quan</h4>
        @forelse($context['orders'] as $order)
            <details class="chat-context-record">
                <summary><span>{{ $order['code'] }}</span><strong>{{ $order['amount'] === null ? 'Chưa rõ giá trị' : format_money($order['amount'], 2).' $' }}</strong></summary>
                <x-chat.reference-card :message="['kind' => 'order_reference', 'reference_payload' => $order]" audience="admin" />
            </details>
        @empty
            <p class="chat-context-empty">Chưa có đơn hàng.</p>
        @endforelse
    </section>
    <section class="chat-context-transactions">
        <h4>Giao dịch gần đây</h4>
        @forelse($context['transactions'] as $transaction)
            <details class="chat-context-record">
                <summary><span>{{ ['deposit' => 'Nạp tiền', 'withdraw' => 'Rút tiền', 'order' => 'Thanh toán đơn', 'profit' => 'Hoa hồng', 'penalty' => 'Tiền phạt'][$transaction['type']] ?? $transaction['type'] }}</span><strong>{{ format_money($transaction['amount'], 2) }} $</strong></summary>
                <x-chat.reference-card :message="['kind' => 'transaction_reference', 'reference_payload' => $transaction]" audience="admin" />
            </details>
        @empty
            <p class="chat-context-empty">Chưa có giao dịch.</p>
        @endforelse
    </section>
@endif
