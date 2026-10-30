<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Cảnh báo cuộc trò chuyện chờ phản hồi</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            line-height: 1.6;
            color: #1f2937;
        }
        .email-container {
            max-width: 620px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }
        .header {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header-icon {
            font-size: 42px;
            margin-bottom: 8px;
            display: inline-block;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 8px 0 0 0;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
        }
        .content {
            padding: 30px 25px;
        }
        .alert-badge {
            display: inline-block;
            background-color: #fee2e2;
            color: #991b1b;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }
        .alert-badge.offline {
            background-color: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }
        .section-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 18px;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            border-bottom: 1px dashed #e5e7eb;
            padding-bottom: 8px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 6px 0;
            font-size: 14px;
            vertical-align: top;
        }
        .info-label {
            color: #6b7280;
            width: 38%;
            font-weight: 500;
        }
        .info-value {
            color: #111827;
            font-weight: 600;
        }
        .messages-container {
            margin-top: 10px;
        }
        .message-item {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #ea580c;
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 10px;
        }
        .message-text {
            color: #1f2937;
            font-size: 14px;
            line-height: 1.5;
            word-break: break-word;
        }
        .message-time {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 6px;
            text-align: right;
        }
        .auto-reply-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 22px;
        }
        .auto-reply-box p {
            margin: 0;
            font-size: 13px;
            color: #166534;
        }
        .cta-container {
            text-align: center;
            margin: 25px 0 15px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 34px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 6px rgba(234, 88, 12, 0.25);
        }
        .footer {
            background: #111827;
            color: #9ca3af;
            padding: 24px 20px;
            text-align: center;
            font-size: 12px;
        }
        .footer a {
            color: #fdba74;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        Khách hàng {{ $customer->full_name ?? $customer->username ?? 'người dùng #' . $userId }} đang chờ phản hồi trong cuộc trò chuyện #{{ $conversationId }}.
    </div>

    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">⚠️</div>
            <h1>YÊU CẦU PHẢN HỒI TIN NHẮN</h1>
            <p>Cuộc trò chuyện <strong>#{{ $conversationId }}</strong> đang chờ hỗ trợ</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Alert Badge -->
            <div style="text-align: center;">
                @if ($afterMinutes > 0)
                    <div class="alert-badge">
                        ⏱️ Đã quá <strong>{{ $afterMinutes }} phút</strong> khách chưa nhận được phản hồi
                    </div>
                @else
                    <div class="alert-badge offline">
                        ⚪ Nhân viên phụ trách đang Ngoại tuyến (Offline) khi khách nhắn tin
                    </div>
                @endif
            </div>

            <!-- Customer Info -->
            <div class="section-box">
                <div class="section-title">👤 Thông tin khách hàng</div>
                <table class="info-grid">
                    <tr>
                        <td class="info-label">Họ và tên:</td>
                        <td class="info-value">{{ $customer->full_name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Tên tài khoản:</td>
                        <td class="info-value">{{ $customer->username ?? 'Khách #' . $userId }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Email:</td>
                        <td class="info-value">{{ $customer->email ?? '-' }}</td>
                    </tr>
                    @if (!empty($customer?->phone))
                    <tr>
                        <td class="info-label">Số điện thoại:</td>
                        <td class="info-value">{{ $customer->phone }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="info-label">Mã cuộc trò chuyện:</td>
                        <td class="info-value">#{{ $conversationId }}</td>
                    </tr>
                </table>
            </div>

            <!-- Staff In-charge Info -->
            <div class="section-box">
                <div class="section-title">🎧 Nhân viên CSKH phụ trách</div>
                <table class="info-grid">
                    <tr>
                        <td class="info-label">Nhân viên:</td>
                        <td class="info-value">{{ $staff?->full_name ?? ($staff?->username ?? 'Chưa phân công') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Email nhân viên:</td>
                        <td class="info-value">{{ $staff?->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Trạng thái hiện tại:</td>
                        <td class="info-value">
                            @if ($staff && method_exists($staff, 'isOnline') && $staff->isOnline())
                                <span style="color: #16a34a;">🟢 Đang Online</span>
                            @else
                                <span style="color: #6b7280;">⚪ Đang Offline</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Customer Message(s) -->
            <div class="section-box">
                <div class="section-title">
                    💬 Tin nhắn từ khách hàng 
                    @if (isset($customerMessages) && $customerMessages->count() > 1)
                        ({{ $customerMessages->count() }} tin chưa trả lời)
                    @endif
                </div>

                <div class="messages-container">
                    @if (isset($customerMessages) && $customerMessages->isNotEmpty())
                        @foreach ($customerMessages as $msg)
                            <div class="message-item">
                                <div class="message-text">
                                    @if ($msg->type === 'image')
                                        <em>[Khách gửi hình ảnh đính kèm]</em>
                                    @else
                                        {{ $msg->message }}
                                    @endif
                                </div>
                                <div class="message-time">
                                    {{ $msg->created_at ? $msg->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') : '-' }}
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p style="color: #6b7280; font-style: italic; margin: 0; font-size: 13px;">Khách hàng vừa gửi tin nhắn mới.</p>
                    @endif
                </div>
            </div>

            <!-- Auto Reply Sent -->
            @if (!empty($autoReplyMessage))
            <div class="auto-reply-box">
                <p><strong>🤖 Tin nhắn chào tự động hệ thống đã gửi:</strong></p>
                <p style="margin-top: 4px; font-style: italic;">"{{ $autoReplyMessage }}"</p>
            </div>
            @endif

            <!-- CTA Button -->
            <div class="cta-container">
                <a href="{{ $chatUrl }}" class="cta-button">
                    👉 MỞ VÀ PHẢN HỒI NGAY
                </a>
            </div>
            <p style="text-align: center; color: #6b7280; font-size: 13px; margin-top: 10px;">
                Vui lòng vào trang quản trị để hỗ trợ khách hàng kịp thời, tránh gián đoạn trải nghiệm của khách.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="margin: 0 0 6px 0;"><strong>{{ config('app.name') }} - Hệ thống quản lý chăm sóc khách hàng</strong></p>
            <p style="margin: 0 0 10px 0;">Email thông báo nghiệp vụ tự động dành cho Quản lý và Nhân viên phụ trách.</p>
            <p style="margin: 0; color: #6b7280;">Thời gian gửi: {{ now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }} (GMT+7)</p>
        </div>
    </div>
</body>
</html>
