import { Alert, Button, Input, Typography } from 'antd';
import { DeleteOutlined, PictureOutlined } from '@ant-design/icons';
import { useMemo, useState } from 'react';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import { AdminFormActions, AdminFormSection, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';
import './banner.css';

const { Text } = Typography;

function assetUrl(base, path) {
    return `${String(base || '/storage').replace(/\/$/, '')}/${String(path || '').replace(/^\//, '')}`;
}

export default function BannerEditPage({ config }) {
    const banner = config.banner || {};
    const [deleted, setDeleted] = useState([]);
    const currentImages = useMemo(
        () => (banner.banner_images || []).filter((image) => !deleted.includes(String(image.id))),
        [banner.banner_images, deleted]
    );
    const nameError = fieldError(config.form, 'name');
    const imagesError = fieldError(config.form, 'images');

    return (
        <AdminPage width="content">
            <AdminPageHeader eyebrow="Nội dung hình ảnh" icon={<PictureOutlined />} title={`Chỉnh sửa: ${banner.name}`} description="Cập nhật tên, loại bỏ slide cũ hoặc thêm hình ảnh mới cho bộ banner." backHref={config.routes.index} backLabel="Danh sách banner" />
            <AdminSectionCard title="Nội dung bộ banner" description="Ảnh bị đánh dấu xóa chỉ được loại khỏi bộ sau khi bạn bấm cập nhật.">
                <LaravelForm action={config.routes.update} method="PUT" encType="multipart/form-data">
                    <input type="hidden" name="deleted_images" value={deleted.join(',')} />
                    <AdminFormSection title="Tên bộ banner">
                        <label className="form-label-modern" htmlFor="banner-name">Tên bộ banner <span className="text-danger">*</span></label>
                        <Input id="banner-name" name="name" defaultValue={oldValue(config.form, 'name', banner.name || '')} placeholder="Ví dụ: Banner trang chủ mùa hè" required />
                        {nameError && <span className="banner-react-error">{nameError}</span>}
                    </AdminFormSection>
                    <AdminFormSection title="Hình ảnh hiện tại" description="Xóa những slide không còn sử dụng; thứ tự hiện tại vẫn được giữ theo dữ liệu có sẵn.">
                        <label className="form-label-modern">Hình ảnh hiện tại</label>
                        <div className="banner-react-current-images">
                            {currentImages.length === 0 && <Text type="secondary">Chưa có ảnh nào trong bộ này.</Text>}
                            {currentImages.map((image) => (
                                <div className="banner-react-current-image" key={image.id}>
                                    <img src={assetUrl(config.storageBaseUrl, image.path)} alt="Banner slide" />
                                    <button type="button" title="Xóa ảnh" onClick={() => setDeleted((items) => [...items, String(image.id)])}>
                                        <DeleteOutlined />
                                    </button>
                                </div>
                            ))}
                        </div>
                    </AdminFormSection>
                    <AdminFormSection title="Tải thêm hình ảnh">
                        <label className="form-label-modern" htmlFor="banner-images">Tải thêm hình ảnh mới</label>
                        <input id="banner-images" type="file" name="images[]" accept="image/*" multiple className="form-control" />
                        {imagesError && <Alert className="mt-2" type="error" showIcon message={imagesError} />}
                    </AdminFormSection>
                    <AdminFormActions>
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" htmlType="submit">Cập nhật banner</Button>
                    </AdminFormActions>
                </LaravelForm>
            </AdminSectionCard>
        </AdminPage>
    );
}
