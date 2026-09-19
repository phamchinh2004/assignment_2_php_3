<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Mật khẩu mới của bạn</title>
</head>

<body>
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">Thông tin đặt lại mật khẩu cho tài khoản {{ config('app.name') }}.</div>
    <h2>Xin chào {{ $user->full_name ?? $user->username }},</h2>

    <p>Chúng tôi đã cấp lại mật khẩu cho bạn theo yêu cầu.</p>

    <p>Mật khẩu tạm thời của bạn: <strong>{{ $newPassword }}</strong></p>

    <p>Vui lòng đăng nhập vào hệ thống và đổi mật khẩu ngay sau khi đăng nhập để đảm bảo an toàn tài khoản.</p>

    <p>Nếu bạn không yêu cầu thay đổi này, vui lòng liên hệ <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>

    <p>Trân trọng,</p>
    <p><em>Đội ngũ hỗ trợ {{ config('app.name') }}</em></p>
</body>

</html>
