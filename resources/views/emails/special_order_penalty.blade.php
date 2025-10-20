<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thông báo phạt đơn hàng đặc biệt</title>
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
    <div class="header">
        <h2>🚨 Thông báo phạt đơn hàng đặc biệt</h2>
    </div>
    
    <div class="content">
        <p>Xin chào <strong>{{ $user->name }}</strong>,</p>
        
        <div class="penalty">
            <strong>⚠️ CẢNH BÁO NGHIÊM TRỌNG:</strong> Bạn đã bị phạt do không phân phối đơn hàng đặc biệt trong thời gian quy định!
        </div>
        
        <p>Chúng tôi rất tiếc phải thông báo rằng bạn đã không thực hiện phân phối đơn hàng đặc biệt trong vòng 24 giờ như đã được nhắc nhở trước đó. Theo quy định của hệ thống, bạn sẽ bị phạt.</p>
        
        <div class="order-info">
            <h3>Thông tin đơn hàng:</h3>
            <p><strong>Mã đơn hàng:</strong> {{ $frozenOrder->order->order_code }}</p>
            <p><strong>Tên đơn hàng:</strong> {{ $frozenOrder->order->name }}</p>
            <p><strong>Giá đặc biệt:</strong> ${{ number_format($frozenOrder->custom_price, 2) }}</p>
            <p><strong>Thời gian nhận:</strong> {{ $frozenOrder->created_at->format('d/m/Y H:i:s') }}</p>
            <p><strong>Thời gian đã trôi qua:</strong> {{ $hoursPassed }} giờ</p>
        </div>
        
        <div class="penalty-amount">
            <p>Số tiền phạt: ${{ number_format($penaltyAmount, 2) }}</p>
            <p>(30% tổng giá trị đơn hàng)</p>
        </div>
        
        <p><strong>Hành động cần thực hiện:</strong></p>
        <ul>
            <li>Bạn cần nạp thêm ${{ number_format($penaltyAmount, 2) }} vào tài khoản</li>
            <li>Sau khi nạp tiền, vui lòng liên hệ với bộ phận hỗ trợ để được hỗ trợ</li>
            <li>Đơn hàng này sẽ được xử lý sau khi bạn hoàn thành nghĩa vụ tài chính</li>
        </ul>
        
        <p><strong>Lưu ý:</strong> Đây là quy định của hệ thống nhằm đảm bảo tính công bằng và hiệu quả trong việc phân phối đơn hàng. Chúng tôi khuyến khích bạn tuân thủ các quy định để tránh các hậu quả không mong muốn trong tương lai.</p>
        
        <p>Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với bộ phận hỗ trợ khách hàng.</p>
        
        <p>Trân trọng,<br>Đội ngũ {{ config('app.name') }}</p>
    </div>
    
    <div class="footer">
        <p>Email này được gửi tự động từ hệ thống {{ config('app.name') }}</p>
        <p>Vui lòng không trả lời email này.</p>
    </div>
</body>
</html>
