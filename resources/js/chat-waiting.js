// Anchor to the server clock so a customer's device timezone/clock cannot change the wait.
export function createSupportWaitSession({ render, now = () => performance.now(), schedule = setTimeout, cancel = clearTimeout }) {
    let startedAt = null, serverNow = 0, syncedAt = 0, timer = null, state = null;

    function refresh() {
        cancel(timer);
        timer = null;
        const elapsed = startedAt === null ? 0 : Math.max(0, serverNow + now() - syncedAt - startedAt);
        const next = startedAt === null || elapsed < 30000 ? 'idle' : elapsed < 60000 ? 'connecting' : 'waiting';
        if (next !== state) {
            state = next;
            render(state);
        }
        if (startedAt !== null && elapsed < 60000) {
            timer = schedule(refresh, (elapsed < 30000 ? 30000 : 60000) - elapsed);
        }
    }

    return {
        sync(since, timestamp) {
            const parsedSince = since === null || since === '' ? null : Number(since);
            startedAt = Number.isFinite(parsedSince) ? parsedSince : null;
            serverNow = Number(timestamp);
            if (!Number.isFinite(serverNow)) startedAt = null;
            syncedAt = now();
            refresh();
        },
        refresh,
        destroy() { cancel(timer); timer = null; },
    };
}

let current = null;

function syncSupportWait() {
    const root = document.getElementById('chat-root');
    const banner = root?.querySelector('[data-chat-wait-status]');
    if (current?.root !== root || current?.banner !== banner) {
        current?.session.destroy();
        current = null;
        if (!root || !banner) return;
        const session = createSupportWaitSession({
            render(state) {
                banner.dataset.state = state;
                if (state !== 'idle') {
                    banner.querySelector('[data-chat-wait-title]').textContent = banner.dataset[`${state}Title`];
                    banner.querySelector('[data-chat-wait-description]').textContent = banner.dataset[`${state}Description`];
                }
                banner.hidden = state === 'idle';
            },
        });
        current = { root, banner, session, stamp: null };
    }
    if (!current) return;
    const stamp = `${root.dataset.chatWaitSince}:${root.dataset.chatWaitNow}`;
    if (current.stamp === stamp) return;
    current.stamp = stamp;
    current.session.sync(root.dataset.chatWaitSince, root.dataset.chatWaitNow);
}

if (typeof document !== 'undefined') {
    const observer = new MutationObserver(syncSupportWait);
    observer.observe(document.documentElement, {
        childList: true, subtree: true, attributes: true,
        attributeFilter: ['data-chat-wait-since', 'data-chat-wait-now'],
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) current?.session.refresh();
    });
    window.addEventListener('pagehide', () => current?.session.destroy());
    window.addEventListener('pageshow', () => { syncSupportWait(); current?.session.refresh(); });
    syncSupportWait();
}
