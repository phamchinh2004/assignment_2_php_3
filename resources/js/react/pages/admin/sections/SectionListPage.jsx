import { Button, Popconfirm, Space, Table, Tag, Typography, message } from 'antd';
import { FileTextOutlined, PlusOutlined } from '@ant-design/icons';
import { spaGetAction } from '../../../navigation';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

const { Text } = Typography;
const routeFor = (template, id) => String(template || '').replace('__SECTION_ID__', encodeURIComponent(String(id)));

export default function SectionListPage({ config }) {
    const sections = config.sections || [];
    const permissions = config.permissions || {};
    const active = sections.filter((section) => Number(section.status) === 1).length;
    const translations = sections.reduce((sum, section) => sum + (section.section_languages?.length || 0), 0);
    const columns = [
        { title: '#', width: 64, render: (_, __, index) => <Tag>#{index + 1}</Tag> },
        { title: 'Section', render: (_, section) => <div>{permissions.viewDetail ? <a href={routeFor(config.routes.show, section.id)}><Text strong>{section.name}</Text></a> : <Text strong>{section.name}</Text>}<div><Text code>{section.code}</Text></div></div> },
        { title: 'Ngôn ngữ', align: 'center', render: (_, section) => <Tag>{section.section_languages?.length || 0} bản dịch</Tag> },
        { title: 'Trạng thái', align: 'center', render: (_, section) => <Tag color={Number(section.status) === 1 ? 'success' : 'default'}>{Number(section.status) === 1 ? 'Đang hoạt động' : 'Đã tắt'}</Tag> },
        {
            title: 'Thao tác', width: 240, align: 'right', render: (_, section) => <Space>
                {permissions.viewDetail && <Button href={routeFor(config.routes.show, section.id)}>Xem</Button>}
                {permissions.update && <Button href={routeFor(config.routes.edit, section.id)}>Sửa</Button>}
                {permissions.changeStatus && <Popconfirm title={Number(section.status) === 1 ? 'Dừng hiển thị section này?' : 'Kích hoạt section này?'} onConfirm={async () => {
                    const payload = await spaGetAction(routeFor(config.routes.changeStatus, section.id));
                    const text = payload?.props?.flash?.success || payload?.props?.flash?.error;
                    if (text) message[payload?.props?.flash?.error ? 'error' : 'success'](text);
                }}><Button>{Number(section.status) === 1 ? 'Tắt' : 'Bật'}</Button></Popconfirm>}
            </Space>,
        },
    ];

    return <AdminPage>
        <AdminPageHeader
            eyebrow="Website"
            icon={<FileTextOutlined />}
            title="Nội dung website"
            description="Quản lý các section và tình trạng nội dung đa ngôn ngữ đang được sử dụng trên website."
            actions={permissions.create && <Button type="primary" icon={<PlusOutlined />} href={config.routes.create}>Thêm section mới</Button>}
        />
        <AdminMetricGrid items={[
            {key:'all',title:'Tổng section',value:sections.length,tone:'primary'},
            {key:'active',title:'Đang hoạt động',value:active,tone:'success'},
            {key:'translations',title:'Tổng bản dịch',value:translations,tone:'info'},
        ]} min={3} />
        <AdminDataCard title="Danh sách section" description="Ưu tiên kiểm tra trạng thái và số bản dịch trước khi chỉnh sửa nội dung.">
            <Table rowKey="id" dataSource={sections} columns={columns} scroll={{ x: 760 }} pagination={{ pageSize: 10 }} locale={{emptyText:'Chưa có section nội dung'}} />
        </AdminDataCard>
    </AdminPage>;
}
