{{ config('app.name') }} - Đơn hàng đang chờ xử lý

Xin chào {{ $user->full_name ?? $user->username }},

Đơn hàng {{ $frozenOrder->order->order_code }} đang chờ bạn xử lý.
Tên đơn hàng: {{ $frozenOrder->order->name }}
Giá trị: ${{ number_format($frozenOrder->custom_price, 2) }}
Thời gian đã trôi qua: {{ $hoursPassed }} giờ

Xem thông tin tài khoản tại {{ config('app.url') }}.
Nếu cần hỗ trợ, hãy liên hệ {{ config('mail.from.address') }}.

Email dịch vụ này liên quan đến đơn hàng trên tài khoản {{ config('app.name') }} của bạn.
