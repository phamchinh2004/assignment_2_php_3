import { Button, Card, Descriptions, Table, Tag, Typography } from 'antd';
import { ArrowLeftOutlined, EditOutlined } from '@ant-design/icons';

const { Text, Title } = Typography;
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

    return <div className="container-fluid px-4 pb-5">
        <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách chức năng</Button>
        <div className="d-flex justify-content-between gap-3 mb-4"><div><Title level={2}>{setting.manager_name}</Title><Text code>{setting.manager_code}</Text></div><Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa chức năng</Button></div>
        <Card className="mb-4"><Descriptions bordered column={1}><Descriptions.Item label="Tên chức năng">{setting.manager_name}</Descriptions.Item><Descriptions.Item label="Mã quyền">{setting.manager_code}</Descriptions.Item><Descriptions.Item label="Loại">{setting.parent_manager_setting_id ? 'Chức năng con' : 'Chức năng cha'}</Descriptions.Item></Descriptions></Card>
        <Card title={`Nhân viên đang được cấp (${rows.length})`}><Table rowKey="id" dataSource={rows} columns={columns} pagination={{ pageSize: 10 }} /></Card>
    </div>;
}
