import { Button, Checkbox, Collapse, Empty, Input, Switch, message } from 'antd';
import {
    CheckOutlined, SafetyCertificateOutlined, SearchOutlined, StopOutlined,
} from '@ant-design/icons';
import { useEffect, useMemo, useState } from 'react';
import LaravelForm from '../../../components/LaravelForm';
import { requestJson } from '../../../lib/http';
import { matchesOperationsSearch } from '../../../lib/operations';
import { AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';
import '../../../../../css/admin/staff-permissions.css';
import { ChevronDown, ChevronRight, ChevronUp } from 'lucide';
import AdminMorphIcon from '../../../components/admin/AdminMorphIcon';

const assignmentIds = (groups) => groups.flatMap((group) => (
    (group.permissions || []).map((permission) => Number(permission.assignment?.id))
)).filter(Boolean);

export default function StaffPermissionsPage({ config }) {
    const [groups, setGroups] = useState(config.permissionGroups || []);
    const [query, setQuery] = useState('');
    const [selected, setSelected] = useState([]);
    const [loadingId, setLoadingId] = useState(null);
    const [expandedGroups, setExpandedGroups] = useState(() => (config.permissionGroups || []).map((group) => group.key));

    useEffect(() => {
        setGroups(config.permissionGroups || []);
        setSelected([]);
        setExpandedGroups((config.permissionGroups || []).map((group) => group.key));
    }, [config.permissionGroups]);

    const visibleGroups = useMemo(() => groups.map((group) => ({
        ...group,
        permissions: (group.permissions || []).filter((permission) => (
            matchesOperationsSearch([group.label, permission.label, permission.code], query)
        )),
    })).filter((group) => group.permissions.length > 0), [groups, query]);

    const permissions = groups.flatMap((group) => group.permissions || []);
    const activeCount = permissions.filter((permission) => permission.active).length;
    const visibleIds = assignmentIds(visibleGroups);
    const hiddenSelectedCount = selected.filter((id) => !visibleIds.includes(id)).length;
    const allExpanded = visibleGroups.length > 0 && visibleGroups.every((group) => expandedGroups.includes(group.key));

    const togglePermission = async (permission) => {
        const id = Number(permission.assignment?.id);
        if (!id || loadingId) return;
        setLoadingId(id);
        try {
            const payload = await requestJson(config.routes.toggle, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id }),
            });
            setGroups((current) => current.map((group) => ({
                ...group,
                permissions: (group.permissions || []).map((item) => Number(item.assignment?.id) === id ? { ...item, active: Boolean(payload.is_active) } : item),
            })));
            message.success(payload.message || 'Đã cập nhật quyền.');
        } catch (error) {
            message.error(error.message || 'Không thể cập nhật quyền.');
        } finally {
            setLoadingId(null);
        }
    };

    const setGroupSelection = (group, checked) => {
        const ids = assignmentIds([group]);
        setSelected((current) => checked ? Array.from(new Set([...current, ...ids])) : current.filter((id) => !ids.includes(id)));
    };

    const collapseItems = visibleGroups.map((group) => {
        const ids = assignmentIds([group]);
        const checkedCount = ids.filter((id) => selected.includes(id)).length;
        const granted = group.permissions.filter((permission) => permission.active).length;
        return {
            key: group.key,
            label: <div className="staff-permissions-group">
                <Checkbox
                    aria-label={`Chọn nhóm ${group.label}`}
                    checked={ids.length > 0 && checkedCount === ids.length}
                    indeterminate={checkedCount > 0 && checkedCount < ids.length}
                    disabled={!ids.length}
                    onClick={(event) => event.stopPropagation()}
                    onKeyDown={(event) => event.stopPropagation()}
                    onChange={(event) => setGroupSelection(group, event.target.checked)}
                />
                <strong>{group.label}</strong>
                <span className={`staff-permissions-group__count${granted === group.permissions.length ? ' is-complete' : ''}`}>
                    {granted}/{group.permissions.length} đã cấp
                </span>
            </div>,
            children: <div className="staff-permissions-grid">{group.permissions.map((permission) => {
                const id = Number(permission.assignment?.id);
                const isSelected = selected.includes(id);
                return <div className={`staff-permissions-item${isSelected ? ' is-selected' : ''}`} key={permission.code}>
                    <Checkbox
                        aria-label={`Chọn quyền ${permission.label}`}
                        checked={isSelected}
                        disabled={!id}
                        onChange={(event) => setSelected((current) => event.target.checked ? Array.from(new Set([...current, id])) : current.filter((value) => value !== id))}
                    />
                    <div className="staff-permissions-item__content">
                        <strong>{permission.label}</strong>
                        <span className="staff-permissions-item__code">{permission.code}</span>
                    </div>
                    <div className={`staff-permissions-item__state${permission.active ? ' is-active' : ''}`}>
                        <Switch
                            size="small"
                            aria-label={`${permission.active ? 'Thu hồi' : 'Cấp'} quyền ${permission.label}`}
                            checked={Boolean(permission.active)}
                            loading={loadingId === id}
                            disabled={!id || Boolean(loadingId && loadingId !== id)}
                            onChange={() => togglePermission(permission)}
                        />
                        <span>{permission.active ? 'Đã cấp' : 'Chưa cấp'}</span>
                    </div>
                </div>;
            })}</div>,
        };
    });

    const bulkAction = (active) => <LaravelForm
        action={config.routes.bulk}
        method="POST"
        onSubmit={(event) => { if (!selected.length || loadingId) event.preventDefault(); }}
    >
        <input type="hidden" name="staff_id" value={config.staff?.id} />
        {selected.map((id) => <input key={id} type="hidden" name="assignment_ids[]" value={id} />)}
        <input type="hidden" name="is_active" value={active ? '1' : '0'} />
        <Button
            type={active ? 'primary' : 'default'}
            danger={!active}
            htmlType="submit"
            icon={active ? <CheckOutlined aria-hidden="true" /> : <StopOutlined aria-hidden="true" />}
            disabled={!selected.length || Boolean(loadingId)}
        >{active ? 'Cấp quyền' : 'Thu hồi'}</Button>
    </LaravelForm>;

    return <AdminPage width="content" className="admin-staff-permissions">
        <AdminPageHeader
            eyebrow="Phân quyền nhân sự"
            icon={<SafetyCertificateOutlined />}
            title={`Phân quyền: ${config.staff?.full_name || config.staff?.username || 'Nhân sự'}`}
            description="Bật/tắt từng quyền hoặc chọn theo nhóm để cấp, thu hồi hàng loạt."
            backHref={config.routes.index}
            backLabel="Danh sách nhân sự"
            meta={<>
                <span className="staff-permissions-account">@{config.staff?.username}</span>
                <span>{groups.length} nhóm</span>
                <span className="staff-permissions-summary"><CheckOutlined aria-hidden="true" /> {activeCount}/{permissions.length} quyền đã cấp</span>
            </>}
        />
        <section className="staff-permissions-panel" aria-label="Danh sách quyền">
            <div className="staff-permissions-toolbar">
                <Input
                    className="staff-permissions-search"
                    prefix={<SearchOutlined aria-hidden="true" />}
                    aria-label="Tìm nhóm, tên hoặc mã quyền"
                    value={query}
                    onChange={(event) => {
                        setQuery(event.target.value);
                        setExpandedGroups(groups.map((group) => group.key));
                    }}
                    placeholder="Tìm nhóm, tên hoặc mã quyền..."
                    allowClear
                />
                <Button
                    type="text"
                    icon={<AdminMorphIcon icon={allExpanded ? ChevronUp : ChevronDown} />}
                    disabled={!visibleGroups.length}
                    onClick={() => setExpandedGroups(allExpanded ? [] : visibleGroups.map((group) => group.key))}
                >{allExpanded ? 'Thu gọn nhóm' : 'Mở các nhóm'}</Button>
            </div>
            <div className="staff-permissions-bulk">
                <div className="staff-permissions-selection">
                    <Button
                        type="text"
                        disabled={!visibleIds.length}
                        onClick={() => setSelected((current) => Array.from(new Set([...current, ...visibleIds])))}
                    >{query.trim() ? 'Chọn kết quả' : 'Chọn tất cả'}</Button>
                    <Button type="text" disabled={!selected.length} onClick={() => setSelected([])}>Bỏ chọn</Button>
                    <span role="status" aria-live="polite" aria-atomic="true">
                        Đã chọn <strong>{selected.length}</strong> quyền
                        {hiddenSelectedCount > 0 && ` (${hiddenSelectedCount} ngoài kết quả)`}
                    </span>
                </div>
                <div className="staff-permissions-bulk__actions">{bulkAction(true)}{bulkAction(false)}</div>
            </div>
            {collapseItems.length ? <Collapse
                className="staff-permissions-collapse"
                activeKey={expandedGroups}
                onChange={setExpandedGroups}
                items={collapseItems}
                expandIconPosition="end"
                expandIcon={({ isActive }) => <AdminMorphIcon icon={isActive ? ChevronDown : ChevronRight} size={14} />}
            /> : <Empty
                className="staff-permissions-empty"
                image={Empty.PRESENTED_IMAGE_SIMPLE}
                description={query.trim() ? 'Không tìm thấy quyền phù hợp.' : 'Chưa có quyền có thể phân cho nhân sự này.'}
            />}
        </section>
    </AdminPage>;
}
