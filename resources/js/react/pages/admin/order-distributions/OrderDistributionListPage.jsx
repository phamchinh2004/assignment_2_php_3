import { Button, Card, Col, Form, Input, Pagination, Row, Select, Segmented, Space, Statistic, Table, Tag, Typography } from 'antd';
import { SearchOutlined } from '@ant-design/icons';
import { spaNavigate } from '../../../navigation';
import { AdminDataCard, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';
import { AdminDatePicker } from '../../../components/admin/AdminDatePicker';

const { Text, Title } = Typography;
const routeFor = (template, id) => String(template || '').replace('__FROZEN_ID__', encodeURIComponent(String(id)));
const formatDate = (value) => value ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : '—';
const statusLabel = (statuses, value) => statuses.find((item) => item.name === value)?.display_name || (value === 'unknown' || !value ? 'Chưa ghi nhận' : value);

export default function OrderDistributionListPage({ config }) {
    const page = config.frozenOrders || {};
    const rows = page.data || [];
    const filters = config.filters || {};
    const statuses = config.statuses || [];
    const stats = config.stats || {};

    const changeQuickStatus = (status) => {
        const url = new URL(window.location.href);
        if (status) url.searchParams.set('status', status); else url.searchParams.delete('status');
        url.searchParams.delete('page');
        spaNavigate(url.toString());
    };

    const changePage = (nextPage) => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', nextPage);
        spaNavigate(url.toString());
    };

    const openDetail = (id) => {
        const url = new URL(routeFor(config.routes.show, id), window.location.origin);
        const current = new URL(window.location.href);
        current.searchParams.forEach((value, key) => url.searchParams.set(key, value));
        spaNavigate(url.toString());
    };

    const applyFilters = (values) => {
        const url = new URL(config.routes.index, window.location.origin);
        Object.entries(values || {}).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                url.searchParams.set(key, String(value));
            }
        });
        spaNavigate(url.toString());
    };

    const columns = [
        {
            title: 'Đơn hàng',
            render: (_, item) => <Space>{item.snapshot_image_url && <img src={item.snapshot_image_url} alt="" style={{ width: 48, height: 48, objectFit: 'cover', borderRadius: 8 }} />}<div><Text strong>{item.snapshot_order_code || `Đơn #${item.id}`}</Text><div><Text type="secondary">{item.snapshot_name || 'Tên sản phẩm chưa ghi nhận'}</Text></div><Text type="secondary">#{item.id} · {item.custom_price !== null ? 'Đơn giá trị cao' : 'Đơn thường'}</Text></div></Space>,
        },
        { title: 'Người nhận', render: (_, item) => <div><Text strong>{item.user?.full_name || item.user?.username || 'Tài khoản không còn tồn tại'}</Text><div><Text type="secondary">User #{item.user_id}</Text></div></div> },
        { title: 'Người phân phối', render: (_, item) => <div><Text>{item.assigned_by?.full_name || item.assigned_by?.username || (item.assignment_source === 'spin' ? 'Hệ thống tự phân phối' : item.assignment_source === 'admin' ? 'Không còn thông tin' : 'Chưa ghi nhận')}</Text><div><Tag>{item.assignment_source === 'admin' ? 'Giao thủ công' : item.assignment_source === 'spin' ? 'Tự nhận' : 'Không rõ nguồn'}</Tag></div></div> },
        { title: 'Trạng thái', render: (_, item) => <Tag color={item.status === 'completed' ? 'success' : item.status === 'cancelled' ? 'error' : item.status ? 'processing' : 'default'}>{statusLabel(statuses, item.status)}</Tag> },
        { title: 'Phân phối lúc', dataIndex: 'created_at', render: formatDate },
        { title: 'Cập nhật', dataIndex: 'updated_at', render: formatDate },
        { title: 'Thao tác', align: 'right', render: (_, item) => config.permissions?.viewDetail ? <Button onClick={() => openDetail(item.id)}>Audit</Button> : <Text type="secondary">—</Text> },
    ];

    const statusOptions = [{ value: '', label: 'Tất cả' }, ...statuses.map((item) => ({ value: item.name, label: item.display_name })), { value: 'unknown', label: 'Chưa ghi nhận' }];
    const assignerOptions = [{ value: '', label: 'Tất cả người phân phối' }, ...(config.assigners || []).map((user) => ({ value: String(user.id), label: user.full_name || user.username }))];

    return <AdminPage>
        <AdminPageHeader eyebrow="Audit vận hành" title="Phân phối đơn hàng" description="Audit đơn đã phân phối, người nhận, nguồn phân phối và tiến độ xử lý." />

        <Row gutter={[16,16]} className="mb-4">
            <Col xs={12} lg={6}><Card><Statistic title="Tổng" value={Number(stats.total || 0)} /></Card></Col>
            <Col xs={12} lg={6}><Card><Statistic title="Chờ xử lý" value={Number(stats.pending || 0)} /></Card></Col>
            <Col xs={12} lg={6}><Card><Statistic title="Đang xử lý" value={Number(stats.processing || 0)} /></Card></Col>
            <Col xs={12} lg={6}><Card><Statistic title="Hoàn thành" value={Number(stats.completed || 0)} /></Card></Col>
        </Row>

        <Card className="mb-4">
            <Form layout="vertical" onFinish={applyFilters} initialValues={{ ...filters, assigned_by: filters.assigned_by ? String(filters.assigned_by) : '', status: filters.status || '', source: filters.source || '', sort: filters.sort || 'created_at', direction: filters.direction || 'desc' }}>
                <Row gutter={12}>
                    <Col xs={24} md={8}><Form.Item label="Tìm kiếm" name="q"><Input name="q" prefix={<SearchOutlined/>} placeholder="Mã đơn, tên sản phẩm, người nhận..." /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Trạng thái" name="status"><Select name="status" placeholder="Tất cả trạng thái" options={statusOptions} /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Nguồn" name="source"><Select name="source" placeholder="Tất cả nguồn" options={[{value:'',label:'Tất cả'},{value:'admin',label:'Admin giao'},{value:'spin',label:'Người dùng tự nhận'},{value:'unknown',label:'Chưa ghi nhận'}]} /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Người phân phối" name="assigned_by"><Select name="assigned_by" showSearch optionFilterProp="label" placeholder="Tất cả người phân phối" options={assignerOptions} /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="User ID" name="user_id"><Input name="user_id" inputMode="numeric" placeholder="Nhập User ID" /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Order ID" name="order_id"><Input name="order_id" inputMode="numeric" placeholder="Nhập Order ID" /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Từ ngày" name="from"><AdminDatePicker /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Đến ngày" name="to"><AdminDatePicker /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Sắp xếp" name="sort"><Select name="sort" options={[{value:'created_at',label:'Ngày tạo'},{value:'updated_at',label:'Cập nhật'},{value:'id',label:'ID'},{value:'status',label:'Trạng thái'}]} /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Chiều" name="direction"><Select name="direction" options={[{value:'desc',label:'Giảm dần'},{value:'asc',label:'Tăng dần'}]} /></Form.Item></Col>
                </Row>
                <Space><Button type="primary" htmlType="submit">Lọc</Button><Button href={config.routes.index}>Xóa lọc</Button></Space>
            </Form>
        </Card>

        <AdminDataCard
            title="Danh sách đơn phân phối"
            description="Mở Audit để kiểm tra snapshot, lịch sử trạng thái và quyết toán."
            extra={<Segmented value={String(filters.status || '')} onChange={changeQuickStatus} options={[{label:`Tất cả (${stats.total || 0})`,value:''},{label:`Chờ xử lý (${stats.pending || 0})`,value:'pending'},{label:`Hoàn thành (${stats.completed || 0})`,value:'completed'}]} />}
        >
            <Table rowKey="id" dataSource={rows} columns={columns} pagination={false} scroll={{x:1150}} />
            {Number(page.last_page || 1) > 1 && <div className="d-flex justify-content-end mt-3"><Pagination current={Number(page.current_page || 1)} total={Number(page.total || 0)} pageSize={Number(page.per_page || 25)} showSizeChanger={false} onChange={changePage}/></div>}
        </AdminDataCard>
    </AdminPage>;
}
