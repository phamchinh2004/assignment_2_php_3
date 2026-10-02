import {
    EditOutlined,
    PhoneOutlined,
    ShoppingOutlined,
    UserOutlined,
} from '@ant-design/icons';
import {
    Button,
    Col,
    Descriptions,
    Image,
    Row,
    Space,
    Table,
    Tag,
    Typography,
} from 'antd';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';
import { formatLocalDateTime } from '../../../../shared/datetime';
import './order-show.css';

const { Text } = Typography;

function formatMoney(value) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
}

function formatDateTime(value) {
    return formatLocalDateTime(value, { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
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
        <AdminPage className="order-show-react-page">
            <AdminPageHeader
                eyebrow="Chi tiết đơn hàng"
                icon={<ShoppingOutlined />}
                title={order.name || 'Đơn hàng'}
                description="Thông tin sản phẩm, người nhận, thanh toán và lịch sử gán đơn cho thành viên."
                backHref={config.routes.index}
                backLabel="Danh sách đơn hàng"
                meta={<>{config.imageUrl && <Image src={config.imageUrl} width={34} height={34} preview style={{objectFit:'cover',borderRadius:8}} />}<Tag>#{order.order_code}</Tag><Tag color={Number(order.status) === 1 ? 'success' : 'error'}>{Number(order.status) === 1 ? 'Hoạt động' : 'Đã ẩn'}</Tag><Tag color={Number(order.is_paid) === 1 ? 'success' : 'warning'}>{Number(order.is_paid) === 1 ? 'Đã thanh toán' : 'Chưa thanh toán'}</Tag>{order.partner?.name && <Tag color="blue">{order.partner.name}</Tag>}</>}
                actions={config.permissions?.update && (
                    <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>
                        Chỉnh sửa đơn hàng
                    </Button>
                )}
            />

            <AdminMetricGrid items={[
                {key:'price',title:'Đơn giá sản phẩm',value:Number(order.price || 0),precision:2,prefix:'$',tone:'primary'},
                {key:'quantity',title:'Số lượng',value:Number(order.quantity || 0),tone:'info'},
                {key:'total',title:'Tổng giá trị đơn',value:totalValue,precision:2,prefix:'$',tone:'success'},
                {key:'commission',title:'Tỷ lệ hoa hồng',value:Number(order.commission_percentage || 0),suffix:'%',tone:'warning'},
            ]} />

            <Row gutter={[16, 16]}>
                <Col xs={24} xl={14}>
                    <AdminSectionCard title="Chi tiết mặt hàng & cấu hình" description="Cấu hình cốt lõi của đơn hàng mẫu." className="order-show-card">
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
                    </AdminSectionCard>

                    <div className="order-show-card-spaced"><AdminDataCard title="Lịch sử gán cho thành viên" description={`${frozenOrders.length} lần gán đơn được ghi nhận.`} className="order-show-card">
                        <Table
                            rowKey="id"
                            columns={frozenColumns}
                            dataSource={frozenOrders}
                            pagination={false}
                            scroll={{ x: 720 }}
                            locale={{ emptyText: 'Chưa có thành viên nào nhận đơn này' }}
                        />
                    </AdminDataCard></div>
                </Col>

                <Col xs={24} xl={10}>
                    <AdminSectionCard title="Thông tin khách hàng nhận" description="Thông tin nhận hàng đang gắn với đơn." className="order-show-card">
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
                    </AdminSectionCard>

                    <AdminSectionCard title="Thanh toán & nền tảng" description="Nguồn đơn và trạng thái thanh toán hiện tại." className="order-show-card order-show-card-spaced">
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
                    </AdminSectionCard>

                    {order.fake_price ? (
                        <Text type="secondary" className="order-show-fake-price">
                            Giá niêm yết trước đây: ${formatMoney(order.fake_price)}
                        </Text>
                    ) : null}
                </Col>
            </Row>
        </AdminPage>
    );
}
