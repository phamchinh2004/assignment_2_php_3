const fs = require('fs');
const vm = require('vm');

const source = fs.readFileSync('resources/js/user/order_detail.js', 'utf8');

function makePage() {
    const listeners = {};
    const pageRoot = {
        dataset: {},
        contains: () => true,
        addEventListener() {},
    };
    const button = {
        disabled: false,
        innerHTML: 'Confirm',
        addEventListener(type, handler) {
            listeners[type] = handler;
        },
    };

    return {
        listeners,
        pageRoot,
        button,
        spinner: { hidden: true },
    };
}

let currentPage = makePage();
const fetchUrls = [];
let timerId = 0;
const timers = new Map();

const context = {
    console: { log() {}, warn() {}, error() {} },
    AbortController,
    JSON,
    navigator: { clipboard: { writeText: async () => {} } },
    confirm: () => true,
    prompt: () => '',
    alert() {},
    notification() {},
    fetch: async (url) => {
        fetchUrls.push(url);
        return {
            json: async () => ({ status: 200, message: 'ok' }),
        };
    },
};

context.window = {
    orderDetailPageConfig: configFor('A'),
    setTimeout(fn) {
        const id = ++timerId;
        timers.set(id, fn);
        return id;
    },
    clearTimeout(id) {
        timers.delete(id);
    },
    __spaNavigate() {},
    location: { href: '', reload() {} },
};

context.document = {
    querySelector(selector) {
        if (selector === '.od-page') return currentPage.pageRoot;
        if (selector === 'meta[name="csrf-token"]') return { content: 'meta-csrf' };
        return null;
    },
    getElementById(id) {
        if (id === 'btn_confirm_order') return currentPage.button;
        if (id === 'spinner') return currentPage.spinner;
        return null;
    },
};

function configFor(orderId) {
    return {
        trans: {},
        routes: {
            confirm: `/order/${orderId}/confirm`,
            cancel: `/order/${orderId}/cancel`,
            report: `/order/${orderId}/report`,
            order: '/order',
            distribution: '/distribution',
        },
        csrf: `csrf-${orderId}`,
    };
}

async function clickConfirm() {
    await currentPage.listeners.click({
        preventDefault() {},
        stopPropagation() {},
        target: currentPage.button,
    });
}

vm.createContext(context);
vm.runInContext(source, context, { filename: 'order_detail.js' });

(async () => {
    const cleanupA = context.window.__initOrderDetailPage();
    await clickConfirm();
    cleanupA();

    currentPage = makePage();
    context.window.orderDetailPageConfig = configFor('B');

    context.window.__initOrderDetailPage();
    await clickConfirm();

    const expected = ['/order/A/confirm', '/order/B/confirm'];
    const actual = fetchUrls;

    if (JSON.stringify(actual) !== JSON.stringify(expected)) {
        console.error('FAIL: second order confirmation used a stale SPA route');
        console.error('expected:', expected.join(' -> '));
        console.error('actual:  ', actual.join(' -> '));
        process.exit(1);
    }

    console.log('PASS: each order confirmation used its current SPA route');
})();
