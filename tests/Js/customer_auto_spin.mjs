import assert from 'node:assert/strict';
import test from 'node:test';
import { createRequire } from 'node:module';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { build } from 'esbuild';

const bundle = await build({
    stdin: {
        contents: `export { default as Modal } from './resources/js/react/pages/admin/users/CustomerAutoSpinModal.jsx';
            export { default as List } from './resources/js/react/pages/admin/users/UserListPage.jsx';`,
        resolveDir: process.cwd(),
    },
    bundle: true, write: false, platform: 'node', format: 'cjs', packages: 'external', jsx: 'automatic',
    plugins: [{
        name: 'controlled-http',
        setup(builder) {
            builder.onLoad({ filter: /react[\\/]lib[\\/]http\.js$/ }, () => ({
                contents: 'export const requestJson = (...args) => globalThis.requestJson(...args);', loader: 'js',
            }));
        },
    }],
});

// Exercise the real component handlers with controlled hooks and HTTP responses.
// Ant Design owns DOM rendering; these tests cover the loop, interruption and permission wiring.
function harness() {
    const values = [];
    const effects = [];
    let cursor = 0;
    let pendingEffects = [];
    const react = {
        useState(initial) {
            const index = cursor++;
            if (!(index in values)) values[index] = initial;
            return [values[index], (next) => { values[index] = typeof next === 'function' ? next(values[index]) : next; }];
        },
        useRef(initial) {
            const index = cursor++;
            if (!(index in values)) values[index] = { current: initial };
            return values[index];
        },
        useMemo: (calculate) => calculate(),
        useCallback: (callback) => callback,
        useEffect(effect, dependencies) {
            const index = cursor++;
            if (!effects[index] || dependencies.some((value, i) => value !== effects[index].dependencies[i])) {
                effects[index] = { effect, dependencies };
                pendingEffects.push(index);
            }
        },
    };
    const require = createRequire(import.meta.url);
    const context = {
        module: { exports: {} }, AbortController,
        requestJson: async () => { throw new Error('Unexpected request'); },
        require(name) {
            if (name === 'react') return react;
            if (name === 'antd') return new Proxy({}, { get: (_, key) => key === 'Typography' ? { Text: 'Text', Title: 'Title' } : key });
            if (name === '@ant-design/icons') return new Proxy({}, { get: (_, key) => key });
            return require(name);
        },
    };
    vm.runInNewContext(bundle.outputFiles[0].text, context);
    return {
        components: context.module.exports,
        browser(value) { context.window = value; },
        http(handler) { context.requestJson = handler; },
        render(Component, props) {
            cursor = 0;
            return Component(props);
        },
        effects() {
            for (const index of pendingEffects) effects[index].cleanup = effects[index].effect();
            pendingEffects = [];
        },
        strictRemount() {
            for (const entry of effects.filter(Boolean)) {
                entry.cleanup?.();
                entry.cleanup = entry.effect();
            }
        },
        cleanup() { effects.filter(Boolean).forEach((entry) => entry.cleanup?.()); },
    };
}

function nodes(tree, type) {
    if (Array.isArray(tree)) return tree.flatMap((item) => nodes(item, type));
    if (!tree || typeof tree !== 'object') return [];
    return [...(tree.type === type ? [tree] : []), ...nodes(tree.props?.children, type), ...nodes(tree.props?.footer, type)];
}
const flush = () => new Promise((resolve) => setImmediate(resolve));
const user = { id: 7, username: 'clone-a', full_name: 'Clone A', can_auto_spin: true };

async function modal(initial, handler, onProgress) {
    const app = harness();
    app.http(handler || (async () => initial));
    const props = { user, url: '/auto-spin/7', onClose() {}, onProgress };
    const render = () => app.render(app.components.Modal, props);
    render();
    app.effects();
    await flush();
    return { app, render };
}

test('runs the actual modal from 5 through 40 with exactly 35 sequential requests', async () => {
    let spin = 5;
    const posts = [];
    const updates = [];
    const { app, render } = await modal(null, async (_, options = {}) => {
        if (!options.method) return { current_spin: spin, total_spins: 60, pending_order_id: null };
        const payload = JSON.parse(options.body);
        posts.push(payload);
        assert.equal(payload.expected_spin, spin);
        assert.equal(payload.target_spin, 40);
        assert.equal(payload.expected_pending_order_id, null);
        spin += 1;
        return { status: 200, current_spin: spin, total_spins: 60, pending_order_id: null, confirmed_order_id: spin, done: spin === 40 };
    }, (id, progress) => updates.push({ id, spin: progress.current_spin }));
    nodes(render(), 'InputNumber')[0].props.onChange(40);
    await nodes(render(), 'Button').find((node) => node.key === 'start').props.onClick();
    assert.equal(posts.length, 35);
    assert.equal(spin, 40);
    assert.equal(updates.length, 36);
    assert.ok(updates.every((update) => update.id === user.id));
    assert.equal(updates.at(-1).spin, 40);
    assert.equal(nodes(render(), 'Alert')[0].props.type, 'success');
    assert.ok(nodes(render(), 'Alert')[0].props.message.includes('35'));
    app.cleanup();
});

test('stops after the in-flight order and does not submit another request', async () => {
    let release;
    let posts = 0;
    const { app, render } = await modal(null, async (_, options = {}) => {
        if (!options.method) return { current_spin: 5, total_spins: 60, pending_order_id: null };
        posts += 1;
        await new Promise((resolve) => { release = resolve; });
        return { status: 200, current_spin: 6, total_spins: 60, pending_order_id: null, confirmed_order_id: 1, done: false };
    });
    nodes(render(), 'InputNumber')[0].props.onChange(40);
    const running = nodes(render(), 'Button').find((node) => node.key === 'start').props.onClick();
    assert.equal(render().props.closable, false);
    nodes(render(), 'Button').find((node) => node.key === 'stop').props.onClick();
    release();
    await running;
    assert.equal(posts, 1);
    assert.equal(nodes(render(), 'Alert')[0].props.type, 'info');
    app.cleanup();
});

test('insufficient funds refreshes the pending position and allows retrying the target order', async () => {
    let pending = false;
    const updates = [];
    const { app, render } = await modal(null, async (_, options = {}) => {
        if (!options.method) return { current_spin: pending ? 6 : 5, total_spins: 60, pending_order_id: pending ? 81 : null };
        pending = true;
        throw new Error('Số dư không đủ');
    }, (_, progress) => updates.push(progress.current_spin));
    await nodes(render(), 'Button').find((node) => node.key === 'start').props.onClick();
    assert.equal(nodes(render(), 'Alert')[0].props.type, 'error');
    assert.equal(updates.at(-1), 6);
    assert.ok(nodes(render(), 'Alert')[0].props.message.includes('lượt 6'));
    assert.equal(nodes(render(), 'Progress')[0].props.status, 'exception');
    assert.ok(nodes(render(), 'Progress')[0].props.percent < 100);
    assert.equal(nodes(render(), 'Button').find((node) => node.key === 'start').props.disabled, false);
    app.http(async (_, options) => {
        assert.equal(JSON.parse(options.body).expected_pending_order_id, 81);
        return { status: 200, current_spin: 6, total_spins: 60, pending_order_id: null, confirmed_order_id: 81, done: true };
    });
    await nodes(render(), 'Button').find((node) => node.key === 'start').props.onClick();
    assert.equal(nodes(render(), 'Alert')[0].props.type, 'success');
    app.cleanup();
});

test('double-clicking start cannot run a second loop', async () => {
    let release;
    let posts = 0;
    const { app, render } = await modal(null, async (_, options = {}) => {
        if (!options.method) return { current_spin: 5, total_spins: 60, pending_order_id: null };
        posts += 1;
        await new Promise((resolve) => { release = resolve; });
        return { status: 200, current_spin: 6, total_spins: 60, pending_order_id: null, confirmed_order_id: 1, done: true };
    });
    const start = nodes(render(), 'Button').find((node) => node.key === 'start').props.onClick;
    const first = start();
    await start();
    release();
    await first;
    assert.equal(posts, 1);
    app.cleanup();
});

test('React StrictMode effect remount gets a fresh signal instead of remaining aborted', async () => {
    const signals = [];
    const { app, render } = await modal(null, async (_, options) => {
        signals.push(options.signal);
        return { current_spin: 5, total_spins: 60, pending_order_id: null };
    });
    app.strictRemount();
    await flush();
    assert.equal(signals[0].aborted, true);
    assert.equal(signals[1].aborted, false);
    assert.equal(nodes(render(), 'InputNumber')[0].props.value, 6);
    app.cleanup();
});

test('the more-actions menu requires both the permission and access to this customer', () => {
    for (const [permission, scoped, expected] of [[false, true, false], [true, false, false], [true, true, true]]) {
        const app = harness();
        const customer = { ...user, can_auto_spin: scoped };
        const tree = app.render(app.components.List, { config: {
            users: [customer], permissions: { autoSpin: permission }, routes: { autoSpin: '/user/__USER_ID__/auto-spin' },
        } });
        const actions = nodes(tree, 'Table')[0].props.columns.find((column) => column.key === 'actions').render(null, customer);
        const items = nodes(actions, 'Dropdown')[0].props.menu.items;
        assert.equal(items.some((item) => item.key === 'auto-spin'), expected);
    }
});

test('closing automatic spin preserves customer filters and pagination without refreshing the page', () => {
    const app = harness();
    let refreshes = 0;
    app.browser({
        __spaRefresh: () => { refreshes += 1; },
        location: { reload: () => { refreshes += 1; } },
    });
    const props = { config: {
        users: Array.from({ length: 30 }, (_, index) => ({ ...user, id: index + 1, username: `clone-a-${index}`, clone_account: true })),
        permissions: { autoSpin: true },
        routes: { autoSpin: '/user/__USER_ID__/auto-spin' },
    } };
    const render = () => app.render(app.components.List, props);
    nodes(render(), 'Input')[0].props.onChange({ target: { value: 'clone-a' } });
    nodes(render(), 'button').find((node) => node.key === 'clone').props.onClick();
    nodes(render(), 'Button').find((node) => node.props['aria-label'] === 'Trang sau').props.onClick();
    const table = nodes(render(), 'Table')[0];
    const pageIds = table.props.dataSource.map((customer) => customer.id);
    const actions = table.props.columns.find((column) => column.key === 'actions').render(null, table.props.dataSource[0]);
    nodes(actions, 'Dropdown')[0].props.menu.items.find((item) => item.key === 'auto-spin').onClick();
    const opened = nodes(render(), app.components.Modal)[0];
    assert.ok(opened);
    const spunId = table.props.dataSource[0].id;
    opened.props.onProgress(spunId, { current_spin: 40, total_spins: 60 });
    opened.props.onClose();
    const closed = render();
    assert.equal(nodes(closed, app.components.Modal).length, 0);
    assert.equal(refreshes, 0);
    assert.equal(nodes(closed, 'Input')[0].props.value, 'clone-a');
    assert.equal(nodes(closed, 'button').find((node) => node.key === 'clone').props['aria-selected'], true);
    assert.deepEqual(nodes(closed, 'Table')[0].props.dataSource.map((customer) => customer.id), pageIds);
    assert.equal(nodes(closed, 'span').find((node) => node.props.className === 'customer-current-page').props.children, 2);
    const updatedUsers = nodes(closed, 'Table')[0].props.dataSource;
    assert.equal(updatedUsers.find((customer) => customer.id === spunId).current_spin, 40);
    assert.equal(updatedUsers.find((customer) => customer.id === spunId).total_spins, 60);
    assert.ok(updatedUsers.filter((customer) => customer.id !== spunId).every((customer) => customer.current_spin === undefined));
});

test('customer progress displays 4/50 on desktop and mobile with safe empty defaults', () => {
    for (const [progress, expected] of [[{ current_spin: 4, total_spins: 50 }, 'Đơn 4/50'], [{}, 'Đơn 0/0']]) {
        const app = harness();
        const customer = { ...user, ...progress };
        const tree = app.render(app.components.List, { config: { users: [customer] } });
        const table = nodes(tree, 'Table')[0];
        const identity = table.props.columns.find((column) => column.key === 'customer').render(null, customer);
        const badge = (content) => nodes(content, 'Tag').find((node) => node.props.className === 'customer-badge customer-badge--spin');
        assert.equal(badge(identity).props.children.join(''), expected);
        assert.equal(badge(tree).props.children.join(''), expected);
    }
});

test('the dialog and its mask are above the fixed admin header, sidebar and flash layer', async () => {
    const { app, render } = await modal({ current_spin: 5, total_spins: 60, pending_order_id: null });
    const layers = [
        ['resources/css/admin/header.css', /\.arround_topbar\s*\{[^}]*z-index:\s*(\d+)/],
        ['resources/css/admin/sidebar.css', /z-index:\s*(\d+)/],
        ['resources/css/admin/react-admin.css', /\.admin-react-flash\s*\{[^}]*z-index:\s*(\d+)/],
    ].map(([path, pattern]) => {
        const match = readFileSync(path, 'utf8').match(pattern);
        assert.ok(match, `Missing fixed layer in ${path}`);
        return Number(match[1]);
    });
    // Modal's zIndex prop controls both rc-dialog's wrapper and mask, unlike a content-only CSS override.
    assert.ok(render().props.zIndex > Math.max(...layers));
    app.cleanup();
});
