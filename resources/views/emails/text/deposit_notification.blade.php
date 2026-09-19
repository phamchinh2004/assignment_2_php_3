{{ config('app.name') }} - Xác nhận giao dịch

Xin chào {{ $user->full_name ?? $user->username }},

Giao dịch {{ $transactionType === 'normal' ? 'nạp tiền' : 'tiền thưởng' }} trên tài khoản của bạn đã được ghi nhận.

Số tiền: ${{ number_format($amount, 2) }}
Số dư sau giao dịch: ${{ number_format($newBalance, 2) }}
Thời gian: {{ now()->format('d/m/Y H:i') }}

Bạn có thể kiểm tra lịch sử giao dịch tại {{ url('/balance-fluctuation?tab=deposit') }}.
Nếu không nhận ra giao dịch này, hãy liên hệ {{ config('mail.from.address') }}.

Email dịch vụ này được gửi vì tài khoản của bạn vừa phát sinh giao dịch tại {{ config('app.name') }}.
