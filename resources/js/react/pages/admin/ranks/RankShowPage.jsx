import { Button, Card, Descriptions, Image, Pagination, Space, Statistic, Table, Tag, Typography } from 'antd';
import { ArrowLeftOutlined, EditOutlined } from '@ant-design/icons';
import { spaNavigate } from '../../../navigation';

const { Text, Title } = Typography;
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

    return <div className="container-fluid px-4 pb-5">
        <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách cấp độ</Button>
        <div className="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <Space align="start">
                {rank.image && <Image width={64} src={`${String(config.storageBaseUrl || '/storage').replace(/\/$/, '')}/${rank.image}`} />}
                <div><Title level={2} style={{ marginBottom: 4 }}>{rank.name}</Title><Text type="secondary">ID {rank.id}</Text></div>
            </Space>
            {config.permissions?.update && <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa cấp độ</Button>}
        </div>
        <div className="row mb-4">
            <div className="col-md-4 mb-3"><Card><Statistic title="Hoa hồng" value={Number(rank.commission_percentage || 0)} suffix="%" /></Card></div>
            <div className="col-md-4 mb-3"><Card><Statistic title="Số đơn / vòng" value={Number(rank.spin_count || 0)} suffix="đơn" /></Card></div>
            <div className="col-md-4 mb-3"><Card><Statistic title="Thành viên cấp này" value={Number(usersPage.total || 0)} /></Card></div>
        </div>
        <Card className="mb-4">
            <Descriptions bordered column={{ xs: 1, md: 2 }}>
                <Descriptions.Item label="Phí nâng cấp">${money(rank.upgrade_fee)}</Descriptions.Item>
                <Descriptions.Item label="Tổng giá trị đơn">${money(rank.value)}</Descriptions.Item>
                <Descriptions.Item label="Số lần rút tối đa/ngày">{rank.maximum_number_of_withdrawals}</Descriptions.Item>
                <Descriptions.Item label="Số tiền rút tối đa/lượt">${money(rank.maximum_withdrawal_amount)}</Descriptions.Item>
                <Descriptions.Item label="Số đơn mẫu đã tạo">{rank.orders_count || 0}</Descriptions.Item>
            </Descriptions>
        </Card>
        <Card title="Thành viên đang ở cấp độ này">
            <Table rowKey="id" dataSource={users} columns={columns} pagination={false} scroll={{ x: 760 }} />
            {Number(usersPage.last_page || 1) > 1 && <div className="d-flex justify-content-end mt-3"><Pagination current={Number(usersPage.current_page || 1)} pageSize={Number(usersPage.per_page || 10)} total={Number(usersPage.total || 0)} onChange={changePage} showSizeChanger={false} /></div>}
        </Card>
    </div>;
}
