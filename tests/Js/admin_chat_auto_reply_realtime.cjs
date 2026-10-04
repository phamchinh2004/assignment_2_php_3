const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const template = fs
    .readFileSync('resources/views/livewire/admin/chat-component.blade.php', 'utf8')
    .replace(/\r\n/g, '\n');

function listenerBody(sectionStart, nextListener, label) {
    const section = template.slice(template.indexOf(sectionStart));
    const marker = ".listen('.MessageSent', (e) => {";
    const markerAt = section.indexOf(marker);
    assert.ok(markerAt >= 0, `${label} MessageSent listener must exist`);

    const start = markerAt + marker.length;
    const end = section.indexOf(`\n                })\n                .listen('${nextListener}'`, start);
    assert.ok(end > start, `${label} MessageSent listener must be extractable`);

    return section
        .slice(start, end)
        .replace(/\{\{\s*auth\(\)->id\(\)\s*\}\}/g, '7')
        .replace(/@if\([^\n]+\)[\s\S]*?@endif/g, '');
}

const conversationBody = listenerBody('// Join conversation channel', '.MessageRead', 'conversation');
const staffBody = listenerBody('// Listen MessageSent event', '.UserJoinChat', 'staff');

const calls = [];
const component = {
    call(name, payload) {
        calls.push([name, payload]);
    },
};

const conversationHandler = vm.runInNewContext(`(e) => {${conversationBody}}`, {
    window: { isAdminConversationMuted: () => true },
    document: {
        getElementById: () => ({ getAttribute: () => 'wire-1' }),
    },
    Livewire: { find: () => component },
    playNotificationSound() {},
});

conversationHandler({
    message: {
        id: 1001,
        conversation_id: 55,
        sender_id: 7,
        kind: 'auto_reply',
        type: 'text',
        message: 'auto',
    },
});

assert.ok(
    calls.some(([name]) => name === 'messageReceived'),
    'Auto-reply created with the current staff sender_id must reach admin Livewire in realtime',
);

calls.length = 0;
conversationHandler({
    message: {
        id: 1002,
        conversation_id: 55,
        sender_id: 7,
        kind: 'text',
        type: 'text',
        message: 'manual',
    },
});

assert.equal(
    calls.some(([name]) => name === 'messageReceived'),
    false,
    'A normal message sent manually by the current staff must still be ignored to avoid duplicates',
);

calls.length = 0;
component.get = () => 999;
const staffHandler = vm.runInNewContext(`(e) => {${staffBody}}`, {
    currentUserId: 7,
    document: {
        getElementById: () => ({ getAttribute: () => 'wire-1' }),
    },
    Livewire: { find: () => component },
});

staffHandler({
    message: {
        id: 1003,
        conversation_id: 55,
        sender_id: 7,
        kind: 'auto_reply',
        type: 'text',
        message: 'auto',
    },
});

assert.ok(
    calls.some(([name]) => name === 'messageReceived'),
    'Auto-reply for another conversation must not be swallowed by the staff channel self-message filter',
);

calls.length = 0;
component.get = () => 55;
staffHandler({
    message: {
        id: 1004,
        conversation_id: 55,
        sender_id: 21,
        kind: 'text',
        type: 'text',
        message: 'customer message',
    },
});

assert.ok(
    calls.some(([name]) => name === 'messageReceived'),
    'The staff channel must update the focused conversation when its conversation subscription is not ready yet',
);

assert.match(
    template,
    /document\.addEventListener\('admin-chat:mounted'[\s\S]*?dataset\.chatConversation[\s\S]*?Livewire\.dispatch\('join-conversation-channel'/,
    'React remount must resubscribe the conversation that is already selected',
);

console.log('PASS: auto-reply reaches admin Livewire realtime through conversation and staff channels');
