import { Button, Input, Segmented, Space, Table, Tag, Typography } from 'antd';
import { TeamOutlined, UserAddOutlined } from '@ant-design/icons';
import { useEffect, useMemo, useState } from 'react';
import { requestJson } from '../../../lib/http';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

const { Text } = Typography;
const routeFor = (template, id) => String(template || '').replace('__STAFF_ID__', encodeURIComponent(String(id)));
const money = (value) => `${new Intl.NumberFormat('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}).format(Number(value || 0))}$`;

export default function StaffListPage({ config }) {
    const [filter, setFilter] = useState('all');
    const [search, setSearch] = useState('');
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
    }).filter((item) => {
        const needle = search.trim().toLocaleLowerCase('vi');
        if (!needle) return true;
        return [item.full_name, item.username, item.phone, item.email, item.role]
            .filter(Boolean)
            .join(' ')
            .toLocaleLowerCase('vi')
            .includes(needle);
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

    return <AdminPage>
        <AdminPageHeader
            eyebrow="Nhân sự nội bộ"
            icon={<TeamOutlined />}
            title="Quản lý admin & nhân viên"
            description="Theo dõi tài khoản quản trị, trạng thái hoạt động và phạm vi quản lý. Quyền thao tác vẫn được giới hạn theo backend."
            actions={config.permissions?.create&&<Button type="primary" icon={<UserAddOutlined />} href={config.routes.create}>Thêm tài khoản mới</Button>}
        />
        <AdminMetricGrid items={[
            {key:'all',title:'Tổng nhân sự',value:counts.total,tone:'primary'},
            {key:'online',title:'Đang online',value:presenceStats.online,hint:`${presenceStats.offline} offline`,tone:'success'},
            {key:'active',title:'Đã kích hoạt',value:counts.active,tone:'info'},
            {key:'banned',title:'Bị khóa',value:counts.banned,tone:'danger'},
        ]} />
        <AdminDataCard
            title="Danh sách tài khoản nội bộ"
            description="Tìm theo tên, username, email hoặc số điện thoại; các thao tác chỉ xuất hiện khi tài khoản hiện tại có quyền tương ứng."
            toolbar={<><div style={{overflowX:'auto',maxWidth:'100%'}}><Segmented value={filter} onChange={setFilter} options={[{label:`Tất cả (${counts.total})`,value:'all'},{label:`Online (${presenceStats.online})`,value:'online'},{label:`Offline (${presenceStats.offline})`,value:'offline'},{label:`Kích hoạt (${counts.active})`,value:'activated'},{label:`Bị khóa (${counts.banned})`,value:'banned'}]}/></div><Input.Search className="admin-list-search" allowClear value={search} onChange={(event)=>setSearch(event.target.value)} placeholder="Tìm nhân sự..." /></>}
        >
            <div className="admin-data-summary" style={{marginBottom:10}}>Đang hiển thị {filtered.length} / {staffs.length} tài khoản</div>
            <Table rowKey="id" dataSource={filtered} columns={columns} scroll={{x:1000}} pagination={{pageSize:20,showSizeChanger:false}} locale={{emptyText:'Không có tài khoản phù hợp'}} />
        </AdminDataCard>
    </AdminPage>;
}
