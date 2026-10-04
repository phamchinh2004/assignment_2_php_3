import assert from 'node:assert/strict';
import { createTypingSession } from '../../resources/js/chat-typing.js';

let time = 0, sequence = 0, names = [];
const timers = new Map(), sent = [];
const tick = ms => {
    time += ms;
    for (const [id, timer] of [...timers]) if (timer.at <= time) { timers.delete(id); timer.fn(); }
};
const session = createTypingSession({ conversationId: 1, viewerId: 2,
    send: value => sent.push(value), render: value => { names = value; }, now: () => time,
    schedule: (fn, ms) => { timers.set(++sequence, { fn, at: time + ms }); return sequence; },
    cancel: id => timers.delete(id),
});
session.input('Hi');
session.input('Hi there');
assert.deepEqual(sent, [true], 'Keystrokes are throttled');
tick(2600); session.input('Hi there!');
assert.deepEqual(sent, [true, true], 'Long typing renews its presence');
tick(3000);
assert.deepEqual(sent, [true, true, false], 'Idle input clears typing');
session.receive({ conversation_id: 2, user_id: 1, name: 'Wrong chat', typing: true });
session.receive({ conversation_id: 1, user_id: 2, name: 'Me', typing: true });
assert.deepEqual(names, []);
session.receive({ conversation_id: 1, user_id: 1, name: 'Customer', typing: true });
session.receive({ conversation_id: 1, user_id: 3, name: 'Admin', typing: true });
assert.deepEqual(names, ['Customer', 'Admin']);
session.receive({ conversation_id: 1, user_id: 3, typing: false });
assert.deepEqual(names, ['Customer']);
tick(7000);
assert.deepEqual(names, [], 'Disconnected senders expire without a stop event');
session.input('draft'); session.destroy();
assert.equal(sent.at(-1), false);
assert.equal(timers.size, 0, 'Navigation cancels every typing timer');
console.log('PASS: typing throttle, idle stop, own/foreign filtering, multiple writers, expiry and cleanup');
