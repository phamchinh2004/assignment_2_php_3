import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import { test } from 'node:test';
import {
    adminDateInput, formatAdminDateTime, formatLocalDateTime,
    hydrateLocalDateTimes, observeLocalDateTimes, parseDateTime,
} from '../../resources/js/shared/datetime.js';

const clock = { hour: '2-digit', minute: '2-digit', hour12: false };
const instant = '2026-10-02T02:00:00+07:00';

test('customers see device time; admin always sees Vietnam time', () => {
    for (const [zone, localTime] of [
        ['Asia/Ho_Chi_Minh', '02:00'], ['Asia/Tokyo', '04:00'],
        ['America/New_York', '15:00'],
    ]) {
        process.env.TZ = zone;
        assert.equal(formatLocalDateTime(instant, clock), localTime);
        assert.equal(formatAdminDateTime(instant, clock), '02:00');
        assert.equal(formatAdminDateTime(instant, { ...clock, timeZone: zone }), '02:00');
        assert.equal(parseDateTime('2026-10-02 02:00:00').toISOString(), '2026-10-01T19:00:00.000Z');
        assert.equal(formatAdminDateTime('2026-10-02 02:00:00', clock), '02:00');
        assert.equal(parseDateTime('2026-10-01T19:00:00Z').getTime(), parseDateTime(instant).getTime());
    }
    assert.equal(formatAdminDateTime('bad timestamp'), '—');
    assert.equal(formatLocalDateTime(null, {}, 'empty'), 'empty');
});

test('report presets use the Vietnam calendar across midnight and month boundaries', () => {
    const source = fs.readFileSync('resources/js/react/pages/admin/statistics/statistics.js', 'utf8')
        .replace(/^import .+;\s*/m, '').replace(/export /g, '');
    const fixed = '2026-09-30T17:30:00Z'; // October 1 in Vietnam; September 30 in New York.
    class FixedDate extends Date {
        constructor(...args) { super(...(args.length ? args : [fixed])); }
    }
    const context = vm.createContext({ Date: FixedDate, adminDateInput });
    vm.runInContext(source, context);
    for (const zone of ['Asia/Tokyo', 'America/New_York']) {
        process.env.TZ = zone;
        assert.equal(context.today(), '2026-10-01');
        assert.equal(context.startOfMonth(), '2026-10-01');
        assert.equal(context.daysAgo(1), '2026-09-30');
        assert.equal(context.daysAgo(7), '2026-09-24');
        assert.equal(context.inclusiveDays('2026-10-01', '2026-10-07'), 7);
        assert.equal(context.inclusiveDays('2026-03-07', '2026-03-10'), 4);
    }
});

class ElementStub {
    constructor(dataset = {}, children = []) {
        this.dataset = dataset;
        this.children = children;
        this.textContent = 'server fallback';
        this.hidden = false;
        this.nodeType = 1;
    }
    matches(selector) {
        const key = selector === '[data-local-datetime]' ? 'localDatetime' : 'localDateSeparator';
        return key in this.dataset;
    }
    querySelectorAll(selector) { return this.children.filter((child) => child.matches(selector)); }
    setAttribute(name, value) { this[name] = value; }
}

test('Blade timestamps and chat separators use the customer calendar after Livewire morphs', () => {
    process.env.TZ = 'Asia/Tokyo';
    globalThis.Element = ElementStub;
    globalThis.Node = { ELEMENT_NODE: 1 };
    globalThis.document = { documentElement: { dataset: {} } };
    const time = new ElementStub({ localDatetime: instant, localFormat: 'time' });
    const reference = new ElementStub({ localDatetime: instant, timeZone: 'Asia/Ho_Chi_Minh', localFormat: 'time' });
    const separator = new ElementStub({
        // Same Vietnam day, different Japan days: separator must be visible.
        localDateSeparator: '2026-10-01T23:30:00+07:00',
        nextDatetime: '2026-10-01T21:30:00+07:00',
    });
    separator.hidden = true;
    const root = new ElementStub({}, [time, reference, separator]);
    time.parentElement = root;
    separator.parentElement = root;
    hydrateLocalDateTimes(root);
    assert.equal(time.textContent, '04:00');
    assert.equal(time.title, 'Asia/Tokyo');
    assert.equal(reference.textContent, '02:00');
    assert.equal(separator.hidden, false);

    separator.dataset.nextDatetime = '2026-10-02T00:30:00+07:00';
    hydrateLocalDateTimes(root);
    assert.equal(separator.hidden, true); // Different Vietnam days, same Japan day.
    separator.dataset.nextDatetime = '';
    hydrateLocalDateTimes(root);
    assert.equal(separator.hidden, false); // Oldest loaded message keeps a date marker.

    let callback;
    let observedOptions;
    globalThis.MutationObserver = class {
        constructor(fn) { callback = fn; }
        observe(target, options) { observedOptions = options; }
    };
    observeLocalDateTimes(root);
    assert.equal(observedOptions.characterData, true);
    time.dataset.localDatetime = '2026-10-02T03:00:00+07:00';
    callback([{ type: 'attributes', target: time, attributeName: 'data-local-datetime' }]);
    assert.equal(time.textContent, '05:00');
    time.textContent = '03:00'; // Livewire overwrites the server-rendered text.
    callback([{ type: 'childList', target: time, addedNodes: [] }]);
    assert.equal(time.textContent, '05:00');
    document.documentElement.dataset.displayTimeZone = 'Asia/Ho_Chi_Minh';
    hydrateLocalDateTimes(root);
    assert.equal(time.textContent, '03:00');
});

test('all admin date displays use the fixed formatter', () => {
    for (const directory of ['resources/js/react/pages/admin', 'resources/js/react/components/admin']) {
        for (const file of fs.readdirSync(directory, { recursive: true }).filter((name) => name.endsWith('.jsx'))) {
            const source = fs.readFileSync(`${directory}/${file}`, 'utf8');
            assert.doesNotMatch(source, /formatLocalDateTime|new Intl\.DateTimeFormat|toLocaleString\('vi-VN'/, file);
        }
    }
});
