<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cảnh báo chat chưa có phản hồi</title>
</head>
<body>
    <h2>Cảnh báo chat chưa được phản hồi</h2>
    <p>Khách hàng đã nhắn tin nhưng sau {{ $afterMinutes }} phút vẫn chưa có phản hồi từ admin/staff.</p>
    <p><strong>Tin nhắn tự động gần nhất:</strong> {{ $autoReplyMessage }}</p>
    <p><strong>Conversation ID:</strong> {{ $conversationId }}</p>
    <p><strong>User ID:</strong> {{ $userId }}</p>
    <p>Vui lòng kiểm tra và trả lời khách hàng sớm.</p>
</body>
</html>
