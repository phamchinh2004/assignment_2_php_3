import { Button, Card, Collapse, Popconfirm, Space, Statistic, Tag, Typography } from 'antd';
import { PlusOutlined, SafetyCertificateOutlined } from '@ant-design/icons';
import LaravelForm from '../../../components/LaravelForm';

const { Text, Title } = Typography;
const routeFor = (template, id) => String(template || '').replace('__SETTING_ID__', encodeURIComponent(String(id)));

export default function ManagerSettingListPage({ config }) {
    const groups = config.permissionGroups || [];
    const items = groups.map((group) => ({
        key: group.key,
        label: <Space><SafetyCertificateOutlined /><Text strong>{group.label}</Text><Tag>{group.settings?.length || 0}</Tag></Space>,
        children: <Space direction="vertical" className="w-100" size="middle">{(group.settings || []).map((setting) => <Card size="small" key={setting.id}>
            <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div><a href={routeFor(config.routes.show, setting.id)}><Text strong>{setting.manager_name}</Text></a><div><Text code>{setting.manager_code}</Text>{setting.parent_manager_setting_id ? <Tag className="ml-2">Quyền con</Tag> : <Tag color="blue" className="ml-2">Nhóm cha</Tag>}</div></div>
                <Space>
                    <Button href={routeFor(config.routes.show, setting.id)}>Xem</Button>
                    <Button href={routeFor(config.routes.edit, setting.id)}>Sửa</Button>
                    <Popconfirm title="Xóa chức năng này?" onConfirm={() => document.getElementById(`delete-setting-${setting.id}`)?.requestSubmit()}><Button danger>Xóa</Button></Popconfirm>
                    <LaravelForm id={`delete-setting-${setting.id}`} action={routeFor(config.routes.destroy, setting.id)} method="DELETE" style={{ display: 'none' }} />
                </Space>
            </div>
        </Card>)}</Space>,
    }));

    return <div className="container-fluid px-4 pb-5">
        <div className="page-header-wrapper d-flex justify-content-between align-items-center gap-3"><div><Title level={2}>Danh sách chức năng phân quyền</Title><Text type="secondary">Nhóm chức năng con theo chức năng cha.</Text></div><Button type="primary" icon={<PlusOutlined />} href={config.routes.create}>Thêm chức năng mới</Button></div>
        <div className="mb-4"><Card><Statistic title="Tổng chức năng" value={Number(config.totalSettings || 0)} /></Card></div>
        <Collapse items={items} defaultActiveKey={groups.map((group) => group.key)} />
    </div>;
}
