import Modal from 'bootstrap/js/dist/modal';

const page = document.querySelector('[data-personal-profile-page]');

if (page) {
    document.body.classList.add('personal-information-active');

    const notify = (type, message, title = 'Thông báo') => {
        if (!message) return;

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

    const avatarModalElement = document.getElementById('profileAvatarModal');
    const avatarModal = avatarModalElement ? Modal.getOrCreateInstance(avatarModalElement) : null;
    const avatarFileInput = document.getElementById('avatarFile');
    const avatarPreview = document.getElementById('avatarEditorPreview');
    const avatarPreviewState = page.querySelector('[data-avatar-preview-state]');
    const avatarSubmit = page.querySelector('[data-avatar-submit]');
    const avatarMessage = page.querySelector('[data-avatar-message]');
    const avatarProgress = page.querySelector('[data-avatar-progress]');
    const avatarProgressBar = page.querySelector('[data-avatar-progress-bar]');
    const avatarProgressText = page.querySelector('[data-avatar-progress-text]');
    const avatarEndpoint = page.dataset.avatarUploadRoute;
    const allowedAvatarTypes = new Set(['image/jpeg', 'image/png', 'image/gif']);

    let selectedAvatar = null;
    let previewObjectUrl = null;
    let avatarUploading = false;
    let currentAvatarUrl = avatarPreview?.getAttribute('src') || '';

    const setAvatarMessage = (message = '', type = '') => {
        if (!avatarMessage) return;

        avatarMessage.textContent = message;
        avatarMessage.hidden = !message;
        avatarMessage.classList.toggle('is-error', type === 'error');
        avatarMessage.classList.toggle('is-success', type === 'success');
    };

    const setAvatarProgress = (visible, percent = 0) => {
        if (!avatarProgress) return;

        avatarProgress.hidden = !visible;
        const safePercent = Math.max(0, Math.min(100, percent));
        if (avatarProgressBar) avatarProgressBar.style.width = safePercent + '%';
        if (avatarProgressText) avatarProgressText.textContent = 'Đang tải lên... ' + Math.round(safePercent) + '%';
    };

    const resetAvatarSelection = () => {
        if (previewObjectUrl) {
            URL.revokeObjectURL(previewObjectUrl);
            previewObjectUrl = null;
        }

        selectedAvatar = null;
        if (avatarFileInput) avatarFileInput.value = '';
        if (avatarPreview) avatarPreview.src = currentAvatarUrl;
        if (avatarPreviewState) avatarPreviewState.textContent = 'Ảnh hiện tại';
        if (avatarSubmit) avatarSubmit.disabled = true;
        setAvatarMessage();
        setAvatarProgress(false);
    };

    const validateAvatar = (file) => {
        if (!file) return 'Vui lòng chọn ảnh đại diện.';
        if (!allowedAvatarTypes.has(file.type)) return 'Chỉ hỗ trợ ảnh JPG, PNG hoặc GIF.';
        if (file.size > 2 * 1024 * 1024) return 'Ảnh đại diện không được vượt quá 2MB.';
        return '';
    };

    const markSignalComplete = (element) => {
        if (!element) return;

        element.classList.add('is-complete');
        const icon = element.querySelector('i');
        icon?.classList.remove('fa-circle');
        icon?.classList.add('fa-circle-check');
    };

    page.querySelectorAll('[data-avatar-open]').forEach((button) => {
        button.addEventListener('click', () => {
            resetAvatarSelection();
            avatarModal?.show();
        });
    });

    avatarFileInput?.addEventListener('change', () => {
        const file = avatarFileInput.files?.[0] || null;
        const validationMessage = validateAvatar(file);

        if (validationMessage) {
            selectedAvatar = null;
            if (avatarSubmit) avatarSubmit.disabled = true;
            setAvatarMessage(validationMessage, 'error');
            return;
        }

        if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = URL.createObjectURL(file);
        selectedAvatar = file;
        if (avatarPreview) avatarPreview.src = previewObjectUrl;
        if (avatarPreviewState) avatarPreviewState.textContent = 'Xem trước ảnh mới';
        if (avatarSubmit) avatarSubmit.disabled = false;
        setAvatarMessage();
    });

    avatarSubmit?.addEventListener('click', () => {
        if (avatarUploading) return;

        const validationMessage = validateAvatar(selectedAvatar);
        if (validationMessage) {
            setAvatarMessage(validationMessage, 'error');
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!csrfToken || !avatarEndpoint) {
            setAvatarMessage('Không thể bắt đầu cập nhật ảnh. Vui lòng tải lại trang.', 'error');
            return;
        }

        avatarUploading = true;
        avatarSubmit.disabled = true;
        avatarSubmit.dataset.originalHtml = avatarSubmit.innerHTML;
        avatarSubmit.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i><span>Đang cập nhật...</span>';
        setAvatarMessage();
        setAvatarProgress(true, 0);

        const formData = new FormData();
        formData.append('avatar', selectedAvatar);
        formData.append('_token', csrfToken);

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) return;
            setAvatarProgress(true, (event.loaded / event.total) * 100);
        });

        xhr.addEventListener('load', () => {
            let response = {};

            try {
                response = JSON.parse(xhr.responseText || '{}');
            } catch (error) {
                response = {};
            }

            if (xhr.status >= 200 && xhr.status < 300 && response.status === 200 && response.avatar_url) {
                currentAvatarUrl = response.avatar_url;
                page.querySelectorAll('[data-profile-avatar]').forEach((image) => {
                    image.src = currentAvatarUrl;
                });
                if (avatarPreview) avatarPreview.src = currentAvatarUrl;
                if (avatarPreviewState) avatarPreviewState.textContent = 'Ảnh đã cập nhật';
                markSignalComplete(page.querySelector('[data-avatar-hero-status]'));
                setAvatarProgress(true, 100);
                setAvatarMessage(response.message || 'Cập nhật ảnh đại diện thành công!', 'success');
                notify('success', response.message || 'Cập nhật ảnh đại diện thành công!', 'Thành công');

                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                    previewObjectUrl = null;
                }

                selectedAvatar = null;
                if (avatarFileInput) avatarFileInput.value = '';
                window.setTimeout(() => avatarModal?.hide(), 550);
                return;
            }

            const validationError = response.errors?.avatar?.[0];
            const message = validationError || response.message || 'Không thể cập nhật ảnh đại diện. Vui lòng thử lại.';
            setAvatarMessage(message, 'error');
            notify('error', message, 'Cập nhật thất bại');
        });

        xhr.addEventListener('error', () => {
            const message = 'Kết nối bị gián đoạn khi tải ảnh. Vui lòng thử lại.';
            setAvatarMessage(message, 'error');
            notify('error', message, 'Cập nhật thất bại');
        });

        xhr.addEventListener('loadend', () => {
            avatarUploading = false;
            setAvatarProgress(false);

            if (avatarSubmit.dataset.originalHtml) {
                avatarSubmit.innerHTML = avatarSubmit.dataset.originalHtml;
                delete avatarSubmit.dataset.originalHtml;
            }

            avatarSubmit.disabled = !selectedAvatar;
        });

        xhr.open('POST', avatarEndpoint);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(formData);
    });

    avatarModalElement?.addEventListener('hidden.bs.modal', () => {
        if (!avatarUploading) resetAvatarSelection();
    });

    const bankRoot = page.querySelector('[data-bank-account]');
    const bankStatus = page.querySelector('[data-bank-hero-status]');
    const transactionStatus = page.querySelector('[data-transaction-hero-status]');
    const transactionSetupAction = page.querySelector('[data-open-bank-account]');
    const paymentPanel = document.getElementById('payment-method');

    const syncBankLinkedState = () => {
        if (bankRoot?.dataset.linked !== 'true') return;

        markSignalComplete(bankStatus);
        markSignalComplete(transactionStatus);

        if (transactionSetupAction) {
            transactionSetupAction.dataset.transactionReady = 'true';
            const helper = transactionSetupAction.querySelector('.profile-action-row__copy small');
            const meta = transactionSetupAction.querySelector('.profile-action-row__meta');
            if (helper) helper.textContent = 'Đã thiết lập cho giao dịch';
            if (meta) {
                meta.textContent = 'Đã có';
                meta.classList.remove('is-pending');
                meta.classList.add('is-complete');
            }
        }
    };

    transactionSetupAction?.addEventListener('click', () => {
        if (transactionSetupAction.dataset.transactionReady === 'true') {
            const transactionModal = document.getElementById('changeTransactionPasswordModal');
            if (transactionModal) Modal.getOrCreateInstance(transactionModal).show();
            return;
        }

        paymentPanel?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.setTimeout(() => bankRoot?.querySelector('[data-bank-account-open]')?.click(), 280);
    });

    if (bankRoot) {
        syncBankLinkedState();
        new MutationObserver(syncBankLinkedState).observe(bankRoot, {
            attributes: true,
            attributeFilter: ['data-linked'],
        });
    }

    const warehouseModalElement = document.getElementById('profileWarehouseModal');
    const warehouseForm = warehouseModalElement?.querySelector('form');

    if (page.dataset.openWarehouseOnLoad === 'true' && warehouseModalElement) {
        Modal.getOrCreateInstance(warehouseModalElement).show();
    }

    warehouseForm?.addEventListener('submit', () => {
        if (!warehouseForm.checkValidity()) return;

        const submitButton = warehouseForm.querySelector('button[type="submit"]');
        if (!submitButton || submitButton.disabled) return;

        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i><span>Đang lưu...</span>';
    });

    const flashSuccess = page.dataset.flashSuccess?.trim();
    if (flashSuccess) {
        notify('success', flashSuccess, 'Đã cập nhật');
    }
}
