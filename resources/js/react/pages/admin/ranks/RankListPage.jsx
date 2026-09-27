import { Button, Card, Image, Popconfirm, Space, Statistic, Table, Tag, Typography } from 'antd';
import { CrownOutlined, PlusOutlined } from '@ant-design/icons';
import LaravelForm from '../../../components/LaravelForm';

const { Text, Title } = Typography;
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

    return <div className="container-fluid px-4 pb-5">
        <div className="page-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div><Title level={2}>Quản lý cấp độ (Rank)</Title><Text type="secondary">Thiết lập cấp bậc, hoa hồng, số đơn và hạn mức rút tiền.</Text></div>
            {permissions.create && <Button type="primary" icon={<PlusOutlined />} href={config.routes.create}>Thêm cấp độ mới</Button>}
        </div>
        <div className="row mb-4">
            <div className="col-md-4 mb-3"><Card><Statistic title="Tổng cấp bậc" value={ranks.length} prefix={<CrownOutlined />} /></Card></div>
            <div className="col-md-4 mb-3"><Card><Statistic title="Hoa hồng cao nhất" value={maxCommission} suffix="%" /></Card></div>
            <div className="col-md-4 mb-3"><Card><Statistic title="Tổng đơn theo rank" value={totalOrders} /></Card></div>
        </div>
        <Card><Table rowKey="id" dataSource={ranks} columns={columns} scroll={{ x: 1050 }} pagination={{ pageSize: 10 }} /></Card>
    </div>;
}
