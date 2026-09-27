import { Button, Card, Descriptions, Empty, Image, Popconfirm, Space, Table, Tag, Typography } from 'antd';
import { ArrowLeftOutlined, EditOutlined, EyeOutlined, PlusOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../LaravelForm';

const { Text, Title } = Typography;

export function routeFor(template, token, id) {
    return String(template || '').replace(token, encodeURIComponent(String(id)));
}

export function storageUrl(base, path) {
    if (!path) return null;
    return `${String(base || '/storage').replace(/\/$/, '')}/${String(path).replace(/^\//, '')}`;
}

function formatDate(value) {
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' }).format(date);
}

export function SimpleMediaList({ config, labels, token, secondaryKey, secondaryLabel, allowDelete = false }) {
    const items = config.items || [];
    const permissions = config.permissions || {};
    const columns = [
        { title: '#', width: 70, render: (_, __, index) => <Tag>#{index + 1}</Tag> },
        {
            title: labels.image,
            width: 120,
            render: (_, item) => item.image
                ? <Image src={storageUrl(config.storageBaseUrl, item.image)} width={64} height={64} style={{ objectFit: 'cover', borderRadius: 10 }} />
                : <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={false} />,
        },
        {
            title: labels.name,
            render: (_, item) => permissions.viewDetail
                ? <a href={routeFor(config.routes.show, token, item.id)}><Text strong>{item.name}</Text></a>
                : <Text strong>{item.name}</Text>,
        },
        {
            title: secondaryLabel,
            render: (_, item) => secondaryKey === 'link' && item[secondaryKey]
                ? <a href={item[secondaryKey]} target="_blank" rel="noreferrer">{item[secondaryKey]}</a>
                : <Text code={secondaryKey === 'code'}>{item[secondaryKey] || '—'}</Text>,
        },
        { title: labels.date, width: 170, render: (_, item) => <Text type="secondary">{formatDate(item.created_at)}</Text> },
        {
            title: 'Thao tác',
            width: allowDelete ? 230 : 170,
            align: 'right',
            render: (_, item) => (
                <Space>
                    {permissions.viewDetail && <Button icon={<EyeOutlined />} href={routeFor(config.routes.show, token, item.id)}>Xem</Button>}
                    {permissions.update && <Button href={routeFor(config.routes.edit, token, item.id)}>Sửa</Button>}
                    {allowDelete && permissions.delete && (
                        <Popconfirm title={`Xóa ${labels.singular.toLowerCase()} này?`} onConfirm={() => document.getElementById(`delete-${labels.key}-${item.id}`)?.requestSubmit()}>
                            <Button danger>Xóa</Button>
                        </Popconfirm>
                    )}
                    {allowDelete && permissions.delete && (
                        <LaravelForm id={`delete-${labels.key}-${item.id}`} action={routeFor(config.routes.destroy, token, item.id)} method="DELETE" style={{ display: 'none' }} />
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div className="container-fluid px-4 pb-5">
            <div className="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <Title level={2}>{labels.title}</Title>
                    <Text type="secondary">{labels.subtitle}</Text>
                </div>
                {permissions.create && <Button type="primary" icon={<PlusOutlined />} size="large" href={config.routes.create}>{labels.create}</Button>}
            </div>
            <Card>
                <Table rowKey="id" columns={columns} dataSource={items} scroll={{ x: 900 }} pagination={{ pageSize: 10 }} />
            </Card>
        </div>
    );
}

export function SimpleMediaForm({ config, labels, secondaryKey, secondaryLabel, secondaryType = 'text', editing = false }) {
    const item = config.item || {};
    const form = config.form || {};
    const nameError = fieldError(form, 'name');
    const secondaryError = fieldError(form, secondaryKey);
    const imageError = fieldError(form, 'image');

    return (
        <div className="container-fluid px-4 pb-5">
            <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách</Button>
            <Title level={2}>{editing ? labels.edit : labels.create}</Title>
            <Text type="secondary">{labels.formSubtitle}</Text>
            <Card className="mt-4">
                <LaravelForm action={editing ? config.routes.update : config.routes.store} method={editing ? 'PUT' : 'POST'} encType="multipart/form-data">
                    <div className="form-group-modern">
                        <label className="form-label-modern" htmlFor={`${labels.key}-name`}>{labels.name} <span className="text-danger">*</span></label>
                        <input id={`${labels.key}-name`} className="form-control" name="name" defaultValue={oldValue(form, 'name', item.name || '')} required />
                        {nameError && <span className="text-danger small">{nameError}</span>}
                    </div>
                    <div className="form-group-modern mt-3">
                        <label className="form-label-modern" htmlFor={`${labels.key}-${secondaryKey}`}>{secondaryLabel}</label>
                        <input id={`${labels.key}-${secondaryKey}`} type={secondaryType} className="form-control" name={secondaryKey} defaultValue={oldValue(form, secondaryKey, item[secondaryKey] || '')} />
                        {secondaryError && <span className="text-danger small">{secondaryError}</span>}
                    </div>
                    {editing && item.image && (
                        <div className="mt-3">
                            <Text type="secondary">Ảnh hiện tại</Text>
                            <div className="mt-2"><Image src={storageUrl(config.storageBaseUrl, item.image)} width={160} style={{ borderRadius: 12 }} /></div>
                        </div>
                    )}
                    <div className="form-group-modern mt-3">
                        <label className="form-label-modern" htmlFor={`${labels.key}-image`}>{editing ? 'Thay ảnh' : labels.image}</label>
                        <input id={`${labels.key}-image`} type="file" name="image" accept="image/*" className="form-control" />
                        {imageError && <span className="text-danger small">{imageError}</span>}
                    </div>
                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" htmlType="submit">{editing ? 'Cập nhật' : 'Tạo mới'}</Button>
                    </div>
                </LaravelForm>
            </Card>
        </div>
    );
}

export function SimpleMediaShow({ config, labels, secondaryKey, secondaryLabel }) {
    const item = config.item || {};
    return (
        <div className="container-fluid px-4 pb-5">
            <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách</Button>
            <div className="d-flex justify-content-between align-items-start gap-3 mb-4">
                <div><Title level={2}>{item.name}</Title><Text type="secondary">Mã #{item.id}</Text></div>
                {config.permissions?.update && <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa</Button>}
            </div>
            <Card>
                <div className="row">
                    <div className="col-md-4 mb-3">
                        {item.image ? <Image src={storageUrl(config.storageBaseUrl, item.image)} style={{ width: '100%', maxHeight: 260, objectFit: 'contain' }} /> : <Empty description="Chưa có ảnh" />}
                    </div>
                    <div className="col-md-8">
                        <Descriptions bordered column={1}>
                            <Descriptions.Item label={labels.name}>{item.name || '—'}</Descriptions.Item>
                            <Descriptions.Item label={secondaryLabel}>{secondaryKey === 'link' && item[secondaryKey] ? <a href={item[secondaryKey]} target="_blank" rel="noreferrer">{item[secondaryKey]}</a> : item[secondaryKey] || '—'}</Descriptions.Item>
                            <Descriptions.Item label="Ngày tạo">{formatDate(item.created_at)}</Descriptions.Item>
                            <Descriptions.Item label="Cập nhật">{formatDate(item.updated_at)}</Descriptions.Item>
                        </Descriptions>
                    </div>
                </div>
            </Card>
        </div>
    );
}
