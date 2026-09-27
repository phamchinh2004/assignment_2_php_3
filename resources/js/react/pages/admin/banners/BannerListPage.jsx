import { Button, Card, Popconfirm, Space, Statistic, Table, Tag, Typography, message } from 'antd';
import { CheckCircleOutlined, EyeOutlined, LockOutlined, PlusOutlined, PictureOutlined, UnlockOutlined } from '@ant-design/icons';
import LaravelForm from '../../../components/LaravelForm';
import { spaGetAction } from '../../../navigation';
import './banner.css';

const { Text, Title } = Typography;

function routeFor(template, id) {
    return String(template || '').replace('__BANNER_ID__', encodeURIComponent(String(id)));
}

function assetUrl(base, path) {
    if (!path) return '';
    return `${String(base || '/storage').replace(/\/$/, '')}/${String(path).replace(/^\//, '')}`;
}

export default function BannerListPage({ config }) {
    const banners = config.banners || [];
    const permissions = config.permissions || {};
    const totalImages = banners.reduce((sum, banner) => sum + (banner.banner_images?.length || 0), 0);
    const active = banners.filter((banner) => Number(banner.status) === 1).length;

    const columns = [
        { title: '#', width: 64, render: (_, __, index) => <Tag>#{index + 1}</Tag> },
        {
            title: 'Banner',
            render: (_, banner) => (
                <Space direction="vertical" size={1}>
                    {permissions.viewDetail
                        ? <a href={routeFor(config.routes.show, banner.id)}><Text strong>{banner.name}</Text></a>
                        : <Text strong>{banner.name}</Text>}
                    <Text type="secondary">Mã #{banner.id}</Text>
                </Space>
            ),
        },
        {
            title: 'Slides',
            render: (_, banner) => (
                <div className="banner-react-thumbnails">
                    {(banner.banner_images || []).length === 0 && <Text type="secondary">Chưa có ảnh</Text>}
                    {(banner.banner_images || []).map((image) => (
                        <span className="banner-react-thumb" key={image.id}>
                            <img src={assetUrl(config.storageBaseUrl, image.path)} alt="Banner slide" />
                        </span>
                    ))}
                </div>
            ),
        },
        { title: 'Số ảnh', width: 100, align: 'center', render: (_, banner) => <Tag>{banner.banner_images?.length || 0} ảnh</Tag> },
        { title: 'Trạng thái', width: 140, align: 'center', render: (_, banner) => Number(banner.status) === 1 ? <Tag color="success">Đang hiển thị</Tag> : <Tag>Đang ẩn</Tag> },
        {
            title: 'Thao tác',
            width: 250,
            align: 'right',
            render: (_, banner) => (
                <Space wrap>
                    {permissions.viewDetail && <Button icon={<EyeOutlined />} href={routeFor(config.routes.show, banner.id)}>Xem</Button>}
                    {permissions.update && <Button href={routeFor(config.routes.edit, banner.id)}>Sửa</Button>}
                    {permissions.changeStatus && (
                        <Popconfirm
                            title={Number(banner.status) === 1 ? 'Tắt banner này?' : 'Kích hoạt banner này?'}
                            onConfirm={async () => {
                                const payload = await spaGetAction(routeFor(config.routes.changeStatus, banner.id));
                                const text = payload?.props?.flash?.success || payload?.props?.flash?.error;
                                if (text) message[payload?.props?.flash?.error ? 'error' : 'success'](text);
                            }}
                        >
                            <Button icon={Number(banner.status) === 1 ? <LockOutlined /> : <UnlockOutlined />}>
                                {Number(banner.status) === 1 ? 'Ẩn' : 'Bật'}
                            </Button>
                        </Popconfirm>
                    )}
                    {permissions.delete && (
                        <Popconfirm
                            title="Xóa bộ banner này?"
                            onConfirm={() => document.getElementById(`delete-banner-${banner.id}`)?.requestSubmit()}
                        >
                            <Button danger>Xóa</Button>
                        </Popconfirm>
                    )}
                    {permissions.delete && (
                        <LaravelForm id={`delete-banner-${banner.id}`} action={routeFor(config.routes.destroy, banner.id)} method="DELETE" style={{ display: 'none' }} />
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div className="container-fluid px-4 pb-5">
            <div className="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <Title level={2}>Quản lý banner quảng cáo</Title>
                    <Text type="secondary">Quản lý các slide banner hiển thị tại trang chủ và ứng dụng thành viên</Text>
                </div>
                {permissions.create && <Button type="primary" size="large" icon={<PlusOutlined />} href={config.routes.create}>Thêm banner mới</Button>}
            </div>

            <div className="row mb-4">
                <div className="col-md-4 mb-3"><Card><Statistic title="Tổng bộ banner" value={banners.length} prefix={<PictureOutlined />} /></Card></div>
                <div className="col-md-4 mb-3"><Card><Statistic title="Đang hiển thị" value={active} prefix={<CheckCircleOutlined />} /></Card></div>
                <div className="col-md-4 mb-3"><Card><Statistic title="Tổng hình ảnh" value={totalImages} prefix={<PictureOutlined />} /></Card></div>
            </div>

            <Card title="Danh sách các bộ banner">
                <Table rowKey="id" dataSource={banners} columns={columns} scroll={{ x: 980 }} pagination={{ pageSize: 10 }} />
            </Card>
        </div>
    );
}
