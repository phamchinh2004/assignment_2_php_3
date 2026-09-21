@extends('user.layouts.master')
@section('css-libs')
@vite('resources/css/user/withdraw_money.css')
@endsection
@section('script-libs')
<script>
    const trans = {
        VuiLongNhapSoTienRut: @json(__('withdraw_money.VuiLongNhapSoTienRut')),
        CanhBao: @json(__('withdraw_money.CanhBao')),
        VuiLongNhapDayDuThongTinNganHang: @json(__('withdraw_money.VuiLongNhapDayDuThongTinNganHang')),
        XacNhanMatKhauGiaoDichKhongKhop: @json(__('withdraw_money.XacNhanMatKhauGiaoDichKhongKhop')),
        ThanhCong: @json(__('withdraw_money.ThanhCong')),
        XacNhanRutTien: 'Xác nhận rút tiền',
        Huy: 'Hủy',
        XacNhan: 'Xác nhận rút',
        DangXuLy: 'Đang xử lý...',
        LoiKetNoi: 'Không thể gửi yêu cầu lúc này. Vui lòng thử lại.',
    };
</script>
@vite('resources/js/user/withdraw_money.js')
@endsection
@section('content')
@php
    $progressPercent = $total_orders > 0 ? min(100, round(($current_orders / $total_orders) * 100)) : 0;
    $remainingOrders = max(0, $total_orders - $current_orders);
    $accountNumber = (string) ($user->account_number ?? '');
    $maskedAccount = strlen($accountNumber) > 4
        ? str_repeat('•', max(0, strlen($accountNumber) - 4)) . substr($accountNumber, -4)
        : $accountNumber;
    $submitBlocked = $has_processing_withdrawal
        || $has_frozen_balance
        || $maximum_number_of_withdrawals <= 0
        || !$order_progress_ready
        || !$has_bank_account
        || $effective_withdrawal_limit <= 0;
    $blockedMessage = '';
    if ($has_frozen_balance) {
        $blockedMessage = 'Bạn đang có số dư bị đóng băng do còn đơn hàng chưa hoàn tất. Hãy hoàn tất các đơn hàng đang xử lý trước khi thực hiện rút tiền.';
    } elseif ($has_processing_withdrawal) {
        $blockedMessage = 'Bạn đang có một yêu cầu rút tiền ở trạng thái chờ xử lý.';
    } elseif ($maximum_number_of_withdrawals <= 0) {
        $blockedMessage = 'Số lượt rút trong ngày đã đạt giới hạn của cấp thành viên hiện tại.';
    } elseif (!$order_progress_ready) {
        $blockedMessage = $remainingOrders > 0
            ? "Bạn cần hoàn thành thêm {$remainingOrders} đơn hàng."
            : 'Tiến độ đơn hàng hiện chưa đủ điều kiện xử lý yêu cầu rút.';
    } elseif (!$has_bank_account) {
        $blockedMessage = 'Hãy liên kết tài khoản ngân hàng trong hồ sơ trước khi tạo yêu cầu rút.';
    } elseif ($effective_withdrawal_limit <= 0) {
        $blockedMessage = 'Nguồn số dư hiện tại không có số tiền khả dụng để tạo yêu cầu.';
    }
    $ctaLabel = $has_frozen_balance
        ? 'Rút tiền đang bị khóa'
        : ($submitBlocked ? 'Chưa thể tạo yêu cầu rút' : 'Xác nhận rút tiền');
@endphp
<main class="withdraw-page {{ $has_frozen_balance ? 'is-withdrawal-locked' : '' }}">
<header class="withdraw-header">
    <div class="withdraw-header__title-wrap">
        <a class="withdraw-back" href="#" onclick="history.back(); return false;" aria-label="{{ __('withdraw_money.QuayLai') }}">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <p class="withdraw-eyebrow">Tài chính</p>
            <h1>{{ __('withdraw_money.RutTien') }}</h1>
        </div>
    </div>
    <a href="{{ route('balance_fluctuation') }}?tab=withdraw" class="withdraw-history-link">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <span>Lịch sử rút</span>
    </a>
</header>
<div class="box_content_withdraw_money">
    <div class="withdraw-summary-card">
        <div class="withdraw-summary-header">
            <div>
                <div class="withdraw-summary-badge">
                    <span class="wallet-live-dot {{ $has_frozen_balance ? 'is-locked' : '' }}"></span>
                    <span>{{ $has_frozen_balance ? 'Số dư tài khoản' : 'Số dư khả dụng để rút' }}</span>
                </div>
                <div class="wallet-balance-value">
                    <strong>{{ format_money($withdrawable_balance) }}</strong><span>USD</span>
                </div>
                <div class="wallet-source-label">
                    <i class="fa-solid fa-wallet"></i>
                    Nguồn rút: số dư tài khoản
                </div>
                @if($has_frozen_balance)
                    <div class="frozen-balance-pill">
                        <i class="fa-solid fa-snowflake"></i>
                        <span>Đang đóng băng: <strong>{{ format_money($frozen_balance) }} USD</strong></span>
                    </div>
                @endif
            </div>
            <div class="withdraw-rank-pill"><i class="fa-solid fa-crown"></i>{{ $rank->name }}</div>
        </div>

        <div class="withdraw-summary-stats">
            <div class="withdraw-stat">
                <div class="stat-content">
                    <div class="withdraw-stat__label">Tối đa mỗi yêu cầu</div>
                    <div class="withdraw-stat__value">{{ format_money($maximum_withdrawal_amount) }} USD</div>
                </div>
            </div>
            <div class="withdraw-stat">
                <div class="stat-content">
                    <div class="withdraw-stat__label">Lượt rút còn lại hôm nay</div>
                    <div class="withdraw-stat__value">{{ $maximum_number_of_withdrawals }} lượt</div>
                </div>
            </div>
            <div class="withdraw-stat withdraw-stat--progress">
                <div class="stat-content">
                    <div class="withdraw-stat__label">Tối đa có thể nhập lúc này</div>
                    <div class="withdraw-stat__value">{{ format_money($effective_withdrawal_limit) }} USD</div>
                    <div class="withdraw-progress">
                        <div class="withdraw-progress__track"><div class="withdraw-progress__fill" style="width: {{ $progressPercent }}%"></div></div>
                        <div class="withdraw-progress__value">{{ $current_orders }}/{{ $total_orders }} đơn</div>
                    </div>
                </div>
            </div>
        </div>
        
        @if($has_frozen_balance || $has_processing_withdrawal || !$order_progress_ready)
        <div class="remaining-orders-alert {{ $has_frozen_balance ? 'is-locked' : ($has_processing_withdrawal ? 'is-pending' : '') }}">
            <div class="alert-icon">
                <i class="fas {{ $has_frozen_balance ? 'fa-lock' : ($has_processing_withdrawal ? 'fa-hourglass-half' : 'fa-circle-exclamation') }}"></i>
            </div>
            <div class="alert-content">
                <div class="alert-title">
                    {{ $has_frozen_balance ? 'Rút tiền đang bị khóa' : ($has_processing_withdrawal ? 'Yêu cầu trước đang được xử lý' : 'Tiến độ đơn hàng chưa hoàn tất') }}
                </div>
                <div class="alert-message">{{ $blockedMessage }}</div>
                @if($has_frozen_balance || (!$order_progress_ready && $remainingOrders > 0))
                    <a href="{{ route('distribution') }}" class="alert-action">
                        <span>Đi tới phân phối</span><i class="fa-solid fa-arrow-right"></i>
                    </a>
                @endif
            </div>
        </div>
        @endif
    </div>
    <div class="withdraw-layout">
    <div class="withdraw-flow">
    <section class="withdraw-card amount-card {{ $has_frozen_balance ? 'is-locked' : '' }}">
        <div class="withdraw-card__heading">
            <span class="step-badge">1</span>
            <div><p class="withdraw-eyebrow">Số tiền</p><h2>Bạn muốn rút bao nhiêu?</h2></div>
        </div>
        <div class="amount-field-wrap">
            <input type="text" inputmode="decimal" autocomplete="off" class="amount-field" id="amount_input_field" placeholder="0.00" {{ $has_frozen_balance ? 'disabled' : '' }}>
            <button type="button" class="amount-max-button" id="withdraw_all" {{ $has_frozen_balance ? 'disabled' : '' }}>Tối đa</button>
        </div>
        <div class="amount-hint">
            <span>Giới hạn theo số dư và hạn mức mỗi lần</span>
            <strong>{{ format_money($effective_withdrawal_limit) }} USD</strong>
        </div>
        <p class="field-error" id="amount-error" hidden></p>
        <input type="hidden" id="temple_amount" value="{{ $effective_withdrawal_limit }}">
        <input type="hidden" id="has_password" value="{{ $has_password ? '1' : '0' }}">
    </section>
    <section class="withdraw-card destination-card">
        <div class="withdraw-card__heading withdraw-card__heading--split">
            <div class="withdraw-card__heading-group">
                <span class="step-badge">2</span>
                <div><p class="withdraw-eyebrow">Nơi nhận</p><h2>Tài khoản nhận tiền</h2></div>
            </div>
            @if($has_bank_account)
                <a href="{{ route('personal_information') }}" class="text-action">Quản lý</a>
            @endif
        </div>
        @if($has_bank_account)
            <div class="bank-account-card">
                <span class="bank-account-card__icon"><i class="fa-solid fa-building-columns"></i></span>
                <div class="bank-account-card__copy">
                    <div class="bank-account-card__topline">
                        <strong>{{ $user->bank_name }}</strong>
                        <span class="linked-badge"><i class="fa-solid fa-lock"></i>Đã liên kết</span>
                    </div>
                    <p>{{ $user->username_bank }}</p>
                    <span>{{ $maskedAccount }}</span>
                </div>
            </div>
            <p class="bank-account-note"><i class="fa-solid fa-circle-info"></i>Tài khoản nhận được lấy từ hồ sơ đã liên kết.</p>
        @else
            <div class="bank-empty-state">
                <span><i class="fa-solid fa-building-columns"></i></span>
                <div><strong>Chưa có tài khoản nhận tiền</strong><p>Liên kết tài khoản ngân hàng trong hồ sơ để tiếp tục.</p></div>
                <a href="{{ route('personal_information') }}">Thiết lập</a>
            </div>
        @endif
        <input id="username_bank" type="hidden" value="{{ $user->username_bank ?? '' }}">
        <input id="select_bank_name" type="hidden" value="{{ $user->bank_name ?? '' }}">
        <input id="account_number" type="hidden" value="{{ $user->account_number ?? '' }}">
    </section>
    <section class="withdraw-card security-card {{ $has_frozen_balance ? 'is-locked' : '' }}">
        <div class="withdraw-card__heading">
            <span class="step-badge">3</span>
            <div><p class="withdraw-eyebrow">Bảo mật</p><h2>Xác thực giao dịch</h2></div>
        </div>
        <div class="security-copy">
            <i class="fa-solid fa-shield-halved"></i>
            <p>{{ $has_password ? 'Nhập mật khẩu giao dịch để xác nhận yêu cầu.' : 'Mật khẩu dưới đây sẽ được thiết lập khi yêu cầu hợp lệ được tạo.' }}</p>
        </div>
        <div class="form-field">
            <label for="transaction_password">{{ __('withdraw_money.MatKhauGiaoDich') }}</label>
            <div class="password-field">
                <input id="transaction_password" type="password" autocomplete="current-password" placeholder="Nhập mật khẩu giao dịch" {{ $has_frozen_balance ? 'disabled' : '' }}>
                <button type="button" class="password-toggle" data-target="transaction_password" aria-label="Hiện hoặc ẩn mật khẩu" {{ $has_frozen_balance ? 'disabled' : '' }}><i class="fa-regular fa-eye"></i></button>
            </div>
        </div>
        @if(!$has_password)
            <div class="form-field">
                <label for="confirm_transaction_password">{{ __('withdraw_money.XacNhanLaiMatKhau') }}</label>
                <div class="password-field">
                    <input id="confirm_transaction_password" type="password" autocomplete="new-password" placeholder="Nhập lại mật khẩu giao dịch" {{ $has_frozen_balance ? 'disabled' : '' }}>
                    <button type="button" class="password-toggle" data-target="confirm_transaction_password" aria-label="Hiện hoặc ẩn mật khẩu" {{ $has_frozen_balance ? 'disabled' : '' }}><i class="fa-regular fa-eye"></i></button>
                </div>
                <p class="field-error" id="password-error" hidden></p>
            </div>
        @else
            <input id="confirm_transaction_password" type="hidden" value="">
        @endif
    </section>
    <div class="withdraw-notice">
        <i class="fa-solid fa-circle-info"></i>
        <p>{{ __('withdraw_money.VuiLongKiemTra') }}</p>
    </div>
    </div>

    <aside class="withdraw-review">
        <div class="review-card">
            <div class="review-card__header">
                <div><p class="withdraw-eyebrow">Bước cuối</p><h2>Kiểm tra yêu cầu</h2></div>
                <span class="review-card__shield"><i class="fa-solid fa-shield-halved"></i></span>
            </div>
            <div class="review-amount">
                <span>Số tiền yêu cầu</span>
                <strong id="review_amount">0.00 USD</strong>
            </div>
            <div class="review-rows">
                <div class="review-row"><span>Phí xử lý</span><strong id="review_fee">0.00 USD <small>(0%)</small></strong></div>
                <div class="review-row review-row--net"><span>Thực nhận</span><strong id="review_net">0.00 USD</strong></div>
                <div class="review-row"><span>Nơi nhận</span><strong>{{ $has_bank_account ? $user->bank_name . ' · ' . $maskedAccount : 'Chưa thiết lập' }}</strong></div>
            </div>
            <div class="eligibility-block">
                <div class="eligibility-block__title"><span>Điều kiện xử lý</span><span>{{ $current_orders }}/{{ $total_orders }} đơn</span></div>
                <div class="order-progress"><span style="width: {{ $progressPercent }}%"></span></div>
                <div class="eligibility-list">
                    <div class="eligibility-item {{ !$has_frozen_balance ? 'is-ok' : 'is-blocked' }}"><i class="fa-solid {{ !$has_frozen_balance ? 'fa-circle-check' : 'fa-lock' }}"></i><span>Số dư đóng băng</span><strong>{{ $has_frozen_balance ? format_money($frozen_balance) . ' USD đang khóa' : 'Không có' }}</strong></div>
                    <div class="eligibility-item {{ $order_progress_ready ? 'is-ok' : 'is-blocked' }}"><i class="fa-solid {{ $order_progress_ready ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i><span>Tiến độ đơn hàng</span><strong>{{ $order_progress_ready ? 'Đã đủ' : 'Còn ' . $remainingOrders . ' đơn' }}</strong></div>
                    <div class="eligibility-item {{ $maximum_number_of_withdrawals > 0 ? 'is-ok' : 'is-blocked' }}"><i class="fa-solid {{ $maximum_number_of_withdrawals > 0 ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i><span>Lượt rút hôm nay</span><strong>{{ $maximum_number_of_withdrawals }} lượt</strong></div>
                    <div class="eligibility-item {{ !$has_processing_withdrawal ? 'is-ok' : 'is-blocked' }}"><i class="fa-solid {{ !$has_processing_withdrawal ? 'fa-circle-check' : 'fa-clock' }}"></i><span>Yêu cầu đang xử lý</span><strong>{{ $has_processing_withdrawal ? 'Đang có' : 'Không có' }}</strong></div>
                    <div class="eligibility-item {{ $has_bank_account ? 'is-ok' : 'is-blocked' }}"><i class="fa-solid {{ $has_bank_account ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i><span>Tài khoản nhận</span><strong>{{ $has_bank_account ? 'Đã liên kết' : 'Chưa có' }}</strong></div>
                </div>
            </div>
            @if($blockedMessage)
                <p class="review-blocked-message"><i class="fa-solid fa-circle-info"></i>{{ $blockedMessage }}</p>
            @endif
            <button type="button" class="withdraw-submit" id="btn_withdraw_now" {{ $submitBlocked ? 'disabled' : '' }}><span>{{ $ctaLabel }}</span><i class="fa-solid fa-arrow-right"></i></button>
            <p class="review-footnote">Hệ thống sẽ kiểm tra lại điều kiện và số dư thực tế tại thời điểm gửi yêu cầu.</p>
        </div>
    </aside>
    </div>
    <div id="withdrawal-config" hidden data-max-amount="{{ $effective_withdrawal_limit }}" data-fee-rate="0" data-has-password="{{ $has_password ? '1' : '0' }}" data-bank-name="{{ $user->bank_name ?? '' }}" data-account-mask="{{ $maskedAccount }}" data-submit-blocked="{{ $submitBlocked ? '1' : '0' }}"></div>
</div>
</main>
@endsection
