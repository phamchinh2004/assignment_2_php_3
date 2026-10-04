const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const page = fs.readFileSync('resources/js/react/pages/admin/chat/AdminChatPage.jsx', 'utf8');
const source = page.slice(page.indexOf('function trackChatViewport'), page.indexOf('export default'));
function events() {
    const handlers = new Map();
    return {
        handlers,
        addEventListener: (key, fn) => handlers.set(key, fn),
        removeEventListener: key => handlers.delete(key),
    };
}
const styles = new Map();
const container = {
    style: { setProperty: (key, value) => styles.set(key, value), removeProperty: key => styles.delete(key) },
    getBoundingClientRect: () => ({ top: 64 }),
};
const viewport = { ...events(), height: 844, offsetTop: 0 };
let frame;
const window = {
    ...events(), innerWidth: 390, visualViewport: viewport,
    requestAnimationFrame: fn => { frame = fn; return 1; },
    cancelAnimationFrame: () => { frame = null; },
};
const context = vm.createContext({ window });
vm.runInContext(source, context);
const cleanup = context.trackChatViewport(container);
assert.equal(styles.get('--chat-viewport-height'), '780px');
viewport.height = 430;
viewport.handlers.get('resize')(); frame();
assert.equal(styles.get('--chat-viewport-height'), '366px', 'Keep the composer above the phone keyboard');
viewport.height = 844;
viewport.handlers.get('resize')(); frame();
assert.equal(styles.get('--chat-viewport-height'), '780px', 'Restore height after dismissing keyboard');
window.innerWidth = 1440;
window.handlers.get('resize')(); frame();
assert.equal(styles.has('--chat-viewport-height'), false, 'Desktop returns to CSS viewport sizing');
cleanup();
assert.equal(viewport.handlers.size + window.handlers.size, 0, 'SPA navigation must release viewport listeners');

const template = fs.readFileSync('resources/views/livewire/admin/chat-component.blade.php', 'utf8');
const stateSource = template.match(/x-data="(\{[\s\S]*?)"\s+x-init=/)[1];
const phone = vm.runInNewContext(`(${stateSource})`, {
    $wire: { selectedConversationId: null }, window: { matchMedia: () => ({ matches: false }) },
});
assert.equal(phone.mobileListOpen, true, 'First mobile visit opens the inbox');
assert.equal(phone.contextOpen, false, 'Customer details must not cover the mobile chat initially');
const desktop = vm.runInNewContext(`(${stateSource})`, {
    $wire: { selectedConversationId: 1 }, window: { matchMedia: () => ({ matches: true }) },
});
assert.equal(desktop.contextOpen, true, 'Desktop shows the third column');

let focusCount = 0;
const draft = { style: {}, scrollHeight: 900, dispatchEvent() {}, focus: () => focusCount++ };
const quickState = { wide: false, contextOpen: true, $nextTick: fn => fn() };
const document = {
    getElementById: () => draft,
    createElement: () => ({ style: {}, remove() {} }),
    body: { appendChild() {} },
};
const quickContext = vm.createContext({ ...quickState, document });
const onQuick = template.match(/x-on:chat-quick-message-selected\.window="([^"]+)"/)[1];
const quickWindow = { dispatchEvent: () => vm.runInContext(onQuick, quickContext) };
vm.runInNewContext(fs.readFileSync('resources/js/admin/chat.js', 'utf8').replace(/^import .*;$/gm, ''), {
    document, window: quickWindow, Event: class {}, CustomEvent: class {}, setTimeout() {},
});
quickWindow.copyQuickMessage('A saved reply');
assert.equal(draft.value, 'A saved reply');
assert.equal(draft.style.height, '150px');
assert.equal(quickContext.contextOpen, false, 'Choosing a reply returns mobile users to the composer');
assert.ok(focusCount > 0);
const emojiSource = template.match(/x-data="(\{ emojiOpen:[^\n]+)"/)[1];
let inputEvents = 0;
const emojiInput = {
    value: 'Xin !', selectionStart: 4, selectionEnd: 4,
    setRangeText(value, start, end) { this.value = this.value.slice(0, start) + value + this.value.slice(end); },
    dispatchEvent() { inputEvents++; }, focus() {},
};
const emojiState = vm.runInNewContext(`(${emojiSource})`, { document: { getElementById: () => emojiInput }, Event: class {} });
emojiState.emojiOpen = true;
emojiState.insertEmoji('😊');
assert.equal(emojiInput.value, 'Xin 😊!', 'Emoji is inserted at the caret without losing the draft');
assert.equal(inputEvents, 1, 'Livewire receives the emoji for both send and edit modes');
assert.equal(emojiState.emojiOpen, false);
console.log('PASS: mobile inbox, detail panel, quick replies, keyboard viewport and SPA cleanup');
