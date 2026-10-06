const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/js/shared/datetime.js', 'utf8').replace(/\bexport /g, '')
    + '\n' + fs.readFileSync('resources/js/user/distribution.js', 'utf8').replace(/^import .+;\r?$/gm, '');

async function renderReceipt(snapshot) {
    const elements = new Map();
    const timers = [];
    const element = (id) => {
        if (!elements.has(id)) elements.set(id, {
            dataset: {}, style: {}, hidden: true,
            classList: { add() {}, remove() {} }, addEventListener() {},
        });
        return elements.get(id);
    };
    const context = {
        console, AbortController, JSON,
        Fireworks: class { start() {} stop() {} },
        notification() { throw new Error('Receipt unexpectedly failed'); },
        format_currency: (value) => String(value),
        route_get_10_orders_next: '/orders/next', route_check_frozen_order: '/orders/check',
        csrf: 'test', trans: { ThoiGianDatPhanPhoi: '' }, spinner: element('spinner'),
        document: {
            documentElement: { lang: 'vi' },
            querySelector: element, getElementById: element,
        },
        window: {
            setTimeout(callback) { timers.push(callback); return timers.length; },
            clearTimeout() {}, location: { href: '' },
        },
        fetch: async (url) => ({
            ok: true,
            json: async () => url === '/orders/next'
                ? { status: 200, orders: [{ id: 6, name: 'Product', quantity: 9, price: 0.68, commission_percentage: 5 }], order_next: 6 }
                : { status: 200, is_frozen: true, is_high_value_order: true, is_new_order: true,
                    order_id: 6, frozen_id: 42, custom_price: 2000, order_amount: 2000,
                    commission_percentage: 10, commission_amount: 200, ...snapshot },
        }),
    };
    vm.createContext(context);
    vm.runInContext(source, context);
    context.window.__initDistributionPage();
    await context.window.distribution();
    while (timers.length) timers.shift()();
    return element;
}

(async () => {
    // The cached source order differs from the quantity captured by the backend.
    const receipt = await renderReceipt({ order_quantity: 7, unit_price: 285.714286 });
    assert.equal(receipt('order_details_quantity').innerText, 'x7');
    assert.equal(receipt('order_details_price').innerText, '285.714286');
    assert.equal(receipt('order_details_end_value_total_price').innerText, '2000');
    assert.equal(receipt('order_details_end_value_price_rose').innerText, '200');
    assert.equal(receipt('order_details_end_value_total').innerText, '2200');

    const legacyResponse = await renderReceipt({});
    assert.equal(legacyResponse('order_details_quantity').innerText, 'x9');
    assert.equal(legacyResponse('order_details_price').innerText, String(2000 / 9));
    console.log('PASS: HVO receipt uses captured quantity and unit price; older API responses still render');
})().catch((error) => { console.error(error); process.exit(1); });
