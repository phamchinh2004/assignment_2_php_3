<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Tin nhắn mới từ {{ $user->full_name ?? $user->username }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            line-height: 1.6;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            color: rgba(255, 255, 255, 0.9);
            margin: 10px 0 0 0;
            font-size: 16px;
        }
        .icon-box {
            background: rgba(255, 255, 255, 0.2);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 40px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .user-info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .user-info h3 {
            color: #1f2937;
            font-size: 16px;
            margin: 0 0 10px 0;
            font-weight: 600;
        }
        .user-info p {
            color: #4b5563;
            margin: 5px 0;
            font-size: 14px;
        }
        .message-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .message-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .message-content {
            color: #1f2937;
            font-size: 15px;
            line-height: 1.6;
            word-wrap: break-word;
        }
        .cta-button {
            display: inline-block;
            background: #3b82f6;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            margin: 20px 0;
        }
        .info-box {
            background: #fef3c7;
            border: 1px solid #fde68a;
            padding: 16px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .info-box p {
            color: #92400e;
            margin: 5px 0;
            font-size: 13px;
        }
        .footer {
            background: #1f2937;
            color: #9ca3af;
            padding: 30px 20px;
            text-align: center;
            font-size: 13px;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 30px 0;
        }
        .timestamp {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="icon-box">
                💬
            </div>
            <h1>Tin nhắn mới</h1>
            <p>Bạn có tin nhắn chưa đọc từ khách hàng</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Xin chào,
            </div>

            <div class="user-info">
                <h3>👤 Thông tin khách hàng</h3>
                <p><strong>Tên:</strong> {{ $user->full_name ?? $user->username }}</p>
                <p><strong>Username:</strong> {{ $user->username }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                @if($user->phone)
                <p><strong>SĐT:</strong> {{ $user->phone }}</p>
                @endif
            </div>

            <!-- Message -->
            <div class="message-box">
                <div class="message-label">📩 Nội dung tin nhắn</div>
                <div class="message-content">
                    {{ $userMessage }}
                </div>
                <div class="timestamp">
                    Gửi lúc: {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>

            <div class="divider"></div>

            <!-- CTA Button -->
            <div style="text-align: center;">
                <a href="{{ url('/admin/chat-panel') }}" class="cta-button">
                    💬 Trả lời ngay
                </a>
            </div>

            <!-- Warning Box -->
            <div class="info-box">
                <p><strong>⚠️ Lưu ý:</strong></p>
                <p>• Email này được gửi vì bạn đang offline</p>
                <p>• Khách hàng đang chờ phản hồi từ bạn</p>
                <p>• Vui lòng trả lời sớm để nâng cao trải nghiệm khách hàng</p>
            </div>

            <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
                Email này được gửi tự động từ hệ thống chat. Vui lòng không trả lời email này.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>AmazonLogistics</strong></p>
            <p>Hệ thống phân phối đơn hàng tự động</p>
            <p style="margin-top: 15px;">
                Website: <a href="{{ url('/') }}">{{ url('/') }}</a>
            </p>
            <p>
                Email hỗ trợ: <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
            </p>
            <p style="margin-top: 15px; color: #6b7280; font-size: 12px;">
                © {{ date('Y') }} AmazonLogistics. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

