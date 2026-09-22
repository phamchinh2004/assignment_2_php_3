{{ config('app.name') }} - Cuộc trò chuyện đang chờ phản hồi

@if ($afterMinutes > 0)
Cuộc trò chuyện #{{ $conversationId }} của người dùng #{{ $userId }} chưa nhận được phản hồi sau {{ $afterMinutes }} phút.
@else
Quản lý được phân công đang offline khi người dùng #{{ $userId }} gửi tin nhắn trong cuộc trò chuyện #{{ $conversationId }}.
@endif

Tin nhắn tự động gần nhất:
{{ $autoReplyMessage }}

Mở trang quản lý hỗ trợ: {{ url('/admin/chat-panel') }}

Đây là thông báo nghiệp vụ dành cho nhân viên hỗ trợ của {{ config('app.name') }}.
