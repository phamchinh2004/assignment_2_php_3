import { Button, Card, Empty, Space, Tag, Typography } from 'antd';
import { ArrowLeftOutlined, EditOutlined } from '@ant-design/icons';
import './banner.css';

const { Text, Title } = Typography;

function assetUrl(base, path) {
    return `${String(base || '/storage').replace(/\/$/, '')}/${String(path || '').replace(/^\//, '')}`;
}

function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' }).format(date);
}

export default function BannerShowPage({ config }) {
    const banner = config.banner || {};
    const images = banner.banner_images || [];

    return (
        <div className="container-fluid px-4 pb-5">
            <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách banner</Button>

            <div className="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <Space wrap align="center">
                        <Title level={2} style={{ margin: 0 }}>{banner.name}</Title>
                        <Tag>#{banner.id}</Tag>
                        <Tag color={Number(banner.status) === 1 ? 'success' : 'default'}>{Number(banner.status) === 1 ? 'Đang hiển thị' : 'Đang ẩn'}</Tag>
                    </Space>
                    <div className="mt-2"><Text type="secondary">{images.length} slides · Tạo {formatDate(banner.created_at)}</Text></div>
                </div>
                {config.permissions?.update && <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa bộ banner</Button>}
            </div>

            <Card title="Tất cả slide hình ảnh">
                {images.length === 0 ? <Empty description="Chưa có ảnh nào trong bộ này" /> : (
                    <div className="banner-react-gallery">
                        {images.map((image, index) => (
                            <div className="banner-react-gallery-item" key={image.id}>
                                <img src={assetUrl(config.storageBaseUrl, image.path)} alt={`Banner slide ${index + 1}`} />
                                <div className="banner-react-gallery-meta">
                                    <Text strong>Slide #{index + 1}</Text>
                                    <a href={assetUrl(config.storageBaseUrl, image.path)} target="_blank" rel="noreferrer">Xem ảnh gốc</a>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </Card>
        </div>
    );
}
