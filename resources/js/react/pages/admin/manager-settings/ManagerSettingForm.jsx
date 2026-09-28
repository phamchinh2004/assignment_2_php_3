import { Button, Input, Select, Typography } from 'antd';
import { SafetyCertificateOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import { AdminFormActions, AdminFormSection, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;

export default function ManagerSettingForm({ config, editing = false }) {
    const setting = config.setting || {};
    const form = config.form || {};
    const nameError = fieldError(form, 'manager_name');
    const parentError = fieldError(form, 'parent_manager_setting_id');
    const parentValue = oldValue(form, 'parent_manager_setting_id', setting.parent_manager_setting_id ?? '');

    return <AdminPage width="form">
        <AdminPageHeader
            eyebrow="Phân quyền"
            icon={<SafetyCertificateOutlined />}
            title={editing ? 'Chỉnh sửa chức năng quản lý' : 'Thêm chức năng quản lý'}
            description="Chức năng cha dùng để nhóm quyền; chức năng con là quyền thao tác cụ thể mà nhân viên có thể được cấp."
            backHref={config.routes.index}
            backLabel="Danh sách chức năng"
            meta={editing ? <Text code>{setting.manager_code}</Text> : null}
        />
        <AdminSectionCard title="Thông tin chức năng" description="Đặt tên ngắn, đúng nghiệp vụ và gắn đúng nhóm cha để tránh quyền bị rơi sai khu vực.">
            <LaravelForm action={editing ? config.routes.update : config.routes.store} method={editing ? 'PUT' : 'POST'}>
                <AdminFormSection title="Định danh hiển thị" description="Tên này xuất hiện trên màn hình phân quyền nhân viên.">
                    <label className="form-label-modern">Tên chức năng <span className="text-danger">*</span></label>
                    <Input name="manager_name" defaultValue={oldValue(form, 'manager_name', setting.manager_name || '')} placeholder="Ví dụ: Quản lý khách hàng" required />
                    {nameError && <Text type="danger" className="d-block mt-1">{nameError}</Text>}
                </AdminFormSection>
                <AdminFormSection title="Nhóm quyền" description="Để trống khi đây là nhóm cha; nếu là quyền thao tác, hãy chọn nhóm nghiệp vụ tương ứng.">
                    <label className="form-label-modern">Chức năng cha</label>
                    <Select
                        className="w-100"
                        defaultValue={parentValue === '' || parentValue === null ? '' : String(parentValue)}
                        options={[{ value: '', label: 'Không có / Đây là chức năng cha' }, ...(config.parents || []).map((parent) => ({ value: String(parent.id), label: parent.manager_name }))]}
                        onChange={(value) => {
                            const input = document.getElementById('manager-parent-value');
                            if (input) input.value = value;
                        }}
                    />
                    <input id="manager-parent-value" type="hidden" name="parent_manager_setting_id" defaultValue={parentValue ?? ''} />
                    {parentError && <Text type="danger" className="d-block mt-1">{parentError}</Text>}
                </AdminFormSection>
                <AdminFormActions><Button href={config.routes.index}>Hủy</Button><Button type="primary" htmlType="submit">{editing ? 'Cập nhật' : 'Tạo chức năng'}</Button></AdminFormActions>
            </LaravelForm>
        </AdminSectionCard>
    </AdminPage>;
}
