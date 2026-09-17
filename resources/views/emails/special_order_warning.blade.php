<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Cảnh báo đơn hàng sắp quá hạn</title>
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
            background-color: #fff3cd;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }
        .content {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .warning {
            background-color: #fff8e1;
            border: 1px solid #ffd54f;
            color: #8a6d3b;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
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
        <h2>⚠️ Cảnh báo xử lý đơn hàng</h2>
    </div>

    <div class="content">
        <p>Xin chào <strong>{{ $user->name }}</strong>,</p>

        <div class="warning">
            @if($warningType === 'first')
                Đơn hàng của bạn còn <strong>{{ $remainingHours }} giờ</strong> nữa là đến mốc cảnh báo lần 1 ({{ $warningThreshold }} giờ trước hạn).
            @else
                Đơn hàng của bạn còn <strong>{{ $remainingHours }} giờ</strong> nữa là đến mốc cảnh báo lần 2 và cần xử lý ngay.
            @endif
        </div>

        <p>Hiện tại đơn hàng đang nằm trong trạng thái chưa xử lý quá lâu. Nếu không thực hiện đúng tiến độ, hệ thống sẽ áp dụng mức phạt theo chính sách.</p>

        <div class="order-info">
            <h3>Thông tin đơn hàng:</h3>
            <p><strong>Mã đơn hàng:</strong> {{ $frozenOrder->order->order_code }}</p>
            <p><strong>Tên đơn hàng:</strong> {{ $frozenOrder->order->name }}</p>
            <p><strong>Giá trị đơn hàng:</strong> ${{ number_format($frozenOrder->custom_price ?? ($frozenOrder->order->price * $frozenOrder->order->quantity), 2) }}</p>
            <p><strong>Thời gian đã trôi qua:</strong> {{ $hoursPassed }} giờ</p>
            <p><strong>Thời gian còn lại:</strong> {{ $remainingHours }} giờ</p>
        </div>

        <p>Vui lòng truy cập hệ thống và xử lý đơn hàng sớm nhất để tránh bị áp dụng phí trễ.</p>
        <p>Bạn có thể truy cập: <a href="{{ config('app.url') }}">{{ config('app.url') }}</a></p>

        <p>Trân trọng,<br>Đội ngũ {{ config('app.name') }}</p>
    </div>

    <div class="footer">
        <p><strong>{{ config('app.name') }}</strong></p>
        <p>Email: {{ config('mail.from.address') }} | Website: {{ config('app.url') }}</p>
    </div>
</body>
</html>
