import './account-profile.js';

const headerRoot = document.getElementById('adminHeaderState');

if (headerRoot) {
    const profileDropdown = headerRoot.querySelector('.admin-profile-dropdown');
    if (profileDropdown) {
        window.jQuery(profileDropdown.parentElement).on('hide.bs.dropdown', (event) => {
            if (event.clickEvent && profileDropdown.contains(event.clickEvent.target)) {
                event.preventDefault();
            }
        });
    }

    const notificationList = document.getElementById('adminNotificationList');
    const messageList = document.getElementById('adminMessageList');
    const notificationBadge = document.getElementById('adminNotificationBadge');
    const messageBadge = document.getElementById('adminMessageBadge');
    const sidebarMessageBadge = document.getElementById('adminSidebarMessageBadge');
    const sidebarTransactionBadge = document.getElementById('adminSidebarTransactionBadge');
    const sidebarWithdrawBadge = document.getElementById('adminSidebarWithdrawBadge');
    const sidebarRewardBadge = document.getElementById('adminSidebarRewardBadge');
    const sidebarOrderReportBadge = document.getElementById('adminSidebarOrderReportBadge');
    const sidebarBugReportBadge = document.getElementById('adminSidebarBugReportBadge');
    const readAllButton = document.getElementById('adminNotificationReadAll');
    const loadMoreButton = document.getElementById('adminNotificationLoadMore');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const stateUrl = headerRoot.dataset.stateUrl;
    const readUrlTemplate = headerRoot.dataset.notificationReadUrl;
    const readAllUrl = headerRoot.dataset.notificationReadAllUrl;
    const userId = headerRoot.dataset.userId;
    let notificationLimit = 6;
    let refreshTimer = null;
    let realtimeBound = false;
    let livewireBound = false;

    const quickSearch = document.getElementById('adminQuickSearch');
    const quickSearchInput = document.getElementById('adminQuickSearchInput');
    const quickSearchResults = document.getElementById('adminQuickSearchResults');
    let quickSearchActiveIndex = -1;

    const normalizeSearchText = (value) => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase();

    function getQuickSearchItems() {
        const seen = new Set();

        return Array.from(document.querySelectorAll('.admin-sidebar__link[href], .admin-sidebar__submenu-link[href]'))
            .filter((link) => !link.hidden && link.getAttribute('href') && link.getAttribute('href') !== '#')
            .map((link) => {
                const label = link.querySelector('.admin-sidebar__label')?.textContent?.trim()
                    || link.textContent?.trim()
                    || '';
                const section = link.closest('.admin-sidebar__section')
                    ?.querySelector('.admin-sidebar__section-title')
                    ?.textContent
                    ?.trim() || 'Quản trị';

                return { label, section, href: link.href };
            })
            .filter((item) => {
                if (!item.label || seen.has(item.href)) return false;
                seen.add(item.href);
                return true;
            });
    }

    function closeQuickSearch() {
        if (!quickSearchResults || !quickSearchInput) return;
        quickSearchResults.hidden = true;
        quickSearchResults.replaceChildren();
        quickSearchInput.setAttribute('aria-expanded', 'false');
        quickSearchActiveIndex = -1;
    }

    function setQuickSearchActive(index) {
        if (!quickSearchResults) return;
        const options = Array.from(quickSearchResults.querySelectorAll('.admin-quick-search__result'));
        if (!options.length) {
            quickSearchActiveIndex = -1;
            return;
        }

        quickSearchActiveIndex = Math.max(0, Math.min(index, options.length - 1));
        options.forEach((option, optionIndex) => {
            const active = optionIndex === quickSearchActiveIndex;
            option.classList.toggle('is-active', active);
            option.setAttribute('aria-selected', active ? 'true' : 'false');
            if (active) option.scrollIntoView({ block: 'nearest' });
        });
    }

    function renderQuickSearch(value = '') {
        if (!quickSearchResults || !quickSearchInput) return;

        const needle = normalizeSearchText(value.trim());
        const items = getQuickSearchItems()
            .filter((item) => !needle || normalizeSearchText(`${item.label} ${item.section}`).includes(needle))
            .slice(0, 8);

        quickSearchResults.replaceChildren();
        quickSearchActiveIndex = -1;

        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'admin-quick-search__empty';
            empty.textContent = 'Không tìm thấy chức năng phù hợp';
            quickSearchResults.appendChild(empty);
        } else {
            items.forEach((item) => {
                const link = document.createElement('a');
                link.className = 'admin-quick-search__result';
                link.href = item.href;
                link.setAttribute('role', 'option');
                link.setAttribute('aria-selected', 'false');

                const label = document.createElement('strong');
                label.textContent = item.label;
                const section = document.createElement('span');
                section.textContent = item.section;

                link.append(label, section);
                quickSearchResults.appendChild(link);
            });
        }

        quickSearchResults.hidden = false;
        quickSearchInput.setAttribute('aria-expanded', 'true');
    }

    quickSearchInput?.setAttribute('aria-controls', 'adminQuickSearchResults');
    quickSearchInput?.setAttribute('aria-expanded', 'false');
    quickSearchInput?.addEventListener('focus', () => renderQuickSearch(quickSearchInput.value));
    quickSearchInput?.addEventListener('input', () => renderQuickSearch(quickSearchInput.value));
    quickSearchInput?.addEventListener('keydown', (event) => {
        const options = quickSearchResults
            ? Array.from(quickSearchResults.querySelectorAll('.admin-quick-search__result'))
            : [];

        if (event.key === 'ArrowDown' && options.length) {
            event.preventDefault();
            setQuickSearchActive(quickSearchActiveIndex < options.length - 1 ? quickSearchActiveIndex + 1 : 0);
        } else if (event.key === 'ArrowUp' && options.length) {
            event.preventDefault();
            setQuickSearchActive(quickSearchActiveIndex > 0 ? quickSearchActiveIndex - 1 : options.length - 1);
        } else if (event.key === 'Enter' && options.length) {
            event.preventDefault();
            options[Math.max(quickSearchActiveIndex, 0)]?.click();
        } else if (event.key === 'Escape') {
            closeQuickSearch();
            quickSearchInput.blur();
        }
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            if (!quickSearchInput || !quickSearch || quickSearch.offsetParent === null) return;
            event.preventDefault();
            quickSearchInput.focus();
            quickSearchInput.select();
            renderQuickSearch(quickSearchInput.value);
        }
    });

    document.addEventListener('mousedown', (event) => {
        if (quickSearch && !quickSearch.contains(event.target)) closeQuickSearch();
    });

    const badgeText = (count) => count > 99 ? '99+' : String(count);

    function updateBadge(element, count) {
        if (!element) return;

        const value = Number(count || 0);
        element.hidden = value === 0;
        element.textContent = value === 0 ? '' : badgeText(value);
    }

    function relativeTime(value) {
        if (!value) return '';

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';

        const seconds = Math.round((date.getTime() - Date.now()) / 1000);
        const ranges = [
            ['năm', 31536000],
            ['tháng', 2592000],
            ['ngày', 86400],
            ['giờ', 3600],
            ['phút', 60],
        ];

        for (const [label, size] of ranges) {
            if (Math.abs(seconds) >= size) {
                const amount = Math.round(Math.abs(seconds) / size);
                return seconds < 0 ? amount + ' ' + label + ' trước' : 'sau ' + amount + ' ' + label;
            }
        }

        return 'vừa xong';
    }

    function stateNode(text) {
        const node = document.createElement('div');
        node.className = 'admin-header-state';
        node.textContent = text;
        return node;
    }

    function iconNode(icon) {
        const wrapper = document.createElement('div');
        wrapper.className = 'admin-header-icon';
        const element = document.createElement('i');
        element.className = 'fas ' + (icon || 'fa-bell');
        wrapper.appendChild(element);
        return wrapper;
    }

    function renderNotifications(data) {
        updateBadge(notificationBadge, data.unread_count);
        readAllButton.hidden = Number(data.unread_count || 0) === 0;
        notificationList.replaceChildren();

        if (!data.items?.length) {
            notificationList.appendChild(stateNode('Không có thông báo'));
        } else {
            data.items.forEach((item) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'dropdown-item d-flex align-items-start admin-header-item' + (item.is_read ? '' : ' is-unread');
                button.appendChild(iconNode(item.icon));

                const content = document.createElement('span');
                content.className = 'admin-header-item__content';

                const meta = document.createElement('span');
                meta.className = 'admin-header-item__meta';
                meta.textContent = relativeTime(item.created_at);

                const title = document.createElement('strong');
                title.className = 'admin-header-item__title';
                title.textContent = item.title || 'Thông báo';

                const message = document.createElement('span');
                message.className = 'admin-header-item__preview';
                message.textContent = item.message || '';

                content.append(meta, title, message);
                button.appendChild(content);
                button.addEventListener('click', () => markNotificationRead(item));
                notificationList.appendChild(button);
            });
        }

        loadMoreButton.hidden = Number(data.total_count || 0) <= Number(data.items?.length || 0)
            || notificationLimit >= 30;
    }

    function renderMessages(data) {
        updateBadge(messageBadge, data.unread_count);
        updateBadge(sidebarMessageBadge, data.unread_count);

        if (sidebarMessageBadge) {
            const unreadCount = Number(data.unread_count || 0);
            sidebarMessageBadge.setAttribute(
                'aria-label',
                unreadCount > 0 ? unreadCount + ' tin nhắn chưa đọc' : 'Không có tin nhắn chưa đọc'
            );
        }

        messageList.replaceChildren();

        if (!data.conversations?.length) {
            messageList.appendChild(stateNode('Chưa có tin nhắn'));
            return;
        }

        data.conversations.forEach((conversation) => {
            const link = document.createElement('a');
            link.href = conversation.target_url;
            link.className = 'dropdown-item d-flex align-items-start admin-header-item'
                + (conversation.is_unread ? ' is-unread' : '');

            const avatar = document.createElement('span');
            avatar.className = 'admin-header-avatar';
            avatar.textContent = (conversation.participant_name || '?').trim().charAt(0).toUpperCase();
            if (conversation.participant_avatar_url) {
                const avatarImage = document.createElement('img');
                avatarImage.src = conversation.participant_avatar_url;
                avatarImage.alt = '';
                avatarImage.loading = 'lazy';
                avatarImage.addEventListener('error', () => {
                    avatar.textContent = (conversation.participant_name || '?').trim().charAt(0).toUpperCase();
                }, { once: true });
                avatar.replaceChildren(avatarImage);
            }

            const content = document.createElement('span');
            content.className = 'admin-header-item__content';

            const name = document.createElement('strong');
            name.className = 'admin-header-item__title';
            name.textContent = conversation.participant_name || 'Người dùng';

            const preview = document.createElement('span');
            preview.className = 'admin-header-item__preview';
            preview.textContent = conversation.preview || '';

            const meta = document.createElement('span');
            meta.className = 'admin-header-item__meta';
            meta.textContent = relativeTime(conversation.created_at);
            if (conversation.unread_count > 0) {
                meta.textContent = badgeText(conversation.unread_count) + ' chưa đọc · ' + meta.textContent;
            }

            content.append(name, preview, meta);
            link.append(avatar, content);
            messageList.appendChild(link);
        });
    }

    function renderSidebarBadges(data = {}) {
        const badges = [
            [sidebarTransactionBadge, data.customer_transactions, 'bản ghi giao dịch khách hàng mới'],
            [sidebarWithdrawBadge, data.withdrawals, 'yêu cầu rút tiền mới'],
            [sidebarRewardBadge, data.lucky_wheel_rewards, 'phần thưởng vòng quay chờ duyệt'],
            [sidebarOrderReportBadge, data.order_reports, 'đơn hàng bị báo cáo chờ xử lý'],
            [sidebarBugReportBadge, data.bug_reports, 'báo lỗi hệ thống chờ xử lý'],
        ];

        badges.forEach(([element, count, label]) => {
            updateBadge(element, count);
            if (!element) return;

            const value = Number(count || 0);
            element.setAttribute('aria-label', value > 0 ? value + ' ' + label : 'Không có ' + label);
        });
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                ...(options.headers || {}),
            },
        });

        if (!response.ok) {
            throw new Error('Header request failed with ' + response.status);
        }

        return response.json();
    }

    async function refresh() {
        try {
            const separator = stateUrl.includes('?') ? '&' : '?';
            const state = await request(stateUrl + separator + 'notification_limit=' + notificationLimit);
            renderNotifications(state.notifications);
            renderMessages(state.messages);
            renderSidebarBadges(state.sidebar_badges);
        } catch (error) {
            console.error('[Admin header] Unable to load state', error);
            notificationList.replaceChildren(stateNode('Không thể tải thông báo'));
            messageList.replaceChildren(stateNode('Không thể tải tin nhắn'));
        }
    }

    function scheduleRefresh() {
        window.clearTimeout(refreshTimer);
        refreshTimer = window.setTimeout(refresh, 120);
    }

    async function markNotificationRead(item) {
        try {
            const url = readUrlTemplate.replace('__NOTIFICATION__', encodeURIComponent(item.id));
            await request(url, { method: 'POST' });
            await refresh();
        } catch (error) {
            console.error('[Admin header] Unable to mark notification as read', error);
            return;
        }

        if (item.target_url) {
            window.location.href = item.target_url;
        }
    }

    readAllButton?.addEventListener('click', async () => {
        try {
            await request(readAllUrl, { method: 'POST' });
            await refresh();
        } catch (error) {
            console.error('[Admin header] Unable to mark all notifications as read', error);
        }
    });

    const bugReportForm = document.getElementById('adminBugReportForm');
    const bugReportSubmit = document.getElementById('adminBugReportSubmit');
    const bugReportStatus = document.getElementById('adminBugReportStatus');
    const bugReportTitle = document.getElementById('adminBugReportTitle');
    const bugReportDescription = document.getElementById('adminBugReportDescription');
    const bugReportImages = document.getElementById('adminBugReportImages');
    const bugReportImagePreview = document.getElementById('adminBugReportImagePreview');
    const bugReportPageUrl = document.getElementById('adminBugReportPageUrl');
    const bugReportUserAgent = document.getElementById('adminBugReportUserAgent');
    let bugReportPreviewUrls = [];

    function clearBugReportErrors() {
        [
            [bugReportTitle, document.getElementById('adminBugReportTitleError')],
            [bugReportDescription, document.getElementById('adminBugReportDescriptionError')],
            [bugReportImages, document.getElementById('adminBugReportImagesError')],
        ].forEach(([field, error]) => {
            field?.classList.remove('is-invalid');
            if (error) error.textContent = '';
        });
        bugReportStatus?.classList.add('d-none');
        bugReportStatus?.classList.remove('alert-success', 'alert-danger');
    }

    function showBugReportFieldError(name, message) {
        const normalizedName = String(name || '').startsWith('images') ? 'images' : name;
        const map = {
            title: [bugReportTitle, document.getElementById('adminBugReportTitleError')],
            description: [bugReportDescription, document.getElementById('adminBugReportDescriptionError')],
            images: [bugReportImages, document.getElementById('adminBugReportImagesError')],
        };
        const [field, error] = map[normalizedName] || [];
        field?.classList.add('is-invalid');
        if (error) error.textContent = message;
    }

    function clearBugReportImagePreview() {
        bugReportPreviewUrls.forEach((url) => URL.revokeObjectURL(url));
        bugReportPreviewUrls = [];
        bugReportImagePreview?.replaceChildren();
    }

    bugReportImages?.addEventListener('change', () => {
        clearBugReportImagePreview();

        Array.from(bugReportImages.files || []).slice(0, 5).forEach((file) => {
            const url = URL.createObjectURL(file);
            bugReportPreviewUrls.push(url);

            const image = document.createElement('img');
            image.src = url;
            image.alt = file.name;
            image.className = 'admin-bug-report-images__item';
            bugReportImagePreview?.appendChild(image);
        });
    });

    bugReportForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearBugReportErrors();
        if (bugReportPageUrl) bugReportPageUrl.value = window.location.href;
        if (bugReportUserAgent) bugReportUserAgent.value = navigator.userAgent || '';
        if (bugReportSubmit) bugReportSubmit.disabled = true;

        try {
            const response = await fetch(bugReportForm.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: new FormData(bugReportForm),
            });
            const payload = await response.json().catch(() => ({}));

            if (response.status === 422) {
                Object.entries(payload.errors || {}).forEach(([field, messages]) => {
                    showBugReportFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                });
                return;
            }

            if (!response.ok) {
                throw new Error(payload.message || 'Không thể gửi báo lỗi.');
            }

            bugReportForm.reset();
            clearBugReportImagePreview();
            if (bugReportStatus) {
                bugReportStatus.textContent = payload.message || 'Đã gửi báo lỗi tới chủ hệ thống.';
                bugReportStatus.classList.remove('d-none');
                bugReportStatus.classList.add('alert-success');
            }
        } catch (error) {
            if (bugReportStatus) {
                bugReportStatus.textContent = error.message || 'Không thể gửi báo lỗi. Vui lòng thử lại.';
                bugReportStatus.classList.remove('d-none');
                bugReportStatus.classList.add('alert-danger');
            }
        } finally {
            if (bugReportSubmit) bugReportSubmit.disabled = false;
        }
    });

    loadMoreButton?.addEventListener('click', (event) => {
        event.stopPropagation();
        notificationLimit = Math.min(notificationLimit + 6, 30);
        refresh();
    });

    function bindRealtime() {
        if (realtimeBound || !window.Echo || !userId) return;

        realtimeBound = true;
        window.Echo.private('staff.' + userId)
            .listen('.MessageSent', scheduleRefresh);
        window.Echo.private('user.' + userId)
            .notification(scheduleRefresh);
    }

    function bindLivewireRefresh() {
        if (livewireBound || !window.Livewire) return;

        livewireBound = true;
        window.Livewire.on('conversation-selected', scheduleRefresh);
        window.Livewire.on('refresh-conversations', scheduleRefresh);
    }

    document.addEventListener('livewire:init', bindLivewireRefresh, { once: true });
    document.addEventListener('livewire:initialized', bindLivewireRefresh, { once: true });
    window.addEventListener('echo:ready', bindRealtime, { once: true });

    refresh();
    bindRealtime();
    bindLivewireRefresh();
}
