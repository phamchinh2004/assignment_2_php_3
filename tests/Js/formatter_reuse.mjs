import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import { test } from 'node:test';
import { operationsMoney } from '../../resources/js/react/lib/operations.js';
import { formatAdminDateTime, formatLocalDateTime, parseDateTime } from '../../resources/js/shared/datetime.js';

test('cached numeric formatters preserve currency, rounding, empty values and exceptional numbers', () => {
    const source = fs.readFileSync('resources/js/react/pages/admin/statistics/statistics.js', 'utf8')
        .replace(/^import .+;\s*/m, '').replace(/export /g, '');
    const statistics = vm.runInNewContext(`${source}; ({ money, number })`, { Intl });

    for (const value of [undefined, null, '', 0, -0, 0.001, -0.001, 1234567.895, -12.5, '123.45', 'invalid', NaN, Infinity, -Infinity]) {
        const numeric = Number(value || 0);
        assert.equal(operationsMoney(value), `${new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        }).format(numeric)} $`);
        assert.equal(statistics.money(value), new Intl.NumberFormat('en-US', {
            style: 'currency', currency: 'USD', maximumFractionDigits: 2,
        }).format(numeric));
        assert.equal(statistics.number(value), new Intl.NumberFormat('vi-VN').format(numeric));
    }
});

test('fixed timezone formatting preserves different options, mutations and native validation', () => {
    const value = '2026-10-02T02:00:00+07:00';
    const variants = [
        { dateStyle: 'short', timeStyle: 'short' },
        { dateStyle: 'short', timeStyle: 'medium' },
        { hour: '2-digit', minute: '2-digit', hour12: false },
        { hour: '2-digit', minute: '2-digit', hour12: 'false' },
        { hour: '2-digit', hour12: null },
        { hour: '2-digit', hour12: NaN },
        { year: 'numeric', month: 'long', day: 'numeric' },
    ];
    for (const options of variants) {
        const expected = new Intl.DateTimeFormat('vi-VN', {
            ...options, timeZone: 'Asia/Ho_Chi_Minh',
        }).format(parseDateTime(value));
        assert.equal(formatAdminDateTime(value, options), expected);
        assert.equal(formatAdminDateTime(value, options), expected);
    }

    const options = { hour: '2-digit', timeZone: 'Asia/Tokyo' };
    const first = formatLocalDateTime(value, options);
    options.timeZone = 'America/New_York';
    assert.notEqual(formatLocalDateTime(value, options), first);

    const inherited = Object.assign(Object.create({ hour12: true }), {
        hour: '2-digit', timeZone: 'Asia/Ho_Chi_Minh',
    });
    assert.equal(formatLocalDateTime(value, inherited), new Intl.DateTimeFormat('vi-VN', inherited).format(parseDateTime(value)));
    for (const hour12 of [false, true]) {
        const hiddenOption = { hour: '2-digit', timeZone: 'Asia/Ho_Chi_Minh' };
        Object.defineProperty(hiddenOption, 'hour12', { value: hour12 });
        assert.equal(formatLocalDateTime(value, hiddenOption), new Intl.DateTimeFormat('vi-VN', hiddenOption).format(parseDateTime(value)));
    }
    let timezoneReads = 0;
    const dynamicOptions = { hour: '2-digit', get timeZone() { timezoneReads += 1; return 'Asia/Tokyo'; } };
    formatLocalDateTime(value, dynamicOptions);
    assert.equal(timezoneReads, 1);
    assert.throws(() => formatAdminDateTime(value, { dateStyle: 'invalid' }), RangeError);
    assert.equal(formatAdminDateTime('invalid', {}, 'unavailable'), 'unavailable');
});

test('repeated fixed timezone renders reuse a formatter while customer time follows device changes', async () => {
    const NativeDateTimeFormat = Intl.DateTimeFormat;
    const previousZone = process.env.TZ;
    let constructions = 0;
    Intl.DateTimeFormat = function (...args) {
        constructions += 1;
        return new NativeDateTimeFormat(...args);
    };
    try {
        const datetime = await import(`../../resources/js/shared/datetime.js?reuse=${Date.now()}`);
        constructions = 0;
        for (let index = 0; index < 100; index += 1) {
            datetime.formatAdminDateTime('2026-10-02T02:00:00+07:00');
            datetime.adminDateInput('2026-10-02T02:00:00+07:00');
        }
        assert.equal(constructions, 1);

        process.env.TZ = 'Asia/Tokyo';
        const clock = { hour: '2-digit', minute: '2-digit', hour12: false };
        assert.equal(datetime.formatLocalDateTime('2026-10-02T02:00:00+07:00', clock), '04:00');
        process.env.TZ = 'America/New_York';
        assert.equal(datetime.formatLocalDateTime('2026-10-02T02:00:00+07:00', clock), '15:00');
    } finally {
        Intl.DateTimeFormat = NativeDateTimeFormat;
        if (previousZone === undefined) delete process.env.TZ;
        else process.env.TZ = previousZone;
    }
});
