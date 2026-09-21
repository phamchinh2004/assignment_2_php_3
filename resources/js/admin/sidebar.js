(() => {
    'use strict';

    const sidebar = document.getElementById('accordionSidebar');
    if (!sidebar) {
        return;
    }

    const body = document.body;
    const collapseButton = document.getElementById('adminSidebarCollapse');
    const openButton = document.getElementById('adminSidebarOpen');
    const closeButton = document.getElementById('adminSidebarClose');
    const overlay = document.getElementById('adminSidebarOverlay');
    const scrollContainer = sidebar.querySelector('.admin-sidebar__scroll');
    const desktopQuery = window.matchMedia('(min-width: 992px)');
    const storageKey = 'admin-sidebar-collapsed';
    let lastMobileTrigger = null;

    const readCollapsedPreference = () => {
        try {
            return window.localStorage.getItem(storageKey) === '1';
        } catch {
            return false;
        }
    };

    const persistCollapsedPreference = (collapsed) => {
        try {
            window.localStorage.setItem(storageKey, collapsed ? '1' : '0');
        } catch {
            // localStorage can be unavailable in restricted browsing modes.
        }
    };

    const hideTooltip = () => {
        tooltip.classList.remove('is-visible');
        tooltip.setAttribute('aria-hidden', 'true');
    };

    const closePeek = () => {
        sidebar.classList.remove('is-peek');
        hideTooltip();
    };

    const setCollapsed = (collapsed, persist = true) => {
        body.classList.toggle('admin-sidebar-collapsed', collapsed);
        closePeek();

        if (collapseButton) {
            collapseButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            collapseButton.setAttribute(
                'aria-label',
                collapsed ? 'Mở rộng thanh điều hướng' : 'Thu gọn thanh điều hướng'
            );
        }

        if (persist) {
            persistCollapsedPreference(collapsed);
        }
    };

    const openMobile = () => {
        if (desktopQuery.matches) {
            return;
        }

        lastMobileTrigger = document.activeElement;
        body.classList.add('admin-sidebar-mobile-open');
        openButton?.setAttribute('aria-expanded', 'true');
        window.requestAnimationFrame(() => closeButton?.focus());
    };

    const closeMobile = ({ restoreFocus = false } = {}) => {
        body.classList.remove('admin-sidebar-mobile-open');
        openButton?.setAttribute('aria-expanded', 'false');
        closePeek();

        if (restoreFocus && lastMobileTrigger instanceof HTMLElement) {
            lastMobileTrigger.focus();
        }
    };

    const tooltip = document.createElement('div');
    tooltip.className = 'admin-sidebar-tooltip';
    tooltip.setAttribute('role', 'tooltip');
    document.body.appendChild(tooltip);

    const showTooltip = (element) => {
        if (!desktopQuery.matches || !body.classList.contains('admin-sidebar-collapsed') || sidebar.classList.contains('is-peek')) {
            return;
        }

        const label = element.dataset.sidebarTooltip;
        if (!label) {
            return;
        }

        const rect = element.getBoundingClientRect();
        tooltip.textContent = label;
        tooltip.style.left = `${Math.round(rect.right + 10)}px`;
        tooltip.style.top = `${Math.round(rect.top + rect.height / 2)}px`;
        tooltip.setAttribute('aria-hidden', 'false');
        tooltip.classList.add('is-visible');
    };

    collapseButton?.addEventListener('click', () => {
        if (!desktopQuery.matches) {
            return;
        }

        setCollapsed(!body.classList.contains('admin-sidebar-collapsed'));
    });

    openButton?.addEventListener('click', openMobile);
    closeButton?.addEventListener('click', () => closeMobile({ restoreFocus: true }));
    overlay?.addEventListener('click', () => closeMobile({ restoreFocus: true }));

    sidebar.addEventListener('click', (event) => {
        const submenuTrigger = event.target.closest('.admin-sidebar__submenu-trigger');
        if (submenuTrigger && desktopQuery.matches && body.classList.contains('admin-sidebar-collapsed')) {
            sidebar.classList.add('is-peek');
            hideTooltip();
            return;
        }

        const navigationLink = event.target.closest('a.admin-sidebar__link, a.admin-sidebar__submenu-link');
        if (navigationLink && !desktopQuery.matches) {
            closeMobile();
        }
    });

    document.addEventListener('click', (event) => {
        if (
            sidebar.classList.contains('is-peek')
            && !sidebar.contains(event.target)
            && event.target !== collapseButton
        ) {
            closePeek();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (body.classList.contains('admin-sidebar-mobile-open')) {
            closeMobile({ restoreFocus: true });
            return;
        }

        if (sidebar.classList.contains('is-peek')) {
            closePeek();
            collapseButton?.focus();
        }
    });

    sidebar.addEventListener('mouseover', (event) => {
        const target = event.target.closest('[data-sidebar-tooltip]');
        if (target) {
            showTooltip(target);
        }
    });

    sidebar.addEventListener('mouseout', (event) => {
        const target = event.target.closest('[data-sidebar-tooltip]');
        if (target && !target.contains(event.relatedTarget)) {
            hideTooltip();
        }
    });

    sidebar.addEventListener('focusin', (event) => {
        const target = event.target.closest('[data-sidebar-tooltip]');
        if (target) {
            showTooltip(target);
        }
    });

    sidebar.addEventListener('focusout', (event) => {
        const target = event.target.closest('[data-sidebar-tooltip]');
        if (target && !target.contains(event.relatedTarget)) {
            hideTooltip();
        }
    });

    const scrollActiveIntoView = () => {
        if (!scrollContainer) {
            return;
        }

        const active = sidebar.querySelector(
            '.admin-sidebar__submenu-link.is-active, .admin-sidebar__link.is-active, .admin-sidebar__item.is-active > .admin-sidebar__link'
        );

        if (!active) {
            return;
        }

        const containerRect = scrollContainer.getBoundingClientRect();
        const activeRect = active.getBoundingClientRect();
        const outside = activeRect.top < containerRect.top || activeRect.bottom > containerRect.bottom;

        if (outside) {
            const nextTop = scrollContainer.scrollTop
                + activeRect.top
                - containerRect.top
                - (containerRect.height - activeRect.height) / 2;

            scrollContainer.scrollTo({
                top: Math.max(0, nextTop),
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            });
        }
    };

    const syncViewport = () => {
        hideTooltip();

        if (desktopQuery.matches) {
            closeMobile();
            setCollapsed(readCollapsedPreference(), false);
        } else {
            closePeek();
        }
    };

    if (typeof desktopQuery.addEventListener === 'function') {
        desktopQuery.addEventListener('change', syncViewport);
    } else {
        desktopQuery.addListener(syncViewport);
    }

    window.addEventListener('resize', hideTooltip);
    window.addEventListener('authorization:updated', () => window.requestAnimationFrame(scrollActiveIntoView));

    setCollapsed(readCollapsedPreference(), false);
    window.requestAnimationFrame(scrollActiveIntoView);
})();
