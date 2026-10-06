import { useState } from 'react';
import { Alert, Button, Card, Image, Input, Tag, Timeline, Typography } from 'antd';
import { CheckCircleOutlined, CloseCircleOutlined, FileSearchOutlined, HistoryOutlined, ShoppingOutlined } from '@ant-design/icons';
import LaravelForm, { oldValue } from '../../../components/LaravelForm';
import { AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';
import { CustomerIdentity } from '../../../components/admin/OperationsUi';
import { formatAdminDateTime } from '../../../../shared/datetime';
import '../../../../../css/admin/order-report-detail.css';

const { Paragraph, Text } = Typography;
const { TextArea } = Input;
const money = (value, digits = 2) => value === null || value === undefined || value === ''
    ? '—'
    : `${new Intl.NumberFormat('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits }).format(Number(value))}$`;
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

function SectionTitle({ icon, children }) {
    return <h2 className="report-detail-section-title"><span aria-hidden="true">{icon}</span>{children}</h2>;
}

function DetailField({ label, children, wide = false }) {
    return <div className={`report-detail-field${wide ? ' report-detail-field--wide' : ''}`}><dt>{label}</dt><dd>{children ?? '—'}</dd></div>;
}

function ReportResolution({ config, report, status }) {
    const [note, setNote] = useState(() => oldValue(config.form, 'resolved_note', ''));
    const canConfirm = Boolean(config.permissions?.confirm);
    const canCancel = Boolean(config.permissions?.cancel);
    const resolver = report.resolver || {};

    if (report.status !== 'pending') {
        return <div className="report-detail-resolution">
            <Alert type={report.status === 'approved' ? 'success' : 'info'} showIcon message={status.label} description="Báo cáo này đã được xử lý." />
            <dl className="report-detail-fields report-detail-fields--single">
                <DetailField label="Người xử lý">{resolver.full_name || resolver.username || (report.resolved_by ? `#${report.resolved_by}` : '—')}</DetailField>
                <DetailField label="Thời gian xử lý">{dateTime(report.resolved_at)}</DetailField>
            </dl>
            {report.resolved_note && <div className="report-detail-note"><span>Ghi chú xử lý</span><p>{report.resolved_note}</p></div>}
        </div>;
    }

    if (!canConfirm && !canCancel) {
        return <Alert type="info" showIcon message="Bạn chỉ có quyền xem báo cáo" description="Bạn chưa được cấp quyền xác nhận hoặc hủy đơn hàng." />;
    }

    return <div className="report-detail-resolution">
        <p className="report-detail-hint">Đối chiếu lý do báo cáo và thông tin đơn hàng trước khi xử lý.</p>
        <div className="report-detail-note-input">
            <label htmlFor="order-report-resolved-note">Ghi chú xử lý <span>(tùy chọn)</span></label>
            <TextArea id="order-report-resolved-note" value={note} onChange={(event) => setNote(event.target.value)} rows={3} placeholder="Nhập kết quả đối chiếu hoặc lý do xử lý…" />
        </div>
        <div className="report-detail-actions">
            {canConfirm && <LaravelForm action={config.routes.confirm} method="POST" onSubmit={(event) => { if (!window.confirm('Xác nhận đơn này là đơn thật?')) event.preventDefault(); }}>
                <input type="hidden" name="resolved_note" value={note} />
                <Button htmlType="submit" type="primary" block icon={<CheckCircleOutlined aria-hidden="true" />}>Xác nhận đơn</Button>
                <p>Bác báo cáo, tiếp tục xử lý đơn thật.</p>
            </LaravelForm>}
            {canCancel && <LaravelForm action={config.routes.cancel} method="POST" onSubmit={(event) => { if (!window.confirm('Hủy đơn này và xác nhận báo cáo là đúng?')) event.preventDefault(); }}>
                <input type="hidden" name="resolved_note" value={note} />
                <Button htmlType="submit" danger block icon={<CloseCircleOutlined aria-hidden="true" />}>Hủy đơn</Button>
                <p>Chấp nhận báo cáo, hủy đơn ảo.</p>
            </LaravelForm>}
        </div>
    </div>;
}

export default function OrderReportShowPage({ config }) {
    const report = config.orderReport || {};
    const frozen = config.frozenOrder;
    const display = config.frozenDisplay || {};
    const reporter = report.reporter || {};
    const owner = frozen?.user || {};
    const status = reportStatus[report.status] || { label: report.status || '—', color: 'default' };
    const orderStatus = config.currentStatus?.display_name || orderStatusLabel[display.status] || display.status || '—';

    const timelineItems = (config.timeline || []).map((entry, index) => {
        if (entry.isHighValueOrder || entry.is_high_value_order) {
            const reached = Boolean(entry.isReached ?? entry.is_reached);
            const completed = Boolean(entry.isOrderCompleted ?? entry.is_order_completed);
            return { key: `commission-${index}`, color: reached ? 'green' : completed ? 'orange' : 'gray', children: <div className="report-detail-event"><Text strong>{reached ? 'Đã cộng tiền hoa hồng' : 'Chưa cộng tiền'}</Text><Text type="secondary">{reached ? 'Hệ thống đã cộng hoa hồng.' : completed ? 'Đang chờ cộng tiền.' : 'Đơn hàng chưa hoàn thành.'}</Text></div> };
        }

        const itemStatus = entry.status || {};
        const statusOrder = entry.statusOrder || entry.status_order;
        const reached = Boolean(entry.isReached ?? entry.is_reached);
        return { key: `${itemStatus.id || index}`, color: reached ? (itemStatus.color || 'blue') : 'gray', children: <div className={`report-detail-event${reached ? '' : ' report-detail-event--upcoming'}`}><Text strong={reached}>{itemStatus.display_name || itemStatus.name || 'N/A'}</Text>{statusOrder ? <><Text type="secondary">{dateTime(statusOrder.created_at)}</Text>{statusOrder.changed_by && <CustomerIdentity user={statusOrder.changed_by} />}</> : !reached && <Text type="secondary">Chưa ghi nhận</Text>}</div> };
    });

    if (!frozen) {
        return <AdminPage width="content" className="order-report-detail"><AdminPageHeader title="Chi tiết báo cáo đơn hàng" backHref={config.routes.index} backLabel="Danh sách báo cáo" /><Alert type="error" showIcon message="Không tìm thấy thông tin đơn hàng." /></AdminPage>;
    }

    return (
        <AdminPage width="content" className="order-report-detail">
            <AdminPageHeader
                eyebrow={`Báo cáo #${report.id || ''}`}
                title="Chi tiết báo cáo đơn hàng"
                backHref={config.routes.index}
                backLabel="Danh sách báo cáo"
                actions={<Tag color={status.color}>{status.label}</Tag>}
                meta={<><span>Đơn <strong>{display.order_code || 'N/A'}</strong></span><span>{dateTime(report.created_at)}</span></>}
            />
            {display.uses_snapshot_fallback && <Alert type="warning" showIcon className="report-detail-warning" message="Đơn hàng thiếu dữ liệu lưu tại thời điểm tạo" description="Một phần thông tin đang được lấy từ đơn hàng hiện tại. Vui lòng đối chiếu trước khi xử lý." />}

            <div className="report-detail-layout">
                <div className="report-detail-main">
                    <Card className="report-detail-card" title={<SectionTitle icon={<FileSearchOutlined />}>Thông tin báo cáo</SectionTitle>}>
                        <div className="report-detail-reason"><span>Lý do báo cáo</span><p>{report.reason || 'Chưa có lý do báo cáo.'}</p></div>
                        <div className="report-detail-people">
                            <div><span>Người báo cáo</span><CustomerIdentity user={reporter} name={reporter.full_name || reporter.username || (report.reported_by ? `#${report.reported_by}` : 'Tài khoản không còn tồn tại')} /></div>
                            <div><span>Người đặt hàng</span><CustomerIdentity user={owner} /></div>
                        </div>
                    </Card>

                    <Card className="report-detail-card" title={<SectionTitle icon={<ShoppingOutlined />}>Thông tin đơn hàng</SectionTitle>} extra={<Tag color={config.currentStatus?.color || 'blue'}>{orderStatus}</Tag>}>
                        <div className="report-detail-product">
                            {display.image_url ? <Image src={display.image_url} width={76} height={76} alt={display.name || 'Sản phẩm trong đơn hàng'} /> : <span className="report-detail-product-placeholder" aria-hidden="true"><ShoppingOutlined /></span>}
                            <div><span className="report-detail-product-code">{display.order_code || 'N/A'}</span><h3>{display.name || 'Không có tên sản phẩm'}</h3><span className="report-detail-product-meta">{display.partner_name || 'Chưa có nền tảng'} · Số lượng: {display.quantity ?? '—'}</span></div>
                        </div>
                        <dl className="report-detail-metrics">
                            <DetailField label="Đơn giá">{money(display.unit_price)}</DetailField>
                            <DetailField label="Tổng giá trị">{money(display.order_amount)}</DetailField>
                            <DetailField label={`Hoa hồng${display.commission_percentage != null ? ` (${display.commission_percentage}%)` : ''}`}>{money(display.commission_amount, 5)}</DetailField>
                            {Number(display.penalty_amount || 0) > 0 && <DetailField label="Tiền phạt">-{money(display.penalty_amount)}</DetailField>}
                        </dl>
                        <section className="report-detail-group">
                            <h3>Khách hàng & giao hàng</h3>
                            <dl className="report-detail-fields">
                                <DetailField label="Khách hàng">{display.customer_name || '—'}</DetailField>
                                <DetailField label="Số điện thoại">{display.customer_phone || '—'}</DetailField>
                                <DetailField label="Địa chỉ" wide>{display.customer_address || '—'}</DetailField>
                                <DetailField label="Mã vận đơn">{display.tracking_number || '—'}</DetailField>
                                <DetailField label="Đơn vị vận chuyển">{display.shipping_carrier || '—'}</DetailField>
                            </dl>
                            {display.customer_note && <div className="report-detail-note"><span>Ghi chú khách hàng</span><p>{display.customer_note}</p></div>}
                        </section>
                        <section className="report-detail-group">
                            <h3>Thanh toán & thông tin khác</h3>
                            <dl className="report-detail-fields">
                                <DetailField label="Thanh toán"><div className="report-detail-payment">{display.payment_method || '—'}{display.payment_method && <Tag color={display.is_paid ? 'success' : 'warning'}>{display.is_paid ? 'Đã thanh toán' : 'Chưa thanh toán'}</Tag>}</div></DetailField>
                                <DetailField label="Ngày đặt hàng">{dateTime(display.order_date)}</DetailField>
                                {display.api && <DetailField label="API Key" wide><Paragraph copyable={{ text: display.api }} code className="report-detail-api">{display.api}</Paragraph></DetailField>}
                            </dl>
                        </section>
                    </Card>
                </div>

                <div className="report-detail-sidebar">
                    <Card className="report-detail-card report-detail-processing" title={<SectionTitle icon={<CheckCircleOutlined />}>Xử lý báo cáo</SectionTitle>}>
                        <ReportResolution key={`${report.id}:${report.status}`} config={config} report={report} status={status} />
                    </Card>
                    <Card className="report-detail-card report-detail-history" title={<SectionTitle icon={<HistoryOutlined />}>Lịch sử trạng thái</SectionTitle>}>
                        {timelineItems.length > 0 ? <Timeline items={timelineItems} /> : <p className="report-detail-hint">Chưa có lịch sử thay đổi trạng thái.</p>}
                    </Card>
                </div>
            </div>
        </AdminPage>
    );
}
