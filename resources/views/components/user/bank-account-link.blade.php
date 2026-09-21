@props([
    'user',
    'banks' => [],
    'autoOpen' => false,
])

@php
    $bankLinked = filled($user->username_bank) && filled($user->bank_name) && filled($user->account_number);
    $currentBank = (string) ($user->bank_name ?? '');
    $bankCatalog = collect($banks)->flatten();
    $currentBankInCatalog = $currentBank === '' || $bankCatalog->containsStrict($currentBank);
    $accountNumber = (string) ($user->account_number ?? '');
    $accountLength = mb_strlen($accountNumber);
    $maskedAccount = '';

    if ($accountLength > 0) {
        if ($accountLength <= 4) {
            $maskedAccount = str_repeat('•', $accountLength);
        } else {
            $maskedAccount = str_repeat('•', min(8, $accountLength - 4)) . mb_substr($accountNumber, -4);
        }
    }
@endphp

<section {{ $attributes->class('bank-account') }} data-bank-account data-linked="{{ $bankLinked ? 'true' : 'false' }}"
    data-auto-open="{{ $autoOpen ? 'true' : 'false' }}" data-endpoint="{{ route('bank_link') }}">
    <button type="button" class="bank-account-card {{ $bankLinked ? 'is-linked is-locked' : 'is-unlinked' }}"
        data-bank-account-open aria-haspopup="dialog">
        <span class="bank-account-card__icon" aria-hidden="true">
            <i class="fa-solid fa-building-columns"></i>
        </span>

        <span class="bank-account-card__body">
            <span class="bank-account-card__eyebrow">Tài khoản ngân hàng</span>
            <strong data-bank-summary-name>{{ $bankLinked ? $user->bank_name : 'Chưa liên kết' }}</strong>
            <span class="bank-account-card__meta" data-bank-summary-meta @hidden(!$bankLinked)>
                <span data-bank-summary-account>{{ $maskedAccount }}</span>
                <span aria-hidden="true">•</span>
                <span data-bank-summary-owner>{{ $user->username_bank }}</span>
            </span>
            <span class="bank-account-card__meta" data-bank-summary-empty @hidden($bankLinked)>
                Thiết lập tài khoản nhận tiền và mật khẩu giao dịch.
            </span>
        </span>

        <span class="bank-account-card__side">
            <span class="bank-account-card__status" data-bank-summary-status>
                <i class="fa-solid {{ $bankLinked ? 'fa-circle-check' : 'fa-circle-plus' }}" aria-hidden="true"></i>
                <span>{{ $bankLinked ? 'Đã liên kết' : 'Thiết lập' }}</span>
            </span>
            <span class="bank-account-card__action" data-bank-summary-action>
                {{ $bankLinked ? 'Xem thông tin' : 'Liên kết ngay' }}
                <i class="fa-solid {{ $bankLinked ? 'fa-eye' : 'fa-arrow-right' }}" aria-hidden="true"></i>
            </span>
        </span>
    </button>

    <div class="modal fade bank-account-dialog" data-bank-account-dialog tabindex="-1"
        aria-labelledby="bankAccountDialogTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bank-account-sheet">
                <header class="bank-account-sheet__header">
                    <div class="bank-account-sheet__heading">
                        <span class="bank-account-sheet__icon" aria-hidden="true">
                            <i class="fa-solid fa-building-columns"></i>
                        </span>
                        <div>
                            <span class="bank-account-sheet__eyebrow">Thanh toán & nhận tiền</span>
                            <h2 id="bankAccountDialogTitle" data-bank-dialog-title>
                                {{ $bankLinked ? 'Thông tin tài khoản ngân hàng' : __('home.LienKetTaiKhoanNganHang') }}
                            </h2>
                            <p>{{ $bankLinked ? 'Tài khoản này đã được liên kết và khóa chỉnh sửa.' : 'Nhập chính xác thông tin tài khoản sẽ dùng cho các giao dịch sau này.' }}</p>
                        </div>
                    </div>
                    <button type="button" class="bank-account-sheet__close" data-bs-dismiss="modal" aria-label="Đóng">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </header>

                <div class="bank-account-linked-view" data-bank-linked-view @hidden(!$bankLinked)>
                        <div class="bank-account-linked-view__notice">
                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                            <div>
                                <strong>Thông tin đã được khóa</strong>
                                <span>Sau khi liên kết thành công, thông tin ngân hàng không thể chỉnh sửa.</span>
                            </div>
                        </div>

                        <div class="bank-account-linked-view__list">
                            <div>
                                <span>Ngân hàng</span>
                                <strong data-bank-view-bank>{{ $user->bank_name }}</strong>
                            </div>
                            <div>
                                <span>Chủ tài khoản</span>
                                <strong data-bank-view-owner>{{ $user->username_bank }}</strong>
                            </div>
                            <div>
                                <span>Số tài khoản</span>
                                <strong data-bank-view-account>{{ $accountNumber }}</strong>
                            </div>
                        </div>

                        <div class="bank-account-linked-view__footer">
                            <button type="button" class="bank-account-button bank-account-button--primary" data-bs-dismiss="modal">
                                Đóng
                            </button>
                        </div>
                </div>

                @if(!$bankLinked)
                    <div class="bank-account-progress" aria-label="Quy trình liên kết tài khoản">
                        <span class="is-active" data-bank-progress-step="1" aria-current="step"><b>1</b> Ngân hàng</span>
                        <span data-bank-progress-step="2"><b>2</b> Chủ tài khoản</span>
                        <span data-bank-progress-step="3"><b>3</b> Kiểm tra</span>
                    </div>

                    <form class="bank-account-form" data-bank-account-form novalidate>
                    <div class="bank-account-form__scroll">
                        <div class="bank-account-form__status" data-bank-form-status hidden role="alert"></div>

                        <section class="bank-account-step" aria-labelledby="bankAccountBankInfoTitle">
                            <div class="bank-account-step__number">01</div>
                            <div class="bank-account-step__content">
                                <div class="bank-account-step__heading">
                                    <h3 id="bankAccountBankInfoTitle">Thông tin ngân hàng</h3>
                                    <p>Chọn ngân hàng và nhập số tài khoản đang sử dụng.</p>
                                </div>

                                <div class="bank-field">
                                    <label for="bankAccountBankName">{{ __('withdraw_money.TenNganHang') }} <span>*</span></label>
                                    <select id="bankAccountBankName" name="bank_name" data-bank-select required>
                                        <option value="">{{ __('home.ChonNganHang') }}</option>
                                        @if(!$currentBankInCatalog)
                                            <option value="{{ $currentBank }}" selected>{{ $currentBank }}</option>
                                        @endif
                                        @foreach($banks as $group => $options)
                                            <optgroup label="{{ $group }}">
                                                @foreach($options as $bank)
                                                    <option value="{{ $bank }}" @selected($user->bank_name === $bank)>{{ $bank }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                    <small class="bank-field__hint">Có thể gõ tên ngân hàng để tìm nhanh.</small>
                                    <div class="bank-field__error" data-bank-error="bank_name"></div>
                                </div>

                                <div class="bank-field">
                                    <label for="bankAccountNumber">{{ __('withdraw_money.SoTaiKhoan') }} <span>*</span></label>
                                    <div class="bank-input-shell">
                                        <i class="fa-regular fa-credit-card" aria-hidden="true"></i>
                                        <input id="bankAccountNumber" name="account_number" type="text"
                                            value="{{ $accountNumber }}" autocomplete="off" spellcheck="false"
                                            placeholder="Nhập số tài khoản" required data-bank-account-number>
                                    </div>
                                    <div class="bank-field__error" data-bank-error="account_number"></div>
                                </div>
                            </div>
                        </section>

                        <section class="bank-account-step" aria-labelledby="bankAccountOwnerInfoTitle">
                            <div class="bank-account-step__number">02</div>
                            <div class="bank-account-step__content">
                                <div class="bank-account-step__heading">
                                    <h3 id="bankAccountOwnerInfoTitle">Thông tin chủ tài khoản</h3>
                                    <p>Tên chủ tài khoản và mật khẩu giao dịch được lưu cùng liên kết này.</p>
                                </div>

                                <div class="bank-field">
                                    <label for="bankAccountOwner">{{ __('withdraw_money.TenChuTaiKhoan') }} <span>*</span></label>
                                    <div class="bank-input-shell">
                                        <i class="fa-regular fa-user" aria-hidden="true"></i>
                                        <input id="bankAccountOwner" name="username_bank" type="text"
                                            value="{{ $user->username_bank ?? '' }}" autocomplete="name"
                                            placeholder="Nhập tên chủ tài khoản" required data-bank-owner>
                                    </div>
                                    <div class="bank-field__error" data-bank-error="username_bank"></div>
                                </div>

                                <div class="bank-account-password-grid">
                                    <div class="bank-field">
                                        <label for="bankAccountPassword">{{ __('withdraw_money.MatKhauGiaoDich') }} <span>*</span></label>
                                        <div class="bank-input-shell bank-input-shell--password">
                                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                            <input id="bankAccountPassword" name="transaction_password" type="password"
                                                autocomplete="new-password" placeholder="Nhập mật khẩu giao dịch" required
                                                data-bank-password>
                                            <button type="button" data-bank-password-toggle="bankAccountPassword" aria-label="Hiện hoặc ẩn mật khẩu">
                                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <div class="bank-field__error" data-bank-error="transaction_password"></div>
                                    </div>

                                    <div class="bank-field">
                                        <label for="bankAccountPasswordConfirm">{{ __('home.XacNhanMatKhauGiaoDich') }} <span>*</span></label>
                                        <div class="bank-input-shell bank-input-shell--password">
                                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                            <input id="bankAccountPasswordConfirm" name="confirm_transaction_password" type="password"
                                                autocomplete="new-password" placeholder="Nhập lại mật khẩu giao dịch" required
                                                data-bank-password-confirm>
                                            <button type="button" data-bank-password-toggle="bankAccountPasswordConfirm" aria-label="Hiện hoặc ẩn mật khẩu xác nhận">
                                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <div class="bank-field__error" data-bank-error="confirm_transaction_password"></div>
                                    </div>
                                </div>

                                <div class="bank-account-security-note">
                                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                                    <p>
                                        <strong>Lưu ý về mật khẩu giao dịch</strong>
                                        <span data-bank-security-copy>{{ $bankLinked
                                            ? 'Khi lưu thay đổi, mật khẩu nhập ở đây sẽ được cập nhật cùng thông tin ngân hàng.'
                                            : 'Mật khẩu nhập ở đây sẽ được thiết lập làm mật khẩu giao dịch của tài khoản.' }}</span>
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section class="bank-account-step bank-account-step--review" aria-labelledby="bankAccountReviewTitle">
                            <div class="bank-account-step__number">03</div>
                            <div class="bank-account-step__content">
                                <div class="bank-account-step__heading">
                                    <h3 id="bankAccountReviewTitle">Kiểm tra trước khi liên kết</h3>
                                    <p>Đối chiếu lại thông tin trước khi xác nhận.</p>
                                </div>

                                <div class="bank-account-review">
                                    <div>
                                        <span>Ngân hàng</span>
                                        <strong data-bank-review-bank>{{ $user->bank_name ?: 'Chưa chọn' }}</strong>
                                    </div>
                                    <div>
                                        <span>Chủ tài khoản</span>
                                        <strong data-bank-review-owner>{{ $user->username_bank ?: 'Chưa nhập' }}</strong>
                                    </div>
                                    <div>
                                        <span>Số tài khoản</span>
                                        <strong data-bank-review-account>{{ $maskedAccount ?: 'Chưa nhập' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="bank-account-form__footer">
                        <button type="button" class="bank-account-button bank-account-button--secondary" data-bs-dismiss="modal">
                            {{ __('home.Huy') }}
                        </button>
                        <button type="submit" class="bank-account-button bank-account-button--primary" data-bank-submit>
                            <span data-bank-submit-label>{{ __('home.XacNhanLienKet') }}</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</section>
