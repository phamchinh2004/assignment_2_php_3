import { Avatar, Button, Col, Descriptions, Row, Space, Table, Tag, Typography } from 'antd';
import {
    EditOutlined,
    MessageOutlined,
    UserOutlined,
} from '@ant-design/icons';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;

function SnowflakeIcon() {
    return (
        <svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
            <path d="M12 2v20M4.2 6.5l15.6 11M19.8 6.5l-15.6 11" />
            <path d="m9.5 4.5 2.5 2.2 2.5-2.2M9.5 19.5 12 17.3l2.5 2.2M5.5 9.1l3.2.4.7-3.1M18.5 14.9l-3.2-.4-.7 3.1M18.5 9.1l-3.2.4-.7-3.1M5.5 14.9l3.2-.4.7 3.1" />
        </svg>
    );
}

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

function statusMeta(status) {
    if (status === 'activated') return { color: 'success', label: 'Đã kích hoạt' };
    if (status === 'inactivated') return { color: 'warning', label: 'Chưa kích hoạt' };
    return { color: 'error', label: 'Bị khóa' };
}

function valueOrDash(value) {
    return value === null || value === undefined || value === '' ? '—' : value;
}

export default function UserShowPage({ config }) {
    const user = config.user || {};
    const permissions = config.permissions || {};
    const status = statusMeta(user.status);
    const exactCountry = user.location_country || user.location_country_code;
    const approximateCountry = user.approx_location_country || user.approx_location_country_code;
    const frozenOrders = user.frozen_orders || [];
    const activeFrozenOrders = frozenOrders.filter(
        (item) => Boolean(item.is_frozen) && item.custom_price !== null && item.custom_price !== undefined,
    );

    const walletColumns = [
        { title: 'Thời gian', dataIndex: 'created_at', width: 170, render: dateTime },
        { title: 'Loại', dataIndex: 'type', width: 130, render: (value) => <Tag>{value || '—'}</Tag> },
        { title: 'Số dư trước', dataIndex: 'balance_before', align: 'right', render: (value) => value === null || value === undefined ? '—' : `${money(value, 5)}$` },
        { title: 'Số dư sau', dataIndex: 'balance_after', align: 'right', render: (value) => value === null || value === undefined ? '—' : `${money(value, 5)}$` },
        { title: 'Số tiền', dataIndex: 'value', align: 'right', render: (value) => value === null || value === undefined ? '—' : `${money(value, 5)}$` },
        { title: 'Trạng thái', dataIndex: 'status', width: 140, render: (value) => value || '—' },
    ];

    const transactionColumns = [
        { title: 'Thời gian', dataIndex: 'created_at', width: 170, render: dateTime },
        { title: 'Loại', dataIndex: 'type', width: 140, render: (value) => <Tag>{value || '—'}</Tag> },
        { title: 'Số tiền', dataIndex: 'value', align: 'right', render: (value) => value === null || value === undefined ? '—' : `${money(value, 5)}$` },
        { title: 'Ghi chú', dataIndex: 'note', render: (value) => value || '—' },
    ];

    return (
        <AdminPage>
            <AdminPageHeader
                eyebrow="Hồ sơ khách hàng"
                icon={<UserOutlined />}
                title={user.full_name || user.username || `Tài khoản #${user.id}`}
                description={`@${user.username} · Hồ sơ, vận hành và lịch sử tài chính gần nhất của thành viên.`}
                backHref={config.routes.index}
                backLabel="Danh sách người dùng"
                meta={<><Avatar size={30} src={user.avatar_url} icon={<UserOutlined />} /><Tag>ID {user.id}</Tag><Tag color={status.color}>{status.label}</Tag><Tag color={user.is_online ? 'success' : 'default'}>{user.is_online ? 'Đang online' : 'Offline'}</Tag>{user.clone_account && <Tag color="purple">Clone</Tag>}</>}
                actions={<Space wrap>
                    {config.routes.chat && <Button icon={<MessageOutlined />} href={config.routes.chat}>Nhắn tin</Button>}
                    {permissions.manageFrozenOrders && config.routes.frozenOrders && (
                        <Button icon={<SnowflakeIcon />} href={config.routes.frozenOrders}>Đơn đóng băng</Button>
                    )}
                    {permissions.update && <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa</Button>}
                </Space>}
            />

            <AdminMetricGrid items={[
                permissions.viewFinancials && {key:'balance',title:'Số dư khả dụng',value:money(user.balance, 5),suffix:'$',tone:'success'},
                permissions.viewFinancials && {key:'frozen',title:'Số dư đóng băng',value:money(user.frozen_balance, 5),suffix:'$',tone:'warning'},
                {key:'spin',title:'Tiến độ đơn hàng',value:user.user_spin_progress?.current_spin || 0,suffix:user.rank?.spin_count ? `/ ${user.rank.spin_count}` : '',tone:'primary'},
                {key:'orders',title:'Đơn đang đóng băng',value:activeFrozenOrders.length,tone:'info'},
            ]} />

            <Row gutter={[16, 16]} align="stretch">
                <Col xs={24} xl={14}>
                    <AdminSectionCard title="Hồ sơ tài khoản" description="Thông tin nhận diện, cấp độ và quan hệ quản lý hiện tại." className="h-100">
                        <Descriptions column={{ xs: 1, md: 2 }} size="small">
                            <Descriptions.Item label="Họ tên">{valueOrDash(user.full_name)}</Descriptions.Item>
                            <Descriptions.Item label="Tên đăng nhập">@{valueOrDash(user.username)}</Descriptions.Item>
                            <Descriptions.Item label="Email">{valueOrDash(user.email)}</Descriptions.Item>
                            <Descriptions.Item label="Số điện thoại">{valueOrDash(user.phone)}</Descriptions.Item>
                            <Descriptions.Item label="Cấp độ">{user.rank?.name || 'Chưa có cấp độ'}</Descriptions.Item>
                            <Descriptions.Item label="Vai trò">{valueOrDash(user.role)}</Descriptions.Item>
                            <Descriptions.Item label="Người quản lý">{user.referrer ? (user.referrer.full_name || `@${user.referrer.username}`) : 'Chưa gán'}</Descriptions.Item>
                            <Descriptions.Item label="Mã giới thiệu">{valueOrDash(user.referral_code)}</Descriptions.Item>
                            <Descriptions.Item label="Loại tài khoản">{user.clone_account ? 'Tài khoản clone' : 'Thành viên thường'}</Descriptions.Item>
                            <Descriptions.Item label="Lượt quay may mắn được cấp thêm">{Number(user.lucky_wheel_bonus_spins || 0)}</Descriptions.Item>
                        </Descriptions>
                    </AdminSectionCard>
                </Col>
                <Col xs={24} xl={10}>
                    <AdminSectionCard title="Ngân hàng & nhận hàng" description="Dữ liệu phục vụ rút tiền và vận hành giao nhận." className="h-100">
                        <Descriptions column={1} size="small">
                            <Descriptions.Item label="Chủ tài khoản">{valueOrDash(user.username_bank)}</Descriptions.Item>
                            <Descriptions.Item label="Ngân hàng">{valueOrDash(user.bank_name)}</Descriptions.Item>
                            <Descriptions.Item label="Số tài khoản">{valueOrDash(user.account_number)}</Descriptions.Item>
                            <Descriptions.Item label="Khu vực kho">{valueOrDash(user.warehouse_area)}</Descriptions.Item>
                            <Descriptions.Item label="Địa chỉ kho">{valueOrDash(user.warehouse_address)}</Descriptions.Item>
                        </Descriptions>
                    </AdminSectionCard>
                </Col>
            </Row>

            <Row gutter={[16, 16]} align="stretch">
                <Col xs={24} xl={12}>
                    <AdminSectionCard title="Vị trí người dùng" description="Hiển thị chính xác và tương đối riêng biệt để tránh hiểu nhầm nguồn dữ liệu." className="h-100">
                        <Descriptions column={{ xs: 1, md: 2 }} size="small">
                            <Descriptions.Item label="Thành phố">{valueOrDash(user.location_city)}</Descriptions.Item>
                            <Descriptions.Item label="Quốc gia chính xác">{valueOrDash(exactCountry)}</Descriptions.Item>
                            <Descriptions.Item label="Quốc gia tương đối">{valueOrDash(approximateCountry)}</Descriptions.Item>
                            <Descriptions.Item label="Độ chính xác">{user.location_accuracy ? `${user.location_accuracy} m` : '—'}</Descriptions.Item>
                            <Descriptions.Item label="Tọa độ" span={2}>
                                {user.location_latitude !== null && user.location_latitude !== undefined
                                    ? `${user.location_latitude}, ${user.location_longitude}`
                                    : '—'}
                            </Descriptions.Item>
                            <Descriptions.Item label="Cập nhật vị trí">{dateTime(user.location_updated_at || user.approx_location_updated_at)}</Descriptions.Item>
                        </Descriptions>
                    </AdminSectionCard>
                </Col>
                <Col xs={24} xl={12}>
                    <AdminSectionCard title="Thông tin hệ thống" description="Dữ liệu phục vụ theo dõi tài khoản, không phải thông tin chỉnh sửa thường xuyên." className="h-100">
                        <Descriptions column={{ xs: 1, md: 2 }} size="small">
                            <Descriptions.Item label="Ngày tạo">{dateTime(user.created_at)}</Descriptions.Item>
                            <Descriptions.Item label="Cập nhật gần nhất">{dateTime(user.updated_at)}</Descriptions.Item>
                            <Descriptions.Item label="Đăng nhập gần nhất">{dateTime(user.last_login_at)}</Descriptions.Item>
                            <Descriptions.Item label="Hoạt động gần nhất">{dateTime(user.last_seen)}</Descriptions.Item>
                            <Descriptions.Item label="IP đăng nhập gần nhất">{valueOrDash(user.last_login_ip)}</Descriptions.Item>
                            <Descriptions.Item label="IP đăng ký">{valueOrDash(user.register_ip)}</Descriptions.Item>
                            <Descriptions.Item label="Trạng thái"><Tag color={status.color}>{status.label}</Tag></Descriptions.Item>
                            <Descriptions.Item label="Hiện diện"><Tag color={user.is_online ? 'success' : 'default'}>{user.is_online ? 'Online' : 'Offline'}</Tag></Descriptions.Item>
                        </Descriptions>
                    </AdminSectionCard>
                </Col>
            </Row>

            {permissions.viewFinancials && (
                <Space direction="vertical" size="large" style={{width:'100%'}}>
                    <AdminDataCard title="Biến động số dư gần nhất" description="Lịch sử snapshot số dư giúp đối chiếu trước/sau mỗi biến động."><Table rowKey="id" size="small" pagination={false} dataSource={user.wallet_balance_histories || []} columns={walletColumns} scroll={{ x: 820 }} locale={{ emptyText: 'Chưa có lịch sử biến động số dư' }} /></AdminDataCard>
                    <AdminDataCard title="Giao dịch gần nhất" description="Các giao dịch mới nhất liên quan tới tài khoản này."><Table rowKey="id" size="small" pagination={false} dataSource={user.transaction_histories || []} columns={transactionColumns} scroll={{ x: 720 }} locale={{ emptyText: 'Chưa có giao dịch' }} /></AdminDataCard>
                </Space>
            )}
        </AdminPage>
    );
}
