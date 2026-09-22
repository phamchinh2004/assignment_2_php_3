<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cuộc trò chuyện đang chờ phản hồi</title>
</head>
<body>
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">Cuộc trò chuyện #{{ $conversationId }} đang chờ nhân viên hỗ trợ.</div>
    <h2>Cuộc trò chuyện đang chờ phản hồi</h2>
    @if ($afterMinutes > 0)
        <p>Cuộc trò chuyện của khách hàng chưa nhận được phản hồi sau {{ $afterMinutes }} phút.</p>
    @else
        <p>Quản lý được phân công đang offline khi khách hàng gửi tin nhắn.</p>
    @endif
    <p><strong>Tin nhắn tự động gần nhất:</strong> {{ $autoReplyMessage }}</p>
    <p><strong>Mã cuộc trò chuyện:</strong> {{ $conversationId }}</p>
    <p><strong>Mã người dùng:</strong> {{ $userId }}</p>
    <p><a href="{{ url('/admin/chat-panel') }}">Mở trang quản lý hỗ trợ</a></p>
    <p style="color:#6b7280;font-size:12px;">Thông báo nghiệp vụ dành cho nhân viên hỗ trợ của {{ config('app.name') }}.</p>
</body>
</html>
