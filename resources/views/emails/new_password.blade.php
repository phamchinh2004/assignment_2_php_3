<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Mật khẩu mới của bạn</title>
</head>

<body>
    <h2>Xin chào {{ $user->full_name ?? $user->username }}!</h2>

    <p>Chúng tôi đã cấp lại mật khẩu cho bạn theo yêu cầu.</p>

    <p><strong>Mật khẩu mới của bạn là: <span style="color: #e74c3c;">{{ $newPassword }}</span></strong></p>

    <p>Vui lòng đăng nhập vào hệ thống và đổi mật khẩu ngay sau khi đăng nhập để đảm bảo an toàn tài khoản.</p>

    <p>Trân trọng,</p>
    <p><em>Đội ngũ hỗ trợ {{ config('app.name') }}</em></p>
</body>

</html>