<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $transactionType === 'normal' ? 'Thông báo nạp tiền' : 'Thông báo nhận tiền thưởng' }}</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .message-box {
            background: #f9fafb;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .message-box.bonus {
            border-left-color: #f59e0b;
        }
        .message-box h2 {
            color: #1f2937;
            font-size: 16px;
            margin: 0 0 10px 0;
            font-weight: 600;
        }
        .message-box p {
            color: #4b5563;
            margin: 5px 0;
            font-size: 14px;
        }
        .amount-display {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 8px;
            margin: 20px 0;
        }
        .amount-display.bonus {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }
        .amount-label {
            font-size: 14px;
            color: #065f46;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .amount-display.bonus .amount-label {
            color: #92400e;
        }
        .amount-value {
            font-size: 36px;
            font-weight: 700;
            color: #059669;
            margin: 0;
        }
        .amount-display.bonus .amount-value {
            color: #d97706;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table td {
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        .info-table td:first-child {
            color: #6b7280;
            width: 40%;
        }
        .info-table td:last-child {
            color: #1f2937;
            font-weight: 600;
            text-align: right;
        }
        .info-table tr:last-child td {
            border-bottom: none;
            padding-top: 20px;
            font-size: 16px;
        }
        .cta-button {
            display: inline-block;
            background: #10b981;
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            margin: 20px 0;
        }
        .cta-button.bonus {
            background: #f59e0b;
        }
        .note-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 16px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .note-box p {
            color: #1e40af;
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
            color: #10b981;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="icon-box">
                {{ $transactionType === 'normal' ? '💰' : '🎁' }}
            </div>
            <h1>{{ $transactionType === 'normal' ? 'Nạp tiền thành công' : 'Nhận tiền thưởng' }}</h1>
            <p>Tài khoản của bạn đã được cập nhật</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Xin chào <strong>{{ $user->full_name ?? $user->username }}</strong>,
            </div>

            <div class="message-box {{ $transactionType === 'bonus' ? 'bonus' : '' }}">
                <h2>📩 Thông báo giao dịch</h2>
                <p>
                    @if($transactionType === 'normal')
                        Tài khoản của bạn đã được nạp <strong>${{ number_format($amount, 2) }}</strong> bởi hệ thống.
                    @else
                        Bạn đã nhận <strong>${{ number_format($amount, 2) }}</strong> tiền thưởng từ hệ thống.
                    @endif
                </p>
                <p>Giao dịch đã được xử lý thành công và số dư của bạn đã được cập nhật.</p>
            </div>

            <!-- Amount Display -->
            <div class="amount-display {{ $transactionType === 'bonus' ? 'bonus' : '' }}">
                <div class="amount-label">Số tiền {{ $transactionType === 'normal' ? 'nạp' : 'thưởng' }}</div>
                <div class="amount-value">+${{ number_format($amount, 2) }}</div>
            </div>

            <!-- Transaction Info -->
            <table class="info-table">
                <tr>
                    <td>Loại giao dịch:</td>
                    <td>{{ $transactionType === 'normal' ? 'Nạp tiền' : 'Tiền thưởng' }}</td>
                </tr>
                <tr>
                    <td>Số tiền:</td>
                    <td>${{ number_format($amount, 2) }}</td>
                </tr>
                <tr>
                    <td>Được xử lý bởi:</td>
                    <td>{{ $adminName }}</td>
                </tr>
                <tr>
                    <td>Thời gian:</td>
                    <td>{{ date('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <td><strong>Số dư mới:</strong></td>
                    <td style="color: #10b981;"><strong>${{ number_format($newBalance, 2) }}</strong></td>
                </tr>
            </table>

            <div class="divider"></div>

            <!-- CTA Button -->
            <div style="text-align: center;">
                <a href="{{ url('/') }}" class="cta-button {{ $transactionType === 'bonus' ? 'bonus' : '' }}">
                    Xem tài khoản của tôi
                </a>
            </div>

            <!-- Note -->
            <div class="note-box">
                <p><strong>📌 Lưu ý:</strong></p>
                <p>• Số dư đã được cập nhật vào tài khoản của bạn</p>
                <p>• Bạn có thể sử dụng số dư này để phân phối đơn hàng</p>
                <p>• Nếu có thắc mắc, vui lòng liên hệ bộ phận hỗ trợ</p>
            </div>

            <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
                Email này được gửi tự động từ hệ thống. Vui lòng không trả lời email này.
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

