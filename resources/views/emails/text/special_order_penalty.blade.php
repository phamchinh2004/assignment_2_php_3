{{ config('app.name') }} - Cập nhật phí xử lý đơn hàng

Xin chào {{ $user->full_name ?? $user->username }},

Hệ thống đã cập nhật phí xử lý cho đơn hàng {{ $frozenOrder->order->order_code }} theo chính sách hiện hành.
Tên đơn hàng: {{ $frozenOrder->order->name }}
Giá trị đơn hàng: ${{ number_format($orderValue, 2) }}
Phí xử lý: ${{ number_format($penaltyAmount, 2) }}
Thời gian đã trôi qua: {{ $hoursPassed }} giờ

Vui lòng đăng nhập trực tiếp tại {{ config('app.url') }} để kiểm tra chi tiết. Nếu cần làm rõ, hãy liên hệ {{ config('mail.from.address') }}.

Email dịch vụ này liên quan đến đơn hàng trên tài khoản {{ config('app.name') }} của bạn.
