import { Button, Collapse, Col, Form, Input, Pagination, Row, Select, Space, Table, Tag, Typography } from 'antd';
import { CheckCircleOutlined, ClockCircleOutlined, EyeOutlined, FilterOutlined, SearchOutlined, ShoppingOutlined, SyncOutlined } from '@ant-design/icons';
import { spaNavigate } from '../../../navigation';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';
import { OperationsIdentity, OperationsToolbar } from '../../../components/admin/OperationsUi';
import { AdminDatePicker } from '../../../components/admin/AdminDatePicker';
import { formatAdminDateTime } from '../../../../shared/datetime';

const { Text } = Typography;
const routeFor = (template, id) => String(template || '').replace('__FROZEN_ID__', encodeURIComponent(String(id)));
const formatDate = (value) => formatAdminDateTime(value);
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
            width: 320,
            render: (_, item) => <div className="operations-product">{item.snapshot_image_url ? <img src={item.snapshot_image_url} alt="" width={48} height={48} loading="lazy" /> : <span className="operations-product__placeholder" aria-hidden="true"><ShoppingOutlined /></span>}<div><Text strong>{item.snapshot_order_code || `Đơn #${item.id}`}</Text><div className="operations-product__name">{item.snapshot_name || 'Tên sản phẩm chưa ghi nhận'}</div><div className="operations-cell-note">#{item.id} · {item.custom_price !== null && item.custom_price !== undefined ? 'Đơn giá trị cao' : 'Đơn thường'}</div></div></div>,
        },
        { title: 'Người nhận', width: 250, render: (_, item) => <OperationsIdentity avatarUrl={item.user?.avatar_url} name={item.user?.full_name || item.user?.username || 'Tài khoản không còn tồn tại'} secondary={`User #${item.user_id}`} /> },
        { title: 'Người phân phối', width: 220, render: (_, item) => <div><Text strong>{item.assigned_by?.full_name || item.assigned_by?.username || (item.assignment_source === 'spin' ? 'Hệ thống tự phân phối' : item.assignment_source === 'admin' ? 'Không còn thông tin' : 'Chưa ghi nhận')}</Text><div className="operations-cell-note"><Tag color={item.assignment_source==='admin'?'purple':item.assignment_source==='spin'?'blue':'default'}>{item.assignment_source === 'admin' ? 'Giao thủ công' : item.assignment_source === 'spin' ? 'Tự nhận' : 'Không rõ nguồn'}</Tag></div></div> },
        { title: 'Trạng thái', render: (_, item) => <Tag color={item.status === 'completed' ? 'success' : item.status === 'cancelled' ? 'error' : item.status ? 'processing' : 'default'}>{statusLabel(statuses, item.status)}</Tag> },
        { title: 'Thời gian phân phối', width: 235, render: (_,item)=><div>{formatDate(item.created_at)}<div className="operations-cell-note">Cập nhật: {formatDate(item.updated_at)}</div></div> },
        { title: 'Thao tác', width: 160, align: 'right', render: (_, item) => config.permissions?.viewDetail ? <Button size="small" icon={<EyeOutlined />} onClick={() => openDetail(item.id)}>Xem chi tiết</Button> : <Text type="secondary">—</Text> },
    ];

    const statusOptions = [{ value: '', label: 'Tất cả' }, ...statuses.map((item) => ({ value: item.name, label: item.display_name })), { value: 'unknown', label: 'Chưa ghi nhận' }];
    const assignerOptions = [{ value: '', label: 'Tất cả người phân phối' }, ...(config.assigners || []).map((user) => ({ value: String(user.id), label: user.full_name || user.username }))];

    return <AdminPage className="admin-operations-page">
        <AdminPageHeader eyebrow="Vận hành · Đơn hàng" icon={<ShoppingOutlined />} title="Phân phối đơn hàng" description="Theo dõi người nhận, nguồn phân phối và tiến độ xử lý của từng đơn hàng." />

        <AdminMetricGrid items={[
            {key:'total',title:'Tổng đơn phân phối',value:Number(stats.total||0),tone:'primary',icon:<ShoppingOutlined />},
            {key:'pending',title:'Chờ xử lý',value:Number(stats.pending||0),tone:'warning',icon:<ClockCircleOutlined />},
            {key:'processing',title:'Đang xử lý',value:Number(stats.processing||0),tone:'info',icon:<SyncOutlined />},
            {key:'completed',title:'Hoàn thành',value:Number(stats.completed||0),tone:'success',icon:<CheckCircleOutlined />},
        ]} />

        <AdminSectionCard className="operations-filter-card" title={<><FilterOutlined aria-hidden="true" /> Bộ lọc đơn hàng</>} description="Tìm kiếm theo đơn, người nhận hoặc nguồn phân phối." extra={<Tag>Giờ Việt Nam · UTC+7</Tag>}>
            <Form layout="vertical" onFinish={applyFilters} initialValues={{ ...filters, assigned_by: filters.assigned_by ? String(filters.assigned_by) : '', status: filters.status || '', source: filters.source || '', sort: filters.sort || 'created_at', direction: filters.direction || 'desc' }}>
                <Row gutter={12}>
                    <Col xs={24} lg={9}><Form.Item label="Tìm kiếm" name="q"><Input allowClear name="q" prefix={<SearchOutlined aria-hidden="true" />} placeholder="Mã đơn, tên sản phẩm, người nhận..." /></Form.Item></Col>
                    <Col xs={12} lg={5}><Form.Item label="Trạng thái" name="status"><Select name="status" placeholder="Tất cả trạng thái" options={statusOptions} /></Form.Item></Col>
                    <Col xs={12} lg={5}><Form.Item label="Nguồn" name="source"><Select name="source" placeholder="Tất cả nguồn" options={[{value:'',label:'Tất cả'},{value:'admin',label:'Admin giao'},{value:'spin',label:'Người dùng tự nhận'},{value:'unknown',label:'Chưa ghi nhận'}]} /></Form.Item></Col>
                    <Col xs={24} lg={5}><Form.Item label="Người phân phối" name="assigned_by"><Select name="assigned_by" showSearch optionFilterProp="label" placeholder="Tất cả người phân phối" options={assignerOptions} /></Form.Item></Col>
                </Row>
                <Collapse className="operations-advanced-filters" ghost defaultActiveKey={['user_id','order_id','from','to'].some((key)=>filters[key]) ? ['advanced'] : []} items={[{key:'advanced',label:'Bộ lọc nâng cao · ID, ngày phân phối, sắp xếp',forceRender:true,children:<Row gutter={12}>
                    <Col xs={12} md={4}><Form.Item label="User ID" name="user_id"><Input name="user_id" inputMode="numeric" placeholder="Nhập User ID" /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Order ID" name="order_id"><Input name="order_id" inputMode="numeric" placeholder="Nhập Order ID" /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Từ ngày" name="from"><AdminDatePicker /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Đến ngày" name="to"><AdminDatePicker /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Sắp xếp" name="sort"><Select name="sort" options={[{value:'created_at',label:'Ngày tạo'},{value:'updated_at',label:'Cập nhật'},{value:'id',label:'ID'},{value:'status',label:'Trạng thái'}]} /></Form.Item></Col>
                    <Col xs={12} md={4}><Form.Item label="Chiều" name="direction"><Select name="direction" options={[{value:'desc',label:'Giảm dần'},{value:'asc',label:'Tăng dần'}]} /></Form.Item></Col>
                </Row>}]} />
                <div className="operations-filter-actions"><Space wrap><Button type="primary" htmlType="submit" icon={<FilterOutlined />}>Áp dụng bộ lọc</Button><Button href={config.routes.index}>Xóa bộ lọc</Button></Space></div>
            </Form>
        </AdminSectionCard>

        <AdminDataCard
            title="Danh sách đơn phân phối"
            description="Xem chi tiết để đối chiếu thông tin đơn, lịch sử trạng thái và quyết toán."
            extra={<Tag>{Number(page.total||0)} đơn phù hợp</Tag>}
            toolbar={<OperationsToolbar value={String(filters.status || '')} onChange={changeQuickStatus} options={statusOptions.map((item)=>({value:item.value,label:item.value===''?`Tất cả (${stats.total||0})`:item.value==='pending'?`${item.label} (${stats.pending||0})`:item.value==='completed'?`${item.label} (${stats.completed||0})`:item.label}))} />}
        >
            <div className="operations-summary"><span>{rows.length ? `Hiển thị ${page.from || (Number(page.current_page||1)-1)*Number(page.per_page||25)+1}–${page.to || (Number(page.current_page||1)-1)*Number(page.per_page||25)+rows.length} / ${Number(page.total||0)} đơn` : 'Không có đơn phù hợp'}</span><span>Trang {Number(page.current_page||1)} / {Number(page.last_page||1)}</span></div>
            <Table rowKey="id" dataSource={rows} columns={columns} pagination={false} scroll={{x:1380}} locale={{emptyText:'Không có đơn hàng phù hợp với bộ lọc'}} />
            {Number(page.last_page || 1) > 1 && <div className="operations-pagination"><Pagination current={Number(page.current_page || 1)} total={Number(page.total || 0)} pageSize={Number(page.per_page || 25)} showSizeChanger={false} onChange={changePage}/></div>}
        </AdminDataCard>
    </AdminPage>;
}
