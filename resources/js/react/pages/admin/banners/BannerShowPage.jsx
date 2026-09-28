import { Button, Empty, Tag, Typography } from 'antd';
import { EditOutlined, PictureOutlined } from '@ant-design/icons';
import { AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';
import './banner.css';

const { Text } = Typography;

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
        <AdminPage width="content">
            <AdminPageHeader eyebrow="Chi tiết banner" icon={<PictureOutlined />} title={banner.name} description={`${images.length} slides · Tạo ${formatDate(banner.created_at)}`} backHref={config.routes.index} backLabel="Danh sách banner" meta={<><Tag>#{banner.id}</Tag><Tag color={Number(banner.status) === 1 ? 'success' : 'default'}>{Number(banner.status) === 1 ? 'Đang hiển thị' : 'Đang ẩn'}</Tag></>} actions={config.permissions?.update && <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa bộ banner</Button>} />
            <AdminSectionCard title="Tất cả slide hình ảnh" description="Kiểm tra toàn bộ ảnh của bộ banner và mở ảnh gốc khi cần đối chiếu chất lượng.">
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
            </AdminSectionCard>
        </AdminPage>
    );
}
