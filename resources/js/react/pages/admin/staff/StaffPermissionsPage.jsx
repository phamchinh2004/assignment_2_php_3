import { Button, Checkbox, Collapse, Input, Space, Switch, Tag, Typography, message } from 'antd';
import { SafetyCertificateOutlined } from '@ant-design/icons';
import { useMemo, useState } from 'react';
import LaravelForm from '../../../components/LaravelForm';
import { requestJson } from '../../../lib/http';
import { AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;

export default function StaffPermissionsPage({ config }) {
    const [groups, setGroups] = useState(config.permissionGroups || []);
    const [query, setQuery] = useState('');
    const [selected, setSelected] = useState([]);
    const [loadingId, setLoadingId] = useState(null);

    const visibleGroups = useMemo(() => {
        const needle = query.trim().toLocaleLowerCase('vi');
        if (!needle) return groups;
        return groups.map((group) => ({
            ...group,
            permissions: (group.permissions || []).filter((permission) => `${group.label} ${permission.label} ${permission.code}`.toLocaleLowerCase('vi').includes(needle)),
        })).filter((group) => group.permissions.length > 0);
    }, [groups, query]);

    const allIds = groups.flatMap((group) => (group.permissions || []).map((permission) => Number(permission.assignment?.id))).filter(Boolean);

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
        const ids = (group.permissions || []).map((permission) => Number(permission.assignment?.id)).filter(Boolean);
        setSelected((current) => checked ? Array.from(new Set([...current, ...ids])) : current.filter((id) => !ids.includes(id)));
    };

    const collapseItems = visibleGroups.map((group) => {
        const ids = (group.permissions || []).map((permission) => Number(permission.assignment?.id)).filter(Boolean);
        const checkedCount = ids.filter((id) => selected.includes(id)).length;
        return {
            key: group.key,
            label: <Space><Checkbox checked={ids.length>0&&checkedCount===ids.length} indeterminate={checkedCount>0&&checkedCount<ids.length} onClick={(event)=>event.stopPropagation()} onChange={(event)=>setGroupSelection(group,event.target.checked)}/><Text strong>{group.label}</Text><Tag>{ids.length} quyền</Tag></Space>,
            children: <div className="admin-permission-grid">{(group.permissions||[]).map((permission)=>{
                const id=Number(permission.assignment?.id);
                const isSelected=selected.includes(id);
                return <div className={`admin-permission-item${isSelected?' is-selected':''}`} key={id}>
                    <Checkbox
                        aria-label={`Chọn quyền ${permission.label}`}
                        checked={isSelected}
                        onChange={(event)=>setSelected((current)=>event.target.checked?Array.from(new Set([...current,id])):current.filter((value)=>value!==id))}
                    />
                    <div className="admin-permission-item__content">
                        <Text strong className="admin-permission-item__label">{permission.label}</Text>
                        <Text type="secondary" className="admin-permission-item__code" title={permission.code}>{permission.code}</Text>
                    </div>
                    <Switch
                        size="small"
                        aria-label={`${permission.active?'Thu hồi':'Cấp'} quyền ${permission.label}`}
                        checked={Boolean(permission.active)}
                        loading={loadingId===id}
                        onChange={()=>togglePermission(permission)}
                    />
                </div>;
            })}</div>,
        };
    });

    return <AdminPage width="content">
        <AdminPageHeader
            eyebrow="Phân quyền nhân sự"
            icon={<SafetyCertificateOutlined />}
            title={`Phân quyền: ${config.staff?.full_name || config.staff?.username}`}
            description="Bật/tắt từng quyền hoặc chọn nhiều quyền để cấp và thu hồi theo nhóm. Các quyền được nhóm theo chức năng nghiệp vụ."
            backHref={config.routes.index}
            backLabel="Danh sách nhân sự"
            meta={<Tag>@{config.staff?.username}</Tag>}
        />
        <AdminSectionCard title="Danh sách quyền" description="Tìm theo module, tên hoặc mã quyền. Thao tác hàng loạt chỉ áp dụng cho các quyền đang được chọn.">
            <Space direction="vertical" size="middle" style={{width:'100%'}}>
                <Input.Search value={query} onChange={(event)=>setQuery(event.target.value)} placeholder="Tìm theo module, tên quyền hoặc mã quyền..." allowClear/>
                <div className="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <Space><Button onClick={()=>setSelected(allIds)}>Chọn tất cả</Button><Button onClick={()=>setSelected([])}>Bỏ chọn</Button><Text type="secondary">{selected.length} quyền đã chọn</Text></Space>
                    <Space>
                        <LaravelForm action={config.routes.bulk} method="POST" onSubmit={(event)=>{if(!selected.length)event.preventDefault();}}>
                            <input type="hidden" name="staff_id" value={config.staff?.id}/>{selected.map((id)=><input key={id} type="hidden" name="assignment_ids[]" value={id}/>)}<input type="hidden" name="is_active" value="1"/><Button type="primary" htmlType="submit" disabled={!selected.length}>Cấp quyền đã chọn</Button>
                        </LaravelForm>
                        <LaravelForm action={config.routes.bulk} method="POST" onSubmit={(event)=>{if(!selected.length)event.preventDefault();}}>
                            <input type="hidden" name="staff_id" value={config.staff?.id}/>{selected.map((id)=><input key={id} type="hidden" name="assignment_ids[]" value={id}/>)}<input type="hidden" name="is_active" value="0"/><Button danger htmlType="submit" disabled={!selected.length}>Bỏ quyền đã chọn</Button>
                        </LaravelForm>
                    </Space>
                </div>
                {collapseItems.length?<Collapse className="admin-permission-collapse" defaultActiveKey={collapseItems.map((item)=>item.key)} items={collapseItems}/>:<Text type="secondary">Không tìm thấy quyền phù hợp.</Text>}
            </Space>
        </AdminSectionCard>
    </AdminPage>;
}
