<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nhắc nhở đơn hàng đặc biệt</title>
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
    <div class="header">
        <h2>🔔 Nhắc nhở đơn hàng đặc biệt</h2>
    </div>
    
    <div class="content">
        <p>Xin chào <strong>{{ $user->name }}</strong>,</p>
        
        <div class="warning">
            <strong>⚠️ Cảnh báo:</strong> Bạn có đơn hàng đặc biệt chưa được phân phối trong vòng 8 tiếng!
        </div>
        
        <p>Chúng tôi nhận thấy bạn đã nhận được đơn hàng đặc biệt nhưng chưa thực hiện phân phối. Vui lòng thực hiện phân phối sớm nhất có thể để kịp tiến độ kho vận chuyển đơn hàng tới tay khách hàng đúng hạn.</p>
        
        <div class="order-info">
            <h3>Thông tin đơn hàng:</h3>
            <p><strong>Mã đơn hàng:</strong> {{ $frozenOrder->order->order_code }}</p>
            <p><strong>Tên đơn hàng:</strong> {{ $frozenOrder->order->name }}</p>
            <p><strong>Đơn hàng trị giá:</strong> ${{ number_format($frozenOrder->custom_price, 2) }}</p>
            <p><strong>Thời gian nhận:</strong> {{ $frozenOrder->updated_at->format('d/m/Y H:i:s') }}</p>
            <p><strong>Thời gian đã trôi qua:</strong> {{ $hoursPassed }} giờ</p>
        </div>
        
        <p><strong>Lưu ý quan trọng:</strong></p>
        <ul>
            <li>Nếu bạn không phân phối đơn hàng trong vòng 24 giờ, bạn sẽ bị phạt 30% tổng giá trị đơn hàng</li>
            <li>Hãy truy cập vào hệ thống và thực hiện phân phối ngay lập tức</li>
            <li>Nếu có bất kỳ vấn đề gì, vui lòng liên hệ với bộ phận hỗ trợ</li>
        </ul>
        
        <p>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</p>
    </div>
    
    <div class="footer">
        <p>Email này được gửi tự động từ hệ thống {{ config('app.name') }}</p>
        <p>Vui lòng không trả lời email này.</p>
    </div>
</body>
</html>
