import { formatAdminDateTime } from '../../../../shared/datetime';
import { Button, Descriptions, Pagination, Space, Table, Tag, Typography } from 'antd';
import { EditOutlined, SafetyCertificateOutlined, TeamOutlined } from '@ant-design/icons';
import { spaNavigate } from '../../../navigation';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';
import { CustomerIdentity } from '../../../components/admin/OperationsUi';

const { Text } = Typography;
const routeFor = (template, token, id) => String(template || '').replace(token, encodeURIComponent(String(id)));
const money = (value) => `${new Intl.NumberFormat('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}).format(Number(value || 0))}$`;
const dateTime = (value) => value ? formatAdminDateTime(value, {dateStyle:'short',timeStyle:'short'}) : '—';

export default function StaffShowPage({ config }) {
    const staff = config.staff || {};
    const page = config.referrals || {};
    const referrals = page.data || [];
    const activePermissions = config.activePermissions || [];
    const changePage = (nextPage) => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', nextPage);
        spaNavigate(url.toString());
    };

    const columns = [
        { title:'Khách hàng', render:(_,user)=><CustomerIdentity user={user} /> },
        { title:'Số điện thoại', dataIndex:'phone', render:(value)=>value || '—' },
        { title:'Cấp độ', render:(_,user)=><Tag>{user.rank?.name || 'Mặc định'}</Tag> },
        { title:'Số dư', dataIndex:'balance', render:(value)=><Text strong>{money(value)}</Text> },
        { title:'Trạng thái', dataIndex:'status', render:(value)=><Tag color={value==='activated'?'success':'error'}>{value==='activated'?'Hoạt động':'Bị khóa'}</Tag> },
        { title:'Ngày tạo', dataIndex:'created_at', render:dateTime },
        { title:'Chi tiết', render:(_,user)=>config.permissions?.viewCustomerDetail?<Button size="small" href={routeFor(config.routes.customerShow,'__USER_ID__',user.id)}>Xem</Button>:<Text type="secondary">—</Text> },
    ];

    return <AdminPage>
        <AdminPageHeader
            eyebrow="Hồ sơ nhân sự"
            icon={<TeamOutlined />}
            title={staff.full_name || 'Chưa đặt tên'}
            description={`@${staff.username} · ${staff.phone || 'Chưa có SĐT'}`}
            backHref={config.routes.index}
            backLabel="Danh sách nhân sự"
            meta={<><Tag>#{staff.id}</Tag><Tag color={staff.role==='admin'?'gold':'green'}>{staff.role==='admin'?'Admin':'Staff'}</Tag><Tag color={staff.is_online?'success':'default'}>{staff.is_online ? 'Online' : (staff.last_seen_text || 'Offline')}</Tag></>}
            actions={<Space wrap>{config.permissions?.managePermissions&&<Button icon={<SafetyCertificateOutlined />} href={config.routes.permissions}>Phân quyền</Button>}{config.permissions?.update&&<Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa</Button>}</Space>}
        />

        <AdminMetricGrid items={[
            {key:'deposit',title:staff.role==='admin'?'Doanh số nạp của team':'Doanh số nạp',value:Number(staff.total_deposit || 0),precision:2,suffix:'$',tone:'success'},
            {key:'customers',title:'Thành viên quản lý',value:Number(page.total || 0),tone:'primary'},
            {key:'permissions',title:'Quyền đang bật',value:activePermissions.length,tone:'info'},
            {key:'presence',title:'Trạng thái hiện diện',value:staff.is_online?'Online':'Offline',tone:staff.is_online?'success':'neutral'},
        ]} />

        <div className="row mb-4">
            <div className="col-lg-7 mb-4 mb-lg-0"><AdminSectionCard title="Thông tin hồ sơ" description="Thông tin nhận diện và trạng thái của tài khoản nội bộ."><Descriptions column={{xs:1,md:2}} size="small">
                <Descriptions.Item label="Họ và tên">{staff.full_name || '—'}</Descriptions.Item>
                <Descriptions.Item label="Tên đăng nhập">@{staff.username}</Descriptions.Item>
                <Descriptions.Item label="Email">{staff.email || '—'}</Descriptions.Item>
                <Descriptions.Item label="Số điện thoại">{staff.phone || 'Chưa cập nhật'}</Descriptions.Item>
                <Descriptions.Item label="Vai trò"><Tag color={staff.role==='admin'?'gold':'green'}>{staff.role==='admin'?'Admin':'Staff'}</Tag></Descriptions.Item>
                <Descriptions.Item label="Trạng thái"><Tag color={staff.status==='activated'?'success':staff.status==='inactivated'?'warning':'error'}>{staff.status}</Tag></Descriptions.Item>
                <Descriptions.Item label="Mã giới thiệu">{staff.referral_code || '—'}</Descriptions.Item>
                <Descriptions.Item label="IP đăng ký">{staff.register_ip || 'Chưa ghi nhận'}</Descriptions.Item>
                <Descriptions.Item label="Người quản lý / tạo">{staff.referrer ? `${staff.referrer.full_name || staff.referrer.username} (@${staff.referrer.username})` : 'Quản trị viên tối cao'}</Descriptions.Item>
                <Descriptions.Item label="Ngày khởi tạo">{dateTime(staff.created_at)}</Descriptions.Item>
            </Descriptions></AdminSectionCard></div>
            <div className="col-lg-5"><AdminSectionCard title="Quyền hệ thống đang hoạt động" description="Chỉ liệt kê các quyền đang bật cho tài khoản này.">{activePermissions.length?<Space wrap>{activePermissions.map((permission)=><Tag color="success" key={permission.id}>{permission.label}</Tag>)}</Space>:<Text type="secondary">Chưa được cấp quyền hạn nào.</Text>}</AdminSectionCard></div>
        </div>

        <AdminDataCard title="Thành viên do nhân viên quản lý" description="Danh sách khách hàng thuộc phạm vi quản lý hiện tại của tài khoản này.">
            <Table rowKey="id" dataSource={referrals} columns={columns} pagination={false} scroll={{x:900}}/>
            {Number(page.last_page || 1)>1&&<div className="d-flex justify-content-end mt-3"><Pagination current={Number(page.current_page||1)} total={Number(page.total||0)} pageSize={Number(page.per_page||10)} showSizeChanger={false} onChange={changePage}/></div>}
        </AdminDataCard>
    </AdminPage>;
}
