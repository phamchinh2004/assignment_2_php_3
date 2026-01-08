// ========================================================Sidebar Focus Tab========================================================
// Tự động highlight và scroll đến tab active trong sidebar

(function() {
    'use strict';
    
    function initSidebarFocus() {
        // Đợi jQuery load xong
        if (typeof jQuery === 'undefined') {
            setTimeout(initSidebarFocus, 100);
            return;
        }
        
        const $ = jQuery;
        const currentPath = window.location.pathname;
        const currentUrl = window.location.href;
        
        // Tìm tất cả các nav-link và collapse-item trong sidebar
        const $navLinks = $('.sidebar .nav-link');
        const $collapseItems = $('.sidebar .collapse-item');
        
        let $activeElement = null;
        let $activeNavItem = null;
        
        // Kiểm tra các nav-link trực tiếp
        $navLinks.each(function() {
            const $link = $(this);
            const href = $link.attr('href');
            
            if (href && href !== '#' && href !== 'javascript:void(0)') {
                // Loại bỏ query string và hash
                const linkPath = href.split('?')[0].split('#')[0];
                const currentPathClean = currentPath.split('?')[0].split('#')[0];
                
                // So sánh path - kiểm tra cả absolute và relative URL
                let linkPathClean = linkPath;
                if (linkPath.startsWith('http')) {
                    try {
                        const url = new URL(linkPath);
                        linkPathClean = url.pathname;
                    } catch(e) {
                        // Nếu không parse được URL, dùng path gốc
                    }
                }
                
                // So sánh
                if (currentPathClean === linkPathClean || 
                    currentPathClean.startsWith(linkPathClean + '/') ||
                    linkPathClean === currentPathClean) {
                    $activeElement = $link;
                    $activeNavItem = $link.closest('.nav-item');
                    
                    // Thêm class active
                    if ($activeNavItem.length) {
                        $activeNavItem.addClass('active');
                    }
                    
                    // Mở collapse nếu có
                    const collapseTarget = $link.attr('data-target');
                    if (collapseTarget) {
                        const $collapseElement = $(collapseTarget);
                        if ($collapseElement.length && !$collapseElement.hasClass('show')) {
                            $collapseElement.collapse('show');
                        }
                    }
                    
                    return false; // Break loop
                }
            }
        });
        
        // Kiểm tra các collapse-item (submenu)
        if (!$activeElement || !$activeElement.length) {
            $collapseItems.each(function() {
                const $item = $(this);
                const href = $item.attr('href');
                
                if (href) {
                    const linkPath = href.split('?')[0].split('#')[0];
                    const currentPathClean = currentPath.split('?')[0].split('#')[0];
                    
                    let linkPathClean = linkPath;
                    if (linkPath.startsWith('http')) {
                        try {
                            const url = new URL(linkPath);
                            linkPathClean = url.pathname;
                        } catch(e) {}
                    }
                    
                    if (currentPathClean === linkPathClean || 
                        currentPathClean.startsWith(linkPathClean + '/')) {
                        $activeElement = $item;
                        $activeNavItem = $item.closest('.nav-item');
                        
                        // Thêm class active cho collapse-item
                        $item.addClass('active');
                        
                        // Thêm class active cho nav-item cha
                        if ($activeNavItem.length) {
                            $activeNavItem.addClass('active');
                            
                            // Mở collapse
                            const $collapseElement = $item.closest('.collapse');
                            if ($collapseElement.length && !$collapseElement.hasClass('show')) {
                                $collapseElement.collapse('show');
                            }
                        }
                        
                        return false; // Break loop
                    }
                }
            });
        }
        
        // Scroll đến tab active
        function scrollToActiveTab() {
            if ($activeElement && $activeElement.length) {
                setTimeout(function() {
                    const $sidebar = $('.sidebar');
                    if ($sidebar.length && $activeElement.length) {
                        const sidebarElement = $sidebar[0];
                        const activeElementDom = $activeElement[0];
                        
                        if (sidebarElement && activeElementDom) {
                            // Tính toán vị trí scroll
                            const sidebarRect = sidebarElement.getBoundingClientRect();
                            const elementRect = activeElementDom.getBoundingClientRect();
                            
                            // Scroll đến element (center trong viewport)
                            const scrollTop = sidebarElement.scrollTop + 
                                (elementRect.top - sidebarRect.top) - 
                                (sidebarRect.height / 2) + 
                                (elementRect.height / 2);
                            
                            sidebarElement.scrollTo({
                                top: Math.max(0, scrollTop),
                                behavior: 'smooth'
                            });
                        }
                    }
                }, 300);
            }
        }
        
        // Scroll khi sidebar toggle
        $('#sidebarToggle, #sidebarToggleTop').on('click', function() {
            setTimeout(scrollToActiveTab, 500);
        });
        
        // Scroll khi page load
        setTimeout(scrollToActiveTab, 500);
        
        // Scroll khi collapse được mở
        $('.sidebar .collapse').on('shown.bs.collapse', function() {
            setTimeout(scrollToActiveTab, 200);
        });
    }
    
    // Khởi tạo khi DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarFocus);
    } else {
        initSidebarFocus();
    }
})();

