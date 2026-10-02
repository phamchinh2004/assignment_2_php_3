import { formatAdminDateTime } from '../../../../shared/datetime';
import { Button, Pagination, Segmented, Space, Table, Tag, Typography } from 'antd';
import { AdminDataCard, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';
import { spaNavigate } from '../../../navigation';

const { Text } = Typography;
const routeFor = (template, id) => String(template || '').replace('__REPORT_ID__', encodeURIComponent(String(id)));
const formatDate = (value) => value
    ? formatAdminDateTime(value, { dateStyle: 'short', timeStyle: 'short' })
    : '—';

const statusMeta = {
    pending: { label: 'Chờ xử lý', color: 'warning' },
    resolved: { label: 'Đã xử lý', color: 'success' },
};

const roleLabel = {
    admin: 'Admin',
    staff: 'Nhân viên',
};

export default function BugReportListPage({ config }) {
    const page = config.reports || {};
    const rows = page.data || [];

    const changeStatus = (status) => {
        const url = new URL(config.routes.index, window.location.origin);
        url.searchParams.set('status', status);
        spaNavigate(url.toString());
    };

    const changePage = (nextPage) => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', nextPage);
        spaNavigate(url.toString());
    };

    const columns = [
        { title: 'ID', dataIndex: 'id', width: 80, render: (id) => `#${id}` },
        {
            title: 'Người báo lỗi',
            render: (_, row) => (
                <div>
                    <Text strong>{row.reporter?.full_name || row.reporter?.username || `#${row.reported_by || 'N/A'}`}</Text>
                    <div><Text type="secondary">{roleLabel[row.reporter?.role] || row.reporter?.role || '—'}</Text></div>
                </div>
            ),
        },
        { title: 'Tiêu đề', dataIndex: 'title', ellipsis: true, render: (value) => <Text strong>{value}</Text> },
        {
            title: 'Trang xảy ra lỗi',
            dataIndex: 'page_url',
            ellipsis: true,
            render: (value) => value ? <a href={value} target="_blank" rel="noreferrer">Mở trang</a> : '—',
        },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            width: 120,
            render: (status) => {
                const meta = statusMeta[status] || { label: status, color: 'default' };
                return <Tag color={meta.color}>{meta.label}</Tag>;
            },
        },
        { title: 'Thời gian', dataIndex: 'created_at', width: 160, render: formatDate },
        { title: 'Thao tác', width: 100, align: 'right', render: (_, row) => <Button href={routeFor(config.routes.show, row.id)}>Xem</Button> },
    ];

    return (
        <AdminPage>
            <AdminPageHeader
                eyebrow="Vận hành"
                title="Báo lỗi hệ thống"
                description="Tiếp nhận lỗi do Admin và Nhân viên gửi từ thanh header."
                actions={(
                    <Segmented
                        value={config.status || 'pending'}
                        onChange={changeStatus}
                        options={[
                            { label: 'Chờ xử lý', value: 'pending' },
                            { label: 'Đã xử lý', value: 'resolved' },
                            { label: 'Tất cả', value: 'all' },
                        ]}
                    />
                )}
            />

            <AdminDataCard
                title="Danh sách báo lỗi"
                description="Báo lỗi mới được ưu tiên hiển thị trước để chủ hệ thống tiếp nhận."
            >
                <Table
                    rowKey="id"
                    dataSource={rows}
                    columns={columns}
                    pagination={false}
                    scroll={{ x: 980 }}
                    locale={{ emptyText: 'Chưa có báo lỗi phù hợp' }}
                />
                {Number(page.last_page || 1) > 1 && (
                    <Space style={{ width: '100%', justifyContent: 'flex-end', marginTop: 16 }}>
                        <Pagination
                            current={Number(page.current_page || 1)}
                            total={Number(page.total || 0)}
                            pageSize={Number(page.per_page || 20)}
                            showSizeChanger={false}
                            onChange={changePage}
                        />
                    </Space>
                )}
            </AdminDataCard>
        </AdminPage>
    );
}
