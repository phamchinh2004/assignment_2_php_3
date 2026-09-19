{{ config('app.name') }} - Thông tin đặt lại mật khẩu

Xin chào {{ $user->full_name ?? $user->username }},

Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.
Mật khẩu tạm thời: {{ $newPassword }}

Hãy đăng nhập tại {{ route('login') }} và đổi mật khẩu ngay sau khi đăng nhập.
Nếu bạn không yêu cầu thay đổi này, hãy liên hệ {{ config('mail.from.address') }}.

Đội ngũ hỗ trợ {{ config('app.name') }}
