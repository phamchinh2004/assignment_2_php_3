import { Button, Card, Input, InputNumber, message, Select, Space, Switch, Table, Tag, Typography } from 'antd';
import { SaveOutlined } from '@ant-design/icons';
import { useMemo, useState } from 'react';
import { requestJson } from '../../../lib/http';

const { Text, Title } = Typography;
const routeFor = (template, id) => String(template || '').replace('__TIMING_ID__', encodeURIComponent(String(id)));

export default function OrderStatusTimingListPage({ config }) {
    const canUpdate = Boolean(config.permissions?.update);
    const [saving, setSaving] = useState(false);
    const [rows, setRows] = useState(() => (config.timings || []).map((timing) => ({ ...timing, is_active: Boolean(timing.is_active) })));
    const update = (id, key, value) => setRows((items) => items.map((item) => Number(item.id) === Number(id) ? { ...item, [key]: value } : item));
    const activeCount = useMemo(() => rows.filter((row) => row.is_active).length, [rows]);

    const saveAll = async () => {
        setSaving(true);
        try {
            await requestJson(config.routes.updateMultiple, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ timings: rows.map((row) => ({ id: row.id, min_time: Number(row.min_time), max_time: Number(row.max_time), time_unit: row.time_unit, description: row.description || '', ...(row.is_active ? { is_active: true } : {}) })) }),
            });
            message.success('Cập nhật cấu hình thời gian thành công');
        } catch (error) {
            message.error(error.message || 'Không thể cập nhật cấu hình');
        } finally {
            setSaving(false);
        }
    };

    const columns = [
        { title: 'Bước chuyển', render: (_, row) => <Space><Tag color="blue">{row.from_status}</Tag><span>→</span><Tag color="green">{row.to_status}</Tag></Space> },
        { title: 'Tối thiểu', width: 130, render: (_, row) => <InputNumber min={0} value={Number(row.min_time)} disabled={!canUpdate} onChange={(value) => update(row.id, 'min_time', value ?? 0)} /> },
        { title: 'Tối đa', width: 130, render: (_, row) => <InputNumber min={0} value={Number(row.max_time)} disabled={!canUpdate} onChange={(value) => update(row.id, 'max_time', value ?? 0)} /> },
        { title: 'Đơn vị', width: 140, render: (_, row) => <Select value={row.time_unit} disabled={!canUpdate} onChange={(value) => update(row.id, 'time_unit', value)} options={[{value:'minutes',label:'Phút'},{value:'hours',label:'Giờ'},{value:'days',label:'Ngày'}]} /> },
        { title: 'Mô tả', render: (_, row) => <Input value={row.description || ''} disabled={!canUpdate} onChange={(event) => update(row.id, 'description', event.target.value)} /> },
        { title: 'Tự động', width: 100, align: 'center', render: (_, row) => <Switch checked={Boolean(row.is_active)} disabled={!canUpdate} onChange={(value) => update(row.id, 'is_active', value)} /> },
        { title: '', width: 90, render: (_, row) => canUpdate ? <Button href={routeFor(config.routes.edit, row.id)}>Chi tiết</Button> : null },
    ];

    return <div className="container-fluid px-4 pb-5">
        <div className="d-flex justify-content-between align-items-center gap-3 mb-4"><div><Title level={2}>Thời gian chuyển trạng thái đơn</Title><Text type="secondary">{activeCount}/{rows.length} bước đang bật tự động.</Text></div>{canUpdate && <Button type="primary" icon={<SaveOutlined />} loading={saving} onClick={saveAll}>Lưu tất cả</Button>}</div>
        <Card><Table rowKey="id" dataSource={rows} columns={columns} pagination={false} scroll={{ x: 1000 }} /></Card>
    </div>;
}
