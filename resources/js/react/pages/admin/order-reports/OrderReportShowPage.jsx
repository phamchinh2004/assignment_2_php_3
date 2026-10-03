import { Alert, Button, Card, Col, Descriptions, Image, Input, Row, Space, Tag, Timeline, Typography } from 'antd';
import LaravelForm from '../../../components/LaravelForm';
import { AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';
import { CustomerIdentity } from '../../../components/admin/OperationsUi';
import { formatAdminDateTime } from '../../../../shared/datetime';

const { Paragraph, Text, Title } = Typography;
const { TextArea } = Input;
const money = (value, digits = 2) => `${new Intl.NumberFormat('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits }).format(Number(value || 0))}$`;
const dateTime = (value) => formatAdminDateTime(value, { dateStyle: 'short', timeStyle: 'medium' });

const reportStatus = {
    pending: { label: 'Chờ xử lý', color: 'warning' },
    approved: { label: 'Đã hủy (đơn ảo)', color: 'success' },
    rejected: { label: 'Đã xác nhận (đơn thật)', color: 'default' },
};

const orderStatusLabel = {
    pending: 'Chờ xử lý', confirmed: 'Đã xác nhận', preparing: 'Đang chuẩn bị hàng hóa',
    transit: 'Đang trung chuyển', shipping: 'Đang vận chuyển đến khách hàng', delivered: 'Đã giao hàng',
    completed: 'Đã hoàn thành', cancelled: 'Đã hủy',
};

export default function OrderReportShowPage({ config }) {
    const report = config.orderReport || {};
    const frozen = config.frozenOrder;
    const display = config.frozenDisplay || {};
    const reporter = report.reporter || {};
    const owner = frozen?.user || {};
    const resolver = report.resolver || {};
    const status = reportStatus[report.status] || { label: report.status, color: 'default' };

    const timelineItems = (config.timeline || []).map((entry, index) => {
        if (entry.isHighValueOrder || entry.is_high_value_order) {
            const reached = Boolean(entry.isReached ?? entry.is_reached);
            const completed = Boolean(entry.isOrderCompleted ?? entry.is_order_completed);
            return { key: `commission-${index}`, color: reached ? 'green' : completed ? 'orange' : 'gray', children: <div><Text strong>{reached ? 'Đã cộng tiền hoa hồng' : 'Chưa cộng tiền'}</Text><div><Text type="secondary">{reached ? 'Hệ thống đã cộng hoa hồng.' : completed ? 'Đang chờ cộng tiền.' : 'Đơn hàng chưa hoàn thành.'}</Text></div></div> };
        }

        const itemStatus = entry.status || {};
        const statusOrder = entry.statusOrder || entry.status_order;
        const reached = Boolean(entry.isReached ?? entry.is_reached);
        return { key: `${itemStatus.id || index}`, color: reached ? (itemStatus.color || 'blue') : 'gray', children: <div><Text strong={reached}>{itemStatus.display_name || itemStatus.name || 'N/A'}</Text>{statusOrder && <div><Text type="secondary">{dateTime(statusOrder.created_at)}</Text>{statusOrder.changed_by && <CustomerIdentity user={statusOrder.changed_by} />}</div>}</div> };
    });

    if (!frozen) {
        return <AdminPage width="medium"><Button href={config.routes.index}>Quay lại</Button><Alert type="error" showIcon className="mt-3" message="Không tìm thấy thông tin đơn hàng." /></AdminPage>;
    }

    return (
        <AdminPage>
            <AdminPageHeader eyebrow={'Báo cáo #' + (report.id || '')} title="Chi tiết báo cáo đơn hàng" description="Đối chiếu lý do báo cáo, snapshot đơn và lịch sử xử lý trước khi ra quyết định." backHref={config.routes.index} backLabel="Danh sách báo cáo" meta={<Tag color={status.color}>{status.label}</Tag>} />
            {display.uses_snapshot_fallback && <Alert type="warning" showIcon className="mb-4" message="Frozen order thiếu snapshot; một phần dữ liệu đang dùng fallback từ Order hiện tại." />}
            <Row gutter={[16, 16]}>
                <Col xs={24} lg={16}>
                    <Card title="Thông tin báo cáo" extra={<Tag color={status.color}>{status.label}</Tag>} className="mb-3">
                        <Descriptions bordered column={{ xs: 1, md: 2 }}>
                            <Descriptions.Item label="ID báo cáo">#{report.id}</Descriptions.Item>
                            <Descriptions.Item label="Mã đơn">{display.order_code || 'N/A'}</Descriptions.Item>
                            <Descriptions.Item label="Người báo cáo"><CustomerIdentity user={reporter} name={reporter.full_name || reporter.username || `#${report.reported_by}`} /></Descriptions.Item>
                            <Descriptions.Item label="Người đặt hàng"><CustomerIdentity user={owner} /></Descriptions.Item>
                            <Descriptions.Item label="Thời gian báo cáo">{dateTime(report.created_at)}</Descriptions.Item>
                            <Descriptions.Item label="Người xử lý">{report.resolved_at ? (resolver.full_name || resolver.username || `#${report.resolved_by}`) : '—'}</Descriptions.Item>
                            <Descriptions.Item label="Lý do" span={2}>{report.reason || '—'}</Descriptions.Item>
                            {report.resolved_note && <Descriptions.Item label="Ghi chú xử lý" span={2}>{report.resolved_note}</Descriptions.Item>}
                        </Descriptions>
                    </Card>

                    <Card title="Thông tin đơn hàng" className="mb-3" extra={<Tag color={config.currentStatus?.color || 'blue'}>{config.currentStatus?.display_name || orderStatusLabel[display.status] || display.status}</Tag>}>
                        <Row gutter={[20, 20]}>
                            {display.image_url && <Col xs={24} md={6}><Image src={display.image_url} width="100%" style={{ maxWidth: 180, borderRadius: 8 }} /></Col>}
                            <Col xs={24} md={display.image_url ? 18 : 24}>
                                <Title level={4}>{display.name || 'Không có tên sản phẩm'}</Title>
                                <Descriptions bordered size="small" column={{ xs: 1, md: 2 }}>
                                    <Descriptions.Item label="Giá">{money(display.unit_price)}</Descriptions.Item>
                                    <Descriptions.Item label="Số lượng">{display.quantity ?? '—'}</Descriptions.Item>
                                    <Descriptions.Item label="Tổng giá trị">{money(display.order_amount)}</Descriptions.Item>
                                    <Descriptions.Item label={`Hoa hồng (${display.commission_percentage || 0}%)`}>{money(display.commission_amount, 5)}</Descriptions.Item>
                                    {Number(display.penalty_amount || 0) > 0 && <Descriptions.Item label="Tiền phạt">-{money(display.penalty_amount)}</Descriptions.Item>}
                                    <Descriptions.Item label="Nền tảng">{display.partner_name || '—'}</Descriptions.Item>
                                    <Descriptions.Item label="Khách hàng"><CustomerIdentity name={display.customer_name || '—'} secondary="" /></Descriptions.Item>
                                    <Descriptions.Item label="SĐT">{display.customer_phone || '—'}</Descriptions.Item>
                                    <Descriptions.Item label="Địa chỉ" span={2}>{display.customer_address || '—'}</Descriptions.Item>
                                    <Descriptions.Item label="Thanh toán">{display.payment_method || '—'} {display.payment_method && <Tag color={display.is_paid ? 'success' : 'warning'}>{display.is_paid ? 'Đã thanh toán' : 'Chưa thanh toán'}</Tag>}</Descriptions.Item>
                                    <Descriptions.Item label="Ngày đặt">{dateTime(display.order_date)}</Descriptions.Item>
                                    <Descriptions.Item label="Mã vận đơn">{display.tracking_number || '—'}</Descriptions.Item>
                                    <Descriptions.Item label="Đơn vị vận chuyển">{display.shipping_carrier || '—'}</Descriptions.Item>
                                    {display.customer_note && <Descriptions.Item label="Ghi chú khách" span={2}>{display.customer_note}</Descriptions.Item>}
                                    {display.api && <Descriptions.Item label="API Key" span={2}><Paragraph copyable={{ text: display.api }} code style={{ marginBottom: 0 }}>{display.api}</Paragraph></Descriptions.Item>}
                                </Descriptions>
                            </Col>
                        </Row>
                    </Card>

                    {timelineItems.length > 0 && <Card title="Lịch sử thay đổi trạng thái"><Timeline items={timelineItems} /></Card>}
                </Col>

                <Col xs={24} lg={8}>
                    <Card title="Xử lý báo cáo">
                        {report.status !== 'pending' ? <Alert type="info" showIcon message="Báo cáo này đã được xử lý." /> : (
                            <Space direction="vertical" size="large" style={{ width: '100%' }}>
                                <Alert type="warning" showIcon message="Chọn hành động phù hợp" description="Xác nhận đơn nếu đây là đơn thật; hủy đơn nếu báo cáo là đúng." />
                                {config.permissions?.confirm && <LaravelForm action={config.routes.confirm} method="POST" onSubmit={(event) => { if (!window.confirm('Xác nhận đơn này là đơn thật?')) event.preventDefault(); }}><TextArea name="resolved_note" rows={3} placeholder="Ghi chú (tùy chọn)"/><Button htmlType="submit" type="primary" block style={{ marginTop: 8 }}>Xác nhận đơn</Button></LaravelForm>}
                                {config.permissions?.cancel && <LaravelForm action={config.routes.cancel} method="POST" onSubmit={(event) => { if (!window.confirm('Hủy đơn này và xác nhận báo cáo là đúng?')) event.preventDefault(); }}><TextArea name="resolved_note" rows={3} placeholder="Ghi chú (tùy chọn)"/><Button htmlType="submit" danger block style={{ marginTop: 8 }}>Hủy đơn</Button></LaravelForm>}
                            </Space>
                        )}
                    </Card>
                </Col>
            </Row>
        </AdminPage>
    );
}
