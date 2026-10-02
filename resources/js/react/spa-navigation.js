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
    /^\/admin\/bug-reports(?:\/[^/]+)?$/,
    /^\/admin\/order-distributions(?:\/[^/]+)?$/,
    /^\/admin\/staffs?(?:\/create|\/edit-permissions\/[^/]+|\/[^/]+(?:\/edit)?)?$/,
    /^\/admin\/user(?:\/create|\/frozen-order\/[^/]+|\/[^/]+(?:\/edit)?)?$/,
    /^\/admin\/order(?:\/create|\/[^/]+(?:\/edit)?)?$/,
];

const ADMIN_BREADCRUMB_RULES = [
    { pattern: /^\/admin$/, section: 'Tổng quan', page: 'Dashboard' },
    { pattern: /^\/admin\/(?:tong-doanh-thu|statistical\/revenue|doanh-thu-theo-nhan-vien|doanh-thu-tu-khach-hang|doanh-thu-ban-than)$/, section: 'Tổng quan', page: 'Thống kê' },
    { pattern: /^\/admin\/chat-panel$/, section: 'Vận hành', page: 'Quản lý tin nhắn' },
    { pattern: /^\/admin\/user(?:\/|$)/, section: 'Khách hàng', page: 'Quản lý khách hàng' },
    { pattern: /^\/admin\/(?:withdraw-transaction|deposit-transaction|lucky-wheel-rewards)(?:\/|$)/, section: 'Giao dịch', page: 'Quản lý GDKH' },
    { pattern: /^\/admin\/staffs?(?:\/|$)/, section: 'Nhân sự', page: 'Quản lý nhân viên' },
    { pattern: /^\/admin\/order-distributions(?:\/|$)/, section: 'Đơn hàng', page: 'Phân phối đơn hàng' },
    { pattern: /^\/admin\/order-reports(?:\/|$)/, section: 'Đơn hàng', page: 'Đơn hàng bị báo cáo' },
    { pattern: /^\/admin\/bug-reports(?:\/|$)/, section: 'Vận hành', page: 'Báo lỗi hệ thống' },
    { pattern: /^\/admin\/order-status-timing(?:\/|$)/, section: 'Cấu hình', page: 'Thời gian đơn hàng' },
    { pattern: /^\/admin\/order(?:\/|$)/, section: 'Đơn hàng', page: 'Quản lý đơn hàng' },
    { pattern: /^\/admin\/feature-announcements(?:\/|$)/, section: 'Cấu hình', page: 'Thông báo tính năng' },
    { pattern: /^\/admin\/frozen-order-settings$/, section: 'Cấu hình', page: 'Thời gian xử lý đơn hàng' },
    { pattern: /^\/admin\/rank(?:\/|$)/, section: 'Cấu hình', page: 'Quản lý cấp độ' },
    { pattern: /^\/admin\/manager_setting(?:\/|$)/, section: 'Cấu hình', page: 'Quản lý chức năng' },
    { pattern: /^\/admin\/banner(?:\/|$)/, section: 'Nội dung', page: 'Quản lý banner' },
    { pattern: /^\/admin\/section(?:\/|$)/, section: 'Nội dung', page: 'Nội dung website' },
    { pattern: /^\/admin\/partner(?:\/|$)/, section: 'Nội dung', page: 'Quản lý đối tác' },
    { pattern: /^\/admin\/language(?:\/|$)/, section: 'Nội dung', page: 'Quản lý ngôn ngữ' },
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
        const links = Array.from(document.querySelectorAll(
            '.admin-menu-link[href], #accordionSidebar a.admin-sidebar__link[href], #accordionSidebar a.admin-sidebar__submenu-link[href]',
        ))
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
            const isModernSidebarLink = anchor.classList.contains('admin-sidebar__link')
                || anchor.classList.contains('admin-sidebar__submenu-link');

            anchor.classList.toggle(isModernSidebarLink ? 'is-active' : 'active', active);
            if (isModernSidebarLink) anchor.classList.remove('active');
            if (active) anchor.setAttribute('aria-current', 'page');
            else anchor.removeAttribute('aria-current');
        });

        document.querySelectorAll('#accordionSidebar .admin-sidebar__item').forEach((item) => {
            const hasActiveChild = Boolean(item.querySelector('.admin-sidebar__submenu-link.is-active'));
            const trigger = item.querySelector('.admin-sidebar__submenu-trigger');
            const submenu = item.querySelector('.admin-sidebar__submenu');

            item.classList.toggle('is-active', hasActiveChild);

            if (trigger && submenu) {
                trigger.classList.toggle('collapsed', !hasActiveChild);
                trigger.setAttribute('aria-expanded', hasActiveChild ? 'true' : 'false');
                submenu.classList.toggle('show', hasActiveChild);
            }
        });

        syncAdminBreadcrumb(path);
    }
}

function syncAdminBreadcrumb(path) {
    const sectionElement = document.querySelector('.admin-breadcrumb__section');
    const currentElement = document.querySelector('.admin-breadcrumb__current');
    if (!sectionElement || !currentElement) return;

    const breadcrumb = ADMIN_BREADCRUMB_RULES.find(({ pattern }) => pattern.test(path));
    if (!breadcrumb) return;

    sectionElement.textContent = breadcrumb.section;
    currentElement.textContent = breadcrumb.page;
}

function normalizePath(pathname) {
    if (!pathname || pathname === '/') return '/';
    return pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname;
}
