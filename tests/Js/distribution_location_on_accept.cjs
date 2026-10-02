const fs = require('fs');
const vm = require('vm');

const source = fs.readFileSync('resources/js/user/distribution.js', 'utf8');

const listeners = {};
const root = { dataset: {} };

function makeElement(id = '') {
    return {
        id,
        dataset: id === 'btn_phan_phoi_ngay' ? { frozenId: '42' } : {},
        hidden: true,
        disabled: false,
        style: {},
        classList: { add() {}, remove() {} },
        addEventListener(type, handler) {
            if (id === 'btn_phan_phoi_ngay') listeners[type] = handler;
        },
    };
}

const elements = new Map();
const getElement = (id) => {
    if (!elements.has(id)) elements.set(id, makeElement(id));
    return elements.get(id);
};

let geolocationCalls = 0;
const fetchUrls = [];

const context = {
    console: { log() {}, warn() {}, error() {} },
    AbortController,
    JSON,
    Fireworks: class {
        start() {}
        stop() {}
    },
    navigator: {
        geolocation: {
            getCurrentPosition() {
                geolocationCalls += 1;
                // Deliberately leave the GPS request pending: accepting an order
                // must continue without waiting for location permission/result.
            },
        },
    },
    notification() {},
    format_currency(value) { return String(value); },
    route_get_10_orders_next: '/orders/next',
    route_update_approximate_location: '/location/approximate',
    route_update_location: '/location',
    route_check_frozen_order: '/orders/check',
    route_accept_order: '/accept-order',
    route_order: '/order',
    route_handle_distribution: '/distribution/handle',
    csrf: 'csrf-token',
    trans: { ThanhCong: 'OK', Loi: 'Error' },
    spinner: getElement('spinner'),
    fetch: async (url) => {
        fetchUrls.push(url);

        if (url === '/orders/next') {
            return {
                ok: true,
                json: async () => ({ status: 200, orders: [], order_next: 0 }),
            };
        }

        if (url === '/accept-order') {
            return {
                ok: true,
                json: async () => ({ status: 400, message: 'test-stop' }),
            };
        }

        return {
            ok: true,
            json: async () => ({ status: 200 }),
        };
    },
};

context.window = {
    setTimeout() { return 1; },
    clearTimeout() {},
    location: { href: '' },
};

context.document = {
    documentElement: { lang: 'vi' },
    querySelector(selector) {
        if (selector === '.distribution-page') return root;
        return makeElement(selector);
    },
    getElementById(id) {
        return getElement(id);
    },
};

vm.createContext(context);
vm.runInContext(source, context, { filename: 'distribution.js' });

(async () => {
    context.window.__initDistributionPage();
    await Promise.resolve();

    const click = listeners.click;
    if (typeof click !== 'function') {
        console.error('FAIL: accept-order click handler was not registered');
        process.exit(1);
    }

    await click.call(getElement('btn_phan_phoi_ngay'));

    if (geolocationCalls !== 1) {
        console.error(`FAIL: accepting an order requested geolocation ${geolocationCalls} time(s), expected 1`);
        process.exit(1);
    }

    if (!fetchUrls.includes('/accept-order')) {
        console.error('FAIL: accepting an order waited for geolocation instead of continuing immediately');
        process.exit(1);
    }

    console.log('PASS: accepting an order requests geolocation without blocking the accept request');
})();
