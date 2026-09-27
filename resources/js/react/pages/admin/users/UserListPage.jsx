import { useEffect, useMemo, useState } from 'react';
import {
    Avatar,
    Button,
    Card,
    Col,
    Input,
    InputNumber,
    Modal,
    Radio,
    Row,
    Segmented,
    Space,
    Statistic,
    Table,
    Tag,
    Typography,
    message,
} from 'antd';
import {
    DollarOutlined,
    EditOutlined,
    EyeOutlined,
    PlusOutlined,
    LockOutlined,
    StopOutlined,
    UserOutlined,
} from '@ant-design/icons';
import { requestJson } from '../../../lib/http';
import { spaGetAction, spaRefresh } from '../../../navigation';

const { Text, Title } = Typography;

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

export default function UserListPage({ config }) {
    const initialUsers = config.users || [];
    const permissions = config.permissions || {};
    const routes = config.routes || {};
    const stats = config.stats || {};
    const [users, setUsers] = useState(initialUsers);
    const [filter, setFilter] = useState('all');
    const [search, setSearch] = useState('');
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
        return users.filter((user) => {
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
                user.location_city,
                user.location_country,
            ].some((value) => String(value || '').toLowerCase().includes(needle));
        });
    }, [users, filter, search]);

    const changeStatus = (user) => {
        Modal.confirm({
            title: 'Thay đổi trạng thái tài khoản?',
            content: `Trạng thái hiện tại: ${statusMeta(user.status).label}.`,
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

    const columns = [
        {
            title: 'Khách hàng',
            key: 'customer',
            render: (_, user) => (
                <Space>
                    <Avatar src={user.avatar_url} icon={<UserOutlined />} />
                    <div>
                        {permissions.viewDetail ? (
                            <a href={replaceRoute(routes.show, user.id)}>
                                <Text strong>{user.full_name || 'Chưa đặt tên'}</Text>
                            </a>
                        ) : (
                            <Text strong>{user.full_name || 'Chưa đặt tên'}</Text>
                        )}
                        <div><Text type="secondary">@{user.username} · ID {user.id}</Text></div>
                        <Space size={4} wrap>
                            {user.rank?.name && <Tag>{user.rank.name}</Tag>}
                            {user.clone_account && <Tag color="purple">Clone</Tag>}
                            {user.has_frozen_order && <Tag color="cyan">Đóng băng</Tag>}
                        </Space>
                    </div>
                </Space>
            ),
        },
        {
            title: 'Hoạt động',
            key: 'presence',
            width: 150,
            render: (_, user) => (
                <Tag color={user.is_online ? 'success' : 'default'}>
                    {user.is_online ? 'Online' : (user.last_seen_diff || 'Offline')}
                </Tag>
            ),
        },
        ...(permissions.viewFinancials ? [{
            title: 'Tài chính',
            key: 'finance',
            width: 180,
            render: (_, user) => (
                <div>
                    <Text strong>{money(user.balance, 5)}$</Text>
                    <div><Text type="secondary">Đóng băng: {money(user.frozen_balance, 5)}$</Text></div>
                </div>
            ),
        }] : []),
        {
            title: 'Vị trí',
            key: 'location',
            render: (_, user) => (
                <div>
                    <div>{[user.location_city, user.location_country].filter(Boolean).join(', ') || 'Chưa rõ'}</div>
                    {user.warehouse_area && <Text type="secondary">{user.warehouse_area}</Text>}
                </div>
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            width: 130,
            render: (status) => {
                const meta = statusMeta(status);
                return <Tag color={meta.color}>{meta.label}</Tag>;
            },
        },
        {
            title: 'Thao tác',
            key: 'actions',
            width: 230,
            render: (_, user) => (
                <Space wrap>
                    {permissions.viewDetail && (
                        <Button size="small" icon={<EyeOutlined />} href={replaceRoute(routes.show, user.id)} />
                    )}
                    {permissions.update && (
                        <Button size="small" icon={<EditOutlined />} href={replaceRoute(routes.edit, user.id)} />
                    )}
                    {permissions.manageFrozenOrders && (
                        <Button size="small" icon={<LockOutlined />} href={replaceRoute(routes.frozenOrders, user.id)} />
                    )}
                    {permissions.adjustBalance && (
                        <Button
                            size="small"
                            icon={<DollarOutlined />}
                            onClick={() => {
                                setDepositUser(user);
                                setDepositAmount(null);
                                setDepositType('real');
                            }}
                        />
                    )}
                    {permissions.changeStatus && (
                        <Button size="small" danger icon={<StopOutlined />} onClick={() => changeStatus(user)} />
                    )}
                </Space>
            ),
        },
    ];

    const filterOptions = [
        { label: `Tất cả (${stats.total || 0})`, value: 'all' },
        { label: `Hoạt động (${stats.active || 0})`, value: 'active' },
        { label: `Chưa kích hoạt (${stats.inactive || 0})`, value: 'inactive' },
        { label: `Bị khóa (${stats.locked || 0})`, value: 'locked' },
    ];
    if (stats.frozen) filterOptions.push({ label: `Đóng băng (${stats.frozen})`, value: 'frozen' });
    if (stats.clones) filterOptions.push({ label: `Clone (${stats.clones})`, value: 'clone' });

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Row justify="space-between" align="middle" gutter={[16, 16]}>
                <Col>
                    <Title level={2} style={{ margin: 0 }}>Quản lý người dùng</Title>
                    <Text type="secondary">Theo dõi tài khoản thành viên, trạng thái và nghiệp vụ liên quan.</Text>
                </Col>
                {permissions.create && (
                    <Col><Button type="primary" icon={<PlusOutlined />} href={routes.create}>Thêm thành viên</Button></Col>
                )}
            </Row>

            <Row gutter={[16, 16]}>
                <Col xs={24} md={8}><Card><Statistic title="Tổng thành viên" value={stats.total || 0} /></Card></Col>
                <Col xs={24} md={8}><Card><Statistic title="Đang hoạt động" value={stats.active || 0} /></Card></Col>
                <Col xs={24} md={8}><Card><Statistic title="Chưa kích hoạt / khóa" value={(stats.inactive || 0) + (stats.locked || 0)} /></Card></Col>
                {permissions.viewFinancials && (
                    <Col xs={24}>
                        <Card>
                            <Row gutter={24}>
                                <Col><Statistic title="Tổng số dư" value={money(stats.total_balance)} suffix="$" /></Col>
                                <Col><Statistic title="Tổng số dư đóng băng" value={money(stats.total_frozen_balance)} suffix="$" /></Col>
                            </Row>
                        </Card>
                    </Col>
                )}
            </Row>

            <Card>
                <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                    <Row gutter={[12, 12]} justify="space-between">
                        <Col flex="auto">
                            <Segmented options={filterOptions} value={filter} onChange={setFilter} />
                        </Col>
                        <Col xs={24} md={8}>
                            <Input.Search allowClear placeholder="Tìm tên, username, ID, số điện thoại..." value={search} onChange={(event) => setSearch(event.target.value)} />
                        </Col>
                    </Row>
                    <Table rowKey="id" columns={columns} dataSource={filteredUsers} scroll={{ x: 1000 }} pagination={{ pageSize: 20, showSizeChanger: true }} />
                </Space>
            </Card>

            <Modal
                title={depositUser ? `Nạp tiền cho ${depositUser.full_name || depositUser.username}` : 'Nạp tiền'}
                open={Boolean(depositUser)}
                onCancel={() => setDepositUser(null)}
                onOk={submitDeposit}
                confirmLoading={depositing}
                okText="Xác nhận nạp tiền"
            >
                <Space direction="vertical" style={{ width: '100%' }}>
                    <Text>Số dư hiện tại: <strong>{money(depositUser?.balance, 5)}$</strong></Text>
                    <InputNumber
                        style={{ width: '100%' }}
                        min={0}
                        step={0.01}
                        value={depositAmount}
                        onChange={setDepositAmount}
                        placeholder="Nhập số tiền"
                        addonAfter="$"
                    />
                    <Radio.Group value={depositType} onChange={(event) => setDepositType(event.target.value)}>
                        <Radio value="real">Tiền nạp thực</Radio>
                        <Radio value="bonus">Tiền thưởng</Radio>
                    </Radio.Group>
                </Space>
            </Modal>
        </Space>
    );
}
