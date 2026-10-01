import { useEffect, useMemo, useState } from 'react';
import {
    Avatar,
    Button,
    Dropdown,
    Input,
    InputNumber,
    Modal,
    Select,
    Table,
    Tag,
    message,
} from 'antd';
import {
    CheckCircleOutlined,
    ClockCircleOutlined,
    CloseOutlined,
    CreditCardOutlined,
    DollarOutlined,
    EditOutlined,
    EyeOutlined,
    FilterOutlined,
    GiftOutlined,
    GlobalOutlined,
    LeftOutlined,
    MessageOutlined,
    MoreOutlined,
    PlusOutlined,
    RightOutlined,
    SearchOutlined,
    SortAscendingOutlined,
    StopOutlined,
    TeamOutlined,
    UserOutlined,
    WalletOutlined,
} from '@ant-design/icons';
import { requestJson } from '../../../lib/http';
import { spaGetAction, spaRefresh } from '../../../navigation';
import { AdminPage } from '../../../components/admin/AdminUi';

function replaceRoute(template, id) {
    return template.replace('__USER_ID__', String(id));
}

function money(value, digits = 2) {
    return Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    });
}

function statusMeta(status) {
    if (status === 'activated') return { color: 'success', label: 'Đã kích hoạt' };
    if (status === 'inactivated') return { color: 'warning', label: 'Chưa kích hoạt' };
    return { color: 'error', label: 'Bị khóa' };
}

function percentage(value, total) {
    if (!total) return 0;
    return Math.round((Number(value || 0) / Number(total)) * 100);
}

function lastSeenTimestamp(user) {
    if (!user?.last_seen) return 0;
    const timestamp = new Date(user.last_seen).getTime();
    return Number.isNaN(timestamp) ? 0 : timestamp;
}

function MetricSparkline({ tone }) {
    return (
        <svg className={'customer-metric-spark customer-metric-spark--' + tone} viewBox="0 0 92 38" aria-hidden="true">
            <path d="M2 31 C12 30, 15 22, 23 24 S35 7, 45 13 S57 30, 66 18 S79 22, 90 9" />
        </svg>
    );
}

export default function UserListPage({ config }) {
    const initialUsers = config.users || [];
    const permissions = config.permissions || {};
    const routes = config.routes || {};
    const stats = config.stats || {};
    const [users, setUsers] = useState(initialUsers);
    const [filter, setFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [sortKey, setSortKey] = useState('default');
    const [page, setPage] = useState(1);
    const [pageSize, setPageSize] = useState(20);
    const [depositUser, setDepositUser] = useState(null);
    const [depositAmount, setDepositAmount] = useState(null);
    const [depositType, setDepositType] = useState('real');
    const [depositing, setDepositing] = useState(false);

    useEffect(() => {
        if (!routes.onlineStatuses) return undefined;

        const refresh = async () => {
            try {
                const payload = await requestJson(routes.onlineStatuses);
                if (!payload?.success || !Array.isArray(payload.users)) return;
                const statuses = new Map(payload.users.map((item) => [String(item.id), item]));
                setUsers((current) => current.map((user) => {
                    const status = statuses.get(String(user.id));
                    return status ? {
                        ...user,
                        is_online: status.is_online,
                        last_seen: status.last_seen,
                        last_seen_formatted: status.last_seen_formatted,
                        last_seen_diff: status.last_seen_diff,
                    } : user;
                }));
            } catch {
                // Polling failure is non-blocking.
            }
        };

        const timer = window.setInterval(refresh, 60000);
        window.addEventListener('focus', refresh);
        return () => {
            window.clearInterval(timer);
            window.removeEventListener('focus', refresh);
        };
    }, [routes.onlineStatuses]);

    const filteredUsers = useMemo(() => {
        const needle = search.trim().toLowerCase();
        const matching = users.filter((user) => {
            if (filter === 'active' && user.status !== 'activated') return false;
            if (filter === 'inactive' && user.status !== 'inactivated') return false;
            if (filter === 'locked' && ['activated', 'inactivated'].includes(user.status)) return false;
            if (filter === 'frozen' && !user.has_frozen_order) return false;
            if (filter === 'clone' && !user.clone_account) return false;
            if (!needle) return true;
            return [
                user.id,
                user.full_name,
                user.username,
                user.phone,
                user.rank?.name,
                user.referrer?.full_name,
                user.referrer?.username,
                user.location_city,
                user.location_country,
            ].some((value) => String(value || '').toLowerCase().includes(needle));
        });

        return [...matching].sort((left, right) => {
            const onlinePriority = Number(Boolean(right.is_online)) - Number(Boolean(left.is_online));
            if (onlinePriority !== 0) return onlinePriority;

            const realAccountPriority = Number(Boolean(left.clone_account)) - Number(Boolean(right.clone_account));
            if (realAccountPriority !== 0) return realAccountPriority;

            if (sortKey === 'default') {
                const activityPriority = lastSeenTimestamp(right) - lastSeenTimestamp(left);
                if (activityPriority !== 0) return activityPriority;
                return Number(left.position || 0) - Number(right.position || 0);
            }

            if (sortKey === 'name') {
                return String(left.full_name || left.username || '').localeCompare(
                    String(right.full_name || right.username || ''),
                    'vi',
                );
            }
            if (sortKey === 'newest') {
                return Number(right.id || 0) - Number(left.id || 0);
            }
            if (sortKey === 'balance' && permissions.viewFinancials) {
                return Number(right.balance || 0) - Number(left.balance || 0);
            }
            return Number(left.position || 0) - Number(right.position || 0);
        });
    }, [users, filter, search, sortKey, permissions.viewFinancials]);

    useEffect(() => {
        setPage(1);
    }, [filter, search, sortKey, pageSize]);

    const pageCount = Math.max(1, Math.ceil(filteredUsers.length / pageSize));
    const safePage = Math.min(page, pageCount);
    const pageUsers = useMemo(() => {
        const start = (safePage - 1) * pageSize;
        return filteredUsers.slice(start, start + pageSize);
    }, [filteredUsers, safePage, pageSize]);

    const onlineCount = users.filter((user) => user.is_online).length;
    const realAccountCount = users.filter((user) => !user.clone_account).length;
    const realAccountAvailableBalance = users
        .filter((user) => !user.clone_account)
        .reduce((total, user) => total + Number(user.balance || 0), 0);
    const realAccountFrozenBalance = users
        .filter((user) => !user.clone_account)
        .reduce((total, user) => total + Number(user.frozen_balance || 0), 0);

    const changeStatus = (user) => {
        Modal.confirm({
            title: 'Thay đổi trạng thái tài khoản?',
            content: 'Trạng thái hiện tại: ' + statusMeta(user.status).label + '.',
            okText: 'Tiếp tục',
            cancelText: 'Hủy',
            onOk: async () => {
                const payload = await spaGetAction(replaceRoute(routes.changeStatus, user.id));
                const text = payload?.props?.flash?.success || payload?.props?.flash?.error;
                if (text) message[payload?.props?.flash?.error ? 'error' : 'success'](text);
            },
        });
    };

    const submitDeposit = async () => {
        if (!depositUser || !depositAmount || Number(depositAmount) <= 0) {
            message.warning('Vui lòng nhập số tiền lớn hơn 0.');
            return;
        }
        setDepositing(true);
        try {
            const payload = await requestJson(routes.plusMoney, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    value: Number(depositAmount),
                    user_id: depositUser.id,
                    isRealDeposit: depositType === 'real',
                }),
            });
            if (payload?.status !== 200) {
                throw new Error(payload?.message || 'Không thể nạp tiền.');
            }
            message.success(payload.message || 'Nạp tiền thành công.');
            setDepositUser(null);
            setDepositAmount(null);
            window.setTimeout(() => spaRefresh(), 500);
        } catch (error) {
            message.error(error.message || 'Không thể nạp tiền.');
        } finally {
            setDepositing(false);
        }
    };

    const closeDepositModal = () => {
        if (depositing) return;
        setDepositUser(null);
        setDepositAmount(null);
        setDepositType('real');
    };

    const openDepositModal = (user) => {
        setDepositUser(user);
        setDepositAmount(null);
        setDepositType('real');
    };

    const getUserActionItems = (user) => [
        {
            key: 'chat',
            icon: <MessageOutlined />,
            label: user.chat_url ? <a href={user.chat_url}>Nhắn tin</a> : 'Nhắn tin',
            disabled: !user.chat_url,
        },
        ...(permissions.viewDetail ? [{
            key: 'detail',
            icon: <EyeOutlined />,
            label: <a href={replaceRoute(routes.show, user.id)}>Chi tiết</a>,
        }] : []),
        ...(permissions.update ? [{
            key: 'edit',
            icon: <EditOutlined />,
            label: <a href={replaceRoute(routes.edit, user.id)}>Chỉnh sửa</a>,
        }] : []),
        ...(permissions.manageFrozenOrders ? [{
            key: 'frozen',
            icon: <span className="customer-action-snowflake">❄</span>,
            label: <a href={replaceRoute(routes.frozenOrders, user.id)}>Đơn đóng băng</a>,
        }] : []),
        ...(permissions.changeStatus ? [{
            key: 'status',
            icon: user.status === 'activated' ? <StopOutlined /> : <CheckCircleOutlined />,
            label: user.status === 'activated' ? 'Khóa tài khoản' : 'Kích hoạt tài khoản',
            danger: user.status === 'activated',
            onClick: () => changeStatus(user),
        }] : []),
    ];

    const columns = [
        {
            title: '#',
            dataIndex: 'position',
            width: '5%',
            align: 'center',
            render: (_, __, index) => (
                <span className="customer-row-index">#{((safePage - 1) * pageSize) + index + 1}</span>
            ),
        },
        {
            title: 'Khách hàng',
            key: 'customer',
            width: permissions.viewFinancials ? '32%' : '42%',
            render: (_, user) => (
                <div className="customer-identity">
                    <Avatar size={44} src={user.avatar_url} icon={<UserOutlined />} />
                    <div className="customer-identity__copy">
                        <div className="customer-identity__topline">
                            {permissions.viewDetail ? (
                                <a className="customer-identity__name" href={replaceRoute(routes.show, user.id)}>
                                    {user.full_name || 'Chưa đặt tên'}
                                </a>
                            ) : (
                                <span className="customer-identity__name">{user.full_name || 'Chưa đặt tên'}</span>
                            )}
                            <span className="customer-id-chip">ID: {user.id}</span>
                        </div>
                        <span className="customer-identity__meta">@{user.username}</span>
                        <div className="customer-badges">
                            {user.rank?.name && <Tag className="customer-badge customer-badge--rank">{user.rank.name}</Tag>}
                            {user.referrer && (
                                <span className="customer-manager-note">
                                    QL: {user.referrer.full_name || `@${user.referrer.username}`}
                                </span>
                            )}
                        </div>
                        <span className={'customer-presence customer-presence--inline' + (user.is_online ? ' customer-presence--online' : '')}>
                            <i />
                            {user.is_online ? 'Online' : (user.last_seen_diff || 'Chưa từng online')}
                        </span>
                    </div>
                </div>
            ),
        },
        ...(permissions.viewFinancials ? [{
            title: 'Số dư & tài chính',
            key: 'finance',
            width: '18%',
            render: (_, user) => (
                <div className="customer-finance">
                    <strong><WalletOutlined /> {money(user.balance, 5)}$</strong>
                    <span><span className="customer-frozen-dot">❄</span>{money(user.frozen_balance, 2)}$</span>
                </div>
            ),
        }] : []),
        {
            title: 'Vị trí & khu vực',
            key: 'location',
            width: permissions.viewFinancials ? '19%' : '23%',
            render: (_, user) => (
                <div className="customer-location">
                    <strong><GlobalOutlined /> {user.location_country_code || '—'} {user.location_country || 'Chưa rõ'}</strong>
                    <small>{[user.location_city, user.warehouse_area].filter(Boolean).join(' • ') || 'Chưa định vị'}</small>
                </div>
            ),
        },
        {
            title: 'Trạng thái',
            key: 'status',
            width: permissions.viewFinancials ? '14%' : '17%',
            render: (_, user) => {
                const meta = statusMeta(user.status);
                return (
                    <div className="customer-status-stack">
                        <span className={`customer-status-pill customer-status-pill--${meta.color}`}><i />{meta.label}</span>
                        <span className={`customer-account-kind ${user.clone_account ? 'is-clone' : ''}`}>
                            {user.clone_account ? 'Clone' : 'Tài khoản thật'}
                        </span>
                    </div>
                );
            },
        },
        {
            title: 'Thao tác',
            key: 'actions',
            width: permissions.viewFinancials ? '12%' : '13%',
            render: (_, user) => {
                const actionItems = getUserActionItems(user);

                return (
                    <div className="customer-row-actions">
                        {permissions.adjustBalance && (
                            <Button
                                className="customer-deposit-trigger"
                                type="primary"
                                aria-label="Nạp tiền"
                                title="Nạp tiền"
                                onClick={() => openDepositModal(user)}
                            >
                                +$
                            </Button>
                        )}
                        <Dropdown
                            menu={{ items: actionItems }}
                            trigger={['click']}
                            placement="bottomRight"
                            overlayClassName="customer-actions-dropdown"
                        >
                            <Button
                                className="customer-actions-more"
                                aria-label="Mở danh sách thao tác"
                                title="Thao tác khác"
                                icon={<MoreOutlined />}
                            />
                        </Dropdown>
                    </div>
                );
            },
        },
    ];

    const filterOptions = [
        { label: 'Tất cả', value: 'all', count: stats.total || 0, tone: 'primary' },
        { label: 'Hoạt động', value: 'active', count: stats.active || 0, tone: 'success' },
        { label: 'Chưa kích hoạt', value: 'inactive', count: stats.inactive || 0, tone: 'warning' },
        { label: 'Bị khóa', value: 'locked', count: stats.locked || 0, tone: 'danger' },
        { label: 'Đóng băng', value: 'frozen', count: stats.frozen || 0, tone: 'info' },
        { label: 'Clone', value: 'clone', count: stats.clones || 0, tone: 'violet' },
    ];

    const sortOptions = [
        { label: 'Mặc định', value: 'default' },
        { label: 'Tên A - Z', value: 'name' },
        { label: 'Mới nhất', value: 'newest' },
        ...(permissions.viewFinancials ? [{ label: 'Số dư cao nhất', value: 'balance' }] : []),
    ];

    return (
        <AdminPage className="customer-admin-page">
            <header className="customer-page-header">
                <div className="customer-page-heading">
                    <span className="customer-page-heading__icon" aria-hidden="true"><TeamOutlined /></span>
                    <div>
                        <span className="customer-page-eyebrow">Khách hàng</span>
                        <h1>Quản lý khách hàng</h1>
                        <p>Theo dõi danh tính, người quản lý, trạng thái hoạt động và nghiệp vụ tài chính của thành viên.</p>
                    </div>
                </div>
                {permissions.create && (
                    <Button className="customer-add-button" type="primary" icon={<PlusOutlined />} href={routes.create}>
                        Thêm thành viên
                    </Button>
                )}
            </header>

            <section className="customer-metric-grid customer-metric-grid--primary" aria-label="Tổng quan khách hàng">
                <article className="customer-metric-card customer-metric-card--primary">
                    <span className="customer-metric-card__icon"><TeamOutlined /></span>
                    <div className="customer-metric-card__body">
                        <span>Tổng thành viên</span>
                        <strong>{stats.total || 0}</strong>
                    </div>
                    <span className="customer-metric-watermark"><TeamOutlined /></span>
                </article>

                <article className="customer-metric-card customer-metric-card--success">
                    <span className="customer-metric-card__icon"><span className="customer-status-dot">↗</span></span>
                    <div className="customer-metric-card__body">
                        <span>Đang online</span>
                        <div className="customer-metric-card__value-row">
                            <strong>{onlineCount}</strong>
                            <em>↑ {percentage(onlineCount, stats.total)}%</em>
                        </div>
                    </div>
                    <MetricSparkline tone="success" />
                </article>

                <article className="customer-metric-card customer-metric-card--info">
                    <span className="customer-metric-card__icon"><UserOutlined /></span>
                    <div className="customer-metric-card__body">
                        <span>Tài khoản thật</span>
                        <div className="customer-metric-card__value-row">
                            <strong>{realAccountCount}</strong>
                            <em>→ {percentage(realAccountCount, stats.total)}%</em>
                        </div>
                    </div>
                    <MetricSparkline tone="info" />
                </article>

                <article className="customer-metric-card customer-metric-card--warning">
                    <span className="customer-metric-card__icon"><ClockCircleOutlined /></span>
                    <div className="customer-metric-card__body">
                        <span>Chưa kích hoạt</span>
                        <div className="customer-metric-card__value-row">
                            <strong>{stats.inactive || 0}</strong>
                            <em>↑ {percentage(stats.inactive, stats.total)}%</em>
                        </div>
                    </div>
                    <MetricSparkline tone="warning" />
                </article>
            </section>

            {permissions.viewFinancials && (
                <section className="customer-finance-grid" aria-label="Tổng quan tài chính">
                    <article className="customer-finance-card customer-finance-card--balance">
                        <span className="customer-finance-card__icon"><WalletOutlined /></span>
                        <div>
                            <span>Tổng số dư khả dụng</span>
                            <strong>{money(realAccountAvailableBalance)} $</strong>
                        </div>
                        <span className="customer-finance-card__watermark"><WalletOutlined /></span>
                    </article>
                    <article className="customer-finance-card customer-finance-card--frozen">
                        <span className="customer-finance-card__icon"><span className="customer-snowflake">❄</span></span>
                        <div>
                            <span>Tổng số dư đóng băng</span>
                            <strong>{money(realAccountFrozenBalance)} $</strong>
                        </div>
                        <span className="customer-finance-card__watermark"><span className="customer-snowflake">❄</span></span>
                    </article>
                </section>
            )}

            <section className="customer-list-card">
                <div className="customer-list-card__header">
                    <div className="customer-list-title">
                        <span className="customer-list-title__icon"><TeamOutlined /></span>
                        <div>
                            <h2>Danh sách khách hàng</h2>
                            <p>Tìm kiếm, lọc trạng thái và mở menu thao tác của từng tài khoản.</p>
                        </div>
                    </div>
                    <div className="customer-list-search">
                        <Input
                            allowClear
                            prefix={<SearchOutlined />}
                            placeholder="Tìm tên, username, ID, số điện thoại..."
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                        <Button className="customer-filter-button" aria-label="Bộ lọc" icon={<FilterOutlined />} />
                    </div>
                </div>

                <div className="customer-list-card__toolbar">
                    <div className="customer-filter-tabs" role="tablist" aria-label="Lọc khách hàng">
                        {filterOptions.map((item) => (
                            <button
                                key={item.value}
                                type="button"
                                role="tab"
                                aria-selected={filter === item.value}
                                className={'customer-filter-tab customer-filter-tab--' + item.tone + (filter === item.value ? ' is-active' : '')}
                                onClick={() => setFilter(item.value)}
                            >
                                {item.value !== 'all' && <i />}
                                <span>{item.label}</span>
                                <small>({item.count})</small>
                            </button>
                        ))}
                    </div>
                    <div className="customer-sort-control">
                        <SortAscendingOutlined />
                        <span>Sắp xếp:</span>
                        <Select
                            variant="borderless"
                            value={sortKey}
                            placeholder="Chọn cách sắp xếp"
                            options={sortOptions}
                            onChange={setSortKey}
                            popupMatchSelectWidth={170}
                        />
                    </div>
                </div>

                <div className="customer-table-wrap">
                    <Table
                        className="customer-table"
                        rowKey="id"
                        columns={columns}
                        dataSource={pageUsers}
                        tableLayout="fixed"
                        rowClassName={(user) => !user.clone_account ? 'customer-table-row--real' : ''}
                        pagination={false}
                        locale={{ emptyText: 'Không có tài khoản phù hợp' }}
                    />
                </div>

                <div className="customer-mobile-list" aria-label="Danh sách khách hàng trên thiết bị di động">
                    {pageUsers.length === 0 && (
                        <div className="customer-mobile-empty">Không có tài khoản phù hợp</div>
                    )}
                    {pageUsers.map((user, index) => {
                        const meta = statusMeta(user.status);
                        const actionItems = getUserActionItems(user);

                        return (
                            <article
                                key={user.id}
                                className={'customer-mobile-card' + (!user.clone_account ? ' customer-mobile-card--real' : '')}
                            >
                                <div className="customer-mobile-card__head">
                                    <div className="customer-mobile-card__identity">
                                        <Avatar size={46} src={user.avatar_url} icon={<UserOutlined />} />
                                        <div className="customer-mobile-card__identity-copy">
                                            <div className="customer-mobile-card__name-row">
                                                {permissions.viewDetail ? (
                                                    <a href={replaceRoute(routes.show, user.id)}>
                                                        {user.full_name || 'Chưa đặt tên'}
                                                    </a>
                                                ) : (
                                                    <strong>{user.full_name || 'Chưa đặt tên'}</strong>
                                                )}
                                                <span>ID: {user.id}</span>
                                            </div>
                                            <div className="customer-mobile-card__username">@{user.username}</div>
                                            <div className="customer-mobile-card__meta">
                                                <span className={'customer-presence' + (user.is_online ? ' customer-presence--online' : '')}>
                                                    <i />
                                                    {user.is_online ? 'Online' : (user.last_seen_diff || 'Chưa từng online')}
                                                </span>
                                                {user.rank?.name && <Tag className="customer-badge customer-badge--rank">{user.rank.name}</Tag>}
                                            </div>
                                        </div>
                                    </div>
                                    <span className={`customer-status-pill customer-status-pill--${meta.color}`}><i />{meta.label}</span>
                                </div>

                                <div className="customer-mobile-card__details">
                                    {permissions.viewFinancials && (
                                        <div className="customer-mobile-detail">
                                            <span>Số dư khả dụng</span>
                                            <strong><WalletOutlined /> {money(user.balance, 5)}$</strong>
                                            <small><span className="customer-frozen-dot">❄</span> Đóng băng: {money(user.frozen_balance, 2)}$</small>
                                        </div>
                                    )}
                                    <div className="customer-mobile-detail">
                                        <span>Vị trí & khu vực</span>
                                        <strong><GlobalOutlined /> {user.location_country_code || '—'} {user.location_country || 'Chưa rõ'}</strong>
                                        <small>{[user.location_city, user.warehouse_area].filter(Boolean).join(' • ') || 'Chưa định vị'}</small>
                                    </div>
                                    <div className="customer-mobile-detail">
                                        <span>Phân loại</span>
                                        <strong>{user.clone_account ? 'Tài khoản clone' : 'Tài khoản thật'}</strong>
                                        <small>{user.referrer ? `QL: ${user.referrer.full_name || `@${user.referrer.username}`}` : 'Chưa có người quản lý'}</small>
                                    </div>
                                </div>

                                <div className="customer-mobile-card__actions">
                                    {permissions.adjustBalance && (
                                        <Button type="primary" icon={<DollarOutlined />} onClick={() => openDepositModal(user)}>
                                            Nạp tiền
                                        </Button>
                                    )}
                                    {permissions.viewDetail && (
                                        <Button icon={<EyeOutlined />} href={replaceRoute(routes.show, user.id)}>
                                            Chi tiết
                                        </Button>
                                    )}
                                    <Dropdown
                                        menu={{ items: actionItems }}
                                        trigger={['click']}
                                        placement="bottomRight"
                                        overlayClassName="customer-actions-dropdown"
                                    >
                                        <Button icon={<MoreOutlined />}>Thao tác</Button>
                                    </Dropdown>
                                </div>
                                <span className="customer-mobile-card__index">#{((safePage - 1) * pageSize) + index + 1}</span>
                            </article>
                        );
                    })}
                </div>

                <footer className="customer-list-footer">
                    <span>Đang hiển thị {pageUsers.length} / {filteredUsers.length} tài khoản</span>
                    <div className="customer-pagination">
                        <Select
                            className="customer-page-size"
                            value={pageSize}
                            placeholder="Chọn số dòng mỗi trang"
                            onChange={setPageSize}
                            options={[10, 20, 50, 100].map((value) => ({ value, label: value + ' / trang' }))}
                            popupMatchSelectWidth={110}
                        />
                        <Button
                            type="text"
                            aria-label="Trang trước"
                            icon={<LeftOutlined />}
                            disabled={safePage <= 1}
                            onClick={() => setPage((current) => Math.max(1, current - 1))}
                        />
                        <span className="customer-current-page">{safePage}</span>
                        <Button
                            type="text"
                            aria-label="Trang sau"
                            icon={<RightOutlined />}
                            disabled={safePage >= pageCount}
                            onClick={() => setPage((current) => Math.min(pageCount, current + 1))}
                        />
                    </div>
                </footer>
            </section>

            <Modal
                className="customer-deposit-modal"
                width={390}
                title={null}
                open={Boolean(depositUser)}
                onCancel={closeDepositModal}
                footer={null}
                closable={false}
                maskClosable={!depositing}
                destroyOnHidden
            >
                <div className="customer-deposit-head">
                    <div><WalletOutlined /><span>Nạp tiền cho khách hàng</span></div>
                    <button type="button" aria-label="Đóng" onClick={closeDepositModal}><CloseOutlined /></button>
                </div>

                <div className="customer-deposit-body">
                    <div className="customer-deposit-user-card">
                        <Avatar size={42} src={depositUser?.avatar_url} icon={<UserOutlined />} />
                        <div>
                            <strong>{depositUser?.full_name || depositUser?.username || 'Khách hàng'}</strong>
                            <span>@{depositUser?.username} • Số dư hiện tại: <b>{money(depositUser?.balance, 2)}$</b></span>
                        </div>
                    </div>

                    <div className="customer-deposit-field">
                        <label htmlFor="customer-deposit-amount"><DollarOutlined /> Số tiền nạp (USD)</label>
                        <InputNumber
                            id="customer-deposit-amount"
                            className="customer-deposit-amount"
                            min={0}
                            step={0.01}
                            controls={false}
                            value={depositAmount}
                            onChange={setDepositAmount}
                            placeholder="Nhập số tiền..."
                            addonBefore={<DollarOutlined />}
                            addonAfter="$"
                        />
                    </div>

                    <div className="customer-deposit-presets" aria-label="Chọn nhanh số tiền nạp">
                        {[10, 50, 100, 500, 1000, 5000].map((amount) => (
                            <button key={amount} type="button" onClick={() => setDepositAmount(amount)}>
                                +{amount.toLocaleString('en-US')}$
                            </button>
                        ))}
                    </div>

                    <div className="customer-deposit-accent" />

                    <div className="customer-deposit-type-section">
                        <div className="customer-deposit-section-label"><GiftOutlined /> Loại tiền nạp</div>
                        <div className="customer-deposit-types">
                            <button
                                type="button"
                                className={depositType === 'real' ? 'is-active' : ''}
                                onClick={() => setDepositType('real')}
                                aria-pressed={depositType === 'real'}
                            >
                                <CreditCardOutlined />
                                <span><strong>Tiền nạp thực</strong><small>Khách hàng nạp</small></span>
                            </button>
                            <button
                                type="button"
                                className={depositType === 'bonus' ? 'is-active is-bonus' : 'is-bonus'}
                                onClick={() => setDepositType('bonus')}
                                aria-pressed={depositType === 'bonus'}
                            >
                                <GiftOutlined />
                                <span><strong>Tiền thưởng</strong><small>Hệ thống khuyến mại</small></span>
                            </button>
                        </div>
                    </div>

                    <div className="customer-deposit-summary">
                        <div><span>Số dư hiện tại:</span><strong>{money(depositUser?.balance, 2)}$</strong></div>
                        <div><span>Số dư đóng băng:</span><strong>{money(depositUser?.frozen_balance, 2)}$</strong></div>
                        <div><span>Số tiền nạp:</span><strong className="is-positive">+{money(depositAmount, 2)}$</strong></div>
                        <div className="customer-deposit-summary__total">
                            <span>Tổng tài sản sau nạp:</span>
                            <strong>{money(Number(depositUser?.balance || 0) + Number(depositUser?.frozen_balance || 0) + Number(depositAmount || 0), 5)}$</strong>
                        </div>
                    </div>

                    {depositUser?.has_frozen_order && (
                        <p className="customer-deposit-note">
                            Tài khoản đang có đơn đóng băng. Hệ thống có thể tự chuyển tiền nạp vào số dư đóng băng theo trạng thái đơn hàng.
                        </p>
                    )}

                    <div className="customer-deposit-footer">
                        <Button disabled={depositing} onClick={closeDepositModal} icon={<CloseOutlined />}>Hủy</Button>
                        <Button
                            type="primary"
                            loading={depositing}
                            disabled={!depositAmount || Number(depositAmount) <= 0}
                            onClick={submitDeposit}
                        >
                            Tiếp tục xác nhận
                        </Button>
                    </div>
                </div>
            </Modal>
        </AdminPage>
    );
}
