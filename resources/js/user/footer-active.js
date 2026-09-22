// Footer active state fallback. Primary state is rendered server-side in Blade.
document.addEventListener('DOMContentLoaded', function () {
    const footerItems = Array.from(document.querySelectorAll('.footer-item[href]'));

    if (footerItems.some((item) => item.classList.contains('active'))) {
        return;
    }

    const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
    const activeItem = footerItems
        .map((item) => {
            const url = new URL(item.href, window.location.origin);
            const path = url.pathname.replace(/\/+$/, '') || '/';
            const matches = currentPath === path || (path !== '/' && currentPath.startsWith(`${path}/`));

            return matches ? { item, path } : null;
        })
        .filter(Boolean)
        .sort((a, b) => b.path.length - a.path.length)[0];

    if (activeItem) {
        activeItem.item.classList.add('active');
        activeItem.item.setAttribute('aria-current', 'page');
    }
});
