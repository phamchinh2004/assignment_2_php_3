import { Button, Card, InputNumber, Typography } from 'antd';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';

const { Text, Title } = Typography;

export default function FrozenOrderSettingsPage({ config }) {
    const settings = config.settings || {};
    const form = config.form || {};
    const canUpdate = Boolean(config.permissions?.update);
    const fields = [
        ['processing_time_limit', 'Thời hạn xử lý', settings.processing_time_limit ?? 24],
        ['notification_1_remaining_time', 'Cảnh báo lần 1 còn lại', settings.notification_1_remaining_time ?? 12],
        ['notification_2_remaining_time', 'Cảnh báo lần 2 còn lại', settings.notification_2_remaining_time ?? 1],
    ];
    return <div className="container-fluid px-4 pb-5">
        <Title level={2}>Cấu hình Frozen Order</Title><Text type="secondary">Các giá trị thời gian được tính theo giờ.</Text>
        <Card className="mt-4">
            <LaravelForm action={config.routes.store} method="POST">
                <div className="row">
                    {fields.map(([name,label,fallback]) => <div className="col-md-4 mb-3" key={name}><label className="form-label-modern">{label} (giờ)</label><InputNumber name={name} min={1} precision={0} defaultValue={Number(oldValue(form,name,fallback))} disabled={!canUpdate} className="w-100" required />{fieldError(form,name) && <Text type="danger" className="d-block mt-1">{fieldError(form,name)}</Text>}</div>)}
                </div>
                {canUpdate && <div className="d-flex justify-content-end"><Button type="primary" htmlType="submit">Lưu cấu hình</Button></div>}
            </LaravelForm>
        </Card>
    </div>;
}
