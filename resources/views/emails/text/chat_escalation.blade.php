[{{ config('app.name') }}] CẢNH BÁO: CUỘC TRÒ CHUYỆN ĐANG CHỜ PHẢN HỒI (#{{ $conversationId }})
================================================================================

@if ($afterMinutes > 0)
* Tình trạng: Đã quá {{ $afterMinutes }} phút khách hàng chưa nhận được phản hồi.
@else
* Tình trạng: Quản lý/Nhân viên phụ trách đang Ngoại tuyến (Offline) khi khách gửi tin nhắn.
@endif

THÔNG TIN KHÁCH HÀNG:
- Họ và tên: {{ $customer->full_name ?? '-' }}
- Tên tài khoản: {{ $customer->username ?? 'Khách #' . $userId }}
- Email: {{ $customer->email ?? '-' }}
@if (!empty($customer?->phone))
- Số điện thoại: {{ $customer->phone }}
@endif
- Mã cuộc trò chuyện: #{{ $conversationId }}

NHÂN VIÊN PHỤ TRÁCH:
- Nhân viên: {{ $staff?->full_name ?? ($staff?->username ?? 'Chưa phân công') }}
- Email: {{ $staff?->email ?? '-' }}
- Trạng thái: {{ ($staff && method_exists($staff, 'isOnline') && $staff->isOnline()) ? 'Đang Online' : 'Đang Offline' }}

TIN NHẮN TỪ KHÁCH HÀNG:
@if (isset($customerMessages) && $customerMessages->isNotEmpty())
@foreach ($customerMessages as $msg)
- [{{ $msg->created_at ? $msg->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') : '-' }}] {{ $msg->type === 'image' ? '[Khách gửi hình ảnh]' : $msg->message }}
@endforeach
@else
- Khách hàng đã gửi tin nhắn mới.
@endif

@if (!empty($autoReplyMessage))
TIN NHẮN CHÀO TỰ ĐỘNG ĐÃ GỬI:
"{{ $autoReplyMessage }}"
@endif

================================================================================
MỞ VÀ PHẢN HỒI NGAY TẠI: {{ $chatUrl }}
================================================================================

Thời gian gửi: {{ now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }} (GMT+7)
Thông báo nghiệp vụ dành cho nhân viên hỗ trợ và quản lý của {{ config('app.name') }}.
