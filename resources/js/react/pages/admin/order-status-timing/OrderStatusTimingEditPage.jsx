import { Button, Card, Checkbox, Input, InputNumber, Select, Space, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';

const { Text, Title } = Typography;

export default function OrderStatusTimingEditPage({ config }) {
    const timing = config.timing || {};
    const form = config.form || {};
    return <div className="container-fluid px-4 pb-5">
        <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách cấu hình</Button>
        <Title level={2}>Sửa bước chuyển: {timing.from_status} → {timing.to_status}</Title>
        <Card className="mt-4">
            <LaravelForm action={config.routes.update} method="PUT">
                <Space direction="vertical" size="large" className="w-100">
                    <div className="row">
                        <div className="col-md-4"><label className="form-label-modern">Thời gian tối thiểu</label><InputNumber name="min_time" min={0} defaultValue={Number(oldValue(form,'min_time',timing.min_time || 0))} className="w-100" required />{fieldError(form,'min_time') && <Text type="danger">{fieldError(form,'min_time')}</Text>}</div>
                        <div className="col-md-4"><label className="form-label-modern">Thời gian tối đa</label><InputNumber name="max_time" min={0} defaultValue={Number(oldValue(form,'max_time',timing.max_time || 0))} className="w-100" required />{fieldError(form,'max_time') && <Text type="danger">{fieldError(form,'max_time')}</Text>}</div>
                        <div className="col-md-4"><label className="form-label-modern">Đơn vị</label><Select className="w-100" defaultValue={oldValue(form,'time_unit',timing.time_unit || 'minutes')} options={[{value:'minutes',label:'Phút'},{value:'hours',label:'Giờ'},{value:'days',label:'Ngày'}]} onChange={(value)=>{ const el=document.getElementById('timing-unit'); if(el) el.value=value; }} /><input id="timing-unit" type="hidden" name="time_unit" defaultValue={oldValue(form,'time_unit',timing.time_unit || 'minutes')} /></div>
                    </div>
                    <div><label className="form-label-modern">Mô tả quy trình</label><Input.TextArea name="description" rows={4} defaultValue={oldValue(form,'description',timing.description || '')} maxLength={500} /></div>
                    <Checkbox name="is_active" value="1" defaultChecked={Boolean(oldValue(form,'is_active',timing.is_active))}>Kích hoạt tự động chuyển cho bước này</Checkbox>
                </Space>
                <div className="d-flex justify-content-end gap-2 mt-4"><Button href={config.routes.index}>Hủy</Button><Button type="primary" htmlType="submit">Cập nhật</Button></div>
            </LaravelForm>
        </Card>
    </div>;
}
