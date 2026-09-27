import { Button, Card, Col, Row, Segmented, Space, Statistic, Table, Tag, Typography } from 'antd';
import { useEffect, useMemo, useState } from 'react';
import { requestJson } from '../../../lib/http';

const { Text, Title } = Typography;
const routeFor = (template, id) => String(template || '').replace('__STAFF_ID__', encodeURIComponent(String(id)));
const money = (value) => `${new Intl.NumberFormat('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}).format(Number(value || 0))}$`;

export default function StaffListPage({ config }) {
    const [filter, setFilter] = useState('all');
    const [staffs, setStaffs] = useState(config.staffs || []);
    const [presenceStats, setPresenceStats] = useState({ online: Number(config.onlineStaffCount || 0), offline: Number(config.offlineStaffCount || 0) });

    useEffect(() => {
        let mounted = true;
        const refresh = async () => {
            try {
                const payload = await requestJson(config.routes.onlineStatuses);
                if (!mounted || !payload?.success) return;
                const states = new Map((payload.staffs || []).map((item) => [Number(item.id), item]));
                setStaffs((current) => current.map((staff) => {
                    const next = states.get(Number(staff.id));
                    return next ? { ...staff, is_online: next.is_online, last_seen_text: next.last_seen_text, last_seen_formatted: next.last_seen_formatted } : staff;
                }));
                setPresenceStats({ online: Number(payload.online_count || 0), offline: Number(payload.offline_count || 0) });
            } catch {
                // Presence polling is non-critical; keep the last known values.
            }
        };
        const timer = window.setInterval(refresh, 60000);
        window.addEventListener('focus', refresh);
        return () => { mounted = false; window.clearInterval(timer); window.removeEventListener('focus', refresh); };
    }, [config.routes.onlineStatuses]);

    const counts = useMemo(() => ({
        total: staffs.length,
        active: staffs.filter((item) => item.status === 'activated').length,
        banned: staffs.filter((item) => item.status === 'banned').length,
        revenue: staffs.reduce((sum, item) => sum + Number(item.total_deposit || 0), 0),
    }), [staffs]);

    const filtered = staffs.filter((item) => {
        if (filter === 'online') return Boolean(item.is_online);
        if (filter === 'offline') return !item.is_online;
        if (filter === 'activated') return item.status === 'activated';
        if (filter === 'banned') return item.status === 'banned';
        return true;
    });

    const columns = [
        { title:'Tài khoản', render:(_,item)=>{const content=<div><Text strong>{item.full_name || 'Chưa đặt tên'}</Text><div><Text type="secondary">@{item.username} · {item.phone || 'Chưa có SĐT'}</Text></div><Tag color={item.role==='admin'?'gold':'green'}>{item.role==='admin'?'Admin':'Staff'}</Tag></div>;return config.permissions?.viewDetail?<a href={routeFor(config.routes.show,item.id)}>{content}</a>:content;}},
        { title:'Hoạt động', render:(_,item)=><Tag color={item.is_online?'success':'default'}>{item.is_online ? 'Online' : (item.last_seen_text || 'Offline')}</Tag> },
        { title:'Tổng doanh số nạp', dataIndex:'total_deposit', render:(value)=><Text strong style={{color:'#16a34a'}}>{money(value)}</Text> },
        { title:'Trạng thái', dataIndex:'status', render:(value)=><Tag color={value==='activated'?'success':value==='inactivated'?'warning':'error'}>{value==='activated'?'Đã kích hoạt':value==='inactivated'?'Chưa kích hoạt':'Bị khóa'}</Tag> },
        { title:'Thao tác', width:300, render:(_,item)=><Space wrap>
            {config.permissions?.viewDetail && <Button size="small" href={routeFor(config.routes.show,item.id)}>Xem</Button>}
            {config.permissions?.viewPermissions && item.can_manage_permissions && <Button size="small" href={routeFor(config.routes.permissions,item.id)}>Phân quyền</Button>}
            {config.permissions?.update && item.can_manage && <Button size="small" href={routeFor(config.routes.edit,item.id)}>Sửa</Button>}
            {config.permissions?.changeStatus && item.can_manage && <Button size="small" danger={item.status==='activated'} href={routeFor(config.routes.changeStatus,item.id)} onClick={(event)=>{if(!window.confirm(item.status==='activated'?'Khóa tài khoản nhân viên này?':'Kích hoạt/mở khóa tài khoản này?')) event.preventDefault();}}>{item.status==='activated'?'Khóa':'Kích hoạt'}</Button>}
        </Space> },
    ];

    return <div className="container-fluid px-4 pb-5">
        <div className="d-flex justify-content-between align-items-start gap-3 mb-4 flex-wrap"><div><Title level={2}>Quản lý admin & nhân viên</Title><Text type="secondary">Owner quản lý admin và staff; admin chỉ quản lý staff theo quyền backend.</Text></div>{config.permissions?.create&&<Button type="primary" href={config.routes.create}>Thêm tài khoản mới</Button>}</div>
        <Row gutter={[16,16]} className="mb-4">
            <Col xs={12} md={8} xl={5}><Card><Statistic title="Tổng nhân sự" value={counts.total}/></Card></Col>
            <Col xs={12} md={8} xl={5}><Card><Statistic title="Đang online" value={presenceStats.online} suffix={<Text type="secondary">· {presenceStats.offline} offline</Text>}/></Card></Col>
            <Col xs={12} md={8} xl={5}><Card><Statistic title="Đã kích hoạt" value={counts.active}/></Card></Col>
            <Col xs={12} md={8} xl={4}><Card><Statistic title="Bị khóa" value={counts.banned}/></Card></Col>
            <Col xs={24} md={8} xl={5}><Card><Statistic title="Doanh số nạp" value={counts.revenue} precision={2} suffix="$"/></Card></Col>
        </Row>
        <Card title="Danh sách tài khoản" extra={<Segmented value={filter} onChange={setFilter} options={[{label:`Tất cả (${counts.total})`,value:'all'},{label:`Online (${presenceStats.online})`,value:'online'},{label:`Offline (${presenceStats.offline})`,value:'offline'},{label:`Kích hoạt (${counts.active})`,value:'activated'},{label:`Bị khóa (${counts.banned})`,value:'banned'}]}/>}>
            <Table rowKey="id" dataSource={filtered} columns={columns} scroll={{x:1000}} pagination={{pageSize:20,showSizeChanger:false}}/>
        </Card>
    </div>;
}
