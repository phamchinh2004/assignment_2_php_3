import { Button, Card, Pagination, Popconfirm, Space, Table, Tag, Typography } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import LaravelForm from '../../../components/LaravelForm';
import { spaNavigate } from '../../../navigation';

const { Text, Title } = Typography;
const routeFor = (template,id) => String(template || '').replace('__ANNOUNCEMENT_ID__',encodeURIComponent(String(id)));
const priorityLabel = { normal:'Bình thường', important:'Quan trọng', critical:'Khẩn cấp' };
const formatDate = (value) => value ? new Intl.DateTimeFormat('vi-VN',{dateStyle:'short',timeStyle:'short'}).format(new Date(value)) : 'Không giới hạn';

export default function FeatureAnnouncementListPage({ config }) {
    const page = config.announcements || {};
    const rows = page.data || [];
    const permissions = config.permissions || {};
    const columns = [
        {title:'Thông báo',render:(_,a)=><div><Text strong>{a.title}</Text><div><Text type="secondary">{priorityLabel[a.priority] || a.priority} · v{a.version}</Text></div></div>},
        {title:'Thời gian',render:(_,a)=><div><div>Từ: {formatDate(a.starts_at)}</div><Text type="secondary">Đến: {formatDate(a.ends_at)}</Text></div>},
        {title:'Đối tượng',render:(_,a)=>a.target_type==='users'?<div><Tag color="blue">{a.targeted_users?.length || 0} tài khoản</Tag><div><Text type="secondary">{(a.targeted_users||[]).slice(0,3).map(u=>u.full_name||u.username).join(', ')}</Text></div></div>:<Space wrap>{(a.target_roles||[]).map(role=><Tag key={role}>{config.roleLabels?.[role] || role}</Tag>)}</Space>},
        {title:'Xác nhận',render:(_,a)=>{const stat=config.announcementStats?.[a.id] || {}; return <Text strong>{stat.acknowledged_count || 0} / {stat.target_count || 0}</Text>;}},
        {title:'Trạng thái',render:(_,a)=>{const now=Date.now(); let label='Đang chạy',color='success'; if(!a.is_active){label='Đã tắt';color='default';} else if(new Date(a.starts_at).getTime()>now){label='Chưa bắt đầu';color='processing';} else if(a.ends_at && new Date(a.ends_at).getTime()<now){label='Đã hết hạn';color='default';} return <Tag color={color}>{label}</Tag>;}},
        {title:'Thao tác',align:'right',width:250,render:(_,a)=><Space>{permissions.report&&<Button href={routeFor(config.routes.show,a.id)}>Xem</Button>}{permissions.update&&<Button href={routeFor(config.routes.edit,a.id)}>Sửa</Button>}{permissions.toggle&&<><LaravelForm id={`toggle-ann-${a.id}`} action={routeFor(config.routes.toggle,a.id)} method="POST" style={{display:'none'}}/><Button onClick={()=>document.getElementById(`toggle-ann-${a.id}`)?.requestSubmit()}>{a.is_active?'Tắt':'Bật'}</Button></>}{permissions.delete&&<><Popconfirm title="Xóa thông báo này?" onConfirm={()=>document.getElementById(`delete-ann-${a.id}`)?.requestSubmit()}><Button danger>Xóa</Button></Popconfirm><LaravelForm id={`delete-ann-${a.id}`} action={routeFor(config.routes.destroy,a.id)} method="DELETE" style={{display:'none'}}/></>}</Space>},
    ];
    const changePage=(p)=>{const url=new URL(window.location.href);url.searchParams.set('page',p);spaNavigate(url.toString());};
    return <div className="container-fluid px-4 pb-5"><div className="d-flex justify-content-between align-items-center gap-3 mb-4"><div><Title level={2}>Thông báo tính năng</Title><Text type="secondary">Quản lý popup bắt buộc xác nhận cho đội ngũ quản trị.</Text></div>{permissions.create&&<Button type="primary" icon={<PlusOutlined/>} href={config.routes.create}>Tạo thông báo</Button>}</div><Card><Table rowKey="id" dataSource={rows} columns={columns} pagination={false} scroll={{x:1100}}/>{Number(page.last_page||1)>1&&<div className="d-flex justify-content-end mt-3"><Pagination current={Number(page.current_page||1)} total={Number(page.total||0)} pageSize={Number(page.per_page||20)} onChange={changePage} showSizeChanger={false}/></div>}</Card></div>;
}
