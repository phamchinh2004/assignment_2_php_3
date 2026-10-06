import assert from 'node:assert/strict';
import { test } from 'node:test';
import fs from 'node:fs';
import vm from 'node:vm';
import { createSupportWaitSession } from '../../resources/js/chat-waiting.js';

function fixture() {
    let time = 0, sequence = 0;
    const timers = new Map(), states = [];
    const session = createSupportWaitSession({
        render: state => states.push(state), now: () => time,
        schedule: (fn, ms) => { timers.set(++sequence, { fn, at: time + ms }); return sequence; },
        cancel: id => timers.delete(id),
    });
    return {
        session, states, timers,
        tick(ms) {
            time += ms;
            for (const [id, timer] of [...timers]) {
                if (timer.at <= time) { timers.delete(id); timer.fn(); }
            }
        },
    };
}

test('the banner appears at 30 seconds and changes at 60 seconds', () => {
    const f = fixture();
    f.session.sync(100000, 100000);
    f.tick(29999); assert.deepEqual(f.states, ['idle']);
    f.tick(1); assert.equal(f.states.at(-1), 'connecting');
    f.tick(29999); assert.equal(f.states.at(-1), 'connecting');
    f.tick(1); assert.equal(f.states.at(-1), 'waiting');
    assert.equal(f.timers.size, 0, 'No perpetual timer is needed after the second threshold');
});

test('follow-up messages and auto-replies keep the first unanswered timestamp', () => {
    const f = fixture();
    f.session.sync(100000, 100000);
    f.tick(25000);
    f.session.sync(100000, 125000);
    f.tick(5000); assert.equal(f.states.at(-1), 'connecting');
    f.session.sync(100000, 130000);
    f.tick(30000); assert.equal(f.states.at(-1), 'waiting');
});

test('a human response hides the banner and a new request starts a new wait', () => {
    const f = fixture();
    f.session.sync(100000, 145000);
    assert.equal(f.states.at(-1), 'connecting');
    f.session.sync(null, 145001);
    assert.equal(f.states.at(-1), 'idle');
    assert.equal(f.timers.size, 0);
    f.session.sync(160000, 160000);
    f.tick(29999); assert.equal(f.states.at(-1), 'idle');
    f.tick(1); assert.equal(f.states.at(-1), 'connecting');
});

test('reopening an unanswered chat restores the elapsed server time', () => {
    const f = fixture();
    f.session.sync(100000, 170000);
    assert.deepEqual(f.states, ['waiting']);
    assert.equal(f.timers.size, 0);
});

test('a delayed browser timer skips directly to the correct state', () => {
    const f = fixture();
    f.session.sync(100000, 100000);
    f.tick(90000);
    assert.deepEqual(f.states, ['idle', 'waiting']);
});

test('empty, invalid or cleared conversations have no waiting timer', () => {
    const f = fixture();
    f.session.sync('', 100000);
    assert.deepEqual(f.states, ['idle']);
    f.session.sync('invalid', 100000);
    f.session.sync(100000, 'invalid');
    assert.equal(f.timers.size, 0);
});

test('destroy cancels the timer during navigation', () => {
    const f = fixture();
    f.session.sync(100000, 100000);
    f.session.destroy();
    f.tick(60000);
    assert.deepEqual(f.states, ['idle']);
    assert.equal(f.timers.size, 0);
});

test('Livewire summary updates repaint the banner, preserve its timer, and clean up on unmount', () => {
    let time = 0, sequence = 0, observeChanges, root;
    const timers = new Map();
    const title = { textContent: '' }, description = { textContent: '' };
    const banner = {
        hidden: true,
        dataset: {
            connectingTitle: 'Đang kết nối với CSKH', connectingDescription: 'Bạn vẫn có thể gửi thêm tin nhắn.',
            waitingTitle: 'Cảm ơn bạn đã chờ', waitingDescription: 'CSKH chưa thể phản hồi ngay.',
        },
        querySelector: selector => selector === '[data-chat-wait-title]' ? title : description,
    };
    root = {
        dataset: { chatWaitSince: '100000', chatWaitNow: '100000' },
        querySelector: () => banner,
    };
    const context = vm.createContext({
        performance: { now: () => time },
        setTimeout: (fn, ms) => { timers.set(++sequence, { fn, at: time + ms }); return sequence; },
        clearTimeout: id => timers.delete(id),
        document: { documentElement: {}, getElementById: () => root, addEventListener() {} },
        window: { addEventListener() {} },
        MutationObserver: class { constructor(callback) { observeChanges = callback; } observe() {} },
    });
    vm.runInContext(fs.readFileSync('resources/js/chat-waiting.js', 'utf8').replace('export function', 'function'), context);
    const tick = ms => {
        time += ms;
        for (const [id, timer] of [...timers]) {
            if (timer.at <= time) { timers.delete(id); timer.fn(); }
        }
    };
    observeChanges();
    assert.equal(timers.size, 1, 'An unrelated DOM mutation does not create a duplicate timer');
    tick(30000);
    assert.equal(banner.hidden, false);
    assert.equal(title.textContent, 'Đang kết nối với CSKH');
    root.dataset.chatWaitNow = '140000'; observeChanges();
    tick(20000);
    assert.equal(title.textContent, 'Cảm ơn bạn đã chờ');
    assert.equal(description.textContent, 'CSKH chưa thể phản hồi ngay.');
    root.dataset.chatWaitSince = ''; root.dataset.chatWaitNow = '160001'; observeChanges();
    assert.equal(banner.hidden, true, 'A persisted human reply hides the status on the next Livewire morph');
    root.dataset.chatWaitSince = '170000'; root.dataset.chatWaitNow = '170000'; observeChanges();
    assert.equal(timers.size, 1);
    root = null; observeChanges();
    assert.equal(timers.size, 0, 'Removing chat from the SPA cancels the pending timer');
});
