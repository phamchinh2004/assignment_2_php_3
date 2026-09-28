import { Button, Descriptions, Image, Pagination, Space, Table, Tag, Typography } from 'antd';
import { CrownOutlined, EditOutlined } from '@ant-design/icons';
import { spaNavigate } from '../../../navigation';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;
const money = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const routeFor = (template, id) => String(template || '').replace('__USER_ID__', encodeURIComponent(String(id)));

export default function RankShowPage({ config }) {
    const rank = config.rank || {};
    const usersPage = config.users || {};
    const users = usersPage.data || [];
    const columns = [
        { title: 'Khách hàng', render: (_, user) => <div><Text strong>{user.full_name || 'Chưa đặt tên'}</Text><div><Text type="secondary">@{user.username}</Text></div></div> },
        { title: 'Điện thoại', dataIndex: 'phone', render: (value) => value || '—' },
        { title: 'Số dư', dataIndex: 'balance', render: (value) => <Text strong>${money(value)}</Text> },
        { title: 'Trạng thái', dataIndex: 'status', render: (value) => <Tag color={value === 'activated' ? 'success' : 'error'}>{value === 'activated' ? 'Hoạt động' : 'Bị khóa'}</Tag> },
        { title: 'Chi tiết', align: 'right', render: (_, user) => config.permissions?.viewCustomerDetail ? <Button href={routeFor(config.routes.userShow, user.id)}>Xem hồ sơ</Button> : '—' },
    ];

    const changePage = (page) => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        spaNavigate(url.toString());
    };

    return <AdminPage>
        <AdminPageHeader
            eyebrow="Chi tiết cấp độ"
            icon={<CrownOutlined />}
            title={rank.name || 'Cấp độ'}
            description="Theo dõi quyền lợi, giới hạn tài chính và số thành viên hiện đang thuộc cấp này."
            backHref={config.routes.index}
            backLabel="Danh sách cấp độ"
            meta={<><Tag>ID {rank.id}</Tag>{rank.image && <Image preview width={34} height={34} src={`${String(config.storageBaseUrl || '/storage').replace(/\/$/, '')}/${rank.image}`} style={{objectFit:'cover',borderRadius:8}} />}</>}
            actions={config.permissions?.update && <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa cấp độ</Button>}
        />
        <AdminMetricGrid items={[
            {key:'commission',title:'Hoa hồng',value:Number(rank.commission_percentage || 0),suffix:'%',tone:'success'},
            {key:'orders',title:'Số đơn / vòng',value:Number(rank.spin_count || 0),suffix:'đơn',tone:'primary'},
            {key:'members',title:'Thành viên cấp này',value:Number(usersPage.total || 0),tone:'info'},
        ]} min={3} />
        <AdminSectionCard className="mb-4" title="Cấu hình đang áp dụng" description="Các giá trị hiện tại ảnh hưởng trực tiếp đến thành viên của cấp độ này.">
            <Descriptions bordered column={{ xs: 1, md: 2 }}>
                <Descriptions.Item label="Phí nâng cấp">${money(rank.upgrade_fee)}</Descriptions.Item>
                <Descriptions.Item label="Tổng giá trị đơn">${money(rank.value)}</Descriptions.Item>
                <Descriptions.Item label="Số lần rút tối đa/ngày">{rank.maximum_number_of_withdrawals}</Descriptions.Item>
                <Descriptions.Item label="Số tiền rút tối đa/lượt">${money(rank.maximum_withdrawal_amount)}</Descriptions.Item>
                <Descriptions.Item label="Số đơn mẫu đã tạo">{rank.orders_count || 0}</Descriptions.Item>
            </Descriptions>
        </AdminSectionCard>
        <AdminDataCard title="Thành viên đang ở cấp độ này" description="Danh sách tài khoản hiện có rank tương ứng; chỉ hiển thị liên kết hồ sơ khi có quyền xem chi tiết khách hàng.">
            <Table rowKey="id" dataSource={users} columns={columns} pagination={false} scroll={{ x: 760 }} />
            {Number(usersPage.last_page || 1) > 1 && <div className="d-flex justify-content-end mt-3"><Pagination current={Number(usersPage.current_page || 1)} pageSize={Number(usersPage.per_page || 10)} total={Number(usersPage.total || 0)} onChange={changePage} showSizeChanger={false} /></div>}
        </AdminDataCard>
    </AdminPage>;
}
