import { Alert, Button, Card, Input, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import './banner.css';

const { Text, Title } = Typography;

export default function BannerCreatePage({ config }) {
    const nameError = fieldError(config.form, 'name');
    const imagesError = fieldError(config.form, 'images');

    return (
        <div className="container-fluid px-4 pb-5">
            <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách banner</Button>
            <Title level={2}>Tạo bộ banner mới</Title>
            <Text type="secondary">Thêm tên bộ sưu tập và chọn các hình ảnh slide trình chiếu</Text>

            <Card className="mt-4">
                <LaravelForm action={config.routes.store} method="POST" encType="multipart/form-data">
                    <div className="form-group-modern">
                        <label className="form-label-modern" htmlFor="banner-name">Tên bộ banner <span className="text-danger">*</span></label>
                        <Input id="banner-name" name="name" defaultValue={oldValue(config.form, 'name')} required placeholder="Ví dụ: Banner trang chủ mùa hè" />
                        {nameError && <span className="banner-react-error">{nameError}</span>}
                    </div>

                    <div className="form-group-modern mt-4">
                        <label className="form-label-modern" htmlFor="banner-images">Hình ảnh slide <span className="text-danger">*</span></label>
                        <input id="banner-images" type="file" name="images[]" accept="image/*" multiple required className="form-control" />
                        {imagesError && <Alert className="mt-2" type="error" showIcon message={imagesError} />}
                    </div>

                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" htmlType="submit">Tạo banner</Button>
                    </div>
                </LaravelForm>
            </Card>
        </div>
    );
}
