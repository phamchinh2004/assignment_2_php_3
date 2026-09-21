document.addEventListener('DOMContentLoaded', function () {
    const config = document.getElementById('withdrawal-config');
    const amountInput = document.getElementById('amount_input_field');
    const maxButton = document.getElementById('withdraw_all');
    const submitButton = document.getElementById('btn_withdraw_now');
    const amountError = document.getElementById('amount-error');
    const passwordError = document.getElementById('password-error');
    const transactionPassword = document.getElementById('transaction_password');
    const confirmTransactionPassword = document.getElementById('confirm_transaction_password');
    const usernameBank = document.getElementById('username_bank');
    const bankName = document.getElementById('select_bank_name');
    const accountNumber = document.getElementById('account_number');

    if (!config || !amountInput || !submitButton) return;

    const maximumAmount = Number(config.dataset.maxAmount || 0);
    const feeRate = Number(config.dataset.feeRate || 0);
    const submitBlocked = config.dataset.submitBlocked === '1';
    const hasPassword = config.dataset.hasPassword === '1';
    const bankLabel = config.dataset.bankName || '';
    const accountMask = config.dataset.accountMask || '';
    const amountNumeric = new AutoNumeric('#amount_input_field', {
        currencySymbol: '$',
        decimalCharacter: '.',
        digitGroupSeparator: ',',
        decimalPlaces: 2,
        minimumValue: '0',
        modifyValueOnWheel: false,
    });

    const formatMoney = (value) => new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value) || 0);

    const getAmount = () => Number(amountNumeric.getNumber()) || 0;

    const setAmountError = (message = '') => {
        if (!amountError) return;
        amountError.textContent = message;
        amountError.hidden = !message;
        amountInput.classList.toggle('has-error', Boolean(message));
    };

    const updateReview = () => {
        const amount = getAmount();
        const fee = amount * feeRate;
        const net = Math.max(0, amount - fee);
        const reviewAmount = document.getElementById('review_amount');
        const reviewFee = document.getElementById('review_fee');
        const reviewNet = document.getElementById('review_net');
        if (reviewAmount) reviewAmount.textContent = `${formatMoney(amount)} USD`;
        if (reviewFee) reviewFee.innerHTML = `${formatMoney(fee)} USD <small>(0%)</small>`;
        if (reviewNet) reviewNet.textContent = `${formatMoney(net)} USD`;
        setAmountError(amount > maximumAmount ? `Số tiền tối đa có thể nhập lúc này là ${formatMoney(maximumAmount)} USD.` : '');
    };

    amountInput.addEventListener('input', updateReview);
    maxButton?.addEventListener('click', function () {
        amountNumeric.set(maximumAmount);
        updateReview();
        amountInput.focus();
    });

    document.querySelectorAll('.password-toggle').forEach((button) => {
        button.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            if (!input) return;
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', showing);
                icon.classList.toggle('fa-eye-slash', !showing);
            }
        });
    });

    const validate = () => {
        const amount = getAmount();
        setAmountError('');
        if (passwordError) {
            passwordError.hidden = true;
            passwordError.textContent = '';
        }

        if (submitBlocked) return false;
        if (amount <= 0) {
            setAmountError(trans.VuiLongNhapSoTienRut);
            amountInput.focus();
            return false;
        }
        if (amount > maximumAmount) {
            setAmountError(`Số tiền tối đa có thể nhập lúc này là ${formatMoney(maximumAmount)} USD.`);
            amountInput.focus();
            return false;
        }
        if (!usernameBank?.value || !bankName?.value || !accountNumber?.value) {
            notification('warning', trans.VuiLongNhapDayDuThongTinNganHang, trans.CanhBao);
            return false;
        }
        if (!transactionPassword?.value) {
            notification('warning', 'Vui lòng nhập mật khẩu giao dịch.', trans.CanhBao);
            transactionPassword?.focus();
            return false;
        }
        if (!hasPassword && (!confirmTransactionPassword?.value || confirmTransactionPassword.value !== transactionPassword.value)) {
            if (passwordError) {
                passwordError.textContent = trans.XacNhanMatKhauGiaoDichKhongKhop;
                passwordError.hidden = false;
            }
            confirmTransactionPassword?.focus();
            return false;
        }
        return true;
    };

    const setLoading = (loading) => {
        submitButton.disabled = loading || submitBlocked;
        submitButton.classList.toggle('is-loading', loading);
        submitButton.innerHTML = loading
            ? `<span>${trans.DangXuLy}</span><i class="fa-solid fa-spinner"></i>`
            : '<span>Xác nhận rút tiền</span><i class="fa-solid fa-arrow-right"></i>';
    };

    const sendWithdrawRequest = async (amount) => {
        const response = await fetch(route_handle_withdraw, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                amount,
                username_bank: usernameBank.value,
                bank_name: bankName.value,
                account_number: accountNumber.value,
                transaction_password: transactionPassword.value,
                confirm_transaction_password: confirmTransactionPassword?.value || '',
            }),
        });

        const body = await response.text();
        try {
            return JSON.parse(body);
        } catch {
            throw new Error('Unexpected withdrawal response');
        }
    };

    submitButton.addEventListener('click', async function () {
        if (!validate()) return;

        const amount = getAmount();
        const net = Math.max(0, amount - (amount * feeRate));
        const destination = [bankLabel, accountMask].filter(Boolean).join(' · ');
        const confirmed = await AppDialog.confirm({
            title: trans.XacNhanRutTien,
            text: `Rút ${formatMoney(amount)} USD về ${destination}. Phí xử lý 0%, thực nhận ${formatMoney(net)} USD.`,
            confirmText: trans.XacNhan,
            cancelText: trans.Huy,
        });
        if (!confirmed) return;

        setLoading(true);
        try {
            const result = await sendWithdrawRequest(getAmount());
            if (result.status === 200) {
                const acknowledged = await AppDialog.alert({
                    title: trans.ThanhCong,
                    text: result.message,
                    icon: 'success',
                    button: 'OK',
                });
                if (acknowledged) location.reload();
                return;
            }
            notification('warning', result.message || trans.LoiKetNoi, trans.CanhBao);
        } catch (error) {
            console.error(error);
            notification('warning', trans.LoiKetNoi, trans.CanhBao);
        } finally {
            setLoading(false);
        }
    });

    submitButton.disabled = submitBlocked;
    updateReview();
});
