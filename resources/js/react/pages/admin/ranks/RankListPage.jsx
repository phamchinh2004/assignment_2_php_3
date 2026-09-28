import { Button, Image, Popconfirm, Space, Table, Tag, Typography } from 'antd';
import { CrownOutlined, PlusOutlined } from '@ant-design/icons';
import LaravelForm from '../../../components/LaravelForm';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

const { Text } = Typography;
const money = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const routeFor = (template, id) => String(template || '').replace('__RANK_ID__', encodeURIComponent(String(id)));

export default function RankListPage({ config }) {
    const ranks = config.ranks || [];
    const permissions = config.permissions || {};
    const maxCommission = ranks.reduce((max, rank) => Math.max(max, Number(rank.commission_percentage || 0)), 0);
    const totalOrders = ranks.reduce((sum, rank) => sum + Number(rank.orders_count || 0), 0);
    const columns = [
        { title: '#', width: 64, render: (_, __, index) => <Tag>#{index + 1}</Tag> },
        {
            title: 'Cấp độ',
            render: (_, rank) => (
                <Space>
                    {rank.image && <Image preview={false} width={44} height={44} src={`${String(config.storageBaseUrl || '/storage').replace(/\/$/, '')}/${rank.image}`} style={{ objectFit: 'cover', borderRadius: 10 }} />}
                    <div>
                        {permissions.viewDetail ? <a href={routeFor(config.routes.show, rank.id)}><Text strong>{rank.name}</Text></a> : <Text strong>{rank.name}</Text>}
                        <div><Text type="secondary">ID {rank.id}</Text></div>
                    </div>
                </Space>
            ),
        },
        { title: 'Phí nâng cấp', render: (_, rank) => <Text strong>${money(rank.upgrade_fee)}</Text> },
        { title: 'Đơn hàng & Giá trị', render: (_, rank) => <div><div>{rank.spin_count} đơn</div><Text type="secondary">${money(rank.value)}</Text></div> },
        { title: 'Hoa hồng', render: (_, rank) => <Tag color="success">{Number(rank.commission_percentage || 0)}%</Tag> },
        { title: 'Hạn mức rút', render: (_, rank) => <div><div>{rank.maximum_number_of_withdrawals} lần/ngày</div><Text type="secondary">${money(rank.maximum_withdrawal_amount)}/lượt</Text></div> },
        { title: 'Đã tạo', align: 'center', render: (_, rank) => <Tag>{Number(rank.orders_count || 0)}</Tag> },
        {
            title: 'Thao tác', align: 'right', width: 220,
            render: (_, rank) => <Space>
                {permissions.viewDetail && <Button href={routeFor(config.routes.show, rank.id)}>Xem</Button>}
                {permissions.update && <Button href={routeFor(config.routes.edit, rank.id)}>Sửa</Button>}
                {permissions.delete && <>
                    <Popconfirm title="Xóa cấp độ này?" onConfirm={() => document.getElementById(`delete-rank-${rank.id}`)?.requestSubmit()}><Button danger>Xóa</Button></Popconfirm>
                    <LaravelForm id={`delete-rank-${rank.id}`} action={routeFor(config.routes.destroy, rank.id)} method="DELETE" style={{ display: 'none' }} />
                </>}
            </Space>,
        },
    ];

    return <AdminPage>
        <AdminPageHeader
            eyebrow="Cấu hình thành viên"
            icon={<CrownOutlined />}
            title="Quản lý cấp độ"
            description="Thiết lập quyền lợi, chu kỳ đơn hàng và hạn mức rút tiền cho từng cấp thành viên."
            actions={permissions.create && <Button type="primary" icon={<PlusOutlined />} href={config.routes.create}>Thêm cấp độ mới</Button>}
        />
        <AdminMetricGrid items={[
            {key:'count',title:'Tổng cấp bậc',value:ranks.length,tone:'primary'},
            {key:'commission',title:'Hoa hồng cao nhất',value:maxCommission,suffix:'%',tone:'success'},
            {key:'orders',title:'Tổng đơn mẫu theo rank',value:totalOrders,tone:'info'},
        ]} min={3} />
        <AdminDataCard title="Danh sách cấp độ" description="So sánh nhanh quyền lợi và giới hạn trước khi chỉnh sửa một cấp bậc.">
            <Table rowKey="id" dataSource={ranks} columns={columns} scroll={{ x: 1050 }} pagination={{ pageSize: 10 }} locale={{emptyText:'Chưa có cấp độ'}} />
        </AdminDataCard>
    </AdminPage>;
}
