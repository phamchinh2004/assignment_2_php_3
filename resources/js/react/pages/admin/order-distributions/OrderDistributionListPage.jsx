import { Button, Collapse, Form, Grid, Input, Pagination, Select, Table, Tooltip } from 'antd';
import {
    ArrowRightOutlined, CheckCircleOutlined, ClockCircleOutlined, CloseCircleOutlined,
    EyeOutlined, FilterOutlined, SearchOutlined, ShoppingOutlined, SyncOutlined,
} from '@ant-design/icons';
import { spaNavigate } from '../../../navigation';
import { AdminEmptyState, AdminPage } from '../../../components/admin/AdminUi';
import { OperationsIdentity } from '../../../components/admin/OperationsUi';
import { AdminDatePicker } from '../../../components/admin/AdminDatePicker';
import { formatAdminDateTime } from '../../../../shared/datetime';
import '../../../../../css/admin/order-distributions.css';

const routeFor = (template, id) => String(template || '').replace('__FROZEN_ID__', encodeURIComponent(String(id)));
const number = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0));
const defaultStatusLabels = { pending: 'Chờ xử lý', confirmed: 'Đã xác nhận', preparing: 'Đang chuẩn bị', transit: 'Đang vận chuyển', shipping: 'Đang giao hàng', delivered: 'Đã giao hàng', completed: 'Hoàn thành', cancelled: 'Đã hủy' };
const statusLabel = (statuses, value) => statuses.find((item) => item.name === value)?.display_name
    || (value === 'unknown' || !value ? 'Chưa ghi nhận' : defaultStatusLabels[value] || value);

function DistributionStatus({ item, statuses }) {
    const tone = item.status === 'completed' ? 'success' : item.status === 'cancelled' ? 'danger'
        : item.status === 'pending' ? 'warning' : item.status ? 'info' : 'neutral';
    const Icon = tone === 'success' ? CheckCircleOutlined : tone === 'danger' ? CloseCircleOutlined
        : tone === 'info' ? SyncOutlined : ClockCircleOutlined;
    return <span className={`distribution-status distribution-status--${tone}`}>
        <Icon aria-hidden="true" />{statusLabel(statuses, item.status)}
    </span>;
}

function DistributionProduct({ item }) {
    const highValue = item.custom_price !== null && item.custom_price !== undefined;
    return <div className="distribution-product">
        {item.snapshot_image_url
            ? <img src={item.snapshot_image_url} alt="" width={36} height={36} loading="lazy" />
            : <span className="distribution-product__placeholder" aria-hidden="true"><ShoppingOutlined /></span>}
        <div className="distribution-product__copy">
            <strong>{item.snapshot_order_code || `Đơn #${item.id}`}</strong>
            <span title={item.snapshot_name || undefined}>{item.snapshot_name || 'Tên sản phẩm chưa ghi nhận'}</span>
            <div className="distribution-product__meta">#{item.id}<span>·</span>
                <span className={highValue ? 'distribution-high-value' : ''}>{highValue ? 'Đơn giá trị cao' : 'Đơn thường'}</span>
            </div>
        </div>
    </div>;
}

function DistributionAssigner({ item }) {
    const source = item.assignment_source;
    return <div className="distribution-assigner">
        <strong>{item.assigned_by?.full_name || item.assigned_by?.username
            || (source === 'spin' ? 'Hệ thống tự phân phối' : source === 'admin' ? 'Không còn thông tin' : 'Chưa ghi nhận')}</strong>
        <span className={`distribution-source distribution-source--${source === 'admin' ? 'manual' : source === 'spin' ? 'auto' : 'unknown'}`}>
            {source === 'admin' ? 'Giao thủ công' : source === 'spin' ? 'Người dùng tự nhận' : 'Chưa ghi nhận nguồn'}
        </span>
    </div>;
}

function DistributionTime({ item }) {
    return <Tooltip trigger={['hover', 'focus', 'click']} title={`Cập nhật: ${formatAdminDateTime(item.updated_at)}`}>
        <div className="distribution-time" tabIndex={0} aria-label={`Phân phối: ${formatAdminDateTime(item.created_at)}. Cập nhật: ${formatAdminDateTime(item.updated_at)}.`}>
            <span>{formatAdminDateTime(item.created_at, { dateStyle: 'short' })}</span>
            <span>{formatAdminDateTime(item.created_at, { timeStyle: 'short' })}</span>
        </div>
    </Tooltip>;
}

export default function OrderDistributionListPage({ config }) {
    const screens = Grid.useBreakpoint();
    const page = config.frozenOrders || {};
    const rows = page.data || [];
    const filters = config.filters || {};
    const statuses = config.statuses || [];
    const stats = config.stats || {};
    const currentPage = Number(page.current_page || 1);
    const total = Number(page.total || 0);
    const lastPage = Number(page.last_page || 1);
    const hasFilters = Object.entries(filters).some(([key, value]) => value && key !== 'sort' && key !== 'direction')
        || (filters.sort && filters.sort !== 'created_at') || (filters.direction && filters.direction !== 'desc');
    const advancedOpen = ['user_id', 'order_id', 'from', 'to', 'updated_from', 'updated_to'].some((key) => filters[key])
        || (filters.sort && filters.sort !== 'created_at') || (filters.direction && filters.direction !== 'desc');

    const changeQuery = (key, value) => {
        const url = new URL(window.location.href);
        if (value) url.searchParams.set(key, value); else url.searchParams.delete(key);
        if (key !== 'page') url.searchParams.delete('page');
        spaNavigate(url.toString());
    };

    const openDetail = (id) => {
        const url = new URL(routeFor(config.routes.show, id), window.location.origin);
        new URL(window.location.href).searchParams.forEach((value, key) => url.searchParams.set(key, value));
        spaNavigate(url.toString());
    };

    const applyFilters = (values) => {
        const url = new URL(config.routes.index, window.location.origin);
        Object.entries(values || {}).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) url.searchParams.set(key, String(value));
        });
        spaNavigate(url.toString());
    };

    const detailButton = (item) => config.permissions?.viewDetail
        ? <Button size="small" className="distribution-detail-button" icon={<EyeOutlined />} onClick={() => openDetail(item.id)}>Xem chi tiết</Button>
        : null;
    const recipient = (item) => <OperationsIdentity avatarUrl={item.user?.avatar_url}
        name={item.user?.full_name || item.user?.username || 'Tài khoản không còn tồn tại'} secondary={`User #${item.user_id}`} />;
    const columns = [
        { title: 'Đơn hàng', width: 250, render: (_, item) => <DistributionProduct item={item} /> },
        { title: 'Người nhận', width: 180, render: (_, item) => recipient(item) },
        { title: 'Người phân phối / Nguồn', width: 185, render: (_, item) => <DistributionAssigner item={item} /> },
        { title: 'Trạng thái', width: 150, render: (_, item) => <DistributionStatus item={item} statuses={statuses} /> },
        { title: 'Phân phối lúc', width: 115, render: (_, item) => <DistributionTime item={item} /> },
        ...(config.permissions?.viewDetail ? [{ title: 'Thao tác', width: 130, align: 'right', render: (_, item) => detailButton(item) }] : []),
    ];
    const statusOptions = [{ value: '', label: 'Tất cả trạng thái' },
        ...statuses.map((item) => ({ value: item.name, label: item.display_name })), { value: 'unknown', label: 'Chưa ghi nhận' }];
    const metrics = [
        { key: 'total', label: 'Tổng đơn phân phối', tone: 'primary', icon: ShoppingOutlined, hint: 'Trong phạm vi quản lý' },
        { key: 'pending', label: 'Chờ xử lý', tone: 'warning', icon: ClockCircleOutlined, hint: 'Gồm đơn chưa ghi nhận trạng thái' },
        { key: 'processing', label: 'Đang xử lý', tone: 'info', icon: SyncOutlined, hint: 'Từ xác nhận đến giao hàng' },
        { key: 'completed', label: 'Hoàn thành', tone: 'success', icon: CheckCircleOutlined, hint: 'Đã hoàn tất xử lý' },
    ];

    return <AdminPage className="admin-operations-page distribution-page">
        <header className="distribution-header">
            <div className="distribution-header__main">
                <span className="distribution-header__icon" aria-hidden="true"><ShoppingOutlined /></span>
                <div><span className="distribution-eyebrow">Vận hành · Đơn hàng</span>
                    <h1>Phân phối đơn hàng</h1><p>Theo dõi người nhận, nguồn phân phối và tiến độ xử lý.</p>
                </div>
            </div>
            <span className="distribution-timezone"><ClockCircleOutlined aria-hidden="true" />Giờ Việt Nam · UTC+7</span>
        </header>

        <section className="distribution-metrics" aria-label="Tổng quan phân phối trong phạm vi quản lý">
            {metrics.map(({ key, label, tone, icon: Icon, hint }) => <article key={key} className={`distribution-metric distribution-metric--${tone}`}>
                <span className="distribution-metric__icon" aria-hidden="true"><Icon /></span>
                <div><span className="distribution-metric__label">{label}</span><strong>{number(stats[key])}</strong><span className="distribution-metric__hint">{hint}</span></div>
            </article>)}
        </section>

        <section className="distribution-list" aria-labelledby="distribution-list-title">
            <div className="distribution-list__heading">
                <h2 id="distribution-list-title">Danh sách phân phối <span className="distribution-result-count" role="status" aria-live="polite" aria-atomic="true">{number(total)} kết quả</span></h2>
                <span className="distribution-list__scope">{hasFilters ? 'Đang áp dụng bộ lọc' : 'Tất cả đơn trong phạm vi quản lý'}</span>
            </div>

            <Form key={JSON.stringify(filters)} size="small" className="distribution-filters" layout="vertical" onFinish={applyFilters}
                initialValues={{ ...filters, assigned_by: filters.assigned_by ? String(filters.assigned_by) : '', status: filters.status || '', source: filters.source || '', sort: filters.sort || 'created_at', direction: filters.direction || 'desc' }}>
                <div className="distribution-filters__main">
                    <Form.Item label="Tìm kiếm đơn hàng" name="q"><Input allowClear name="q" prefix={<SearchOutlined aria-hidden="true" />} placeholder="Mã đơn, sản phẩm, người nhận…" /></Form.Item>
                    <Form.Item label="Trạng thái" name="status"><Select options={statusOptions} /></Form.Item>
                    <Form.Item label="Nguồn phân phối" name="source"><Select options={[{ value: '', label: 'Tất cả nguồn' }, { value: 'admin', label: 'Giao thủ công' }, { value: 'spin', label: 'Người dùng tự nhận' }, { value: 'unknown', label: 'Chưa ghi nhận' }]} /></Form.Item>
                    <Form.Item label="Người phân phối" name="assigned_by"><Select showSearch optionFilterProp="label" options={[{ value: '', label: 'Tất cả người phân phối' }, ...(config.assigners || []).map((user) => ({ value: String(user.id), label: user.full_name || user.username }))]} /></Form.Item>
                </div>
                <div className="distribution-filters__footer">
                    <Collapse ghost className="distribution-advanced" defaultActiveKey={advancedOpen ? ['advanced'] : []} items={[{
                        key: 'advanced', label: <span><FilterOutlined aria-hidden="true" /> Bộ lọc nâng cao</span>, forceRender: true,
                        children: <div className="distribution-filters__advanced">
                            <Form.Item label="User ID" name="user_id"><Input name="user_id" inputMode="numeric" placeholder="ID người nhận" /></Form.Item>
                            <Form.Item label="Order ID" name="order_id"><Input name="order_id" inputMode="numeric" placeholder="ID đơn nguồn" /></Form.Item>
                            <Form.Item label="Phân phối từ ngày" name="from"><AdminDatePicker /></Form.Item>
                            <Form.Item label="Đến ngày" name="to"><AdminDatePicker /></Form.Item>
                            <Form.Item label="Sắp xếp theo" name="sort"><Select options={[{ value: 'created_at', label: 'Ngày phân phối' }, { value: 'updated_at', label: 'Ngày cập nhật' }, { value: 'id', label: 'ID đơn' }, { value: 'status', label: 'Trạng thái' }]} /></Form.Item>
                            <Form.Item label="Thứ tự" name="direction"><Select options={[{ value: 'desc', label: 'Giảm dần' }, { value: 'asc', label: 'Tăng dần' }]} /></Form.Item>
                            {['updated_from', 'updated_to'].some((key) => filters[key]) && <>
                                <Form.Item label="Cập nhật từ ngày" name="updated_from"><AdminDatePicker /></Form.Item>
                                <Form.Item label="Cập nhật đến ngày" name="updated_to"><AdminDatePicker /></Form.Item>
                            </>}
                        </div>,
                    }]} />
                    <div className="distribution-filter-actions">
                        <Button onClick={() => spaNavigate(config.routes.index)}>Xóa bộ lọc</Button>
                        <Button type="primary" htmlType="submit" icon={<SearchOutlined />}>Tìm kiếm</Button>
                    </div>
                </div>
            </Form>

            <div className="distribution-tabs" role="group" aria-label="Lọc nhanh trạng thái">
                {[
                    { value: '', label: 'Tất cả' }, { value: 'pending', label: statusLabel(statuses, 'pending') },
                    { value: 'completed', label: statusLabel(statuses, 'completed') }, { value: 'cancelled', label: statusLabel(statuses, 'cancelled') },
                ].map(({ value, label }) => <button key={value} type="button" aria-pressed={String(filters.status || '') === value}
                    onClick={() => changeQuery('status', value)}>{label}</button>)}
            </div>

            {rows.length ? (screens.xl
                ? <Table size="small" className="distribution-table" rowKey="id" dataSource={rows} columns={columns} pagination={false} scroll={{ x: config.permissions?.viewDetail ? 1010 : 880 }} />
                : <div className="distribution-mobile-list">{rows.map((item) => <article className="distribution-order-card" key={item.id}>
                    <div className="distribution-order-card__heading"><DistributionProduct item={item} /><DistributionStatus item={item} statuses={statuses} /></div>
                    <div className="distribution-order-card__people"><div><span className="distribution-field-label">Người nhận</span>{recipient(item)}</div>
                        <div><span className="distribution-field-label">Người phân phối</span><DistributionAssigner item={item} /></div></div>
                    <div className="distribution-order-card__footer"><div><span className="distribution-field-label">Thời gian phân phối</span><DistributionTime item={item} /></div>{detailButton(item)}</div>
                </article>)}</div>)
                : <AdminEmptyState title="Không có đơn hàng phù hợp" description="Thử thay đổi từ khóa hoặc bộ lọc để tìm đơn hàng."
                    action={hasFilters ? <Button onClick={() => spaNavigate(config.routes.index)} icon={<ArrowRightOutlined />}>Xem tất cả đơn phân phối</Button> : undefined} />}

            <footer className="distribution-list__footer">
                <span>{rows.length ? <>Hiển thị <strong>{number(page.from || (currentPage - 1) * Number(page.per_page || 25) + 1)}–{number(page.to || (currentPage - 1) * Number(page.per_page || 25) + rows.length)}</strong> / {number(total)} đơn</> : '0 đơn phân phối'}<span className="distribution-page-number"> · Trang {number(currentPage)} / {number(lastPage)}</span></span>
                {lastPage > 1 && <Pagination size={screens.md ? 'small' : 'default'} current={currentPage} total={total} pageSize={Number(page.per_page || 25)} showSizeChanger={false} onChange={(next) => changeQuery('page', next)} />}
            </footer>
        </section>
    </AdminPage>;
}
