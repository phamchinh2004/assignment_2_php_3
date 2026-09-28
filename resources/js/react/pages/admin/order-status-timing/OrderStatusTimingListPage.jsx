import { Button, Input, InputNumber, message, Select, Space, Switch, Table, Tag } from 'antd';
import { ClockCircleOutlined, SaveOutlined } from '@ant-design/icons';
import { useMemo, useState } from 'react';
import { requestJson } from '../../../lib/http';
import { AdminDataCard, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

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

    return <AdminPage>
        <AdminPageHeader
            eyebrow="Tự động hóa đơn hàng"
            icon={<ClockCircleOutlined />}
            title="Thời gian chuyển trạng thái đơn"
            description="Cấu hình khoảng thời gian và bước chuyển trạng thái tự động. Các dòng tắt vẫn được giữ để có thể bật lại khi cần."
            meta={<Tag color={activeCount ? 'success' : 'default'}>{activeCount}/{rows.length} bước đang bật</Tag>}
            actions={canUpdate && <Button type="primary" icon={<SaveOutlined />} loading={saving} onClick={saveAll}>Lưu tất cả</Button>}
        />
        <AdminDataCard title="Quy tắc chuyển trạng thái" description="Kiểm tra tối thiểu, tối đa và đơn vị thời gian theo từng bước trước khi lưu đồng loạt.">
            <Table rowKey="id" dataSource={rows} columns={columns} pagination={false} scroll={{ x: 1000 }} locale={{emptyText:'Chưa có quy tắc thời gian'}} />
        </AdminDataCard>
    </AdminPage>;
}
