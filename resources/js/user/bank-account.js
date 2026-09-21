import Modal from 'bootstrap/js/dist/modal';
import SlimSelect from 'slim-select';

const notify = (type, message, title = 'Thông báo') => {
    if (typeof window.notification === 'function') {
        window.notification(type, message, title);
        return;
    }

    if (window.AppDialog?.alert) {
        window.AppDialog.alert({
            icon: type === 'success' ? 'success' : type === 'error' ? 'error' : 'warning',
            title,
            text: message,
            confirmText: 'Đóng',
        });
    }
};

const maskAccountNumber = (value) => {
    const accountNumber = String(value || '');
    if (!accountNumber) return 'Chưa nhập';
    if (accountNumber.length <= 4) return '•'.repeat(accountNumber.length);

    return `${'•'.repeat(Math.min(8, accountNumber.length - 4))}${accountNumber.slice(-4)}`;
};

const initBankAccount = (root) => {
    if (root.dataset.bankAccountReady === 'true') return;
    root.dataset.bankAccountReady = 'true';

    const dialogElement = root.querySelector('[data-bank-account-dialog]');
    if (!dialogElement) return;

    const modal = Modal.getOrCreateInstance(dialogElement);

    root.querySelectorAll('[data-bank-account-open]').forEach((button) => {
        button.addEventListener('click', () => modal.show());
    });

    const form = root.querySelector('[data-bank-account-form]');
    const linkedView = root.querySelector('[data-bank-linked-view]');
    if (root.dataset.linked === 'true' || !form) return;

    const bankSelect = root.querySelector('[data-bank-select]');
    const accountNumberInput = root.querySelector('[data-bank-account-number]');
    const ownerInput = root.querySelector('[data-bank-owner]');
    const passwordInput = root.querySelector('[data-bank-password]');
    const passwordConfirmInput = root.querySelector('[data-bank-password-confirm]');
    const submitButton = root.querySelector('[data-bank-submit]');
    const submitLabel = root.querySelector('[data-bank-submit-label]');
    const formStatus = root.querySelector('[data-bank-form-status]');
    const reviewBank = root.querySelector('[data-bank-review-bank]');
    const reviewOwner = root.querySelector('[data-bank-review-owner]');
    const reviewAccount = root.querySelector('[data-bank-review-account]');
    const progressSteps = [...root.querySelectorAll('[data-bank-progress-step]')];
    const endpoint = root.dataset.endpoint;

    if (!bankSelect || !submitButton || !endpoint) return;
    let isSubmitting = false;

    const fieldByName = (name) => form.querySelector(`[name="${name}"]`)?.closest('.bank-field');

    const setFieldError = (name, message = '') => {
        const field = fieldByName(name);
        const error = form.querySelector(`[data-bank-error="${name}"]`);
        field?.classList.toggle('is-invalid', Boolean(message));
        if (error) error.textContent = message;
    };

    const clearErrors = () => {
        form.querySelectorAll('.bank-field.is-invalid').forEach((field) => field.classList.remove('is-invalid'));
        form.querySelectorAll('[data-bank-error]').forEach((error) => {
            error.textContent = '';
        });
        if (formStatus) {
            formStatus.hidden = true;
            formStatus.textContent = '';
        }
    };

    const showFormStatus = (message) => {
        if (!formStatus) return;
        formStatus.textContent = message;
        formStatus.hidden = false;
        formStatus.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    const updateReview = () => {
        if (reviewBank) reviewBank.textContent = bankSelect.value || 'Chưa chọn';
        if (reviewOwner) reviewOwner.textContent = ownerInput?.value.trim() || 'Chưa nhập';
        if (reviewAccount) reviewAccount.textContent = maskAccountNumber(accountNumberInput?.value);
    };

    const updateProgress = () => {
        const bankComplete = Boolean(bankSelect.value && accountNumberInput?.value.trim());
        const ownerComplete = Boolean(
            ownerInput?.value.trim()
            && passwordInput?.value
            && passwordConfirmInput?.value
            && passwordInput.value === passwordConfirmInput.value
        );
        const activeStep = !bankComplete ? 1 : !ownerComplete ? 2 : 3;

        progressSteps.forEach((progressStep) => {
            const step = Number(progressStep.dataset.bankProgressStep);
            progressStep.classList.toggle('is-active', step === activeStep);
            progressStep.classList.toggle('is-complete', step < activeStep);

            if (step === activeStep) {
                progressStep.setAttribute('aria-current', 'step');
            } else {
                progressStep.removeAttribute('aria-current');
            }
        });
    };

    const validate = () => {
        clearErrors();
        const invalid = [];

        if (!bankSelect.value) {
            setFieldError('bank_name', 'Vui lòng chọn ngân hàng.');
            invalid.push(bankSelect);
        }

        if (!accountNumberInput?.value.trim()) {
            setFieldError('account_number', 'Vui lòng nhập số tài khoản.');
            invalid.push(accountNumberInput);
        }

        if (!ownerInput?.value.trim()) {
            setFieldError('username_bank', 'Vui lòng nhập tên chủ tài khoản.');
            invalid.push(ownerInput);
        }

        if (!passwordInput?.value) {
            setFieldError('transaction_password', 'Vui lòng nhập mật khẩu giao dịch.');
            invalid.push(passwordInput);
        }

        if (!passwordConfirmInput?.value) {
            setFieldError('confirm_transaction_password', 'Vui lòng xác nhận mật khẩu giao dịch.');
            invalid.push(passwordConfirmInput);
        } else if (passwordInput?.value !== passwordConfirmInput.value) {
            setFieldError('confirm_transaction_password', 'Mật khẩu xác nhận không khớp.');
            invalid.push(passwordConfirmInput);
        }

        if (!invalid.length) return true;

        const firstInvalid = invalid[0];
        if (firstInvalid === bankSelect) {
            root.querySelector('.bank-field.is-invalid .ss-main')?.focus();
        } else {
            firstInvalid?.focus();
        }
        return false;
    };

    const setSubmitting = (submitting) => {
        isSubmitting = submitting;
        submitButton.disabled = submitting || root.dataset.linked === 'true';

        if (submitting) {
            submitButton.dataset.originalHtml = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i><span>Đang liên kết...</span>';
            return;
        }

        if (submitButton.dataset.originalHtml) {
            submitButton.innerHTML = submitButton.dataset.originalHtml;
            delete submitButton.dataset.originalHtml;
        }
    };

    const updateLinkedCard = ({ bankName, ownerName, accountNumber }) => {
        root.dataset.linked = 'true';

        const card = root.querySelector('.bank-account-card');
        const summaryName = root.querySelector('[data-bank-summary-name]');
        const summaryAccount = root.querySelector('[data-bank-summary-account]');
        const summaryOwner = root.querySelector('[data-bank-summary-owner]');
        const summaryMeta = root.querySelector('[data-bank-summary-meta]');
        const summaryEmpty = root.querySelector('[data-bank-summary-empty]');
        const summaryStatus = root.querySelector('[data-bank-summary-status]');
        const summaryAction = root.querySelector('[data-bank-summary-action]');
        const dialogTitle = root.querySelector('[data-bank-dialog-title]');
        const securityCopy = root.querySelector('[data-bank-security-copy]');
        const viewBank = root.querySelector('[data-bank-view-bank]');
        const viewOwner = root.querySelector('[data-bank-view-owner]');
        const viewAccount = root.querySelector('[data-bank-view-account]');
        const progress = root.querySelector('.bank-account-progress');

        card?.classList.remove('is-unlinked');
        card?.classList.add('is-linked', 'is-locked');
        if (card) {
            card.disabled = false;
            card.setAttribute('data-bank-account-open', '');
            card.setAttribute('aria-haspopup', 'dialog');
            card.removeAttribute('aria-disabled');
        }
        if (summaryName) summaryName.textContent = bankName;
        if (summaryAccount) summaryAccount.textContent = maskAccountNumber(accountNumber);
        if (summaryOwner) summaryOwner.textContent = ownerName;
        if (summaryMeta) summaryMeta.hidden = false;
        if (summaryEmpty) summaryEmpty.hidden = true;

        if (summaryStatus) {
            summaryStatus.innerHTML = '<i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Đã liên kết</span>';
        }
        if (summaryAction) {
            summaryAction.innerHTML = 'Xem thông tin <i class="fa-solid fa-eye" aria-hidden="true"></i>';
        }
        if (dialogTitle) dialogTitle.textContent = 'Tài khoản ngân hàng đã liên kết';
        if (securityCopy) {
            securityCopy.textContent = 'Tài khoản ngân hàng đã liên kết và không thể chỉnh sửa.';
        }
        if (submitLabel) submitLabel.textContent = 'Đã liên kết';
        submitButton.disabled = true;
        if (viewBank) viewBank.textContent = bankName;
        if (viewOwner) viewOwner.textContent = ownerName;
        if (viewAccount) viewAccount.textContent = accountNumber;
        if (linkedView) linkedView.hidden = false;
        if (progress) progress.hidden = true;
        form.hidden = true;
    };

    const slimSelect = new SlimSelect({
        select: bankSelect,
        settings: {
            searchPlaceholder: 'Tìm kiếm ngân hàng...',
            searchText: 'Không tìm thấy ngân hàng',
            showSearch: true,
            contentLocation: dialogElement,
            contentPosition: 'fixed',
        },
        events: {
            afterChange: () => {
                setFieldError('bank_name');
                updateReview();
                updateProgress();
            },
        },
    });

    root.querySelectorAll('[data-bank-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const fieldId = button.dataset.bankPasswordToggle;
            const field = root.querySelector(`#${CSS.escape(fieldId)}`);
            const icon = button.querySelector('i');
            if (!field) return;

            const showPassword = field.type === 'password';
            field.type = showPassword ? 'text' : 'password';
            icon?.classList.toggle('fa-eye', !showPassword);
            icon?.classList.toggle('fa-eye-slash', showPassword);
        });
    });

    [accountNumberInput, ownerInput, passwordInput, passwordConfirmInput].forEach((input) => {
        input?.addEventListener('input', () => {
            setFieldError(input.name);
            updateReview();
            updateProgress();
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (root.dataset.linked === 'true') return;
        if (isSubmitting || !validate()) return;

        const payload = {
            username_bank: ownerInput.value,
            bank_name: bankSelect.value,
            account_number: accountNumberInput.value,
            transaction_password: passwordInput.value,
        };

        setSubmitting(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });
            const result = await response.json();

            if (result.status !== 200) {
                const message = result.message || 'Không thể liên kết tài khoản ngân hàng.';
                showFormStatus(message);
                notify('warning', message);
                return;
            }

            updateLinkedCard({
                bankName: payload.bank_name,
                ownerName: payload.username_bank,
                accountNumber: payload.account_number,
            });
            passwordInput.value = '';
            passwordConfirmInput.value = '';
            clearErrors();
            modal.hide();
            notify('success', result.message || 'Liên kết tài khoản ngân hàng thành công!', 'Thành công');
        } catch (error) {
            console.error('Unable to link bank account:', error);
            const message = 'Có lỗi xảy ra, vui lòng thử lại.';
            showFormStatus(message);
            notify('error', message, 'Không thể liên kết');
        } finally {
            setSubmitting(false);
        }
    });

    dialogElement.addEventListener('shown.bs.modal', () => {
        updateReview();
        updateProgress();
    });

    dialogElement.addEventListener('hidden.bs.modal', () => {
        clearErrors();
        passwordInput.value = '';
        passwordConfirmInput.value = '';
        passwordInput.type = 'password';
        passwordConfirmInput.type = 'password';
    });

    form.querySelector('.bank-account-form__scroll')?.addEventListener('scroll', () => {
        slimSelect.close();
    }, { passive: true });

    updateReview();
    updateProgress();

    if (root.dataset.autoOpen === 'true' && root.dataset.linked !== 'true') {
        requestAnimationFrame(() => modal.show());
    }

};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bank-account]').forEach(initBankAccount);
});
