import { Button, Card, Input, Radio, Space, Typography } from 'antd';
import { NotificationOutlined } from '@ant-design/icons';
import { useMemo, useState } from 'react';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import { AdminFormActions, AdminFormSection, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';
import { AdminDateTimePicker } from '../../../components/admin/AdminDatePicker';

const { Text } = Typography;

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
    const [startsAt, setStartsAt] = useState(() => String(oldValue(config.form, 'starts_at', values.starts_at || '') || ''));
    const [endsAt, setEndsAt] = useState(() => String(oldValue(config.form, 'ends_at', values.ends_at || '') || ''));
    const selectedRoles = formArray(config, 'target_roles', values.target_roles);
    const selectedUsers = formArray(config, 'target_user_ids', values.target_user_ids).map(String);
    const filteredUsers = useMemo(() => (config.targetUsers || []).filter((user) => {
        const haystack = [user.full_name, user.username, user.email, user.role].filter(Boolean).join(' ').toLocaleLowerCase('vi');
        return !search.trim() || haystack.includes(search.trim().toLocaleLowerCase('vi'));
    }), [config.targetUsers, search]);

    return <AdminPage width="form">
        <AdminPageHeader
            eyebrow="Truyền thông nội bộ"
            icon={<NotificationOutlined />}
            title={editing ? 'Chỉnh sửa thông báo tính năng' : 'Tạo thông báo tính năng'}
            description="Nội dung chỉ nên xuất hiện khi quản trị viên thực sự cần biết hoặc cần xác nhận một thay đổi."
            backHref={config.routes.index}
            backLabel="Danh sách thông báo"
            meta={editing ? <Text code>Version {announcement.version}</Text> : null}
        />
        <AdminSectionCard title="Cấu hình thông báo" description="Thiết lập nội dung, phạm vi người nhận và vòng đời hiển thị.">
            <LaravelForm action={editing ? config.routes.update : config.routes.store} method={editing ? 'PUT' : 'POST'} encType="multipart/form-data">
                <AdminFormSection title="Nội dung" description="Tiêu đề cần nói rõ thay đổi; phần mô tả nên cho người nhận biết họ cần làm gì.">
                    <div className="mb-3"><label className="form-label-modern">Tiêu đề *</label><Input name="title" defaultValue={oldValue(config.form,'title',announcement.title || '')} maxLength={180} placeholder="Ví dụ: Cập nhật quy trình xử lý đơn" required />{fieldError(config.form,'title') && <Text type="danger" className="d-block mt-1">{fieldError(config.form,'title')}</Text>}</div>
                    <div><label className="form-label-modern">Nội dung *</label><Input.TextArea name="content" rows={7} defaultValue={oldValue(config.form,'content',announcement.content || '')} placeholder="Mô tả thay đổi và việc quản trị viên cần thực hiện..." required />{fieldError(config.form,'content') && <Text type="danger" className="d-block mt-1">{fieldError(config.form,'content')}</Text>}</div>
                </AdminFormSection>

                <AdminFormSection title="Thời gian hiển thị" description="Thông báo sẽ chỉ xuất hiện trong khoảng thời gian này khi đang được kích hoạt.">
                    <div className="row">
                        <div className="col-md-4 mb-3 mb-md-0"><label className="form-label-modern">Mức độ ưu tiên</label><select name="priority" className="form-control" defaultValue={oldValue(config.form,'priority',values.priority || 'normal')}><option value="normal">Bình thường</option><option value="important">Quan trọng</option><option value="critical">Khẩn cấp</option></select></div>
                        <div className="col-md-4 mb-3 mb-md-0"><label className="form-label-modern">Bắt đầu</label><AdminDateTimePicker value={startsAt} onChange={setStartsAt} status={fieldError(config.form,'starts_at') ? 'error' : undefined} /><input type="hidden" name="starts_at" value={startsAt} />{fieldError(config.form,'starts_at') && <Text type="danger" className="d-block mt-1">{fieldError(config.form,'starts_at')}</Text>}</div>
                        <div className="col-md-4"><label className="form-label-modern">Kết thúc</label><AdminDateTimePicker value={endsAt} onChange={setEndsAt} status={fieldError(config.form,'ends_at') ? 'error' : undefined} /><input type="hidden" name="ends_at" value={endsAt} /><span className="admin-field-help">Để trống nếu không muốn tự hết hạn.</span>{fieldError(config.form,'ends_at') && <Text type="danger" className="d-block mt-1">{fieldError(config.form,'ends_at')}</Text>}</div>
                    </div>
                </AdminFormSection>

                <AdminFormSection title="Đối tượng nhận" description="Chọn theo vai trò cho thông báo diện rộng, hoặc chọn tài khoản cụ thể cho thay đổi có phạm vi hẹp.">
                    <Radio.Group value={targetType} onChange={(event) => setTargetType(event.target.value)}>
                        <Radio value="roles">Theo vai trò</Radio><Radio value="users">Chọn người cụ thể</Radio>
                    </Radio.Group>
                    <input type="hidden" name="target_type" value={targetType} />
                    {targetType === 'roles' ? <div className="mt-3"><Space wrap>{Object.entries(config.roleOptions || {}).map(([role,label]) => <label key={role} className="border rounded px-3 py-2 mb-0"><input type="checkbox" name="target_roles[]" value={role} defaultChecked={selectedRoles.includes(role)} /> <strong>{label}</strong> <Text type="secondary">({role})</Text></label>)}</Space></div> : <div className="mt-3"><Input.Search placeholder="Tìm tên, username hoặc email" value={search} onChange={(event)=>setSearch(event.target.value)} className="mb-3" /><div style={{maxHeight:360,overflowY:'auto'}}>{filteredUsers.map((user)=><label key={user.id} className="border rounded px-3 py-2 d-block mb-2"><input type="checkbox" name="target_user_ids[]" value={user.id} defaultChecked={selectedUsers.includes(String(user.id))} /> <strong>{user.full_name || user.username}</strong> <Text type="secondary"> @{user.username} · {user.role}{user.email ? ` · ${user.email}` : ''}</Text></label>)}</div></div>}
                    {fieldError(config.form, targetType === 'roles' ? 'target_roles' : 'target_user_ids') && <Text type="danger" className="d-block mt-2">{fieldError(config.form, targetType === 'roles' ? 'target_roles' : 'target_user_ids')}</Text>}
                </AdminFormSection>

                <AdminFormSection title="Hành động & minh họa" description="Chỉ thêm nút hoặc ảnh khi chúng giúp người nhận hiểu hoặc thực hiện thay đổi nhanh hơn.">
                    <div className="row mb-3"><div className="col-md-5 mb-3 mb-md-0"><label className="form-label-modern">Nhãn nút hành động</label><Input name="action_text" defaultValue={oldValue(config.form,'action_text',announcement.action_text || '')} placeholder="Ví dụ: Xem chi tiết" /></div><div className="col-md-7"><label className="form-label-modern">Liên kết</label><Input name="action_url" defaultValue={oldValue(config.form,'action_url',announcement.action_url || '')} placeholder="/admin/... hoặc https://..." /></div></div>
                    {editing && announcement.image_path && <div className="mb-3"><img src={`${String(config.storageBaseUrl || '/storage').replace(/\/$/,'')}/${announcement.image_path}`} alt="Ảnh minh họa thông báo" style={{maxHeight:220,maxWidth:'100%',borderRadius:10}} /><div className="mt-2"><label><input type="checkbox" name="remove_image" value="1" /> Xóa ảnh hiện tại</label></div></div>}
                    <div><label className="form-label-modern">Ảnh / screenshot</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" className="form-control" /></div>
                </AdminFormSection>

                <AdminFormSection title="Phát hành" description="Có thể lưu thông báo ở trạng thái tắt để chuẩn bị nội dung trước khi phát hành.">
                    <input type="hidden" name="is_active" value="0" /><label className="d-block mb-2"><input type="checkbox" name="is_active" value="1" defaultChecked={Boolean(oldValue(config.form,'is_active',values.is_active ?? true))} /> Kích hoạt thông báo</label>
                    {editing && <><input type="hidden" name="require_reacknowledgement" value="0" /><label className="d-block mb-0"><input type="checkbox" name="require_reacknowledgement" value="1" defaultChecked={Boolean(oldValue(config.form,'require_reacknowledgement',false))} /> Yêu cầu người dùng xác nhận lại sau lần cập nhật này (tăng version)</label></>}
                </AdminFormSection>
                <AdminFormActions><Button href={config.routes.index}>Hủy</Button><Button type="primary" htmlType="submit">{editing ? 'Cập nhật thông báo' : 'Tạo thông báo'}</Button></AdminFormActions>
            </LaravelForm>
        </AdminSectionCard>
    </AdminPage>;
}
