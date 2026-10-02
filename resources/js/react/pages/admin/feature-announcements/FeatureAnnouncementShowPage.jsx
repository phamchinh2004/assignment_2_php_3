import { formatAdminDateTime } from '../../../../shared/datetime';
import { Button, Descriptions, Image, Progress, Space, Table, Tag, Typography } from 'antd';
import { EditOutlined, NotificationOutlined } from '@ant-design/icons';
import { AdminDataCard, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text, Paragraph } = Typography;
const formatDate=(value)=>value?formatAdminDateTime(value, {dateStyle:'short',timeStyle:'short'}):'—';

export default function FeatureAnnouncementShowPage({ config }) {
    const a=config.announcement||{}; const stats=config.stats||{}; const users=stats.users||[];
    const percent=Number(stats.target_count||0)>0?Math.round(Number(stats.acknowledged_count||0)*100/Number(stats.target_count)):0;
    const columns=[{title:'Người nhận',render:(_,row)=><div><Text strong>{row.user?.full_name||row.user?.username}</Text><div><Text type="secondary">@{row.user?.username} · {config.roleLabels?.[row.user?.role]||row.user?.role}</Text></div></div>},{title:'Email',render:(_,row)=>row.user?.email||'—'},{title:'Trạng thái',render:(_,row)=>row.acknowledged_at?<Tag color="success">Đã xác nhận</Tag>:<Tag>Chưa xác nhận</Tag>},{title:'Thời gian',render:(_,row)=>row.acknowledged_at?formatDate(row.acknowledged_at):'—'}];
    return <AdminPage>
        <AdminPageHeader
            eyebrow="Báo cáo thông báo"
            icon={<NotificationOutlined />}
            title={a.title}
            description="Kiểm tra nội dung đã phát hành và mức độ xác nhận của đúng nhóm người nhận."
            backHref={config.routes.index}
            backLabel="Danh sách thông báo"
            meta={<><Tag>Version {a.version}</Tag><Tag color={a.is_active ? 'success' : 'default'}>{a.is_active ? 'Đang bật' : 'Đã tắt'}</Tag></>}
            actions={config.permissions?.update&&<Button type="primary" icon={<EditOutlined/>} href={config.routes.edit}>Chỉnh sửa</Button>}
        />
        <div className="row">
            <div className="col-lg-8 mb-4"><AdminSectionCard title="Nội dung đã phát hành" description="Đây là nội dung người nhận nhìn thấy trong popup."><Paragraph style={{whiteSpace:'pre-wrap'}}>{a.content}</Paragraph>{a.image_path&&<Image src={`${String(config.storageBaseUrl||'/storage').replace(/\/$/,'')}/${a.image_path}`} alt="Ảnh minh họa thông báo" style={{maxHeight:360,objectFit:'contain',borderRadius:10}}/>}{a.action_url&&<div className="mt-3"><Button href={a.action_url} target="_blank">{a.action_text||'Mở liên kết'}</Button></div>}</AdminSectionCard></div>
            <div className="col-lg-4 mb-4"><AdminSectionCard title="Phạm vi & tiến độ" description={`${stats.acknowledged_count||0}/${stats.target_count||0} người đã xác nhận.`}><Descriptions column={1} size="small"><Descriptions.Item label="Ưu tiên">{a.priority}</Descriptions.Item><Descriptions.Item label="Bắt đầu">{formatDate(a.starts_at)}</Descriptions.Item><Descriptions.Item label="Kết thúc">{a.ends_at?formatDate(a.ends_at):'Không giới hạn'}</Descriptions.Item><Descriptions.Item label="Đối tượng">{a.target_type==='users'?`${a.targeted_users?.length||0} tài khoản`:(a.target_roles||[]).map(r=>config.roleLabels?.[r]||r).join(', ')}</Descriptions.Item></Descriptions><div className="mt-3"><Progress percent={percent}/></div></AdminSectionCard></div>
        </div>
        <AdminDataCard title="Báo cáo xác nhận" description="Danh sách người nhận và thời điểm họ đã xác nhận thông báo."><Table rowKey={(row)=>row.user?.id} dataSource={users} columns={columns} pagination={{pageSize:10}} locale={{emptyText:'Chưa có người nhận'}} /></AdminDataCard>
    </AdminPage>;
}
