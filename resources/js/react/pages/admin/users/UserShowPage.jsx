import { formatAdminDateTime } from '../../../../shared/datetime';
import { Avatar, Button, Col, Row, Space, Table, Tag } from 'antd';
import {
    ArrowLeftOutlined,
    BankOutlined,
    CalendarOutlined,
    ClockCircleOutlined,
    CrownOutlined,
    DollarOutlined,
    EditOutlined,
    EnvironmentOutlined,
    GiftOutlined,
    IdcardOutlined,
    LockOutlined,
    MailOutlined,
    MessageOutlined,
    PhoneOutlined,
    SafetyCertificateOutlined,
    ShoppingOutlined,
    UserOutlined,
    WalletOutlined,
} from '@ant-design/icons';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminSectionCard } from '../../../components/admin/AdminUi';

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
    return formatAdminDateTime(value, { dateStyle: 'short', timeStyle: 'medium' }, value);
}

function statusMeta(status) {
    if (status === 'activated') return { color: 'success', label: 'Đã kích hoạt' };
    if (status === 'inactivated') return { color: 'warning', label: 'Chưa kích hoạt' };
    return { color: 'error', label: 'Bị khóa' };
}

function valueOrDash(value) {
    return value === null || value === undefined || value === '' ? '—' : value;
}

function DetailItem({ label, children, wide = false, mono = false }) {
    return (
        <div className={`customer-detail-field${wide ? ' customer-detail-field--wide' : ''}`}>
            <span className="customer-detail-field__label">{label}</span>
            <div className={`customer-detail-field__value${mono ? ' customer-detail-field__value--mono' : ''}`}>
                {children}
            </div>
        </div>
    );
}

function HeroFact({ icon, label, children }) {
    return (
        <div className="customer-detail-hero__fact">
            <span className="customer-detail-hero__fact-icon" aria-hidden="true">{icon}</span>
            <div>
                <span>{label}</span>
                <strong>{children}</strong>
            </div>
        </div>
    );
}

export default function UserShowPage({ config }) {
    const user = config.user || {};
    const permissions = config.permissions || {};
    const routes = config.routes || {};
    const status = statusMeta(user.status);
    const exactCountry = user.location_country || user.location_country_code;
    const approximateCountry = user.approx_location_country || user.approx_location_country_code;
    const locationLabel = user.location_city || exactCountry || approximateCountry || 'Chưa ghi nhận vị trí';
    const managerName = user.referrer
        ? (user.referrer.full_name || (user.referrer.username ? `@${user.referrer.username}` : `#${user.referrer.id}`))
        : 'Chưa gán';
    const frozenOrders = user.frozen_orders || [];
    const activeFrozenOrders = frozenOrders.filter(
        (item) => Boolean(item.is_frozen) && item.custom_price !== null && item.custom_price !== undefined,
    );
    const walletHistory = user.wallet_balance_histories || [];
    const transactionHistory = user.transaction_histories || [];

    const walletColumns = [
        { title: 'Thời gian', dataIndex: 'created_at', width: 170, render: dateTime },
        { title: 'Loại', dataIndex: 'type', width: 130, render: (value) => <Tag>{value || '—'}</Tag> },
        { title: 'Số dư trước', dataIndex: 'balance_before', align: 'right', render: (value) => value === null || value === undefined ? '—' : `${money(value, 5)} $` },
        { title: 'Số dư sau', dataIndex: 'balance_after', align: 'right', render: (value) => value === null || value === undefined ? '—' : `${money(value, 5)} $` },
        { title: 'Số tiền', dataIndex: 'value', align: 'right', render: (value) => value === null || value === undefined ? '—' : `${money(value, 5)} $` },
        { title: 'Trạng thái', dataIndex: 'status', width: 140, render: (value) => value || '—' },
    ];

    const transactionColumns = [
        { title: 'Thời gian', dataIndex: 'created_at', width: 170, render: dateTime },
        { title: 'Loại', dataIndex: 'type', width: 140, render: (value) => <Tag>{value || '—'}</Tag> },
        { title: 'Số tiền', dataIndex: 'value', align: 'right', render: (value) => value === null || value === undefined ? '—' : `${money(value, 5)} $` },
        { title: 'Ghi chú', dataIndex: 'note', render: (value) => value || '—' },
    ];

    return (
        <AdminPage className="customer-detail-page">
            <div className="customer-detail-back-row">
                <Button type="text" icon={<ArrowLeftOutlined />} href={routes.index}>
                    Danh sách người dùng
                </Button>
            </div>

            <section className="customer-detail-hero" aria-labelledby="customer-detail-title">
                <div className="customer-detail-hero__main">
                    <div className="customer-detail-avatar-wrap">
                        <Avatar size={84} src={user.avatar_url} icon={<UserOutlined />} />
                        <span
                            className={`customer-detail-presence${user.is_online ? ' customer-detail-presence--online' : ''}`}
                            aria-label={user.is_online ? 'Đang online' : 'Offline'}
                            title={user.is_online ? 'Đang online' : 'Offline'}
                        />
                    </div>

                    <div className="customer-detail-hero__identity">
                        <span className="customer-detail-eyebrow">Hồ sơ khách hàng</span>
                        <h1 id="customer-detail-title">{user.full_name || user.username || `Tài khoản #${user.id}`}</h1>
                        <p>@{valueOrDash(user.username)} · ID #{user.id}</p>

                        <div className="customer-detail-tags" aria-label="Trạng thái tài khoản">
                            <Tag color={status.color}>{status.label}</Tag>
                            <Tag color={user.is_online ? 'success' : 'default'}>{user.is_online ? 'Đang online' : 'Offline'}</Tag>
                            {user.rank?.name && <Tag color="blue">{user.rank.name}</Tag>}
                            {user.clone_account && <Tag color="purple">Tài khoản clone</Tag>}
                        </div>

                        <div className="customer-detail-contact-list">
                            <span><MailOutlined /> {valueOrDash(user.email)}</span>
                            <span><PhoneOutlined /> {valueOrDash(user.phone)}</span>
                            <span><EnvironmentOutlined /> {locationLabel}</span>
                        </div>
                    </div>
                </div>

                <div className="customer-detail-hero__side">
                    <div className="customer-detail-hero__facts">
                        <HeroFact icon={<SafetyCertificateOutlined />} label="Người quản lý">{managerName}</HeroFact>
                        <HeroFact icon={<CalendarOutlined />} label="Ngày tạo">{dateTime(user.created_at)}</HeroFact>
                        <HeroFact icon={<ClockCircleOutlined />} label="Hoạt động gần nhất">{dateTime(user.last_seen)}</HeroFact>
                        <HeroFact icon={<IdcardOutlined />} label="Loại tài khoản">{user.clone_account ? 'Clone' : 'Thành viên thường'}</HeroFact>
                    </div>

                    <div className="customer-detail-hero__actions">
                        {routes.chat && <Button icon={<MessageOutlined />} href={routes.chat}>Nhắn tin</Button>}
                        {permissions.manageFrozenOrders && routes.frozenOrders && (
                            <Button icon={<SnowflakeIcon />} href={routes.frozenOrders}>Đơn đóng băng</Button>
                        )}
                        {permissions.update && <Button type="primary" icon={<EditOutlined />} href={routes.edit}>Chỉnh sửa</Button>}
                    </div>
                </div>
            </section>

            <AdminMetricGrid className="customer-detail-metrics" items={[
                permissions.viewFinancials && {
                    key: 'balance',
                    title: 'Số dư khả dụng',
                    value: money(user.balance, 5),
                    suffix: '$',
                    tone: 'success',
                    icon: <WalletOutlined />,
                    hint: 'Số dư hiện có thể sử dụng.',
                },
                permissions.viewFinancials && {
                    key: 'frozen',
                    title: 'Số dư đóng băng',
                    value: money(user.frozen_balance, 5),
                    suffix: '$',
                    tone: 'warning',
                    icon: <LockOutlined />,
                    hint: 'Số tiền đang bị khóa theo nghiệp vụ.',
                },
                {
                    key: 'spin',
                    title: 'Tiến độ đơn hàng',
                    value: user.user_spin_progress?.current_spin || 0,
                    suffix: user.rank?.spin_count ? `/ ${user.rank.spin_count}` : '',
                    tone: 'primary',
                    icon: <CrownOutlined />,
                    hint: user.rank?.name ? `Cấp độ hiện tại: ${user.rank.name}.` : 'Chưa có cấp độ.',
                },
                {
                    key: 'orders',
                    title: 'Đơn đang đóng băng',
                    value: activeFrozenOrders.length,
                    tone: 'info',
                    icon: <ShoppingOutlined />,
                    hint: 'Đơn có giá tùy chỉnh và đang ở trạng thái đóng băng.',
                },
            ]} />

            <Row gutter={[16, 16]} align="stretch">
                <Col xs={24} xl={14}>
                    <AdminSectionCard
                        title="Hồ sơ tài khoản"
                        description="Thông tin nhận diện, cấp độ và quan hệ quản lý hiện tại."
                        extra={<span className="customer-detail-section-icon" aria-hidden="true"><IdcardOutlined /></span>}
                        className="customer-detail-card h-100"
                    >
                        <div className="customer-detail-field-grid">
                            <DetailItem label="Họ tên">{valueOrDash(user.full_name)}</DetailItem>
                            <DetailItem label="Tên đăng nhập">@{valueOrDash(user.username)}</DetailItem>
                            <DetailItem label="Email">{valueOrDash(user.email)}</DetailItem>
                            <DetailItem label="Số điện thoại">{valueOrDash(user.phone)}</DetailItem>
                            <DetailItem label="Cấp độ">{user.rank?.name || 'Chưa có cấp độ'}</DetailItem>
                            <DetailItem label="Vai trò"><Tag>{valueOrDash(user.role)}</Tag></DetailItem>
                            <DetailItem label="Người quản lý">{managerName}</DetailItem>
                            <DetailItem label="Mã giới thiệu" mono>{valueOrDash(user.referral_code)}</DetailItem>
                            <DetailItem label="Loại tài khoản">{user.clone_account ? 'Tài khoản clone' : 'Thành viên thường'}</DetailItem>
                            <DetailItem label="Lượt quay được cấp thêm"><Tag color="purple" icon={<GiftOutlined />}>{Number(user.lucky_wheel_bonus_spins || 0)}</Tag></DetailItem>
                        </div>
                    </AdminSectionCard>
                </Col>

                <Col xs={24} xl={10}>
                    <AdminSectionCard
                        title="Ngân hàng & nhận hàng"
                        description="Dữ liệu phục vụ rút tiền và vận hành giao nhận."
                        extra={<span className="customer-detail-section-icon" aria-hidden="true"><BankOutlined /></span>}
                        className="customer-detail-card h-100"
                    >
                        <div className="customer-detail-field-grid customer-detail-field-grid--single">
                            <DetailItem label="Chủ tài khoản">{valueOrDash(user.username_bank)}</DetailItem>
                            <DetailItem label="Ngân hàng">{valueOrDash(user.bank_name)}</DetailItem>
                            <DetailItem label="Số tài khoản" mono>{valueOrDash(user.account_number)}</DetailItem>
                            <DetailItem label="Khu vực kho">{valueOrDash(user.warehouse_area)}</DetailItem>
                            <DetailItem label="Địa chỉ kho" wide>{valueOrDash(user.warehouse_address)}</DetailItem>
                        </div>
                    </AdminSectionCard>
                </Col>
            </Row>

            <Row gutter={[16, 16]} align="stretch">
                <Col xs={24} xl={12}>
                    <AdminSectionCard
                        title="Vị trí người dùng"
                        description="Tách dữ liệu vị trí chính xác và vị trí tương đối để dễ đối chiếu nguồn."
                        extra={<span className="customer-detail-section-icon" aria-hidden="true"><EnvironmentOutlined /></span>}
                        className="customer-detail-card h-100"
                    >
                        <div className="customer-detail-field-grid">
                            <DetailItem label="Thành phố">{valueOrDash(user.location_city)}</DetailItem>
                            <DetailItem label="Quốc gia chính xác">{valueOrDash(exactCountry)}</DetailItem>
                            <DetailItem label="Quốc gia tương đối">{valueOrDash(approximateCountry)}</DetailItem>
                            <DetailItem label="Độ chính xác">{user.location_accuracy ? `${user.location_accuracy} m` : '—'}</DetailItem>
                            <DetailItem label="Tọa độ" wide mono>
                                {user.location_latitude !== null && user.location_latitude !== undefined
                                    ? `${user.location_latitude}, ${user.location_longitude}`
                                    : '—'}
                            </DetailItem>
                            <DetailItem label="Cập nhật vị trí" wide>{dateTime(user.location_updated_at || user.approx_location_updated_at)}</DetailItem>
                        </div>
                    </AdminSectionCard>
                </Col>

                <Col xs={24} xl={12}>
                    <AdminSectionCard
                        title="Thông tin hệ thống"
                        description="Dữ liệu phục vụ theo dõi tài khoản và đối chiếu hoạt động."
                        extra={<span className="customer-detail-section-icon" aria-hidden="true"><SafetyCertificateOutlined /></span>}
                        className="customer-detail-card h-100"
                    >
                        <div className="customer-detail-field-grid">
                            <DetailItem label="Ngày tạo">{dateTime(user.created_at)}</DetailItem>
                            <DetailItem label="Cập nhật gần nhất">{dateTime(user.updated_at)}</DetailItem>
                            <DetailItem label="Đăng nhập gần nhất">{dateTime(user.last_login_at)}</DetailItem>
                            <DetailItem label="Hoạt động gần nhất">{dateTime(user.last_seen)}</DetailItem>
                            <DetailItem label="IP đăng nhập gần nhất" mono>{valueOrDash(user.last_login_ip)}</DetailItem>
                            <DetailItem label="IP đăng ký" mono>{valueOrDash(user.register_ip)}</DetailItem>
                            <DetailItem label="Trạng thái"><Tag color={status.color}>{status.label}</Tag></DetailItem>
                            <DetailItem label="Hiện diện"><Tag color={user.is_online ? 'success' : 'default'}>{user.is_online ? 'Online' : 'Offline'}</Tag></DetailItem>
                        </div>
                    </AdminSectionCard>
                </Col>
            </Row>

            {permissions.viewFinancials && (
                <Space direction="vertical" size="large" style={{ width: '100%' }}>
                    <AdminDataCard
                        title="Biến động số dư gần nhất"
                        description="Snapshot trước và sau mỗi biến động để đối chiếu nhanh lịch sử tài chính."
                        extra={<Tag icon={<DollarOutlined />}>{walletHistory.length} bản ghi</Tag>}
                        className="customer-detail-table-card"
                    >
                        <Table
                            rowKey="id"
                            size="small"
                            pagination={false}
                            dataSource={walletHistory}
                            columns={walletColumns}
                            scroll={{ x: 820 }}
                            locale={{ emptyText: 'Chưa có lịch sử biến động số dư' }}
                        />
                    </AdminDataCard>

                    <AdminDataCard
                        title="Giao dịch gần nhất"
                        description="Các giao dịch mới nhất liên quan tới tài khoản này."
                        extra={<Tag>{transactionHistory.length} bản ghi</Tag>}
                        className="customer-detail-table-card"
                    >
                        <Table
                            rowKey="id"
                            size="small"
                            pagination={false}
                            dataSource={transactionHistory}
                            columns={transactionColumns}
                            scroll={{ x: 720 }}
                            locale={{ emptyText: 'Chưa có giao dịch' }}
                        />
                    </AdminDataCard>
                </Space>
            )}
        </AdminPage>
    );
}
