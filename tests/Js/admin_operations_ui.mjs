import assert from 'node:assert/strict';
import fs from 'node:fs';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { test } from 'node:test';
import { buildSync } from 'esbuild';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { matchesOperationsSearch } from '../../resources/js/react/lib/operations.js';

// Render the actual pages with installed Ant Design, without a browser or live data.
const root = fileURLToPath(new URL('../../', import.meta.url));
const output = fileURLToPath(new URL(`.operations-render-${process.pid}.cjs`, import.meta.url));
let pages;
try {
    const bundle = buildSync({
        absWorkingDir: root,
        stdin: {
            contents: `
                export { default as Staff } from './resources/js/react/pages/admin/staff/StaffListPage.jsx';
                export { default as Deposit } from './resources/js/react/pages/admin/transactions/DepositTransactionsPage.jsx';
                export { default as Withdraw } from './resources/js/react/pages/admin/transactions/WithdrawTransactionsPage.jsx';
                export { default as Distribution } from './resources/js/react/pages/admin/order-distributions/OrderDistributionListPage.jsx';
                export { default as Rewards } from './resources/js/react/pages/admin/lucky-wheel-rewards/LuckyWheelRewardsPage.jsx';
                export { default as StaffShow } from './resources/js/react/pages/admin/staff/StaffShowPage.jsx';
                export { default as RankShow } from './resources/js/react/pages/admin/ranks/RankShowPage.jsx';
                export { default as DistributionShow } from './resources/js/react/pages/admin/order-distributions/OrderDistributionShowPage.jsx';
                export { default as Reports } from './resources/js/react/pages/admin/order-reports/OrderReportListPage.jsx';
                export { default as ReportShow } from './resources/js/react/pages/admin/order-reports/OrderReportShowPage.jsx';
                export { default as UserEdit } from './resources/js/react/pages/admin/users/UserEditPage.jsx';
                export { default as UserFrozen } from './resources/js/react/pages/admin/users/UserFrozenOrdersPage.jsx';
                export { CustomerRevenueRanking } from './resources/js/react/pages/admin/statistics/CustomerRevenuePage.jsx';
            `,
            resolveDir: root,
        },
        bundle: true, write: false, platform: 'node', format: 'cjs', packages: 'external', jsx: 'automatic',
    });
    fs.writeFileSync(output, bundle.outputFiles[0].text);
    pages = createRequire(import.meta.url)(output);
} finally {
    if (fs.existsSync(output)) fs.unlinkSync(output);
}

const user = { id: 7, full_name: 'Đặng Minh', username: 'minh', phone: '0901234567', balance: 9999 };
const transaction = { id: 12, user, value: 50, status: 'processing', transaction_type: 'normal', balance_before: null, balance_after: null, created_at: '2026-10-03T02:30:00+07:00' };
const routes = {
    show: '/staff/__STAFF_ID__', permissions: '/staff/__STAFF_ID__/permissions', edit: '/staff/__STAFF_ID__/edit',
    changeStatus: '/staff/__STAFF_ID__/status', onlineStatuses: '/staff/presence', create: '/staff/create',
    customerShow: '/customer/__USER_ID__', changeType: '/deposit/__TRANSACTION_ID__/type',
    destroy: '/deposit/__TRANSACTION_ID__', confirm: '/withdraw/__TRANSACTION_ID__/confirm', cancel: '/withdraw/__TRANSACTION_ID__/cancel',
};

function render(Page, config) {
    // LaravelForm only reads the CSRF meta element during rendering.
    globalThis.document = { querySelector: () => ({ getAttribute: () => 'test-csrf' }) };
    globalThis.window = { location: { origin: 'http://localhost', href: 'http://localhost/admin/test' } };
    try { return renderToStaticMarkup(React.createElement(Page, { config })); }
    finally { delete globalThis.document; delete globalThis.window; }
}

test('search handles Vietnamese accents, mixed case, split fields and absent customers', () => {
    assert.equal(matchesOperationsSearch([user.full_name, user.phone, 'Staff'], 'DANG 0901'), true);
    assert.equal(matchesOperationsSearch([user.full_name, 'Staff'], 'ĐẶNG staff'), true);
    assert.equal(matchesOperationsSearch([null, undefined, 12], '12'), true);
    assert.equal(matchesOperationsSearch([null, undefined], '   '), true);
    assert.equal(matchesOperationsSearch([user.full_name], 'other'), false);
});

test('staff actions require both page permission and permission over the target account', () => {
    const config = { routes, staffs: [{ ...user, role: 'staff', status: 'activated', can_manage: true, can_manage_permissions: true }] };
    const readOnly = render(pages.Staff, config);
    for (const link of ['/staff/7', '/staff/create', '/staff/7/permissions', '/staff/7/edit', '/staff/7/status']) {
        assert.equal(readOnly.includes(`href="${link}"`), false);
    }
    const permissions = { create: true, viewDetail: true, viewPermissions: true, update: true, changeStatus: true };
    const allowed = render(pages.Staff, { ...config, permissions });
    for (const link of ['/staff/7', '/staff/create', '/staff/7/permissions', '/staff/7/edit', '/staff/7/status']) {
        assert.equal(allowed.includes(`href="${link}"`), true);
    }
    const restricted = render(pages.Staff, { ...config, permissions, staffs: [{ ...config.staffs[0], can_manage: false, can_manage_permissions: false }] });
    for (const link of ['/staff/7/permissions', '/staff/7/edit', '/staff/7/status']) assert.equal(restricted.includes(`href="${link}"`), false);
});

test('deposit mutations and customer links respect permissions; unknown snapshots stay unknown', () => {
    const config = { routes, transactions: [transaction] };
    const readOnly = render(pages.Deposit, config);
    assert.equal(readOnly.includes('href="/customer/7"'), false);
    assert.equal(readOnly.includes('href="/deposit/12/type"'), false);
    assert.equal(readOnly.includes('action="/deposit/12"'), false);
    assert.equal(readOnly.includes('9,999.00'), false);
    assert.ok((readOnly.match(/>—<\/span>/g) || []).length >= 2);
    const allowed = render(pages.Deposit, { ...config, permissions: { viewCustomerDetail: true, changeType: true, delete: true } });
    assert.ok(allowed.includes('href="/customer/7"'));
    assert.ok(allowed.includes('href="/deposit/12/type"'));
    assert.ok(allowed.includes('action="/deposit/12"'));
    assert.ok(allowed.includes('name="_method" value="DELETE"'));
    assert.ok(allowed.includes('name="_token" value="test-csrf"'));
});

test('withdrawal actions are visible only with permission and for pending requests', () => {
    const config = { routes, transactions: [transaction] };
    const readOnly = render(pages.Withdraw, config);
    assert.equal(readOnly.includes('>Duyệt</span>'), false);
    assert.equal(readOnly.includes('action="/withdraw/12/cancel"'), false);
    assert.equal(readOnly.includes('Đã xử lý'), false);
    const permissions = { confirm: true, cancel: true };
    const allowed = render(pages.Withdraw, { ...config, permissions });
    assert.ok(allowed.includes('>Duyệt</span>'));
    assert.ok(allowed.includes('action="/withdraw/12/cancel"'));
    assert.ok(allowed.includes('name="_token" value="test-csrf"'));
    const completed = render(pages.Withdraw, { ...config, permissions, transactions: [{ ...transaction, status: 'completed' }] });
    assert.equal(completed.includes('>Duyệt</span>'), false);
    assert.equal(completed.includes('action="/withdraw/12/cancel"'), false);
    assert.ok(completed.includes('Đã xử lý'));
});

test('deposit and withdrawal rows show the customer avatar without requiring a detail link', () => {
    for (const Page of [pages.Deposit, pages.Withdraw]) {
        const avatarUrl = '/storage/uploads/avatars/customer.jpg';
        const config = { routes, transactions: [{ ...transaction, user: { ...user, avatar_url: avatarUrl } }] };
        for (const viewCustomerDetail of [false, true]) {
            const html = render(Page, { ...config, permissions: { viewCustomerDetail } });
            assert.ok(html.includes(`src="${avatarUrl}"`));
            assert.equal(html.includes('href="/customer/7"'), viewCustomerDetail);
        }
        const noAvatar = render(Page, { routes, transactions: [transaction] });
        assert.ok(noAvatar.includes('aria-label="user"'));
        const missingUser = render(Page, { routes, transactions: [{ ...transaction, user: null }] });
        assert.ok(missingUser.includes('Tài khoản đã xóa'));
    }
});

test('wheel rewards show avatars while customer links and approval actions respect permissions', () => {
    const avatarUrl = '/storage/uploads/avatars/reward-customer.jpg';
    const reward = { id: 21, user_id: user.id, user: { ...user, avatar_url: avatarUrl }, reward_status: 'pending', reward_amount: 2 };
    const rewardRoutes = { customerShow: routes.customerShow, approve: '/rewards/__SPIN_ID__/approve', reject: '/rewards/__SPIN_ID__/reject' };
    for (const allowed of [false, true]) {
        const html = render(pages.Rewards, { routes: rewardRoutes, rewards: { data: [reward] }, permissions: { viewCustomerDetail: allowed, approve: allowed, reject: allowed } });
        assert.ok(html.includes(`src="${avatarUrl}"`));
        assert.equal(html.includes('href="/customer/7"'), allowed);
        assert.equal(html.includes('action="/rewards/21/approve"'), allowed);
        assert.equal(html.includes('action="/rewards/21/reject"'), allowed);
    }
    const noAvatar = render(pages.Rewards, { routes: rewardRoutes, rewards: { data: [{ ...reward, user }] } });
    assert.ok(noAvatar.includes('aria-label="user"'));
    const missingUser = render(pages.Rewards, { routes: rewardRoutes, rewards: { data: [{ ...reward, user: null }] } });
    assert.ok(missingUser.includes('Người dùng #7'));
});

test('distribution recipient avatars render with either detail permission and with missing users', () => {
    const avatarUrl = '/storage/uploads/avatars/recipient.jpg';
    const config = {
        routes: { index: '/order-distributions', show: '/order-distributions/__FROZEN_ID__' },
        frozenOrders: { data: [{ id: 8, user: { ...user, avatar_url: avatarUrl }, user_id: 7, status: 'pending', created_at: transaction.created_at }], total: 1 },
        statuses: [{ name: 'pending', display_name: 'Chờ xử lý' }],
    };
    for (const viewDetail of [false, true]) {
        const html = render(pages.Distribution, { ...config, permissions: { viewDetail } });
        assert.ok(html.includes(`src="${avatarUrl}"`));
        assert.equal(html.includes('>Xem chi tiết</span>'), viewDetail);
    }
    const noAvatar = render(pages.Distribution, { ...config, frozenOrders: { data: [{ ...config.frozenOrders.data[0], user }] } });
    assert.ok(noAvatar.includes('aria-label="user"'));
    const missingUser = render(pages.Distribution, { ...config, frozenOrders: { data: [{ ...config.frozenOrders.data[0], user: null }] } });
    assert.ok(missingUser.includes('Tài khoản không còn tồn tại'));
    for (const Page of [pages.Staff, pages.Deposit, pages.Withdraw, pages.Distribution]) assert.ok(render(Page, { routes }).includes('admin-operations-page'));
});

test('customer lists on staff and rank details keep avatars visible with or without profile permission', () => {
    const customer = { ...user, avatar_url: '/storage/uploads/avatars/detail.jpg' };
    for (const Page of [pages.StaffShow, pages.RankShow]) {
        for (const viewCustomerDetail of [false, true]) {
            const html = render(Page, {
                routes: { index: '/admin/list', customerShow: '/customer/__USER_ID__', userShow: '/customer/__USER_ID__' },
                referrals: { data: [customer] }, users: { data: [customer] }, permissions: { viewCustomerDetail },
            });
            assert.ok(html.includes(`src="${customer.avatar_url}"`));
            assert.equal(html.includes('href="/customer/7"'), viewCustomerDetail);
        }
    }
});

test('distribution, report and customer action screens render the account avatar', () => {
    const customer = { ...user, avatar_url: '/storage/uploads/avatars/detail.jpg' };
    const frozen = { id: 8, user_id: user.id, user: customer, status: 'pending' };
    const report = { id: 9, status: 'pending', reporter: customer, frozen_order: frozen };
    const commonRoutes = { index: '/admin/list', show: '/reports/__REPORT_ID__' };
    const cases = [
        [pages.DistributionShow, { routes: commonRoutes, frozenOrder: frozen }],
        [pages.Reports, { routes: commonRoutes, reports: { data: [report] } }],
        [pages.ReportShow, { routes: commonRoutes, orderReport: report, frozenOrder: frozen }],
        [pages.UserEdit, { routes: commonRoutes, user: customer }],
        [pages.UserFrozen, { routes: commonRoutes, user: customer }],
    ];
    for (const [Page, config] of cases) assert.ok(render(Page, config).includes(`src="${customer.avatar_url}"`));
    const actor = { ...user, avatar_url: '/storage/uploads/avatars/history.jpg' };
    const event = { id: 4, changed_by: actor, status: { id: 1, display_name: 'Đã nhận' } };
    assert.ok(render(pages.DistributionShow, { routes: commonRoutes, frozenOrder: { ...frozen, status_orders: [event] } }).includes(`src="${actor.avatar_url}"`));
    assert.ok(render(pages.ReportShow, { routes: commonRoutes, orderReport: report, frozenOrder: frozen, timeline: [{ status: event.status, statusOrder: event, isReached: true }] }).includes(`src="${actor.avatar_url}"`));
});

test('statistics rankings identify customers by avatar and keep the anonymous remainder separate', () => {
    const customers = [{ ...user, avatar_url: '/storage/uploads/avatars/top.jpg', total_revenue: 200 }];
    const ranking = renderToStaticMarkup(React.createElement(pages.CustomerRevenueRanking, { customers, ranked: true }));
    assert.ok(ranking.includes('src="/storage/uploads/avatars/top.jpg"'));
    assert.ok(ranking.includes('200.00'));
    const legend = renderToStaticMarkup(React.createElement(pages.CustomerRevenueRanking, { customers, distribution: { labels: [user.full_name, 'Khác'], values: [200, 100] } }));
    assert.ok(legend.includes('>Khác</span>'));
    assert.equal((legend.match(/<img /g) || []).length, 1);
    const empty = renderToStaticMarkup(React.createElement(pages.CustomerRevenueRanking, { customers: [] }));
    assert.ok(empty.includes('Chưa có dữ liệu khách hàng trong kỳ.'));
});
