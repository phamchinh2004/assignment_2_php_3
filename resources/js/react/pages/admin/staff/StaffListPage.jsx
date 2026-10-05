import { Button, Grid, Input, Pagination, Select, Table, message } from 'antd';
import {
    CheckCircleOutlined, CloseOutlined, EditOutlined, EyeOutlined,
    LockOutlined, SafetyCertificateOutlined, SearchOutlined, TeamOutlined,
    UnlockOutlined, UserAddOutlined, UserOutlined, WifiOutlined,
} from '@ant-design/icons';
import { useEffect, useMemo, useState } from 'react';
import { requestJson } from '../../../lib/http';
import { AdminPage } from '../../../components/admin/AdminUi';
import AccountStatusConfirm from '../../../components/admin/AccountStatusConfirm';
import { matchesOperationsSearch, operationsMoney as money } from '../../../lib/operations';

const routeFor = (template, id) => String(template || '').replace('__STAFF_ID__', encodeURIComponent(String(id)));
const PAGE_SIZE = 20;
const statusLabels = { activated: 'Đã kích hoạt', inactivated: 'Chưa kích hoạt', banned: 'Bị khóa' };

function StaffIdentity({ staff, href }) {
    const name = staff.full_name || staff.username || 'Chưa đặt tên';
    const initials = name.trim().split(/\s+/).slice(-2).map((part) => Array.from(part)[0]).join('').toLocaleUpperCase('vi');
    const content = <>
        <span className={`staff-directory-avatar staff-directory-avatar--${staff.role === 'admin' ? 'admin' : 'staff'}`} aria-hidden="true">{initials}</span>
        <span className="staff-directory-identity__copy">
            <strong>{name}</strong>
            <span className="staff-directory-identity__meta">
                <span>@{staff.username || '—'} <span className="staff-directory-id">· #{staff.id}</span></span>
                <span>{staff.phone || staff.email || 'Chưa có thông tin liên hệ'}</span>
            </span>
        </span>
    </>;
    return href
        ? <a className="staff-directory-identity" href={href}>{content}</a>
        : <div className="staff-directory-identity">{content}</div>;
}

function StaffRole({ role }) {
    return <span className={`staff-directory-role staff-directory-role--${role === 'admin' ? 'admin' : 'staff'}`}>
        {role === 'admin' ? <SafetyCertificateOutlined aria-hidden="true" /> : <UserOutlined aria-hidden="true" />}
        {role === 'admin' ? 'Admin' : 'Staff'}
    </span>;
}

function StaffPresence({ staff }) {
    return <div className="staff-directory-presence-cell">
        <span className={`staff-directory-presence${staff.is_online ? ' staff-directory-presence--online' : ''}`}>
            <i aria-hidden="true" />{staff.is_online ? 'Đang online' : 'Offline'}
        </span>
        {!staff.is_online && <span className="staff-directory-note">{staff.last_seen_text || 'Chưa ghi nhận hoạt động'}</span>}
    </div>;
}

function StaffStatus({ status }) {
    return <span className={`staff-directory-status staff-directory-status--${status}`}>
        {status === 'activated' ? <CheckCircleOutlined aria-hidden="true" /> : status === 'banned' ? <LockOutlined aria-hidden="true" /> : <span className="staff-directory-status-dot" aria-hidden="true" />}
        {statusLabels[status] || 'Chưa xác định'}
    </span>;
}

function StaffActions({ staff, config, onChangeStatus }) {
    const permissions = config.permissions || {};
    const canView = permissions.viewDetail;
    const canPermissions = permissions.viewPermissions && staff.can_manage_permissions;
    const canEdit = permissions.update && staff.can_manage;
    const canStatus = permissions.changeStatus && staff.can_manage;
    if (!canView && !canPermissions && !canEdit && !canStatus) return null;

    const locked = staff.status === 'banned';
    const active = staff.status === 'activated';
    return <div className="staff-directory-actions">
        {canView && <Button icon={<EyeOutlined aria-hidden="true" />} href={routeFor(config.routes.show, staff.id)}>Xem</Button>}
        {canPermissions && <Button icon={<SafetyCertificateOutlined aria-hidden="true" />} href={routeFor(config.routes.permissions, staff.id)}>Phân quyền</Button>}
        {canEdit && <Button icon={<EditOutlined aria-hidden="true" />} href={routeFor(config.routes.edit, staff.id)}>Sửa</Button>}
        {canStatus && <Button
            danger={active}
            icon={active ? <LockOutlined aria-hidden="true" /> : <UnlockOutlined aria-hidden="true" />}
            onClick={() => onChangeStatus(staff)}
        >{active ? 'Khóa' : locked ? 'Mở khóa' : 'Kích hoạt'}</Button>}
    </div>;
}

export default function StaffListPage({ config }) {
    const screens = Grid.useBreakpoint();
    const [filter, setFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [role, setRole] = useState('all');
    const [sort, setSort] = useState('default');
    const [page, setPage] = useState(1);
    const [staffs, setStaffs] = useState(config.staffs || []);
    const [statusStaff, setStatusStaff] = useState(null);
    const [presenceStats, setPresenceStats] = useState({ online: Number(config.onlineStaffCount || 0), offline: Number(config.offlineStaffCount || 0) });

    const changeStatus = async (staff) => {
        const payload = await requestJson(routeFor(config.routes.changeStatus, staff.id));
        if (!payload?.success || !payload.staff) throw new Error(payload?.message || 'Không thể thay đổi trạng thái tài khoản.');
        setStaffs((current) => current.map((item) => String(item.id) === String(payload.staff.id) ? { ...item, status: payload.staff.status } : item));
        message.success(payload.message);
    };

    useEffect(() => {
        let mounted = true;
        const refresh = async () => {
            try {
                const payload = await requestJson(config.routes.onlineStatuses);
                if (!mounted || !payload?.success) return;
                const states = new Map((payload.staffs || []).map((item) => [Number(item.id), item]));
                setStaffs((current) => current.map((staff) => {
                    const next = states.get(Number(staff.id));
                    return next ? { ...staff, is_online: next.is_online, last_seen_text: next.last_seen_text, last_seen_formatted: next.last_seen_formatted } : staff;
                }));
                setPresenceStats({ online: Number(payload.online_count || 0), offline: Number(payload.offline_count || 0) });
            } catch {
                // Presence polling is non-critical; keep the last known values.
            }
        };
        const timer = window.setInterval(refresh, 60000);
        window.addEventListener('focus', refresh);
        return () => { mounted = false; window.clearInterval(timer); window.removeEventListener('focus', refresh); };
    }, [config.routes.onlineStatuses]);

    const counts = useMemo(() => ({
        total: staffs.length,
        active: staffs.filter((item) => item.status === 'activated').length,
        banned: staffs.filter((item) => item.status === 'banned').length,
        inactive: staffs.filter((item) => item.status === 'inactivated').length,
        admins: staffs.filter((item) => item.role === 'admin').length,
    }), [staffs]);

    const filtered = useMemo(() => {
        const result = staffs.filter((item) => {
            if (role !== 'all' && item.role !== role) return false;
            if (filter === 'online' && !item.is_online) return false;
            if (filter === 'offline' && item.is_online) return false;
            if (['activated', 'banned', 'inactivated'].includes(filter) && item.status !== filter) return false;
            return matchesOperationsSearch([item.id, item.full_name, item.username, item.phone, item.email, item.role], search);
        });
        if (sort !== 'default') result.sort((a, b) => sort === 'deposit_desc'
            ? Number(b.total_deposit || 0) - Number(a.total_deposit || 0)
            : Number(a.total_deposit || 0) - Number(b.total_deposit || 0));
        return result;
    }, [staffs, filter, role, search, sort]);

    const currentPage = Math.min(page, Math.max(1, Math.ceil(filtered.length / PAGE_SIZE)));
    const hasFilters = filter !== 'all' || role !== 'all' || Boolean(search.trim());
    const changeFilter = (value) => { setFilter(value); setPage(1); };
    const resetFilters = () => { setFilter('all'); setRole('all'); setSearch(''); setPage(1); };
    const roleOptions = [
        { value: 'all', label: 'Tất cả', count: counts.total, icon: <TeamOutlined aria-hidden="true" /> },
        { value: 'admin', label: 'Admin', count: counts.admins, icon: <SafetyCertificateOutlined aria-hidden="true" /> },
        { value: 'staff', label: 'Staff', count: counts.total - counts.admins, icon: <UserOutlined aria-hidden="true" /> },
    ];
    const metrics = [
        { value: 'all', label: 'Tổng nhân sự', count: counts.total, hint: `${counts.admins} Admin · ${counts.total - counts.admins} Staff`, tone: 'primary', icon: <TeamOutlined /> },
        { value: 'online', label: 'Đang online', count: presenceStats.online, hint: `${presenceStats.offline} offline`, tone: 'success', icon: <WifiOutlined /> },
        { value: 'activated', label: 'Đã kích hoạt', count: counts.active, hint: `${counts.inactive} chưa kích hoạt`, tone: 'info', icon: <CheckCircleOutlined /> },
        { value: 'banned', label: 'Bị khóa', count: counts.banned, hint: 'Hạn chế truy cập', tone: 'danger', icon: <LockOutlined /> },
    ];
    const columns = [
        { title: 'Thành viên', key: 'identity', width: 270, render: (_, staff) => <StaffIdentity staff={staff} href={config.permissions?.viewDetail ? routeFor(config.routes.show, staff.id) : undefined} /> },
        { title: 'Vai trò', dataIndex: 'role', width: 90, render: (value) => <StaffRole role={value} /> },
        { title: 'Hoạt động', key: 'presence', width: 145, render: (_, staff) => <StaffPresence staff={staff} /> },
        { title: 'Tổng doanh số nạp', dataIndex: 'total_deposit', align: 'right', width: 175,
            sorter: true, sortOrder: sort === 'deposit_desc' ? 'descend' : sort === 'deposit_asc' ? 'ascend' : null,
            render: (value, staff) => <div className="staff-directory-deposit"><strong>{money(value)}</strong><span className="staff-directory-note">{staff.role === 'admin' ? 'Gồm staff trực thuộc' : 'Doanh số cá nhân'}</span></div> },
        { title: 'Trạng thái', dataIndex: 'status', width: 145, render: (value) => <StaffStatus status={value} /> },
        { title: 'Thao tác', key: 'actions', width: 230, align: 'right', render: (_, staff) => <StaffActions staff={staff} config={config} onChangeStatus={setStatusStaff} /> },
    ];

    const emptyState = <div className="staff-directory-empty">
        <span className="staff-directory-empty__icon" aria-hidden="true">{hasFilters ? <SearchOutlined /> : <TeamOutlined />}</span>
        <h3>{hasFilters ? 'Chưa tìm thấy tài khoản phù hợp' : 'Đội ngũ của bạn bắt đầu từ đây'}</h3>
        <p>{hasFilters ? 'Thử từ khóa khác hoặc xóa bộ lọc để xem toàn bộ đội ngũ.' : 'Các tài khoản Admin và Staff trong phạm vi quản lý sẽ xuất hiện tại đây.'}</p>
        {hasFilters && <Button onClick={resetFilters}>Xóa bộ lọc</Button>}
        {!hasFilters && config.permissions?.create && <Button type="primary" icon={<UserAddOutlined />} href={config.routes.create}>Thêm tài khoản mới</Button>}
    </div>;

    return <AdminPage className="admin-operations-page staff-directory">
        <AccountStatusConfirm account={statusStaff} onCancel={() => setStatusStaff(null)} onConfirm={changeStatus} />
        <header className="staff-directory-hero">
            <div className="staff-directory-hero__copy">
                <span className="staff-directory-eyebrow"><span aria-hidden="true"><TeamOutlined /></span> ĐỘI NGŨ & VẬN HÀNH</span>
                <h1>Quản lý admin <span>& nhân viên</span></h1>
                <p>Theo dõi hoạt động, doanh số và quyền truy cập.</p>
            </div>
            {config.permissions?.create && <Button className="staff-directory-create" type="primary" icon={<UserAddOutlined aria-hidden="true" />} href={config.routes.create}>Thêm tài khoản mới</Button>}
        </header>

        <section className="staff-directory-metrics" aria-label="Tổng quan nhân sự và lọc nhanh">
            {metrics.map((metric) => <button
                type="button" key={metric.value}
                className={`staff-directory-metric staff-directory-metric--${metric.tone}${filter === metric.value ? ' is-selected' : ''}`}
                aria-pressed={filter === metric.value}
                onClick={() => changeFilter(metric.value)}
            >
                <span className="staff-directory-metric__icon" aria-hidden="true">{metric.icon}</span>
                <span className="staff-directory-metric__copy">
                    <span className="staff-directory-metric__label">{metric.label}<strong>{metric.count}</strong></span>
                    <span className="staff-directory-metric__hint">{metric.hint}</span>
                </span>
            </button>)}
        </section>

        <section className="staff-directory-list" aria-labelledby="staff-directory-list-title">
            <div className="staff-directory-list__heading">
                <h2 id="staff-directory-list-title">Thành viên đội ngũ <span>{counts.total}</span></h2>
                <span className="staff-directory-list__hint"><WifiOutlined aria-hidden="true" /> Online cập nhật mỗi phút</span>
            </div>
            <div className="staff-directory-toolbar">
                <div className="staff-directory-role-tabs" role="group" aria-label="Lọc vai trò">
                    {roleOptions.map((option) => <button type="button" key={option.value} aria-pressed={role === option.value} className={role === option.value ? 'is-selected' : ''} onClick={() => { setRole(option.value); setPage(1); }}>{option.icon}{option.label}<span>{option.count}</span></button>)}
                </div>
                <Input allowClear value={search} prefix={<SearchOutlined aria-hidden="true" />} placeholder="Tìm tên, tài khoản, email, SĐT..." aria-label="Tìm kiếm nhân sự" onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            </div>
            <div className="staff-directory-filters">
                <label className="staff-directory-filter"><span>Trạng thái</span><Select aria-label="Lọc trạng thái" value={filter} onChange={changeFilter} options={[
                    { value: 'all', label: 'Tất cả trạng thái' },
                    { value: 'online', label: `Đang online (${presenceStats.online})` },
                    { value: 'offline', label: `Offline (${presenceStats.offline})` },
                    { value: 'activated', label: `Đã kích hoạt (${counts.active})` },
                    { value: 'inactivated', label: `Chưa kích hoạt (${counts.inactive})` },
                    { value: 'banned', label: `Bị khóa (${counts.banned})` },
                ]} /></label>
                <label className="staff-directory-filter"><span>Sắp xếp</span><Select aria-label="Sắp xếp theo doanh số" value={sort} onChange={(value) => { setSort(value); setPage(1); }} options={[
                    { value: 'default', label: 'Thứ tự mặc định' },
                    { value: 'deposit_desc', label: 'Doanh số: cao → thấp' },
                    { value: 'deposit_asc', label: 'Doanh số: thấp → cao' },
                ]} /></label>
                {hasFilters && <Button type="text" icon={<CloseOutlined aria-hidden="true" />} onClick={resetFilters}>Xóa bộ lọc</Button>}
                <span className="staff-directory-results" role="status">Hiển thị <strong>{filtered.length}</strong> / {counts.total} tài khoản</span>
            </div>

            {screens.xl ? <Table
                className="staff-directory-table" rowKey="id" dataSource={filtered} columns={columns} scroll={{ x: 1055 }}
                pagination={{ current: currentPage, pageSize: PAGE_SIZE, showSizeChanger: false, onChange: setPage }}
                onChange={(_, __, sorter, extra) => {
                    if (extra.action === 'sort') { setSort(sorter.order === 'descend' ? 'deposit_desc' : sorter.order === 'ascend' ? 'deposit_asc' : 'default'); setPage(1); }
                }}
                locale={{ emptyText: emptyState }}
            /> : <>
                <div className="staff-directory-cards">
                    {filtered.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE).map((staff) => <article className="staff-directory-member" key={staff.id} aria-label={`Tài khoản ${staff.full_name || staff.username || staff.id}`}>
                        <div className="staff-directory-member__heading"><StaffIdentity staff={staff} href={config.permissions?.viewDetail ? routeFor(config.routes.show, staff.id) : undefined} /><StaffRole role={staff.role} /></div>
                        <div className="staff-directory-member__details">
                            <div className="staff-directory-deposit"><span className="staff-directory-note">Tổng doanh số nạp</span><strong>{money(staff.total_deposit)}</strong><span className="staff-directory-note">{staff.role === 'admin' ? 'Gồm staff trực thuộc' : 'Doanh số cá nhân'}</span></div>
                            <StaffPresence staff={staff} />
                        </div>
                        <div className="staff-directory-member__status"><span>Trạng thái tài khoản</span><StaffStatus status={staff.status} /></div>
                        <StaffActions staff={staff} config={config} onChangeStatus={setStatusStaff} />
                    </article>)}
                </div>
                {filtered.length === 0 ? emptyState : <div className="staff-directory-pagination"><Pagination current={currentPage} total={filtered.length} pageSize={PAGE_SIZE} showSizeChanger={false} onChange={setPage} /></div>}
            </>}
        </section>
        <p className="staff-directory-footnote"><SafetyCertificateOutlined aria-hidden="true" /> Các thao tác hiển thị theo quyền và phạm vi quản lý của bạn.</p>
    </AdminPage>;
}
