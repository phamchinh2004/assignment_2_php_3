import { Button, Card, Input, Select, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';

const { Text, Title } = Typography;

export default function ManagerSettingForm({ config, editing = false }) {
    const setting = config.setting || {};
    const form = config.form || {};
    const nameError = fieldError(form, 'manager_name');
    const parentError = fieldError(form, 'parent_manager_setting_id');
    const parentValue = oldValue(form, 'parent_manager_setting_id', setting.parent_manager_setting_id ?? '');

    return <div className="container-fluid px-4 pb-5">
        <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách chức năng</Button>
        <Title level={2}>{editing ? 'Chỉnh sửa chức năng quản lý' : 'Thêm chức năng quản lý'}</Title>
        <Text type="secondary">Chức năng con được nhóm dưới chức năng cha trong trang phân quyền.</Text>
        <Card className="mt-4">
            <LaravelForm action={editing ? config.routes.update : config.routes.store} method={editing ? 'PUT' : 'POST'}>
                <div className="mb-4">
                    <label className="form-label-modern">Tên chức năng <span className="text-danger">*</span></label>
                    <Input name="manager_name" defaultValue={oldValue(form, 'manager_name', setting.manager_name || '')} required />
                    {nameError && <Text type="danger" className="d-block mt-1">{nameError}</Text>}
                </div>
                <div className="mb-4">
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
                </div>
                {editing && <div className="mb-4"><Text type="secondary">Mã quyền hiện tại: <Text code>{setting.manager_code}</Text></Text></div>}
                <div className="d-flex justify-content-end gap-2"><Button href={config.routes.index}>Hủy</Button><Button type="primary" htmlType="submit">{editing ? 'Cập nhật' : 'Tạo chức năng'}</Button></div>
            </LaravelForm>
        </Card>
    </div>;
}
