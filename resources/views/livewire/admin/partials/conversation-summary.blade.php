@php
    $name = data_get($contact, 'full_name', 'Khách hàng');
    $avatar = data_get($contact, 'avatar');
    $lastSeen = data_get($contact, 'last_seen');
    $lastSeen = $lastSeen ? \Carbon\Carbon::parse($lastSeen) : null;
    $minutesAway = $lastSeen ? max(0, (int) $lastSeen->diffInMinutes(now())) : null;
    $online = $minutesAway !== null && $minutesAway < 5;
    $offlineLabel = $minutesAway === null ? 'Offline' : ($minutesAway >= 1440
        ? floor($minutesAway / 1440).' ngày'
        : ($minutesAway >= 60 ? floor($minutesAway / 60).' tiếng' : $minutesAway.' phút'));
    $avatarTone = abs(crc32((string) data_get($contact, 'id'))) % 6;
    $kind = data_get($previewMessage, 'kind') ?: data_get($previewMessage, 'type', 'text');
    $preview = match ($kind) {
        'order_reference' => 'Đơn hàng liên quan',
        'transaction_reference' => 'Giao dịch liên quan',
        'image' => 'Hình ảnh',
        default => trim(data_get($previewMessage, 'message', '') ?? ''),
    };
    $sentAt = data_get($previewMessage, 'created_at');
@endphp
<span class="conversation-avatar avatar-tone-{{ $avatarTone }}">
    @if($avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($avatar))
        <img src="{{ asset('storage/'.$avatar) }}" alt="" loading="lazy">
    @else
        <span aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
    @endif
    @if($online)
        <span class="conversation-presence is-online" role="img" aria-label="Đang hoạt động"></span>
    @else
        <span class="conversation-offline" title="{{ $lastSeen ? 'Hoạt động '.$offlineLabel.' trước' : 'Chưa có thông tin hoạt động' }}">{{ $offlineLabel }}</span>
    @endif
</span>
<span class="conversation-details">
    <span class="conversation-name" title="{{ $name }}">{{ $name }} @if($penalized ?? false)<span class="conversation-penalty" title="Đang bị phạt"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><span class="visually-hidden">Đang bị phạt</span></span>@endif</span>
    <span class="conversation-preview">
        @if($previewMessage)
            @if((int) data_get($previewMessage, 'sender_id') === (int) auth()->id())Bạn: @endif{{ $preview }}
        @else
            Chưa có tin nhắn
        @endif
    </span>
    @if($awaitingReply ?? false)<span class="conversation-awaiting">Chờ phản hồi</span>@endif
    
</span>
<span class="conversation-trailing">
    @if($sentAt)
        <time datetime="{{ \Carbon\Carbon::parse($sentAt)->toIso8601String() }}" title="{{ \Carbon\Carbon::parse($sentAt)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}">{{ \Carbon\Carbon::parse($sentAt)->setTimezone('Asia/Ho_Chi_Minh')->format('H:i') }}</time>
    @endif
    @if($unread > 0)<span class="conversation-unread" aria-label="{{ $unread }} tin nhắn bạn chưa đọc">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
</span>
