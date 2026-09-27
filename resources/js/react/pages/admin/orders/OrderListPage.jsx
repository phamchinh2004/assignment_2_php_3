import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
    Alert,
    Avatar,
    Button,
    Card,
    Col,
    Input,
    Row,
    Segmented,
    Select,
    Space,
    Statistic,
    Table,
    Tag,
    Typography,
} from 'antd';
import {
    CheckCircleOutlined,
    DollarOutlined,
    EditOutlined,
    EyeOutlined,
    LockOutlined,
    PlusOutlined,
    ReloadOutlined,
    SearchOutlined,
    ShoppingOutlined,
    StopOutlined,
    UnlockOutlined,
} from '@ant-design/icons';
import '../../../../../css/admin/order/index.css';

const { Text, Title } = Typography;

function routeFor(template, id) {
    return String(template || '').replace('__ORDER_ID__', encodeURIComponent(String(id)));
}

function imageUrl(path, storageBaseUrl) {
    if (!path) return null;
    if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
    return `${String(storageBaseUrl || '/storage').replace(/\/$/, '')}/${path.replace(/^\//, '')}`;
}

function formatMoney(value) {
    const number = Number(value || 0);
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(number) ? number : 0);
}

function formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';

    return new Intl.DateTimeFormat('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(date);
}

function paymentTag(method) {
    const map = {
        COD: { label: 'COD', color: 'red' },
        vnpay: { label: 'VNPay', color: 'blue' },
        momo: { label: 'MoMo', color: 'magenta' },
        paypal: { label: 'PayPal', color: 'geekblue' },
        bank_transfer: { label: 'Ngân hàng', color: 'green' },
        other: { label: 'Khác', color: 'default' },
    };

    return map[method] || { label: method || '—', color: 'default' };
}

function permissionSnapshot(fallback) {
    if (!window.authorization) return fallback;

    return {
        create: window.authorization.can('orders.create'),
        viewDetail: window.authorization.can('orders.view-detail'),
        update: window.authorization.can('orders.update'),
        changeStatus: window.authorization.can('orders.change-status'),
    };
}

export default function OrderListPage({ config }) {
    const rankIds = useMemo(() => new Set((config.ranks || []).map((rank) => String(rank.id))), [config.ranks]);
    const initialStatus = localStorage.getItem('order_index_filter_status');
    const initialRank = localStorage.getItem('order_index_filter_rank');

    const [status, setStatus] = useState(['0', '1'].includes(initialStatus) ? initialStatus : '');
    const [rank, setRank] = useState(initialRank && rankIds.has(String(initialRank)) ? String(initialRank) : '');
    const [search, setSearch] = useState('');
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [permissions, setPermissions] = useState(config.permissions || {});

    useEffect(() => {
        const refreshPermissions = () => {
            setPermissions((current) => permissionSnapshot(current));
        };

        refreshPermissions();
        window.addEventListener('authorization:updated', refreshPermissions);
        return () => window.removeEventListener('authorization:updated', refreshPermissions);
    }, []);

    const loadOrders = useCallback(async () => {
        setLoading(true);
        setError('');

        try {
            const url = new URL(config.routes.index, window.location.origin);
            url.searchParams.set('status', status);
            url.searchParams.set('rank', rank);

            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`Không thể tải danh sách đơn hàng (HTTP ${response.status}).`);
            }

            const payload = await response.json();
            if (payload.status !== 200 || !Array.isArray(payload.data)) {
                throw new Error(payload.message || 'Dữ liệu đơn hàng trả về không hợp lệ.');
            }

            setOrders(payload.data);
        } catch (exception) {
            console.error(exception);
            setOrders([]);
            setError(exception?.message || 'Có lỗi xảy ra khi tải danh sách đơn hàng.');
        } finally {
            setLoading(false);
        }
    }, [config.routes.index, rank, status]);

    useEffect(() => {
        loadOrders();
    }, [loadOrders]);

    const handleStatusChange = (value) => {
        const next = String(value ?? '');
        setStatus(next);
        localStorage.setItem('order_index_filter_status', next);
    };

    const handleRankChange = (value) => {
        const next = value ? String(value) : '';
        setRank(next);
        localStorage.setItem('order_index_filter_rank', next);
    };

    const filteredOrders = useMemo(() => {
        const keyword = search.trim().toLocaleLowerCase('vi');
        if (!keyword) return orders;

        return orders.filter((order) => [
            order.order_code,
            order.name,
            order.customer_name,
            order.customer_phone,
            order.customer_address,
            order.partner?.name,
            order.rank?.name,
            order.payment_method,
        ].some((value) => String(value || '').toLocaleLowerCase('vi').includes(keyword)));
    }, [orders, search]);

    const columns = useMemo(() => [
        {
            title: '#',
            key: 'index',
            width: 72,
            align: 'center',
            render: (_, __, index) => <Tag bordered={false}>#{index + 1}</Tag>,
        },
        {
            title: 'Sản phẩm & đơn hàng',
            key: 'product',
            width: 310,
            render: (_, order) => {
                const detailUrl = routeFor(config.routes.show, order.id);
                const orderTitle = permissions.viewDetail
                    ? <a href={detailUrl} className="order-react-link">{order.name || order.order_code}</a>
                    : <Text strong>{order.name || order.order_code}</Text>;

                return (
                    <Space align="start" size={12}>
                        <Avatar
                            shape="square"
                            size={48}
                            src={imageUrl(order.image, config.storageBaseUrl)}
                            icon={<ShoppingOutlined />}
                        />
                        <Space direction="vertical" size={3}>
                            <Space size={6} wrap>
                                {orderTitle}
                                <Tag>#{order.id}</Tag>
                            </Space>
                            {order.order_code && (
                                <Text copyable={{ text: order.order_code }} className="order-react-code">
                                    {order.order_code}
                                </Text>
                            )}
                            <Space size={[4, 4]} wrap>
                                {order.rank?.name ? <Tag color="gold">{order.rank.name}</Tag> : <Tag>Không cấp độ</Tag>}
                                {order.partner?.name && <Tag color="green">{order.partner.name}</Tag>}
                            </Space>
                        </Space>
                    </Space>
                );
            },
        },
        {
            title: 'Giá bán & hoa hồng',
            key: 'finance',
            width: 210,
            sorter: (a, b) => Number(a.price || 0) - Number(b.price || 0),
            render: (_, order) => (
                <Space direction="vertical" size={4}>
                    <Text strong className="order-react-price">${formatMoney(order.price)}</Text>
                    <Space size={4} wrap>
                        <Tag>x{Number(order.quantity || 1)}</Tag>
                        <Tag color="blue">Hoa hồng {Number(order.commission_percentage || 0)}%</Tag>
                    </Space>
                    {Number(order.quantity || 1) > 1 && (
                        <Text type="secondary">Tổng: ${formatMoney(Number(order.price || 0) * Number(order.quantity || 1))}</Text>
                    )}
                </Space>
            ),
        },
        {
            title: 'Khách hàng & giao hàng',
            key: 'customer',
            width: 270,
            render: (_, order) => {
                const payment = paymentTag(order.payment_method);
                const isPaid = order.is_paid === true || Number(order.is_paid) === 1;

                return (
                    <Space direction="vertical" size={3}>
                        <Text strong>{order.customer_name || '—'}</Text>
                        {order.customer_phone && <Text copyable={{ text: order.customer_phone }}>{order.customer_phone}</Text>}
                        {order.customer_address && (
                            <Text type="secondary" ellipsis={{ tooltip: order.customer_address }} className="order-react-address">
                                {order.customer_address}
                            </Text>
                        )}
                        <Space size={[4, 4]} wrap>
                            {order.payment_method && <Tag color={payment.color}>{payment.label}</Tag>}
                            <Tag color={order.payment_method === 'COD' || !isPaid ? 'orange' : 'success'}>
                                {order.payment_method === 'COD' ? 'COD' : (isPaid ? 'Đã thanh toán' : 'Chưa thanh toán')}
                            </Tag>
                        </Space>
                    </Space>
                );
            },
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            key: 'status',
            width: 155,
            sorter: (a, b) => Number(a.status || 0) - Number(b.status || 0),
            render: (value, order) => (
                <Space direction="vertical" size={4}>
                    {String(value) === '1'
                        ? <Tag color="success" icon={<CheckCircleOutlined />}>Đã kích hoạt</Tag>
                        : <Tag color="error" icon={<StopOutlined />}>Bị khóa</Tag>}
                    {order.api && <Tag color="cyan">API</Tag>}
                </Space>
            ),
        },
        {
            title: 'Lịch sử',
            key: 'history',
            width: 190,
            sorter: (a, b) => new Date(a.updated_at || 0) - new Date(b.updated_at || 0),
            render: (_, order) => (
                <Space direction="vertical" size={2}>
                    <Text type="secondary">Tạo: {formatDateTime(order.created_at)}</Text>
                    <Text type="secondary">Sửa: {formatDateTime(order.updated_at)}</Text>
                </Space>
            ),
        },
        {
            title: 'Thao tác',
            key: 'actions',
            fixed: 'right',
            width: 140,
            align: 'center',
            render: (_, order) => (
                <Space size={4}>
                    {permissions.viewDetail && (
                        <Button
                            type="text"
                            icon={<EyeOutlined />}
                            href={routeFor(config.routes.show, order.id)}
                            title="Xem chi tiết đơn hàng"
                        />
                    )}
                    {permissions.update && (
                        <Button
                            type="text"
                            icon={<EditOutlined />}
                            href={routeFor(config.routes.edit, order.id)}
                            title="Chỉnh sửa đơn hàng"
                        />
                    )}
                    {permissions.changeStatus && (
                        <Button
                            type="text"
                            danger={String(order.status) === '1'}
                            icon={String(order.status) === '1' ? <LockOutlined /> : <UnlockOutlined />}
                            href={routeFor(config.routes.toggleStatus, order.id)}
                            title={String(order.status) === '1' ? 'Khóa đơn hàng' : 'Kích hoạt đơn hàng'}
                        />
                    )}
                </Space>
            ),
        },
    ], [config.routes, config.storageBaseUrl, permissions]);

    const stats = config.stats || {};

    return (
        <div className="order-react-page">
            <div className="order-react-header">
                <div>
                    <Title level={2} className="order-react-title">Quản lý đơn hàng</Title>
                    <Text type="secondary">Theo dõi kho đơn hàng mẫu, giá bán, hoa hồng và trạng thái phân phối.</Text>
                </div>
                {permissions.create && (
                    <Button type="primary" size="large" icon={<PlusOutlined />} href={config.routes.create}>
                        Tạo đơn hàng mới
                    </Button>
                )}
            </div>

            <Row gutter={[16, 16]} className="order-react-stats">
                <Col xs={24} sm={12} xl={6}>
                    <Card><Statistic title="Tổng đơn hàng" value={stats.total || 0} prefix={<ShoppingOutlined />} /></Card>
                </Col>
                <Col xs={24} sm={12} xl={6}>
                    <Card><Statistic title="Đang hoạt động" value={stats.active || 0} prefix={<CheckCircleOutlined />} /></Card>
                </Col>
                <Col xs={24} sm={12} xl={6}>
                    <Card><Statistic title="Tạm ngừng / Khóa" value={stats.inactive || 0} prefix={<LockOutlined />} /></Card>
                </Col>
                <Col xs={24} sm={12} xl={6}>
                    <Card><Statistic title="Tổng giá trị đơn" value={Number(stats.totalValue || 0)} precision={2} prefix={<DollarOutlined />} suffix="$" /></Card>
                </Col>
            </Row>

            <Card className="order-react-card" title="Danh sách đơn hàng">
                <div className="order-react-toolbar">
                    <Segmented
                        value={status}
                        onChange={handleStatusChange}
                        options={[
                            { label: `Tất cả (${stats.total || 0})`, value: '' },
                            { label: `Đang hoạt động (${stats.active || 0})`, value: '1' },
                            { label: `Tạm ngừng (${stats.inactive || 0})`, value: '0' },
                        ]}
                    />
                    <Select
                        allowClear
                        value={rank || undefined}
                        onChange={handleRankChange}
                        placeholder="Tất cả cấp độ"
                        className="order-react-rank-select"
                        options={(config.ranks || []).map((item) => ({
                            value: String(item.id),
                            label: `${item.name} (${item.orders_count || 0})`,
                        }))}
                    />
                    <Input
                        allowClear
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        prefix={<SearchOutlined />}
                        placeholder="Tìm mã đơn, khách hàng, SĐT..."
                        className="order-react-search"
                    />
                    <Button icon={<ReloadOutlined />} onClick={loadOrders} loading={loading}>
                        Tải lại
                    </Button>
                </div>

                {error && (
                    <Alert
                        type="error"
                        showIcon
                        message="Không tải được danh sách đơn hàng"
                        description={error}
                        className="order-react-alert"
                    />
                )}

                <Table
                    rowKey="id"
                    columns={columns}
                    dataSource={filteredOrders}
                    loading={loading}
                    scroll={{ x: 1450 }}
                    pagination={{
                        defaultPageSize: 10,
                        showSizeChanger: true,
                        pageSizeOptions: ['10', '20', '50', '100'],
                        showTotal: (total) => `Tổng ${total} đơn hàng`,
                    }}
                    locale={{ emptyText: error ? 'Không có dữ liệu để hiển thị' : 'Chưa có đơn hàng' }}
                />
            </Card>
        </div>
    );
}
