import { Button, Descriptions, Table, Tag, Typography } from 'antd';
import { EditOutlined, SafetyCertificateOutlined } from '@ant-design/icons';
import { AdminDataCard, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;
const staffRoute = (template, id) => String(template || '').replace('__STAFF_ID__', encodeURIComponent(String(id)));

export default function ManagerSettingShowPage({ config }) {
    const setting = config.setting || {};
    const assignments = config.assignments || [];
    const rows = assignments.filter((assignment) => assignment.user).map((assignment) => assignment.user);
    const columns = [
        { title: 'Nhân viên', render: (_, user) => <div><a href={staffRoute(config.routes.staffShow, user.id)}><Text strong>{user.full_name || user.username}</Text></a><div><Text type="secondary">@{user.username}</Text></div></div> },
        { title: 'Điện thoại', dataIndex: 'phone', render: (value) => value || '—' },
        { title: 'Trạng thái', dataIndex: 'status', render: (value) => <Tag color={value === 'activated' ? 'success' : 'error'}>{value === 'activated' ? 'Hoạt động' : 'Bị khóa'}</Tag> },
        { title: 'Phân quyền', align: 'right', render: (_, user) => <Button href={staffRoute(config.routes.staffPermissions, user.id)}>Chỉnh quyền</Button> },
    ];

    return <AdminPage width="content">
        <AdminPageHeader
            eyebrow="Chi tiết quyền"
            icon={<SafetyCertificateOutlined />}
            title={setting.manager_name}
            description="Kiểm tra định danh quyền và những tài khoản hiện đang được cấp trước khi chỉnh sửa."
            backHref={config.routes.index}
            backLabel="Danh sách chức năng"
            meta={<><Text code>{setting.manager_code}</Text><Tag>{setting.parent_manager_setting_id ? 'Quyền con' : 'Nhóm cha'}</Tag></>}
            actions={<Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa chức năng</Button>}
        />
        <AdminSectionCard className="mb-4" title="Thông tin quyền" description="Mã quyền là định danh kỹ thuật đang được hệ thống sử dụng."><Descriptions column={{xs:1,md:2}}><Descriptions.Item label="Tên chức năng">{setting.manager_name}</Descriptions.Item><Descriptions.Item label="Mã quyền"><Text code>{setting.manager_code}</Text></Descriptions.Item><Descriptions.Item label="Loại">{setting.parent_manager_setting_id ? 'Chức năng con' : 'Chức năng cha'}</Descriptions.Item></Descriptions></AdminSectionCard>
        <AdminDataCard title="Nhân viên đang được cấp" description={`${rows.length} tài khoản đang có quyền này trong cấu hình hiện tại.`}><Table rowKey="id" dataSource={rows} columns={columns} pagination={{ pageSize: 10 }} locale={{emptyText:'Chưa có nhân viên nào được cấp quyền'}} /></AdminDataCard>
    </AdminPage>;
}
