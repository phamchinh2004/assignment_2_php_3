<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Thông báo phí xử lý đơn hàng</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .penalty {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .order-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .penalty-amount {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Preheader text -->
    <div style="display:none;font-size:1px;color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
        Thông báo phí xử lý đơn hàng quá hạn. Vui lòng xem chi tiết bên trong.
    </div>
    
    <div class="header">
        <h2>📋 Thông báo phí xử lý đơn hàng</h2>
    </div>
    
    <div class="content">
        <p>Xin chào <strong>{{ $user->name }}</strong>,</p>
        
        <div class="penalty">
            <strong>Thông báo quan trọng:</strong> Đơn hàng của bạn đã quá thời hạn phân phối ({{ $hoursPassed }} giờ)
        </div>
        
        <p>Chúng tôi nhận thấy đơn hàng đặc biệt của bạn chưa được phân phối sau 24 giờ kể từ khi nhận. Theo chính sách của hệ thống, một khoản phí xử lý sẽ được áp dụng cho trường hợp này.</p>
        
        <div class="order-info">
            <h3>Thông tin đơn hàng:</h3>
            <p><strong>Mã đơn hàng:</strong> {{ $frozenOrder->order->order_code }}</p>
            <p><strong>Tên đơn hàng:</strong> {{ $frozenOrder->order->name }}</p>
            <p><strong>Giá đặc biệt:</strong> ${{ number_format($frozenOrder->custom_price, 2) }}</p>
            <p><strong>Thời gian nhận:</strong> {{ $frozenOrder->created_at->format('d/m/Y H:i:s') }}</p>
            <p><strong>Thời gian đã trôi qua:</strong> {{ $hoursPassed }} giờ</p>
        </div>
        
        <div class="penalty-amount">
            <p>Phí xử lý áp dụng: ${{ number_format($penaltyAmount, 2) }}</p>
            <p style="font-size: 14px; font-weight: normal;">(30% giá trị đơn hàng theo chính sách)</p>
        </div>
        
        <p><strong>Các bước tiếp theo:</strong></p>
        <ul>
            <li>Nạp thêm ${{ number_format($penaltyAmount, 2) }} vào tài khoản của bạn</li>
            <li>Liên hệ bộ phận hỗ trợ qua email: {{ config('mail.from.address') }}</li>
            <li>Đơn hàng sẽ được xử lý sau khi hoàn tất thanh toán</li>
            <li>Truy cập hệ thống tại: <a href="{{ config('app.url') }}">{{ config('app.url') }}</a></li>
        </ul>
        
        <p><strong>Giải thích về chính sách:</strong> Phí xử lý này được áp dụng để đảm bảo tính công bằng và duy trì chất lượng dịch vụ cho tất cả các thành viên. Chúng tôi khuyến khích bạn phân phối đơn hàng đúng hạn trong các lần tiếp theo.</p>
        
        <p>Nếu có thắc mắc hoặc cần hỗ trợ, vui lòng liên hệ với chúng tôi.</p>
        
        <p>Trân trọng,<br>Đội ngũ {{ config('app.name') }}</p>
    </div>
    
    <div class="footer">
        <p><strong>{{ config('app.name') }}</strong></p>
        <p>Email: {{ config('mail.from.address') }} | Website: {{ config('app.url') }}</p>
        <p style="margin-top: 10px;">Email này được gửi tự động từ hệ thống. Nếu bạn nhận nhầm email này, vui lòng bỏ qua.</p>
        <p style="margin-top: 10px; font-size: 11px; color: #999;">
            Bạn nhận được email này vì bạn là thành viên của {{ config('app.name') }}.
        </p>
    </div>
</body>
</html>
