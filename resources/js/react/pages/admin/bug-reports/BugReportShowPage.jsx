import { Alert, Button, Card, Col, Descriptions, Image, Input, Row, Space, Tag, Typography } from 'antd';
import LaravelForm from '../../../components/LaravelForm';
import { AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

const { Paragraph, Text } = Typography;
const { TextArea } = Input;
const formatDate = (value) => value
    ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'medium' }).format(new Date(value))
    : '—';

const statusMeta = {
    pending: { label: 'Chờ xử lý', color: 'warning' },
    resolved: { label: 'Đã xử lý', color: 'success' },
};

export default function BugReportShowPage({ config }) {
    const report = config.report || {};
    const reporter = report.reporter || {};
    const resolver = report.resolver || {};
    const status = statusMeta[report.status] || { label: report.status, color: 'default' };
    const imageUrls = report.image_urls || [];

    return (
        <AdminPage>
            <AdminPageHeader
                eyebrow={`Báo lỗi #${report.id || ''}`}
                title={report.title || 'Chi tiết báo lỗi'}
                description="Thông tin lỗi được ghi nhận tại thời điểm Admin hoặc Nhân viên gửi báo cáo."
                backHref={config.routes.index}
                backLabel="Danh sách báo lỗi"
                meta={<Tag color={status.color}>{status.label}</Tag>}
            />

            <Row gutter={[16, 16]}>
                <Col xs={24} lg={16}>
                    <Card title="Nội dung báo lỗi" className="mb-3">
                        <Descriptions bordered column={{ xs: 1, md: 2 }}>
                            <Descriptions.Item label="Người báo lỗi">
                                {reporter.full_name || reporter.username || `#${report.reported_by || 'N/A'}`}
                            </Descriptions.Item>
                            <Descriptions.Item label="Vai trò">{reporter.role || '—'}</Descriptions.Item>
                            <Descriptions.Item label="Thời gian gửi">{formatDate(report.created_at)}</Descriptions.Item>
                            <Descriptions.Item label="Trạng thái"><Tag color={status.color}>{status.label}</Tag></Descriptions.Item>
                            <Descriptions.Item label="Trang xảy ra lỗi" span={2}>
                                {report.page_url ? <a href={report.page_url} target="_blank" rel="noreferrer">{report.page_url}</a> : '—'}
                            </Descriptions.Item>
                            <Descriptions.Item label="Mô tả" span={2}>
                                <Paragraph style={{ whiteSpace: 'pre-wrap', marginBottom: 0 }}>{report.description || '—'}</Paragraph>
                            </Descriptions.Item>
                        </Descriptions>
                    </Card>

                    {imageUrls.length > 0 && (
                        <Card title={`Ảnh đính kèm (${imageUrls.length})`} className="mb-3">
                            <Image.PreviewGroup>
                                <Space wrap size="middle">
                                    {imageUrls.map((url, index) => (
                                        <Image
                                            key={url}
                                            src={url}
                                            width={140}
                                            height={100}
                                            style={{ objectFit: 'cover', borderRadius: 8 }}
                                            alt={`Ảnh báo lỗi ${index + 1}`}
                                        />
                                    ))}
                                </Space>
                            </Image.PreviewGroup>
                        </Card>
                    )}

                    <Card title="Thông tin kỹ thuật">
                        <Text type="secondary">Trình duyệt / thiết bị</Text>
                        <Paragraph code copyable={Boolean(report.user_agent)} style={{ marginTop: 8, whiteSpace: 'pre-wrap' }}>
                            {report.user_agent || 'Không có dữ liệu'}
                        </Paragraph>
                    </Card>
                </Col>

                <Col xs={24} lg={8}>
                    <Card title="Xử lý báo lỗi">
                        {report.status === 'resolved' ? (
                            <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                                <Alert type="success" showIcon message="Báo lỗi đã được xử lý" />
                                <Descriptions column={1} size="small">
                                    <Descriptions.Item label="Người xử lý">
                                        {resolver.full_name || resolver.username || `#${report.resolved_by || 'N/A'}`}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Thời gian">{formatDate(report.resolved_at)}</Descriptions.Item>
                                </Descriptions>
                                {report.resolved_note && (
                                    <div>
                                        <Text type="secondary">Ghi chú xử lý</Text>
                                        <Paragraph style={{ whiteSpace: 'pre-wrap', marginTop: 6 }}>{report.resolved_note}</Paragraph>
                                    </div>
                                )}
                                <Button href={config.routes.index} block>Quay lại danh sách</Button>
                            </Space>
                        ) : (
                            <LaravelForm action={config.routes.resolve} method="POST">
                                <Alert
                                    type="info"
                                    showIcon
                                    className="mb-3"
                                    message="Đánh dấu sau khi đã xử lý xong lỗi"
                                />
                                <TextArea name="resolved_note" rows={5} maxLength={3000} placeholder="Ghi chú cách xử lý (tùy chọn)" />
                                <Button type="primary" htmlType="submit" block style={{ marginTop: 12 }}>
                                    Đánh dấu đã xử lý
                                </Button>
                            </LaravelForm>
                        )}
                    </Card>
                </Col>
            </Row>
        </AdminPage>
    );
}
