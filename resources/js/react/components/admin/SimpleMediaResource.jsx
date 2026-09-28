import { Button, Descriptions, Empty, Image, Popconfirm, Space, Table, Tag, Typography } from 'antd';
import { ArrowLeftOutlined, EditOutlined, EyeOutlined, PlusOutlined, SaveOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../LaravelForm';
import {
    AdminDataCard,
    AdminFormActions,
    AdminFormSection,
    AdminPage,
    AdminPageHeader,
    AdminSectionCard,
} from './AdminUi';

const { Text } = Typography;

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
        <AdminPage>
            <AdminPageHeader
                title={labels.title}
                description={labels.subtitle}
                meta={<Tag>{items.length} mục</Tag>}
                actions={permissions.create ? <Button type="primary" icon={<PlusOutlined />} href={config.routes.create}>{labels.create}</Button> : null}
            />
            <AdminDataCard title={`Danh sách ${labels.singular.toLowerCase()}`} description={`Quản lý ${labels.singular.toLowerCase()} đang hiển thị trong hệ thống.`}>
                <Table rowKey="id" columns={columns} dataSource={items} scroll={{ x: 900 }} pagination={{ pageSize: 15, showTotal: (total) => `${total} mục` }} locale={{ emptyText: `Chưa có ${labels.singular.toLowerCase()}` }} />
            </AdminDataCard>
        </AdminPage>
    );
}

export function SimpleMediaForm({ config, labels, secondaryKey, secondaryLabel, secondaryType = 'text', editing = false }) {
    const item = config.item || {};
    const form = config.form || {};
    const nameError = fieldError(form, 'name');
    const secondaryError = fieldError(form, secondaryKey);
    const imageError = fieldError(form, 'image');

    return (
        <AdminPage width="form">
            <AdminPageHeader
                title={editing ? labels.edit : labels.create}
                description={labels.formSubtitle}
                backHref={config.routes.index}
                backLabel="Danh sách"
            />
            <AdminSectionCard title="Thông tin cơ bản" description={`Các trường cần thiết để ${editing ? 'cập nhật' : 'tạo'} ${labels.singular.toLowerCase()}.`}>
                <LaravelForm action={editing ? config.routes.update : config.routes.store} method={editing ? 'PUT' : 'POST'} encType="multipart/form-data">
                    <AdminFormSection title="Nội dung" description="Tên và thông tin nhận diện được hiển thị trực tiếp cho quản trị viên hoặc người dùng.">
                        <label className="admin-field-label" htmlFor={`${labels.key}-name`}>{labels.name} <span className="text-danger">*</span></label>
                        <input id={`${labels.key}-name`} className="form-control" name="name" defaultValue={oldValue(form, 'name', item.name || '')} required />
                        {nameError && <span className="text-danger small">{nameError}</span>}
                        <div style={{ marginTop: 18 }}>
                            <label className="admin-field-label" htmlFor={`${labels.key}-${secondaryKey}`}>{secondaryLabel}</label>
                            <input id={`${labels.key}-${secondaryKey}`} type={secondaryType} className="form-control" name={secondaryKey} defaultValue={oldValue(form, secondaryKey, item[secondaryKey] || '')} />
                            {secondaryError && <span className="text-danger small">{secondaryError}</span>}
                        </div>
                    </AdminFormSection>
                    <AdminFormSection title="Hình ảnh" description="Ảnh nên rõ nét và đúng tỷ lệ hiển thị thực tế để tránh bị cắt hoặc méo.">
                        {editing && item.image && (
                            <div style={{ marginBottom: 16 }}>
                                <div className="admin-field-label">Ảnh hiện tại</div>
                                <div className="mt-2"><Image src={storageUrl(config.storageBaseUrl, item.image)} width={160} style={{ borderRadius: 12 }} /></div>
                            </div>
                        )}
                        <label className="admin-field-label" htmlFor={`${labels.key}-image`}>{editing ? 'Thay ảnh' : labels.image}</label>
                        <input id={`${labels.key}-image`} type="file" name="image" accept="image/*" className="form-control" />
                        <span className="admin-field-help">Chọn ảnh rõ nét, đúng tỷ lệ sử dụng thực tế của nội dung.</span>
                        {imageError && <span className="text-danger small">{imageError}</span>}
                    </AdminFormSection>
                    <AdminFormActions>
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" icon={<SaveOutlined />} htmlType="submit">{editing ? 'Cập nhật' : 'Tạo mới'}</Button>
                    </AdminFormActions>
                </LaravelForm>
            </AdminSectionCard>
        </AdminPage>
    );
}

export function SimpleMediaShow({ config, labels, secondaryKey, secondaryLabel }) {
    const item = config.item || {};
    return (
        <AdminPage width="content">
            <AdminPageHeader
                title={item.name || labels.singular}
                description={`Thông tin chi tiết ${labels.singular.toLowerCase()}`}
                meta={<Tag>#{item.id}</Tag>}
                actions={<Space wrap>
                    <Button icon={<ArrowLeftOutlined />} href={config.routes.index}>Danh sách</Button>
                    {config.permissions?.update && <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa</Button>}
                </Space>}
            />
            <AdminSectionCard title="Thông tin chi tiết" description="Dữ liệu hiện đang được lưu và sử dụng trong hệ thống.">
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
            </AdminSectionCard>
        </AdminPage>
    );
}
