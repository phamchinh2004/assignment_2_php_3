import { Button, Pagination, Segmented, Space, Table, Tag, Typography } from 'antd';
import { spaNavigate } from '../../../navigation';
import { AdminDataCard, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

const { Text } = Typography;
const routeFor = (template, id) => String(template || '').replace('__REPORT_ID__', encodeURIComponent(String(id)));
const formatDate = (value) => value ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : '—';

const reportStatus = {
    pending: { label: 'Chờ xử lý', color: 'warning' },
    approved: { label: 'Đã hủy (đơn ảo)', color: 'success' },
    rejected: { label: 'Đã xác nhận (đơn thật)', color: 'default' },
};

export default function OrderReportListPage({ config }) {
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
        { title: 'Mã đơn', dataIndex: 'order_code', render: (value) => <Text strong>{value || 'N/A'}</Text> },
        { title: 'Người đặt', render: (_, row) => row.frozen_order?.user?.full_name || row.frozen_order?.user?.username || `#${row.frozen_order?.user?.id || 'N/A'}` },
        { title: 'Người báo cáo', render: (_, row) => row.reporter?.full_name || row.reporter?.username || `#${row.reported_by}` },
        { title: 'Lý do', dataIndex: 'reason', ellipsis: true, render: (value) => value || '—' },
        {
            title: 'Trạng thái',
            dataIndex: 'status',
            render: (status) => {
                const meta = reportStatus[status] || { label: status, color: 'default' };
                return <Tag color={meta.color}>{meta.label}</Tag>;
            },
        },
        { title: 'Thời gian', dataIndex: 'created_at', render: formatDate },
        { title: 'Thao tác', align: 'right', render: (_, row) => config.permissions?.viewDetail ? <Button href={routeFor(config.routes.show, row.id)}>Xem</Button> : <Text type="secondary">—</Text> },
    ];

    return (
        <AdminPage>
            <AdminPageHeader
                eyebrow="Kiểm soát đơn hàng"
                title="Đơn hàng bị báo cáo"
                description="Theo dõi và xử lý các báo cáo đơn hàng."
                actions={(<Segmented
                    value={config.status || 'pending'}
                    onChange={changeStatus}
                    options={[
                        { label: 'Chờ xử lý', value: 'pending' },
                        { label: 'Đã hủy (đơn ảo)', value: 'approved' },
                        { label: 'Đã xác nhận (đơn thật)', value: 'rejected' },
                    ]}
                />)}
            />
            <AdminDataCard title="Danh sách báo cáo" description="Ưu tiên các báo cáo chờ xử lý và giữ lịch sử quyết định để audit.">
                <Table rowKey="id" dataSource={rows} columns={columns} pagination={false} scroll={{ x: 1050 }} />
                {Number(page.last_page || 1) > 1 && <Space style={{ width: '100%', justifyContent: 'flex-end', marginTop: 16 }}><Pagination current={Number(page.current_page || 1)} total={Number(page.total || 0)} pageSize={Number(page.per_page || 20)} showSizeChanger={false} onChange={changePage} /></Space>}
            </AdminDataCard>
        </AdminPage>
    );
}
