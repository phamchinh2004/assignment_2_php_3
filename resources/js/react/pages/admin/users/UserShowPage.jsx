import { Avatar, Button, Card, Col, Descriptions, Row, Space, Statistic, Table, Tag, Typography } from 'antd';
import { EditOutlined, UserOutlined } from '@ant-design/icons';

const { Text, Title } = Typography;

function money(value, digits = 2) {
    return Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    });
}

function dateTime(value) {
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString('vi-VN');
}

export default function UserShowPage({ config }) {
    const user = config.user || {};
    const permissions = config.permissions || {};
    const status = user.status === 'activated'
        ? { color: 'success', label: 'Đã kích hoạt' }
        : user.status === 'inactivated'
            ? { color: 'warning', label: 'Chưa kích hoạt' }
            : { color: 'error', label: 'Bị khóa' };

    const walletColumns = [
        { title: 'Thời gian', dataIndex: 'created_at', render: dateTime },
        { title: 'Loại', dataIndex: 'type', render: (value) => <Tag>{value}</Tag> },
        { title: 'Trước', dataIndex: 'balance_before', render: (value) => value === null || value === undefined ? '—' : `${money(value)}$` },
        { title: 'Sau', dataIndex: 'balance_after', render: (value) => value === null || value === undefined ? '—' : `${money(value)}$` },
        { title: 'Giá trị', dataIndex: 'amount', render: (value) => value === null || value === undefined ? '—' : `${money(value)}$` },
    ];

    const transactionColumns = [
        { title: 'Thời gian', dataIndex: 'created_at', render: dateTime },
        { title: 'Loại', dataIndex: 'type', render: (value) => <Tag>{value || '—'}</Tag> },
        { title: 'Số tiền', dataIndex: 'amount', render: (value) => value === null || value === undefined ? '—' : `${money(value)}$` },
        { title: 'Trạng thái', dataIndex: 'status', render: (value) => value || '—' },
    ];

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Row justify="space-between" align="middle" gutter={[16, 16]}>
                <Col>
                    <Space>
                        <Avatar size={64} src={user.avatar_url} icon={<UserOutlined />} />
                        <div>
                            <Title level={2} style={{ margin: 0 }}>{user.full_name || user.username}</Title>
                            <Space><Text type="secondary">@{user.username} · ID {user.id}</Text><Tag color={status.color}>{status.label}</Tag></Space>
                        </div>
                    </Space>
                </Col>
                <Col>
                    <Space>
                        <Button href={config.routes.index}>Quay lại</Button>
                        {permissions.update && <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa</Button>}
                    </Space>
                </Col>
            </Row>

            <Row gutter={[16, 16]}>
                {permissions.viewFinancials && (
                    <>
                        <Col xs={24} md={8}><Card><Statistic title="Số dư" value={money(user.balance)} suffix="$" /></Card></Col>
                        <Col xs={24} md={8}><Card><Statistic title="Số dư đóng băng" value={money(user.frozen_balance)} suffix="$" /></Card></Col>
                    </>
                )}
                <Col xs={24} md={8}>
                    <Card>
                        <Statistic title="Tiến trình vòng quay" value={user.user_spin_progress?.current_spin || 0} suffix={user.rank?.spin_count ? `/ ${user.rank.spin_count}` : ''} />
                    </Card>
                </Col>
            </Row>

            <Card title="Thông tin tài khoản">
                <Descriptions bordered column={{ xs: 1, md: 2 }}>
                    <Descriptions.Item label="Họ tên">{user.full_name || '—'}</Descriptions.Item>
                    <Descriptions.Item label="Username">@{user.username}</Descriptions.Item>
                    <Descriptions.Item label="Email">{user.email || '—'}</Descriptions.Item>
                    <Descriptions.Item label="Số điện thoại">{user.phone || '—'}</Descriptions.Item>
                    <Descriptions.Item label="Cấp độ">{user.rank?.name || 'Chưa có cấp độ'}</Descriptions.Item>
                    <Descriptions.Item label="Mã giới thiệu">{user.referral_code || '—'}</Descriptions.Item>
                    <Descriptions.Item label="Người quản lý">{user.referrer ? (user.referrer.full_name || user.referrer.username) : '—'}</Descriptions.Item>
                    <Descriptions.Item label="Loại tài khoản">{user.clone_account ? 'Clone' : 'Thành viên thực'}</Descriptions.Item>
                    <Descriptions.Item label="Khu vực kho">{user.warehouse_area || '—'}</Descriptions.Item>
                    <Descriptions.Item label="Địa chỉ kho">{user.warehouse_address || '—'}</Descriptions.Item>
                    <Descriptions.Item label="Ngày tạo">{dateTime(user.created_at)}</Descriptions.Item>
                    <Descriptions.Item label="Hoạt động gần nhất">{dateTime(user.last_seen)}</Descriptions.Item>
                </Descriptions>
            </Card>

            <Card title="Vị trí">
                <Descriptions column={{ xs: 1, md: 2 }}>
                    <Descriptions.Item label="Quốc gia">{user.location_country || user.approx_location_country || 'Chưa rõ'}</Descriptions.Item>
                    <Descriptions.Item label="Thành phố">{user.location_city || '—'}</Descriptions.Item>
                    <Descriptions.Item label="Tọa độ">
                        {user.location_latitude !== null && user.location_latitude !== undefined
                            ? `${user.location_latitude}, ${user.location_longitude}`
                            : '—'}
                    </Descriptions.Item>
                    <Descriptions.Item label="Online">{user.is_online ? 'Có' : 'Không'}</Descriptions.Item>
                </Descriptions>
            </Card>

            {permissions.viewFinancials && (
                <>
                    <Card title="Lịch sử số dư gần nhất">
                        <Table rowKey="id" size="small" pagination={false} dataSource={user.wallet_balance_histories || []} columns={walletColumns} scroll={{ x: 700 }} />
                    </Card>
                    <Card title="Giao dịch gần nhất">
                        <Table rowKey="id" size="small" pagination={false} dataSource={user.transaction_histories || []} columns={transactionColumns} scroll={{ x: 650 }} />
                    </Card>
                </>
            )}
        </Space>
    );
}
