import { Alert, Button, Card, Input, Typography } from 'antd';
import { ArrowLeftOutlined, DeleteOutlined } from '@ant-design/icons';
import { useMemo, useState } from 'react';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import './banner.css';

const { Text, Title } = Typography;

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
        <div className="container-fluid px-4 pb-5">
            <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách banner</Button>
            <Title level={2}>Chỉnh sửa bộ banner: {banner.name}</Title>
            <Text type="secondary">Quản lý các slide hình ảnh và cập nhật tên bộ sưu tập</Text>

            <Card className="mt-4">
                <LaravelForm action={config.routes.update} method="PUT" encType="multipart/form-data">
                    <input type="hidden" name="deleted_images" value={deleted.join(',')} />

                    <div className="form-group-modern">
                        <label className="form-label-modern" htmlFor="banner-name">Tên bộ banner <span className="text-danger">*</span></label>
                        <Input id="banner-name" name="name" defaultValue={oldValue(config.form, 'name', banner.name || '')} required />
                        {nameError && <span className="banner-react-error">{nameError}</span>}
                    </div>

                    <div className="mt-4">
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
                    </div>

                    <div className="form-group-modern mt-4">
                        <label className="form-label-modern" htmlFor="banner-images">Tải thêm hình ảnh mới</label>
                        <input id="banner-images" type="file" name="images[]" accept="image/*" multiple className="form-control" />
                        {imagesError && <Alert className="mt-2" type="error" showIcon message={imagesError} />}
                    </div>

                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" htmlType="submit">Cập nhật banner</Button>
                    </div>
                </LaravelForm>
            </Card>
        </div>
    );
}
