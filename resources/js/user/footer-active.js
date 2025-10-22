// ========================================================Footer Active State========================================================
// Automatically highlight the active page in footer
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const footerItems = document.querySelectorAll('.footer-item');
    
    footerItems.forEach(item => {
        const link = item.getAttribute('href');
        if (link) {
            // Check if current path matches the link
            const linkPath = link.split('?')[0];
            if (currentPath === linkPath || currentPath.startsWith(linkPath)) {
                item.classList.add('active');
            }
        }
    });
});

