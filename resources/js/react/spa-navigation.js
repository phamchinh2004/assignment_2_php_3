const USER_PATHS = new Set([
    '/',
    '/balance-fluctuation',
    '/distribution',
    '/withdraw',
    '/personal-information',
    '/vip',
    '/me',
    '/order',
]);

const GUEST_PATHS = new Set([
    '/login',
    '/register',
    '/forgot-password',
]);

const ADMIN_EXACT_PATHS = new Set([
    '/admin',
    '/admin/chat-panel',
    '/admin/tong-doanh-thu',
    '/admin/statistical/revenue',
    '/admin/doanh-thu-theo-nhan-vien',
    '/admin/doanh-thu-tu-khach-hang',
    '/admin/doanh-thu-ban-than',
    '/admin/withdraw-transaction',
    '/admin/deposit-transaction',
    '/admin/frozen-order-settings',
    '/admin/lucky-wheel-rewards',
]);

const ADMIN_PATTERNS = [
    /^\/admin\/(?:banner|partner|language|rank|section|manager_setting)(?:\/create|\/[^/]+(?:\/edit)?)?$/,
    /^\/admin\/order-status-timing(?:\/[^/]+\/edit)?$/,
    /^\/admin\/feature-announcements(?:\/create|\/[^/]+(?:\/edit)?)?$/,
    /^\/admin\/order-reports(?:\/[^/]+)?$/,
    /^\/admin\/order-distributions(?:\/[^/]+)?$/,
    /^\/admin\/staffs?(?:\/create|\/edit-permissions\/[^/]+|\/[^/]+(?:\/edit)?)?$/,
    /^\/admin\/user(?:\/create|\/frozen-order\/[^/]+|\/[^/]+(?:\/edit)?)?$/,
    /^\/admin\/order(?:\/create|\/[^/]+(?:\/edit)?)?$/,
];

export function isSpaNavigationTarget(url, surface) {
    if (!(url instanceof URL) || url.origin !== window.location.origin) return false;

    const path = normalizePath(url.pathname);
    if (surface === 'user') return USER_PATHS.has(path) || /^\/order\/[^/]+$/.test(path);
    if (surface === 'guest') return GUEST_PATHS.has(path);
    if (surface !== 'admin') return false;

    return ADMIN_EXACT_PATHS.has(path) || ADMIN_PATTERNS.some((pattern) => pattern.test(path));
}

export function syncLegacyNavigationState(surface, pathname) {
    const path = normalizePath(pathname);

    if (surface === 'user') {
        document.querySelectorAll('footer .footer-item[href]').forEach((anchor) => {
            const href = new URL(anchor.href, window.location.origin);
            const target = normalizePath(href.pathname);
            const isProfile = target === '/me' && ['/me', '/vip', '/personal-information', '/withdraw'].includes(path);
            const isOrder = target === '/order' && (path === '/order' || path.startsWith('/order/'));
            const active = target === path || isProfile || isOrder;
            anchor.classList.toggle('active', active);
            if (active) anchor.setAttribute('aria-current', 'page');
            else anchor.removeAttribute('aria-current');
        });
        return;
    }

    if (surface === 'admin') {
        const links = Array.from(document.querySelectorAll('.admin-menu-link[href], #accordionSidebar a[href]'))
            .filter((anchor) => {
                try {
                    return new URL(anchor.href, window.location.origin).origin === window.location.origin;
                } catch (_) {
                    return false;
                }
            });

        let best = null;
        let bestLength = -1;
        links.forEach((anchor) => {
            const target = normalizePath(new URL(anchor.href, window.location.origin).pathname);
            const matches = path === target || (target !== '/admin' && path.startsWith(`${target}/`));
            if (matches && target.length > bestLength) {
                best = anchor;
                bestLength = target.length;
            }
        });

        links.forEach((anchor) => {
            const active = anchor === best;
            anchor.classList.toggle('active', active);
            if (active) anchor.setAttribute('aria-current', 'page');
            else anchor.removeAttribute('aria-current');
        });
    }
}

function normalizePath(pathname) {
    if (!pathname || pathname === '/') return '/';
    return pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname;
}
