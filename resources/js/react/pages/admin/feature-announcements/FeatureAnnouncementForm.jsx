import { Button, Card, Checkbox, Input, Radio, Select, Space, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import { useMemo, useState } from 'react';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';

const { Text, Title } = Typography;

function formArray(config, name, fallback = []) {
    const old = config.form?.old || {};
    if (Object.prototype.hasOwnProperty.call(old, name)) return old[name] || [];
    return fallback || [];
}

export default function FeatureAnnouncementForm({ config, editing = false }) {
    const announcement = config.announcement || {};
    const values = config.values || {};
    const initialType = oldValue(config.form, 'target_type', values.target_type || 'roles');
    const [targetType, setTargetType] = useState(initialType);
    const [search, setSearch] = useState('');
    const selectedRoles = formArray(config, 'target_roles', values.target_roles);
    const selectedUsers = formArray(config, 'target_user_ids', values.target_user_ids).map(String);
    const filteredUsers = useMemo(() => (config.targetUsers || []).filter((user) => {
        const haystack = [user.full_name, user.username, user.email, user.role].filter(Boolean).join(' ').toLocaleLowerCase('vi');
        return !search.trim() || haystack.includes(search.trim().toLocaleLowerCase('vi'));
    }), [config.targetUsers, search]);

    return <div className="container-fluid px-4 pb-5">
        <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách</Button>
        <Title level={2}>{editing ? 'Chỉnh sửa thông báo tính năng' : 'Tạo thông báo tính năng mới'}</Title>
        {editing && <Text type="secondary">Version hiện tại: {announcement.version}</Text>}
        <Card className="mt-4">
            <LaravelForm action={editing ? config.routes.update : config.routes.store} method={editing ? 'PUT' : 'POST'} encType="multipart/form-data">
                <div className="mb-3"><label className="form-label-modern">Tiêu đề *</label><Input name="title" defaultValue={oldValue(config.form,'title',announcement.title || '')} maxLength={180} required />{fieldError(config.form,'title') && <Text type="danger">{fieldError(config.form,'title')}</Text>}</div>
                <div className="mb-3"><label className="form-label-modern">Nội dung *</label><Input.TextArea name="content" rows={7} defaultValue={oldValue(config.form,'content',announcement.content || '')} required />{fieldError(config.form,'content') && <Text type="danger">{fieldError(config.form,'content')}</Text>}</div>
                <div className="row mb-4">
                    <div className="col-md-4"><label className="form-label-modern">Mức độ ưu tiên</label><select name="priority" className="form-control" defaultValue={oldValue(config.form,'priority',values.priority || 'normal')}><option value="normal">Bình thường</option><option value="important">Quan trọng</option><option value="critical">Khẩn cấp</option></select></div>
                    <div className="col-md-4"><label className="form-label-modern">Bắt đầu</label><input type="datetime-local" name="starts_at" className="form-control" defaultValue={oldValue(config.form,'starts_at',values.starts_at || '')} required /></div>
                    <div className="col-md-4"><label className="form-label-modern">Kết thúc</label><input type="datetime-local" name="ends_at" className="form-control" defaultValue={oldValue(config.form,'ends_at',values.ends_at || '')} /></div>
                </div>

                <Card size="small" title="Đối tượng nhận" className="mb-4">
                    <Radio.Group value={targetType} onChange={(event) => setTargetType(event.target.value)}>
                        <Radio value="roles">Theo vai trò</Radio><Radio value="users">Chọn người cụ thể</Radio>
                    </Radio.Group>
                    <input type="hidden" name="target_type" value={targetType} />
                    {targetType === 'roles' ? <div className="mt-3"><Space wrap>{Object.entries(config.roleOptions || {}).map(([role,label]) => <label key={role} className="border rounded px-3 py-2 mb-0"><input type="checkbox" name="target_roles[]" value={role} defaultChecked={selectedRoles.includes(role)} /> <strong>{label}</strong> <Text type="secondary">({role})</Text></label>)}</Space></div> : <div className="mt-3"><Input.Search placeholder="Tìm tên, username hoặc email" value={search} onChange={(event)=>setSearch(event.target.value)} className="mb-3" /><div style={{maxHeight:360,overflowY:'auto'}}>{filteredUsers.map((user)=><label key={user.id} className="border rounded px-3 py-2 d-block mb-2"><input type="checkbox" name="target_user_ids[]" value={user.id} defaultChecked={selectedUsers.includes(String(user.id))} /> <strong>{user.full_name || user.username}</strong> <Text type="secondary"> @{user.username} · {user.role}{user.email ? ` · ${user.email}` : ''}</Text></label>)}</div></div>}
                    {fieldError(config.form, targetType === 'roles' ? 'target_roles' : 'target_user_ids') && <Text type="danger" className="d-block mt-2">{fieldError(config.form, targetType === 'roles' ? 'target_roles' : 'target_user_ids')}</Text>}
                </Card>

                <div className="row mb-3"><div className="col-md-5"><label className="form-label-modern">Nhãn nút hành động</label><Input name="action_text" defaultValue={oldValue(config.form,'action_text',announcement.action_text || '')} /></div><div className="col-md-7"><label className="form-label-modern">Liên kết</label><Input name="action_url" defaultValue={oldValue(config.form,'action_url',announcement.action_url || '')} placeholder="/admin/... hoặc https://..." /></div></div>
                {editing && announcement.image_path && <div className="mb-3"><img src={`${String(config.storageBaseUrl || '/storage').replace(/\/$/,'')}/${announcement.image_path}`} alt="Thông báo" style={{maxHeight:220,maxWidth:'100%'}} /><div><label><input type="checkbox" name="remove_image" value="1" /> Xóa ảnh hiện tại</label></div></div>}
                <div className="mb-3"><label className="form-label-modern">Ảnh / screenshot</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" className="form-control" /></div>
                <input type="hidden" name="is_active" value="0" /><label className="d-block mb-3"><input type="checkbox" name="is_active" value="1" defaultChecked={Boolean(oldValue(config.form,'is_active',values.is_active ?? true))} /> Kích hoạt thông báo</label>
                {editing && <><input type="hidden" name="require_reacknowledgement" value="0" /><label className="d-block mb-3"><input type="checkbox" name="require_reacknowledgement" value="1" defaultChecked={Boolean(oldValue(config.form,'require_reacknowledgement',false))} /> Yêu cầu người dùng xác nhận lại (tăng version)</label></>}
                <div className="d-flex justify-content-end gap-2"><Button href={config.routes.index}>Hủy</Button><Button type="primary" htmlType="submit">{editing ? 'Cập nhật thông báo' : 'Tạo thông báo'}</Button></div>
            </LaravelForm>
        </Card>
    </div>;
}
