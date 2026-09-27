import {
    ArrowLeftOutlined,
    EditOutlined,
    PhoneOutlined,
    ShoppingOutlined,
    UserOutlined,
} from '@ant-design/icons';
import {
    Button,
    Card,
    Col,
    Descriptions,
    Image,
    Row,
    Space,
    Statistic,
    Table,
    Tag,
    Typography,
} from 'antd';
import './order-show.css';

const { Text, Title } = Typography;

function formatMoney(value) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
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
        second: '2-digit',
        hour12: false,
    }).format(date);
}

function routeFor(template, id) {
    return String(template || '').replace('__USER_ID__', encodeURIComponent(String(id)));
}

function paymentLabel(method) {
    const map = {
        COD: 'COD (Khi nhận hàng)',
        VNPAY: 'VNPAY',
        MOMO: 'MoMo',
        PAYPAL: 'PayPal',
        BANK_TRANSFER: 'Chuyển khoản ngân hàng',
        OTHER: 'Khác',
    };
    return map[String(method || '').toUpperCase()] || method || 'Chưa xác định';
}

export default function OrderShowPage({ config }) {
    const order = config.order || {};
    const frozenOrders = order.frozen_orders || [];
    const totalValue = Number(order.price || 0) * Number(order.quantity || 0);

    const frozenColumns = [
        {
            title: '#',
            dataIndex: 'id',
            width: 80,
            render: (id) => `#${id}`,
        },
        {
            title: 'Thành viên',
            key: 'user',
            render: (_, item) => {
                if (!item.user) return <Text type="secondary">N/A</Text>;
                const label = item.user.name || item.user.phone_number || `#${item.user.id}`;
                return config.permissions?.viewCustomerDetail ? (
                    <a href={routeFor(config.routes.userShow, item.user.id)}>
                        <Space><UserOutlined />{label}</Space>
                    </a>
                ) : (
                    <Space><UserOutlined />{label}</Space>
                );
            },
        },
        {
            title: 'Trạng thái gán',
            dataIndex: 'is_frozen',
            render: (value) => Number(value) === 1
                ? <Tag color="warning">Đang chờ xử lý</Tag>
                : <Tag color="success">Đã hoàn thành</Tag>,
        },
        {
            title: 'Hoa hồng',
            dataIndex: 'commission_paid',
            render: (value) => Number(value) === 1
                ? <Tag color="success">Đã trả</Tag>
                : <Tag>Chưa trả</Tag>,
        },
        {
            title: 'Thời điểm gán',
            dataIndex: 'created_at',
            render: formatDateTime,
        },
    ];

    return (
        <div className="order-show-react-page">
            <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="order-show-back">
                Quay lại danh sách đơn hàng
            </Button>

            <div className="order-show-header">
                <Space align="start" size={16}>
                    <div className="order-show-image">
                        {config.imageUrl
                            ? <Image src={config.imageUrl} width={100} height={100} preview />
                            : <ShoppingOutlined />}
                    </div>
                    <div>
                        <Title level={2}>{order.name || 'Đơn hàng'}</Title>
                        <Space wrap>
                            <Tag>#{order.order_code}</Tag>
                            <Tag color={Number(order.status) === 1 ? 'success' : 'error'}>
                                {Number(order.status) === 1 ? 'Hoạt động' : 'Đã ẩn'}
                            </Tag>
                            <Tag color={Number(order.is_paid) === 1 ? 'success' : 'warning'}>
                                {Number(order.is_paid) === 1 ? 'Đã thanh toán' : 'Chưa thanh toán'}
                            </Tag>
                            {order.partner?.name && <Tag color="blue">{order.partner.name}</Tag>}
                        </Space>
                    </div>
                </Space>

                {config.permissions?.update && (
                    <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>
                        Chỉnh sửa đơn hàng
                    </Button>
                )}
            </div>

            <Row gutter={[16, 16]} className="order-show-stats">
                <Col xs={24} sm={12} xl={6}>
                    <Card><Statistic title="Đơn giá sản phẩm" value={Number(order.price || 0)} precision={2} prefix="$" /></Card>
                </Col>
                <Col xs={24} sm={12} xl={6}>
                    <Card><Statistic title="Số lượng" value={Number(order.quantity || 0)} /></Card>
                </Col>
                <Col xs={24} sm={12} xl={6}>
                    <Card><Statistic title="Tổng giá trị đơn" value={totalValue} precision={2} prefix="$" /></Card>
                </Col>
                <Col xs={24} sm={12} xl={6}>
                    <Card><Statistic title="Tỷ lệ hoa hồng" value={Number(order.commission_percentage || 0)} suffix="%" /></Card>
                </Col>
            </Row>

            <Row gutter={[16, 16]}>
                <Col xs={24} xl={14}>
                    <Card title="Chi tiết mặt hàng & cấu hình" className="order-show-card">
                        <Descriptions column={1} bordered size="small">
                            <Descriptions.Item label="Tên sản phẩm">{order.name || '—'}</Descriptions.Item>
                            <Descriptions.Item label="Mã hệ thống"><Text code>{order.order_code || '—'}</Text></Descriptions.Item>
                            <Descriptions.Item label="Hạng thành viên">
                                {order.rank?.name ? <Tag color="gold">{order.rank.name}</Tag> : 'Áp dụng tất cả'}
                            </Descriptions.Item>
                            <Descriptions.Item label="Mã theo dõi (API)">
                                {order.api ? <Text code>{order.api}</Text> : 'Không có mã API'}
                            </Descriptions.Item>
                            <Descriptions.Item label="Trạng thái hiển thị">
                                <Tag color={Number(order.status) === 1 ? 'success' : 'error'}>
                                    {Number(order.status) === 1 ? 'Hiển thị trong hệ thống' : 'Đang tạm ẩn'}
                                </Tag>
                            </Descriptions.Item>
                        </Descriptions>
                    </Card>

                    <Card title={`Lịch sử gán cho thành viên (${frozenOrders.length})`} className="order-show-card order-show-card-spaced">
                        <Table
                            rowKey="id"
                            columns={frozenColumns}
                            dataSource={frozenOrders}
                            pagination={false}
                            scroll={{ x: 720 }}
                            locale={{ emptyText: 'Chưa có thành viên nào nhận đơn này' }}
                        />
                    </Card>
                </Col>

                <Col xs={24} xl={10}>
                    <Card title="Thông tin khách hàng nhận" className="order-show-card">
                        <Descriptions column={1} size="small">
                            <Descriptions.Item label="Người nhận">{order.customer_name || '—'}</Descriptions.Item>
                            <Descriptions.Item label="Số điện thoại">
                                {order.customer_phone ? (
                                    <a href={`tel:${order.customer_phone}`}><PhoneOutlined /> {order.customer_phone}</a>
                                ) : '—'}
                            </Descriptions.Item>
                            <Descriptions.Item label="Địa chỉ giao">{order.customer_address || '—'}</Descriptions.Item>
                            <Descriptions.Item label="Ghi chú">{order.customer_note || 'Không có ghi chú'}</Descriptions.Item>
                        </Descriptions>
                    </Card>

                    <Card title="Thanh toán & nền tảng" className="order-show-card order-show-card-spaced">
                        <Descriptions column={1} size="small">
                            <Descriptions.Item label="Nền tảng bán">{order.partner?.name || 'Hệ thống nội bộ'}</Descriptions.Item>
                            <Descriptions.Item label="Hình thức thanh toán">{paymentLabel(order.payment_method)}</Descriptions.Item>
                            <Descriptions.Item label="Trạng thái thanh toán">
                                <Tag color={Number(order.is_paid) === 1 ? 'success' : 'warning'}>
                                    {Number(order.is_paid) === 1 ? 'Đã thanh toán' : 'Chưa thanh toán'}
                                </Tag>
                            </Descriptions.Item>
                            <Descriptions.Item label="Thời gian tạo">{formatDateTime(order.created_at)}</Descriptions.Item>
                            <Descriptions.Item label="Cập nhật cuối">{formatDateTime(order.updated_at)}</Descriptions.Item>
                        </Descriptions>
                    </Card>

                    {order.fake_price ? (
                        <Text type="secondary" className="order-show-fake-price">
                            Giá niêm yết trước đây: ${formatMoney(order.fake_price)}
                        </Text>
                    ) : null}
                </Col>
            </Row>
        </div>
    );
}
