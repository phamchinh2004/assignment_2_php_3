import { Alert, Button, Input } from 'antd';
import { PictureOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import { AdminFormActions, AdminFormSection, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';
import './banner.css';

export default function BannerCreatePage({ config }) {
    const nameError = fieldError(config.form, 'name');
    const imagesError = fieldError(config.form, 'images');

    return (
        <AdminPage width="form">
            <AdminPageHeader eyebrow="Nội dung hình ảnh" icon={<PictureOutlined />} title="Tạo bộ banner mới" description="Tạo một bộ slide có tên rõ ràng và tải các hình ảnh sẽ được dùng để trình chiếu." backHref={config.routes.index} backLabel="Danh sách banner" />
            <AdminSectionCard title="Thông tin banner" description="Tên dùng để quản lý nội bộ; hình ảnh là nội dung hiển thị thực tế.">
                <LaravelForm action={config.routes.store} method="POST" encType="multipart/form-data">
                    <AdminFormSection title="Tên bộ banner" description="Đặt tên theo vị trí hoặc chiến dịch để dễ nhận diện khi có nhiều bộ banner.">
                        <label className="form-label-modern" htmlFor="banner-name">Tên bộ banner <span className="text-danger">*</span></label>
                        <Input id="banner-name" name="name" defaultValue={oldValue(config.form, 'name')} required placeholder="Ví dụ: Banner trang chủ mùa hè" />
                        {nameError && <span className="banner-react-error">{nameError}</span>}
                    </AdminFormSection>
                    <AdminFormSection title="Hình ảnh slide" description="Có thể chọn nhiều ảnh cùng lúc; nên dùng cùng tỷ lệ để tránh giao diện bị nhảy khi trình chiếu.">
                        <label className="form-label-modern" htmlFor="banner-images">Hình ảnh slide <span className="text-danger">*</span></label>
                        <input id="banner-images" type="file" name="images[]" accept="image/*" multiple required className="form-control" />
                        {imagesError && <Alert className="mt-2" type="error" showIcon message={imagesError} />}
                    </AdminFormSection>
                    <AdminFormActions>
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" htmlType="submit">Tạo banner</Button>
                    </AdminFormActions>
                </LaravelForm>
            </AdminSectionCard>
        </AdminPage>
    );
}
