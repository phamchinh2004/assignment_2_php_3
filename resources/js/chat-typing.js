// Shared input lifecycle: admin inbox and customer chat both publish typing activity.
export function createTypingSession({ conversationId, viewerId, send, render, now = Date.now, schedule = setTimeout, cancel = clearTimeout }) {
    const peers = new Map();
    let idleTimer, expiryTimer, lastSent = -Infinity, active = false;
    const paint = () => render([...peers.values()].map(peer => peer.name));
    const expire = () => {
        for (const [id, peer] of peers) if (peer.until <= now()) peers.delete(id);
        paint();
        expiryTimer = peers.size ? schedule(expire, 1000) : null;
    };
    const stop = () => {
        cancel(idleTimer);
        if (active) send(false);
        active = false;
        lastSent = -Infinity;
    };
    return {
        input(value) {
            if (!value.trim()) return stop();
            active = true;
            if (now() - lastSent >= 2500) { send(true); lastSent = now(); }
            cancel(idleTimer);
            idleTimer = schedule(stop, 3000);
        },
        receive(event) {
            if (Number(event.conversation_id) !== Number(conversationId) || Number(event.user_id) === Number(viewerId)) return;
            if (event.typing) peers.set(event.user_id, { name: String(event.name), until: now() + 7000 });
            else peers.delete(event.user_id);
            cancel(expiryTimer);
            expire();
        },
        stop,
        destroy() { stop(); cancel(expiryTimer); peers.clear(); paint(); },
    };
}

let current = null;

function sendTyping(conversationId, typing) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) return Promise.resolve();

    return fetch('/chat/typing', {
        method: 'POST',
        credentials: 'same-origin',
        keepalive: !typing,
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ conversation_id: conversationId, typing }),
    }).then(response => {
        if (!response.ok) throw new Error(`Typing update failed with HTTP ${response.status}`);
    });
}

function syncTyping() {
    const root = document.querySelector('[data-chat-conversation]');
    const id = Number(root?.dataset.chatConversation);
    if (current?.root === root && current?.id === id) return;
    if (current) {
        current.session.destroy();
        current.channel?.stopListening('.ChatTyping', current.session.receive);
        current = null;
    }
    if (!root || !id) return;
    const session = createTypingSession({
        conversationId: id, viewerId: root.dataset.chatViewer,
        send: typing => { sendTyping(id, typing).catch(() => {}); },
        render: names => {
            const status = root.querySelector('[data-chat-typing-status]');
            if (!status) return;
            status.hidden = !names.length;
            status.querySelector('[data-chat-typing-label]').textContent = names.length ? `${names.join(', ')} đang nhập...` : '';
        },
    });
    current = { root, id, session, channel: null };
    subscribe();
}
function subscribe() {
    if (!current || current.channel || !window.Echo || !current.root.querySelector('[data-chat-typing-status]')) return;
    current.channel = window.Echo.private(`chat.conversation.${current.id}`);
    current.channel.listen('.ChatTyping', current.session.receive);
}
if (typeof document !== 'undefined') {
    document.addEventListener('input', event => {
        if (!event.target.matches('[data-chat-typing-input]')) return;
        syncTyping();
        current?.session.input(event.target.value);
    });
    document.addEventListener('focusout', event => {
        if (event.target.matches('[data-chat-typing-input]')) current?.session.stop();
    });
    document.addEventListener('submit', event => {
        if (event.target.querySelector('[data-chat-typing-input]')) current?.session.stop();
    });
    document.addEventListener('visibilitychange', () => { if (document.hidden) current?.session.stop(); });
    window.addEventListener('pagehide', () => current?.session.stop());
    window.addEventListener('echo:ready', subscribe);
    window.addEventListener('chat:channel-changed', () => {
        syncTyping();
        if (current) {
            current.channel?.stopListening('.ChatTyping', current.session.receive);
            current.channel = null;
            subscribe();
        }
    });
    const observer = new MutationObserver(syncTyping);
    observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-chat-conversation'] });
    syncTyping();
}
