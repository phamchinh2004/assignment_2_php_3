import { Button, InputNumber, Typography } from 'antd';
import { HourglassOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import { AdminFormActions, AdminFormSection, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;

export default function FrozenOrderSettingsPage({ config }) {
    const settings = config.settings || {};
    const form = config.form || {};
    const canUpdate = Boolean(config.permissions?.update);
    const fields = [
        ['processing_time_limit', 'Thời hạn xử lý', settings.processing_time_limit ?? 24],
        ['notification_1_remaining_time', 'Cảnh báo lần 1 còn lại', settings.notification_1_remaining_time ?? 12],
        ['notification_2_remaining_time', 'Cảnh báo lần 2 còn lại', settings.notification_2_remaining_time ?? 1],
    ];
    return <AdminPage width="form">
        <AdminPageHeader
            eyebrow="Cấu hình đơn hàng"
            icon={<HourglassOutlined />}
            title="Thời gian xử lý đơn hàng đóng băng"
            description="Thiết lập thời hạn xử lý và các mốc cảnh báo cho đơn đang bị đóng băng. Tất cả giá trị được tính theo giờ."
        />
        <AdminSectionCard title="Mốc thời gian" description="Các mốc cảnh báo nên nhỏ hơn thời hạn xử lý để thông báo được gửi trước khi hết thời gian.">
            <LaravelForm action={config.routes.store} method="POST">
                <AdminFormSection title="Thời hạn & cảnh báo" description="Cảnh báo lần 1 dùng cho nhắc sớm; cảnh báo lần 2 nên sát thời điểm hết hạn hơn.">
                    <div className="row">
                        {fields.map(([name,label,fallback]) => <div className="col-md-4 mb-3 mb-md-0" key={name}><label className="form-label-modern">{label} (giờ)</label><InputNumber name={name} min={1} precision={0} defaultValue={Number(oldValue(form,name,fallback))} placeholder={`Nhập ${label.toLocaleLowerCase('vi')}`} disabled={!canUpdate} className="w-100" required />{fieldError(form,name) && <Text type="danger" className="d-block mt-1">{fieldError(form,name)}</Text>}</div>)}
                    </div>
                </AdminFormSection>
                {canUpdate && <AdminFormActions><Button type="primary" htmlType="submit">Lưu cấu hình</Button></AdminFormActions>}
            </LaravelForm>
        </AdminSectionCard>
    </AdminPage>;
}
