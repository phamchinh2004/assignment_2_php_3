<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Nhắc nhở đơn hàng đặc biệt</title>
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
            background-color: #f8f9fa;
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
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
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
        Bạn có đơn hàng đặc biệt đang chờ xử lý. Vui lòng kiểm tra ngay.
    </div>
    
    <div class="header">
        <h2>📦 Thông báo đơn hàng đặc biệt</h2>
    </div>
    
    <div class="content">
        <p>Xin chào <strong>{{ $user->name }}</strong>,</p>
        
        <div class="warning">
            <strong>Thông báo quan trọng:</strong> Bạn có đơn hàng đặc biệt đang chờ phân phối ({{ $hoursPassed }} giờ)
        </div>
        
        <p>Chúng tôi nhận thấy bạn đã nhận được đơn hàng đặc biệt nhưng chưa thực hiện phân phối. Để đảm bảo đơn hàng được giao đến khách hàng đúng hạn, vui lòng thực hiện phân phối sớm nhất có thể.</p>
        
        <div class="order-info">
            <h3>Thông tin đơn hàng:</h3>
            <p><strong>Mã đơn hàng:</strong> {{ $frozenOrder->order->order_code }}</p>
            <p><strong>Tên đơn hàng:</strong> {{ $frozenOrder->order->name }}</p>
            <p><strong>Đơn hàng trị giá:</strong> ${{ number_format($frozenOrder->custom_price, 2) }}</p>
            <p><strong>Thời gian nhận:</strong> {{ $frozenOrder->updated_at->format('d/m/Y H:i:s') }}</p>
            <p><strong>Thời gian đã trôi qua:</strong> {{ $hoursPassed }} giờ</p>
        </div>
        
        <p><strong>Thời hạn và chính sách:</strong></p>
        <ul>
            <li>Thời hạn phân phối: trong vòng 24 giờ kể từ khi nhận đơn</li>
            <li>Nếu quá thời hạn: sẽ áp dụng phí xử lý trễ (30% giá trị đơn hàng)</li>
            <li>Bạn có thể truy cập hệ thống tại: <a href="{{ config('app.url') }}">{{ config('app.url') }}</a></li>
            <li>Cần hỗ trợ? Liên hệ: {{ config('mail.from.address') }}</li>
        </ul>
        
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
