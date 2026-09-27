import {
    Button,
    Card,
    Col,
    Pagination,
    Popconfirm,
    Row,
    Segmented,
    Space,
    Statistic,
    Switch,
    Table,
    Tag,
    Typography,
} from 'antd';
import {
    CheckCircleOutlined,
    ClockCircleOutlined,
    CloseCircleOutlined,
    DollarOutlined,
    GiftOutlined,
} from '@ant-design/icons';
import LaravelForm from '../../../components/LaravelForm';
import { spaNavigate } from '../../../navigation';

const { Text, Title } = Typography;

const replaceId = (template, token, id) => String(template || '').replace(token, encodeURIComponent(String(id)));
const money = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value || 0));
const integer = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0));
const dateTime = (value) => value
    ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value))
    : '—';

const statusMeta = {
    pending: { label: 'Chờ duyệt', color: 'warning', icon: <ClockCircleOutlined /> },
    approved: { label: 'Đã duyệt', color: 'success', icon: <CheckCircleOutlined /> },
    rejected: { label: 'Đã từ chối', color: 'error', icon: <CloseCircleOutlined /> },
};

export default function LuckyWheelRewardsPage({ config }) {
    const page = config.rewards || {};
    const rows = page.data || [];
    const counts = config.counts || {};
    const permissions = config.permissions || {};
    const selectedStatus = config.selectedStatus || '';
    const autoApprovalEnabled = Boolean(config.setting?.auto_approve_rewards);

    const goToStatus = (status) => {
        const url = new URL(config.routes.index, window.location.origin);
        if (status) url.searchParams.set('status', status);
        spaNavigate(url.toString());
    };

    const changePage = (nextPage) => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', nextPage);
        spaNavigate(url.toString());
    };

    const columns = [
        {
            title: 'Người dùng',
            render: (_, reward) => {
                const user = reward.user;
                const displayName = user?.full_name || user?.username || `Người dùng #${reward.user_id}`;
                const content = (
                    <Space direction="vertical" size={0}>
                        <Text strong>{displayName}</Text>
                        <Text type="secondary">#{reward.user_id}</Text>
                    </Space>
                );

                return user && permissions.viewCustomerDetail
                    ? <a href={replaceId(config.routes.customerShow, '__USER_ID__', reward.user_id)}>{content}</a>
                    : content;
            },
        },
        {
            title: 'Phần thưởng',
            render: (_, reward) => (
                <Space direction="vertical" size={0}>
                    <Text strong style={{ color: '#16a34a' }}>+{money(reward.reward_amount)}</Text>
                    <Text type="secondary">{reward.prize || 'Tiền thưởng'}</Text>
                </Space>
            ),
        },
        {
            title: 'Nguồn',
            render: (_, reward) => reward.spin_type === 'admin_bonus'
                ? <Tag color="blue">Lượt admin cấp</Tag>
                : <Tag>Hoàn thành hằng ngày</Tag>,
        },
        {
            title: 'Trạng thái',
            render: (_, reward) => {
                const meta = statusMeta[reward.reward_status] || { label: reward.reward_status, color: 'default' };
                return <Tag color={meta.color} icon={meta.icon}>{meta.label}</Tag>;
            },
        },
        {
            title: 'Thời gian',
            render: (_, reward) => dateTime(reward.created_at),
        },
        {
            title: 'Xử lý bởi',
            render: (_, reward) => {
                if (!['approved', 'rejected'].includes(reward.reward_status)) return <Text type="secondary">—</Text>;
                const handler = reward.approval_method === 'automatic'
                    ? 'Hệ thống'
                    : (reward.handled_by?.full_name || reward.handled_by?.username || 'Quản trị viên');
                const method = reward.reward_status === 'rejected'
                    ? 'Từ chối thủ công'
                    : reward.approval_method === 'automatic' ? 'Tự động duyệt' : 'Duyệt thủ công';
                return <Space direction="vertical" size={0}><Text strong>{handler}</Text><Text type="secondary">{method}</Text></Space>;
            },
        },
        {
            title: 'Thao tác',
            width: 260,
            render: (_, reward) => {
                if (reward.reward_status !== 'pending' || (!permissions.approve && !permissions.reject)) {
                    return <Text type="secondary">Hoàn tất</Text>;
                }

                return (
                    <Space wrap>
                        {permissions.approve && (
                            <>
                                <LaravelForm id={`approve-reward-${reward.id}`} action={replaceId(config.routes.approve, '__SPIN_ID__', reward.id)} method="POST" style={{ display: 'none' }} />
                                <Popconfirm
                                    title="Duyệt phần thưởng?"
                                    description={`Số tiền ${money(reward.reward_amount)} sẽ được cộng vào tài khoản người dùng.`}
                                    okText="Duyệt"
                                    cancelText="Hủy"
                                    onConfirm={() => document.getElementById(`approve-reward-${reward.id}`)?.requestSubmit()}
                                >
                                    <Button type="primary">Duyệt {money(reward.reward_amount)}</Button>
                                </Popconfirm>
                            </>
                        )}
                        {permissions.reject && (
                            <>
                                <LaravelForm id={`reject-reward-${reward.id}`} action={replaceId(config.routes.reject, '__SPIN_ID__', reward.id)} method="POST" style={{ display: 'none' }} />
                                <Popconfirm
                                    title="Từ chối phần thưởng?"
                                    okText="Từ chối"
                                    cancelText="Hủy"
                                    okButtonProps={{ danger: true }}
                                    onConfirm={() => document.getElementById(`reject-reward-${reward.id}`)?.requestSubmit()}
                                >
                                    <Button danger>Từ chối</Button>
                                </Popconfirm>
                            </>
                        )}
                    </Space>
                );
            },
        },
    ];

    return (
        <div className="container-fluid px-4 pb-5">
            <div className="d-flex justify-content-between align-items-start gap-3 mb-4 flex-wrap">
                <div>
                    <Title level={2} style={{ marginBottom: 4 }}>Quản lý phần thưởng vòng quay</Title>
                    <Text type="secondary">Duyệt thưởng tiền mặt và cộng vào tài khoản theo luồng thưởng hiện có.</Text>
                </div>

                {permissions.configureAutoApproval && (
                    <Card size="small" style={{ minWidth: 300 }}>
                        <Space align="center">
                            <div>
                                <Text strong>Tự động duyệt</Text>
                                <div><Text type="secondary">Chỉ áp dụng cho lượt quay mới.</Text></div>
                            </div>
                            <LaravelForm id="auto-approval-form" action={config.routes.autoApproval} method="POST" style={{ display: 'none' }}>
                                <input type="hidden" name="enabled" value={autoApprovalEnabled ? '0' : '1'} />
                            </LaravelForm>
                            <Switch
                                checked={autoApprovalEnabled}
                                onChange={() => document.getElementById('auto-approval-form')?.requestSubmit()}
                            />
                        </Space>
                    </Card>
                )}
            </div>

            <Row gutter={[16, 16]} className="mb-4">
                <Col xs={24} sm={12} lg={8} xl={4}><Card><Statistic title="Tổng phần thưởng" value={Number(counts.total || 0)} prefix={<GiftOutlined />} formatter={integer} /></Card></Col>
                <Col xs={24} sm={12} lg={8} xl={5}><Card><Statistic title="Chờ duyệt" value={Number(counts.pending || 0)} prefix={<ClockCircleOutlined />} formatter={integer} /></Card></Col>
                <Col xs={24} sm={12} lg={8} xl={5}><Card><Statistic title="Đã duyệt" value={Number(counts.approved || 0)} prefix={<CheckCircleOutlined />} formatter={integer} /></Card></Col>
                <Col xs={24} sm={12} lg={8} xl={5}><Card><Statistic title="Đã từ chối" value={Number(counts.rejected || 0)} prefix={<CloseCircleOutlined />} formatter={integer} /></Card></Col>
                <Col xs={24} sm={12} lg={8} xl={5}><Card><Statistic title="Đã trả thưởng" value={Number(counts.paid_amount || 0)} precision={2} prefix={<DollarOutlined />} /></Card></Col>
            </Row>

            <Card
                title="Danh sách phần thưởng"
                extra={(
                    <Segmented
                        value={selectedStatus}
                        onChange={goToStatus}
                        options={[
                            { label: 'Tất cả', value: '' },
                            { label: `Chờ duyệt (${integer(counts.pending)})`, value: 'pending' },
                            { label: 'Đã duyệt', value: 'approved' },
                            { label: 'Đã từ chối', value: 'rejected' },
                        ]}
                    />
                )}
            >
                <Table
                    rowKey="id"
                    dataSource={rows}
                    columns={columns}
                    pagination={false}
                    scroll={{ x: 1200 }}
                    locale={{ emptyText: 'Chưa có phần thưởng phù hợp.' }}
                />

                {Number(page.last_page || 1) > 1 && (
                    <div className="d-flex justify-content-end mt-3">
                        <Pagination
                            current={Number(page.current_page || 1)}
                            total={Number(page.total || 0)}
                            pageSize={Number(page.per_page || 20)}
                            showSizeChanger={false}
                            onChange={changePage}
                        />
                    </div>
                )}
            </Card>
        </div>
    );
}
