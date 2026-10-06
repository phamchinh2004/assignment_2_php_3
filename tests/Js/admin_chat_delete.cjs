const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const template = fs.readFileSync('resources/views/livewire/admin/chat-component.blade.php', 'utf8');
const source = template.slice(template.indexOf('    // Delete Modal Handlers'), template.indexOf('    window.confirmDeleteSingleMessage'));

function setup() {
    const calls = [];
    const alerts = [];
    const timers = [];
    const modalInstances = new Map();
    const elements = new Map();
    function element(id) {
        const handlers = new Map();
        return {
            id, disabled: false, innerHTML: '',
            addEventListener(name, handler) {
                const list = handlers.get(name) || [];
                list.push(handler);
                handlers.set(name, list);
            },
            emit(name, event = {}) {
                for (const handler of handlers.get(name) || []) handler({ target: this, ...event });
            },
            closest(selector) { return selector.split(',').some(part => part.trim() === `#${id}`) ? this : null; },
            contains() { return true; },
            getAttribute() { return this.wireId; },
        };
    }
    function mount(wireId) {
        for (const id of ['chat-root', 'confirmDeleteConversationBtn', 'confirmDeleteMessagesBtn', 'deleteConversationModal', 'deleteMessagesModal', 'deleteSuccessModal', 'deleteSuccessMessage']) {
            elements.set(id, element(id));
        }
        elements.get('chat-root').wireId = wireId;
    }
    mount('first-component');
    const document = element('document');
    document.readyState = 'complete';
    document.getElementById = id => elements.get(id) || null;
    let result = true;
    class Modal {
        constructor(target) { this.target = target; this.shows = 0; this.hides = 0; modalInstances.set(target, this); }
        show() { this.shows++; }
        hide() { this.hides++; }
        static getOrCreateInstance(target) { return modalInstances.get(target) || new Modal(target); }
    }
    const context = vm.createContext({
        document, bootstrap: { Modal },
        window: { Livewire: { find: wireId => ({ call: method => {
            calls.push({ wireId, method });
            return typeof result === 'function' ? result() : Promise.resolve(result);
        } }) } },
        setTimeout: callback => timers.push(callback),
        console: { error() {} }, alert: text => alerts.push(text),
    });
    vm.runInContext(source, context);
    document.emit('livewire:initialized');
    document.emit('admin-chat:mounted');
    async function click(id) {
        const button = elements.get(id);
        button.emit('click');
        // Clicking the icon inside a confirmation button bubbles to the document.
        document.emit('click', { target: { closest: selector => button.closest(selector) } });
        await new Promise(resolve => setImmediate(resolve));
        while (timers.length) timers.shift()();
        await new Promise(resolve => setImmediate(resolve));
    }
    return { context, document, elements, calls, alerts, modalInstances, mount, click, setResult: value => { result = value; } };
}

(async () => {
    const chat = setup();
    vm.runInContext('confirmDeleteConversation()', chat.context);
    await chat.click('confirmDeleteConversationBtn');
    assert.deepEqual(chat.calls, [{ wireId: 'first-component', method: 'deleteConversation' }], 'Confirming deletion after React mount must call the selected Livewire component');
    assert.equal(chat.modalInstances.get(chat.elements.get('deleteSuccessModal'))?.shows, 1);
    assert.equal(chat.elements.get('confirmDeleteConversationBtn').disabled, false);

    chat.mount('replacement-component');
    chat.document.emit('admin-chat:mounted');
    chat.document.emit('admin-chat:mounted');
    vm.runInContext('confirmDeleteConversation()', chat.context);
    assert.equal(chat.modalInstances.get(chat.elements.get('deleteConversationModal'))?.shows, 1, 'Returning to chat must open the current modal');
    await chat.click('confirmDeleteConversationBtn');
    assert.deepEqual(chat.calls[1], { wireId: 'replacement-component', method: 'deleteConversation' });
    assert.equal(chat.calls.length, 2, 'Remount must not duplicate delete requests');

    const rejected = setup();
    rejected.setResult(false);
    vm.runInContext('confirmDeleteConversation()', rejected.context);
    await rejected.click('confirmDeleteConversationBtn');
    assert.equal(rejected.modalInstances.get(rejected.elements.get('deleteSuccessModal'))?.shows || 0, 0, 'Backend rejection must never show deletion success');
    assert.equal(rejected.alerts.length, 1);
    assert.equal(rejected.elements.get('confirmDeleteConversationBtn').disabled, false);

    const pending = setup();
    let resolveDelete;
    pending.setResult(() => new Promise(resolve => { resolveDelete = resolve; }));
    vm.runInContext('confirmDeleteConversation()', pending.context);
    await pending.click('confirmDeleteConversationBtn');
    assert.equal(pending.elements.get('confirmDeleteConversationBtn').disabled, true);
    await pending.click('confirmDeleteConversationBtn');
    assert.equal(pending.calls.length, 1, 'An in-flight deletion must block duplicate requests');
    resolveDelete(true);
    await pending.click('confirmDeleteConversationBtn');
    assert.equal(pending.elements.get('confirmDeleteConversationBtn').disabled, false);

    const failed = setup();
    failed.setResult(() => Promise.reject(new Error('Request failed')));
    vm.runInContext('confirmDeleteConversation()', failed.context);
    await failed.click('confirmDeleteConversationBtn');
    assert.equal(failed.alerts.length, 1);
    assert.equal(failed.elements.get('confirmDeleteConversationBtn').disabled, false);
    assert.equal(failed.modalInstances.get(failed.elements.get('deleteSuccessModal'))?.shows || 0, 0);

    const clear = setup();
    vm.runInContext('confirmDeleteMessages()', clear.context);
    await clear.click('confirmDeleteMessagesBtn');
    assert.deepEqual(clear.calls, [{ wireId: 'first-component', method: 'deleteAllMessages' }]);
    assert.equal(clear.modalInstances.get(clear.elements.get('deleteMessagesModal')).hides, 1);
    assert.equal(clear.elements.get('confirmDeleteMessagesBtn').disabled, false);
    assert.equal(clear.modalInstances.get(clear.elements.get('deleteSuccessModal'))?.shows, 1, 'Clearing messages must show the styled success modal exactly once');
    assert.equal(clear.elements.get('deleteSuccessMessage').textContent, 'Tất cả tin nhắn đã được xóa.');
    vm.runInContext('confirmDeleteConversation()', clear.context);
    await clear.click('confirmDeleteConversationBtn');
    assert.equal(clear.elements.get('deleteSuccessMessage').textContent, 'Hội thoại đã được xóa thành công');

    const deniedClear = setup();
    deniedClear.setResult(false);
    vm.runInContext('confirmDeleteMessages()', deniedClear.context);
    await deniedClear.click('confirmDeleteMessagesBtn');
    assert.equal(deniedClear.modalInstances.get(deniedClear.elements.get('deleteSuccessModal'))?.shows || 0, 0, 'Failed clearing must not show success');
    assert.equal(deniedClear.elements.get('confirmDeleteMessagesBtn').disabled, false);
    assert.equal(deniedClear.alerts.length, 0, 'Backend app-dialog owns operation errors');
    console.log('PASS: chat deletion after React mount, remount, backend rejection and clearing messages');
})().catch(error => { console.error(error); process.exitCode = 1; });
