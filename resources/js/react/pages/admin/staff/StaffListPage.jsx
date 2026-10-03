import { Button, Select, Space, Table, Tag, Typography } from 'antd';
import { CheckCircleOutlined, EditOutlined, EyeOutlined, LockOutlined, SafetyCertificateOutlined, TeamOutlined, UserAddOutlined, WifiOutlined } from '@ant-design/icons';
import { useEffect, useMemo, useState } from 'react';
import { requestJson } from '../../../lib/http';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';
import { OperationsIdentity, OperationsSummary, OperationsToolbar } from '../../../components/admin/OperationsUi';
import { matchesOperationsSearch, operationsMoney as money } from '../../../lib/operations';

const { Text } = Typography;
const routeFor = (template, id) => String(template || '').replace('__STAFF_ID__', encodeURIComponent(String(id)));

export default function StaffListPage({ config }) {
    const [filter, setFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [role, setRole] = useState('all');
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
        inactive: staffs.filter((item) => item.status === 'inactivated').length,
        admins: staffs.filter((item) => item.role === 'admin').length,
    }), [staffs]);

    const filtered = staffs.filter((item) => {
        if (filter === 'online') return Boolean(item.is_online);
        if (filter === 'offline') return !item.is_online;
        if (filter === 'activated') return item.status === 'activated';
        if (filter === 'banned') return item.status === 'banned';
        if (filter === 'inactivated') return item.status === 'inactivated';
        return true;
    }).filter((item) => (role === 'all' || item.role === role)
        && matchesOperationsSearch([item.id, item.full_name, item.username, item.phone, item.email, item.role], search));

    const columns = [
        { title:'Tài khoản', width:290, render:(_,item)=><OperationsIdentity name={item.full_name || 'Chưa đặt tên'} secondary={`@${item.username || '—'} · ${item.phone || 'Chưa có SĐT'}`} meta={<><Tag color={item.role==='admin'?'purple':'blue'}>{item.role==='admin'?'Admin':'Staff'}</Tag><span>#{item.id}</span></>} href={config.permissions?.viewDetail ? routeFor(config.routes.show,item.id) : undefined} />},
        { title:'Hoạt động', width:185, render:(_,item)=><div><span className={`operations-presence${item.is_online?' operations-presence--online':''}`}><i aria-hidden="true" />{item.is_online ? 'Đang online' : 'Offline'}</span>{!item.is_online&&<div className="operations-cell-note">{item.last_seen_text || 'Chưa ghi nhận hoạt động'}</div>}</div> },
        { title:'Tổng doanh số nạp', dataIndex:'total_deposit', align:'right', width:205, sorter:(a,b)=>Number(a.total_deposit||0)-Number(b.total_deposit||0), render:(value,item)=><div><span className="operations-money operations-money--positive">{money(value)}</span>{item.role==='admin'&&<div className="operations-cell-note">Gồm staff trực thuộc</div>}</div> },
        { title:'Trạng thái', dataIndex:'status', render:(value)=><Tag color={value==='activated'?'success':value==='inactivated'?'warning':'error'}>{value==='activated'?'Đã kích hoạt':value==='inactivated'?'Chưa kích hoạt':'Bị khóa'}</Tag> },
        { title:'Thao tác', width:280, align:'right', render:(_,item)=><Space wrap className="operations-row-actions">
            {config.permissions?.viewDetail && <Button size="small" icon={<EyeOutlined />} href={routeFor(config.routes.show,item.id)}>Xem</Button>}
            {config.permissions?.viewPermissions && item.can_manage_permissions && <Button size="small" icon={<SafetyCertificateOutlined />} href={routeFor(config.routes.permissions,item.id)}>Phân quyền</Button>}
            {config.permissions?.update && item.can_manage && <Button size="small" icon={<EditOutlined />} href={routeFor(config.routes.edit,item.id)}>Sửa</Button>}
            {config.permissions?.changeStatus && item.can_manage && <Button size="small" danger={item.status==='activated'} href={routeFor(config.routes.changeStatus,item.id)} onClick={(event)=>{if(!window.confirm(item.status==='activated'?'Khóa tài khoản nhân viên này?':'Kích hoạt/mở khóa tài khoản này?')) event.preventDefault();}}>{item.status==='activated'?'Khóa':'Kích hoạt'}</Button>}
        </Space> },
    ];

    return <AdminPage className="admin-operations-page">
        <AdminPageHeader
            eyebrow="Nhân sự nội bộ"
            icon={<TeamOutlined />}
            title="Quản lý admin & nhân viên"
            description="Quản lý đội ngũ, theo dõi hoạt động và doanh số nạp của từng tài khoản."
            actions={config.permissions?.create&&<Button type="primary" icon={<UserAddOutlined />} href={config.routes.create}>Thêm tài khoản mới</Button>}
        />
        <AdminMetricGrid items={[
            {key:'all',title:'Tổng nhân sự',value:counts.total,hint:`${counts.admins} Admin · ${counts.total-counts.admins} Staff`,tone:'primary',icon:<TeamOutlined />},
            {key:'online',title:'Đang online',value:presenceStats.online,hint:`${presenceStats.offline} tài khoản offline`,tone:'success',icon:<WifiOutlined />},
            {key:'active',title:'Đã kích hoạt',value:counts.active,hint:`${counts.inactive} tài khoản chưa kích hoạt`,tone:'info',icon:<CheckCircleOutlined />},
            {key:'banned',title:'Bị khóa',value:counts.banned,hint:'Tài khoản đang bị hạn chế truy cập',tone:'danger',icon:<LockOutlined />},
        ]} />
        <AdminDataCard
            title="Danh sách tài khoản nội bộ"
            description="Theo dõi đội ngũ và truy cập nhanh thông tin, phân quyền, trạng thái tài khoản."
            extra={<Tag>{staffs.length} tài khoản</Tag>}
            toolbar={<OperationsToolbar value={filter} onChange={setFilter} options={[{label:`Tất cả (${counts.total})`,value:'all'},{label:`Online (${presenceStats.online})`,value:'online'},{label:`Offline (${presenceStats.offline})`,value:'offline'},{label:`Kích hoạt (${counts.active})`,value:'activated'},{label:`Chưa kích hoạt (${counts.inactive})`,value:'inactivated'},{label:`Bị khóa (${counts.banned})`,value:'banned'}]} search={search} onSearch={setSearch} placeholder="Tìm tên, tài khoản, email, SĐT..." extra={<Select aria-label="Lọc vai trò" value={role} onChange={setRole} options={[{value:'all',label:'Tất cả vai trò'},{value:'admin',label:'Admin'},{value:'staff',label:'Staff'}]} />} />}
        >
            <OperationsSummary shown={filtered.length} total={staffs.length} unit="tài khoản" />
            <Table rowKey="id" dataSource={filtered} columns={columns} scroll={{x:1120}} pagination={{pageSize:20,showSizeChanger:false,showTotal:(total)=>`${total} tài khoản`}} locale={{emptyText:'Không có tài khoản phù hợp với bộ lọc'}} />
        </AdminDataCard>
    </AdminPage>;
}
