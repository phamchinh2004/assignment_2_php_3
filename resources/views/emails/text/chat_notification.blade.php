{{ config('app.name') }} - Tin nhắn mới

Bạn có tin nhắn mới trong cuộc trò chuyện #{{ $conversationId }}.

Khách hàng: {{ $user->full_name ?? $user->username }}
Tên đăng nhập: {{ $user->username }}
Nội dung: {{ $userMessage }}

Mở trang trò chuyện: {{ url('/admin/chat-panel') }}

Đây là thông báo nghiệp vụ được gửi đến nhân viên hỗ trợ của {{ config('app.name') }}.
