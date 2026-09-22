const headerRoot = document.getElementById('adminHeaderState');

if (headerRoot) {
    const notificationList = document.getElementById('adminNotificationList');
    const messageList = document.getElementById('adminMessageList');
    const notificationBadge = document.getElementById('adminNotificationBadge');
    const messageBadge = document.getElementById('adminMessageBadge');
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

    const badgeText = (count) => count > 99 ? '99+' : String(count);

    function updateBadge(element, count) {
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
