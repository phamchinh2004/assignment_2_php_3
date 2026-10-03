import { formatAdminDateTime } from '../../../../shared/datetime';
import {
    Button,
    Card,
    Pagination,
    Popconfirm,
    Segmented,
    Space,
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
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';
import { OperationsIdentity } from '../../../components/admin/OperationsUi';

const { Text } = Typography;

const replaceId = (template, token, id) => String(template || '').replace(token, encodeURIComponent(String(id)));
const money = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(Number(value || 0));
const integer = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0));
const dateTime = (value) => value
    ? formatAdminDateTime(value, { dateStyle: 'short', timeStyle: 'short' })
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
                return <OperationsIdentity
                    name={displayName}
                    secondary={`#${reward.user_id}`}
                    avatarUrl={user?.avatar_url}
                    href={user && permissions.viewCustomerDetail
                        ? replaceId(config.routes.customerShow, '__USER_ID__', reward.user_id)
                        : undefined}
                />;
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
        <AdminPage>
            <AdminPageHeader
                eyebrow="Giao dịch khách hàng"
                icon={<GiftOutlined />}
                title="Quản lý phần thưởng vòng quay"
                description="Duyệt thưởng tiền mặt và cộng vào tài khoản theo luồng thưởng hiện có."
                actions={permissions.configureAutoApproval ? (
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
                ) : null}
            />

            <AdminMetricGrid min={4} className="lucky-wheel-reward-metrics" items={[
                { key: 'total', title: 'Tổng phần thưởng', value: Number(counts.total || 0), tone: 'primary', icon: <GiftOutlined />, formatter: integer },
                { key: 'pending', title: 'Chờ duyệt', value: Number(counts.pending || 0), tone: 'warning', icon: <ClockCircleOutlined />, formatter: integer },
                { key: 'approved', title: 'Đã duyệt', value: Number(counts.approved || 0), tone: 'success', icon: <CheckCircleOutlined />, formatter: integer },
                { key: 'rejected', title: 'Đã từ chối', value: Number(counts.rejected || 0), tone: 'danger', icon: <CloseCircleOutlined />, formatter: integer },
                { key: 'paid', title: 'Đã trả thưởng', value: Number(counts.paid_amount || 0), tone: 'info', icon: <DollarOutlined />, precision: 2, prefix: '$' },
            ]} />

            <AdminDataCard
                title="Danh sách phần thưởng"
                description="Lọc theo trạng thái và xử lý từng phần thưởng đang chờ duyệt."
                toolbar={(
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
            </AdminDataCard>
        </AdminPage>
    );
}
