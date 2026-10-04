const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const template = fs
    .readFileSync('resources/views/livewire/admin/chat-component.blade.php', 'utf8')
    .replace(/\r\n/g, '\n');

const section = template.slice(template.indexOf('// Join conversation channel'));
const marker = ".listen('.MessageSent', (e) => {";
const markerAt = section.indexOf(marker);
assert.ok(markerAt >= 0, 'Conversation MessageSent listener must exist');

const start = markerAt + marker.length;
const end = section.indexOf("\n                })\n                .listen('.MessageRead'", start);
assert.ok(end > start, 'Conversation MessageSent listener must be extractable');

const body = section
    .slice(start, end)
    .replace(/\{\{\s*auth\(\)->id\(\)\s*\}\}/g, '7');

function makeHandler({ root, muted = false }) {
    let soundCount = 0;
    const calls = [];
    const component = {
        call(name, payload) {
            calls.push([name, payload]);
        },
    };

    const handler = vm.runInNewContext(`(e) => {${body}}`, {
        window: { isAdminConversationMuted: () => muted },
        document: { getElementById: () => root },
        Livewire: { find: () => component },
        playNotificationSound() {
            soundCount += 1;
        },
    });

    return {
        handler,
        calls,
        get soundCount() {
            return soundCount;
        },
    };
}

const messageEvent = {
    message: {
        id: 2001,
        conversation_id: 55,
        sender_id: 21,
        kind: 'text',
        type: 'text',
        message: 'hello',
    },
};

const staleListener = makeHandler({ root: null });
assert.doesNotThrow(
    () => staleListener.handler(messageEvent),
    'A stale conversation listener must become inert after the admin chat DOM is gone',
);
assert.equal(
    staleListener.soundCount,
    0,
    'A stale conversation listener must not add a second notification sound off the chat page',
);

const activeListener = makeHandler({
    root: { getAttribute: () => 'wire-1' },
});
activeListener.handler(messageEvent);
assert.equal(activeListener.soundCount, 1, 'The active conversation must still play one sound');
assert.ok(
    activeListener.calls.some(([name]) => name === 'messageReceived'),
    'The active conversation must still forward the message to Livewire',
);

console.log('PASS: admin chat conversation listener plays exactly one sound only while the chat DOM is active');
