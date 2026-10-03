const form = document.getElementById('adminAccountForm');

if (form) {
    const modal = document.getElementById('updateAccountModal');
    const submit = document.getElementById('adminAccountSubmit');
    const status = document.getElementById('adminAccountStatus');
    const fields = {
        full_name: [document.getElementById('adminAccountFullName'), document.getElementById('adminAccountFullNameError')],
        email: [document.getElementById('adminAccountEmail'), document.getElementById('adminAccountEmailError')],
    };
    let submitting = false;

    function clearErrors() {
        Object.values(fields).forEach(([input, error]) => {
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
            error.textContent = '';
        });
        status.textContent = '';
        status.classList.add('d-none');
        status.classList.remove('alert-success', 'alert-danger');
    }

    Object.values(fields).forEach(([input, error]) => {
        input.addEventListener('input', () => {
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
            error.textContent = '';
            status.classList.add('d-none');
        });
    });

    window.jQuery(modal).on('show.bs.modal', () => {
        if (!submitting) {
            form.reset();
            clearErrors();
        }
    }).on('shown.bs.modal', () => fields.full_name[0].focus());

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (submitting) return;
        clearErrors();
        submitting = true;
        submit.disabled = true;
        submit.textContent = 'Đang lưu...';
        Object.values(fields).forEach(([input]) => { input.readOnly = true; });

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                body: new FormData(form),
            });
            const payload = await response.json().catch(() => ({}));

            if (response.status === 422) {
                Object.entries(payload.errors || {}).forEach(([name, messages]) => {
                    const [input, error] = fields[name] || [];
                    if (!input) return;
                    input.classList.add('is-invalid');
                    input.setAttribute('aria-invalid', 'true');
                    error.textContent = Array.isArray(messages) ? messages[0] : messages;
                });
                Object.values(fields).find(([input]) => input.classList.contains('is-invalid'))?.[0].focus();
                return;
            }

            if (!response.ok) {
                throw new Error(payload.message || 'Không thể cập nhật thông tin tài khoản. Vui lòng thử lại.');
            }

            Object.entries(fields).forEach(([name, [input]]) => {
                input.value = payload.user[name] ?? '';
                input.defaultValue = input.value;
            });
            status.textContent = payload.message;
            status.classList.remove('d-none');
            status.classList.add('alert-success');
        } catch (error) {
            status.textContent = error.message || 'Không thể cập nhật thông tin tài khoản. Vui lòng thử lại.';
            status.classList.remove('d-none');
            status.classList.add('alert-danger');
        } finally {
            submitting = false;
            submit.disabled = false;
            submit.textContent = 'Lưu thay đổi';
            Object.values(fields).forEach(([input]) => { input.readOnly = false; });
        }
    });
}
