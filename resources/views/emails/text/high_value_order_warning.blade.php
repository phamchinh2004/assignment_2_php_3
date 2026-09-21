{{ config('app.name') }} - Cập nhật thời hạn đơn hàng

Xin chào {{ $user->full_name ?? $user->username }},

Đơn hàng {{ $frozenOrder->display_order_code ?? $frozenOrder->order_id }} còn {{ $remainingHours }} giờ trong thời hạn xử lý hiện tại.
Tên đơn hàng: {{ $frozenOrder->display_name ?? 'N/A' }}
Giá trị: ${{ number_format($frozenOrder->display_order_amount ?? 0, 2) }}

Vui lòng đăng nhập trực tiếp tại {{ config('app.url') }} để xem trạng thái và chính sách áp dụng.
Nếu cần hỗ trợ, hãy liên hệ {{ config('mail.from.address') }}.

Email dịch vụ này liên quan đến đơn hàng trên tài khoản {{ config('app.name') }} của bạn.
