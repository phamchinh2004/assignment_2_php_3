export function spaNavigate(url, options = {}) {
    if (typeof window.__spaNavigate === 'function') {
        return window.__spaNavigate(url, options);
    }

    window.location.assign(new URL(url, window.location.href).toString());
    return false;
}

export function spaRefresh() {
    if (typeof window.__spaRefresh === 'function') {
        window.__spaRefresh();
        return true;
    }

    window.location.reload();
    return false;
}

export function spaCommitBootstrap(url, payload, options = {}) {
    if (typeof window.__spaCommitBootstrap === 'function') {
        return window.__spaCommitBootstrap(url, payload, options);
    }

    return false;
}

export async function spaSubmitForm(form, submitter = null) {
    const action = form.action || window.location.href;
    const method = String(form.method || 'POST').toUpperCase();
    const formData = submitter ? new FormData(form, submitter) : new FormData(form);
    const response = await fetch(action, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'text/html,application/xhtml+xml',
            'X-Requested-With': 'XMLHttpRequest',
            'X-React-Navigation': '1',
        },
        body: ['GET', 'HEAD'].includes(method) ? undefined : formData,
    });

    if (!response.ok) {
        throw new Error(`Form submission returned HTTP ${response.status}`);
    }

    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
        window.location.assign(response.url || action);
        return null;
    }

    const payload = await response.json();
    if (!spaCommitBootstrap(response.url || window.location.href, payload, { replace: true })) {
        spaRefresh();
    }

    return payload;
}

export async function spaGetAction(url) {
    const target = new URL(url, window.location.href);

    try {
        const response = await fetch(target.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-React-Navigation': '1',
            },
        });
        const contentType = response.headers.get('content-type') || '';
        if (!response.ok || !contentType.includes('application/json')) {
            throw new Error(`SPA action returned HTTP ${response.status}`);
        }

        const payload = await response.json();
        const finalUrl = new URL(response.url || window.location.href, window.location.href);
        if (!spaCommitBootstrap(finalUrl.toString(), payload, { replace: true })) {
            const currentKey = `${window.location.pathname}${window.location.search}`;
            const finalKey = `${finalUrl.pathname}${finalUrl.search}`;
            if (finalKey !== currentKey) spaNavigate(finalUrl.toString(), { replace: true });
            else spaRefresh();
        }

        return payload;
    } catch (error) {
        console.error('SPA action failed, falling back to a full request.', error);
        window.location.assign(target.toString());
        return null;
    }
}
