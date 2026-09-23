const form = document.getElementById('distribution-transition-form');

if (form) {
    const button = form.querySelector('button[type="submit"]');
    const feedback = document.getElementById('transition-feedback');
    const reloadLink = document.getElementById('distribution-conflict-reload');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (button.disabled) return;

        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        feedback.hidden = true;
        reloadLink.hidden = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                feedback.textContent = payload.message || 'Không thể cập nhật trạng thái. Vui lòng thử lại.';
                feedback.hidden = false;
                if (response.status === 409) reloadLink.hidden = false;
                return;
            }

            // A fresh server render includes the new status, allowed action and status history.
            window.location.reload();
        } catch (error) {
            feedback.textContent = 'Không thể kết nối với máy chủ. Vui lòng kiểm tra mạng và thử lại.';
            feedback.hidden = false;
        } finally {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        }
    });
}
