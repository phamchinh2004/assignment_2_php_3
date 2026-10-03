import { Button, Form, Input, Radio, Select, Typography } from 'antd';
import {
    CheckCircleOutlined,
    KeyOutlined,
    LockOutlined,
    MailOutlined,
    SafetyCertificateOutlined,
    UserOutlined,
    UserSwitchOutlined,
} from '@ant-design/icons';
import { useState } from 'react';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import { AdminFormActions, AdminFormSection, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;

export default function StaffForm({ config, mode }) {
    const staff = config.staff || {};
    const form = config.form || {};
    const editing = mode === 'edit';
    const action = editing ? config.routes.update : config.routes.store;
    const [createRole, setCreateRole] = useState(() => oldValue(form, 'role', 'staff'));
    const [editRole, setEditRole] = useState(() => oldValue(form, 'role', staff.role || 'staff'));
    const [managerId, setManagerId] = useState(() => String(oldValue(
        form,
        'manager_id',
        editing ? (staff.referrer_id ?? '') : (config.managerCandidates?.[0]?.id || ''),
    ) ?? ''));

    if (!editing) {
        const usernameError = fieldError(form, 'username');
        const emailError = fieldError(form, 'email');
        const passwordError = fieldError(form, 'password');
        const roleError = fieldError(form, 'role');

        return (
            <LaravelForm action={action} method="POST" className="staff-create-form">
                <div className="staff-create-layout">
                    <div className="staff-create-main">
                        <section className="staff-create-panel">
                            <div className="staff-create-panel__heading">
                                <span className="staff-create-panel__icon" aria-hidden="true"><UserOutlined /></span>
                                <div>
                                    <span className="staff-create-step">01 · Thông tin tài khoản</span>
                                    <h2>Thông tin đăng nhập</h2>
                                    <p>Nhập thông tin dùng để nhận diện và đăng nhập vào hệ thống quản trị.</p>
                                </div>
                            </div>

                            <div className="staff-create-fields staff-create-fields--two">
                                <div className="staff-create-field">
                                    <label className="staff-create-label" htmlFor="staff-create-username">
                                        Tên đăng nhập <span aria-hidden="true">*</span>
                                    </label>
                                    <Input
                                        id="staff-create-username"
                                        name="username"
                                        autoComplete="off"
                                        prefix={<UserOutlined aria-hidden="true" />}
                                        placeholder="Ví dụ: nguyenvana"
                                        defaultValue={oldValue(form, 'username', '')}
                                        minLength={6}
                                        required
                                        status={usernameError ? 'error' : ''}
                                        aria-invalid={Boolean(usernameError)}
                                    />
                                    {usernameError
                                        ? <Text type="danger" className="staff-create-error">{usernameError}</Text>
                                        : <span className="staff-create-help">Tối thiểu 6 ký tự và không được trùng tài khoản khác.</span>}
                                </div>

                                <div className="staff-create-field">
                                    <label className="staff-create-label" htmlFor="staff-create-email">
                                        Email <span aria-hidden="true">*</span>
                                    </label>
                                    <Input
                                        id="staff-create-email"
                                        type="email"
                                        name="email"
                                        autoComplete="email"
                                        prefix={<MailOutlined aria-hidden="true" />}
                                        placeholder="name@company.com"
                                        defaultValue={oldValue(form, 'email', '')}
                                        required
                                        status={emailError ? 'error' : ''}
                                        aria-invalid={Boolean(emailError)}
                                    />
                                    {emailError
                                        ? <Text type="danger" className="staff-create-error">{emailError}</Text>
                                        : <span className="staff-create-help">Dùng để nhận diện tài khoản và phục vụ liên hệ nội bộ.</span>}
                                </div>
                            </div>
                        </section>

                        <section className="staff-create-panel">
                            <div className="staff-create-panel__heading">
                                <span className="staff-create-panel__icon staff-create-panel__icon--violet" aria-hidden="true"><KeyOutlined /></span>
                                <div>
                                    <span className="staff-create-step">02 · Bảo mật</span>
                                    <h2>Mật khẩu ban đầu</h2>
                                    <p>Thiết lập mật khẩu đăng nhập đầu tiên cho tài khoản mới.</p>
                                </div>
                            </div>

                            <div className="staff-create-field">
                                <label className="staff-create-label" htmlFor="staff-create-password">Mật khẩu</label>
                                <Input.Password
                                    id="staff-create-password"
                                    name="password"
                                    autoComplete="new-password"
                                    prefix={<LockOutlined aria-hidden="true" />}
                                    placeholder="Nhập tối thiểu 6 ký tự"
                                    defaultValue={oldValue(form, 'password', '')}
                                    minLength={6}
                                    status={passwordError ? 'error' : ''}
                                    aria-invalid={Boolean(passwordError)}
                                />
                                {passwordError
                                    ? <Text type="danger" className="staff-create-error">{passwordError}</Text>
                                    : <span className="staff-create-help">Có thể để trống; hệ thống sẽ dùng mật khẩu mặc định <strong>123456</strong>.</span>}
                            </div>
                        </section>

                        <section className="staff-create-panel">
                            <div className="staff-create-panel__heading">
                                <span className="staff-create-panel__icon staff-create-panel__icon--amber" aria-hidden="true"><SafetyCertificateOutlined /></span>
                                <div>
                                    <span className="staff-create-step">03 · Phạm vi tài khoản</span>
                                    <h2>Vai trò hệ thống</h2>
                                    <p>Vai trò xác định loại tài khoản; quyền chức năng chi tiết được quản lý riêng sau khi tạo.</p>
                                </div>
                            </div>

                            {config.canChooseRole ? (
                                <>
                                    <Radio.Group
                                        className="staff-role-picker"
                                        value={createRole}
                                        onChange={(event) => setCreateRole(event.target.value)}
                                    >
                                        <Radio.Button value="staff">
                                            <span className="staff-role-option__icon" aria-hidden="true"><UserOutlined /></span>
                                            <span className="staff-role-option__copy">
                                                <strong>Staff</strong>
                                                <small>Nhân viên vận hành, cấp quyền theo chức năng.</small>
                                            </span>
                                            <CheckCircleOutlined className="staff-role-option__check" aria-hidden="true" />
                                        </Radio.Button>
                                        <Radio.Button value="admin">
                                            <span className="staff-role-option__icon staff-role-option__icon--admin" aria-hidden="true"><SafetyCertificateOutlined /></span>
                                            <span className="staff-role-option__copy">
                                                <strong>Admin</strong>
                                                <small>Tài khoản quản trị với phạm vi cao hơn.</small>
                                            </span>
                                            <CheckCircleOutlined className="staff-role-option__check" aria-hidden="true" />
                                        </Radio.Button>
                                    </Radio.Group>
                                    <input type="hidden" name="role" value={createRole} />
                                    {roleError && <Text type="danger" className="staff-create-error">{roleError}</Text>}
                                    {createRole === 'staff' && config.managerCandidates?.length > 0 && (
                                        <div className="staff-create-field" style={{ marginTop: 18 }}>
                                            <label className="staff-create-label">Admin quản lý</label>
                                            <Select
                                                style={{ width: '100%' }}
                                                value={managerId || undefined}
                                                onChange={(value) => setManagerId(String(value))}
                                                options={config.managerCandidates.map((manager) => ({
                                                    value: String(manager.id),
                                                    label: manager.role === 'own'
                                                        ? `${manager.full_name || manager.username} (Chủ hệ thống)`
                                                        : `${manager.full_name || manager.username} (@${manager.username})`,
                                                }))}
                                            />
                                            <input type="hidden" name="manager_id" value={managerId} />
                                            {fieldError(form, 'manager_id') && <Text type="danger" className="staff-create-error">{fieldError(form, 'manager_id')}</Text>}
                                            <span className="staff-create-help">Staff chỉ nằm trong phạm vi dữ liệu của Admin được chọn.</span>
                                        </div>
                                    )}
                                </>
                            ) : (
                                <div className="staff-role-locked">
                                    <span className="staff-role-option__icon" aria-hidden="true"><UserOutlined /></span>
                                    <span>
                                        <strong>Staff</strong>
                                        <small>Tài khoản mới được tạo với vai trò Staff theo quyền hiện tại của bạn.</small>
                                    </span>
                                    <LockOutlined aria-hidden="true" />
                                </div>
                            )}
                        </section>
                    </div>

                    <aside className="staff-create-aside" aria-label="Tóm tắt tài khoản mới">
                        <div className="staff-create-summary">
                            <div className="staff-create-summary__hero">
                                <span className="staff-create-summary__avatar" aria-hidden="true"><UserSwitchOutlined /></span>
                                <div>
                                    <span className="staff-create-summary__eyebrow">Tài khoản mới</span>
                                    <h3>{config.canChooseRole && createRole === 'admin' ? 'Admin' : 'Staff'}</h3>
                                </div>
                            </div>

                            <div className="staff-create-summary__list">
                                <div className="staff-create-summary__item">
                                    <span className="staff-create-summary__status staff-create-summary__status--success" aria-hidden="true"><CheckCircleOutlined /></span>
                                    <div><strong>Kích hoạt ngay</strong><span>Tài khoản có thể đăng nhập sau khi tạo thành công.</span></div>
                                </div>
                                <div className="staff-create-summary__item">
                                    <span className="staff-create-summary__status" aria-hidden="true"><SafetyCertificateOutlined /></span>
                                    <div><strong>Quyền chức năng</strong><span>Được cấu hình riêng tại trang phân quyền nhân viên.</span></div>
                                </div>
                                <div className="staff-create-summary__item">
                                    <span className="staff-create-summary__status" aria-hidden="true"><UserOutlined /></span>
                                    <div><strong>Thông tin hồ sơ</strong><span>Họ tên, số điện thoại và mã giới thiệu được hệ thống tạo tự động.</span></div>
                                </div>
                            </div>

                            <div className="staff-create-summary__note">
                                <LockOutlined aria-hidden="true" />
                                <span>Chỉ các trường cần thiết cho việc tạo tài khoản mới được hiển thị tại đây.</span>
                            </div>
                        </div>
                    </aside>
                </div>

                <div className="staff-create-footer">
                    <div className="staff-create-footer__copy">
                        <strong>Sẵn sàng tạo tài khoản?</strong>
                        <span>Kiểm tra lại username, email và vai trò trước khi lưu.</span>
                    </div>
                    <div className="staff-create-footer__actions">
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" htmlType="submit" icon={<UserSwitchOutlined />}>Tạo tài khoản</Button>
                    </div>
                </div>
            </LaravelForm>
        );
    }

    return (
        <LaravelForm action={action} method={editing ? 'PUT' : 'POST'}>
            <AdminSectionCard title="Thông tin tài khoản" description="Chỉ nhập các thông tin cần thiết để nhận diện và đăng nhập tài khoản nội bộ.">
                <AdminFormSection title="Danh tính & đăng nhập" description="Tên đăng nhập và email được dùng để nhận diện tài khoản trong hệ thống.">
                {editing && (
                    <Form.Item
                        label="Họ và tên"
                        validateStatus={fieldError(form, 'full_name') ? 'error' : ''}
                        help={fieldError(form, 'full_name') || null}
                    >
                        <Input name="full_name" defaultValue={oldValue(form, 'full_name', staff.full_name || '')} placeholder="Nhập họ và tên" required />
                    </Form.Item>
                )}

                <Form.Item
                    label="Tên đăng nhập"
                    validateStatus={fieldError(form, 'username') ? 'error' : ''}
                    help={fieldError(form, 'username') || null}
                    extra={!editing ? 'Tối thiểu 6 ký tự.' : null}
                >
                    <Input name="username" defaultValue={oldValue(form, 'username', staff.username || '')} placeholder="Ví dụ: nguyenvana" required minLength={6} />
                </Form.Item>

                <Form.Item
                    label="Email"
                    validateStatus={fieldError(form, 'email') ? 'error' : ''}
                    help={fieldError(form, 'email') || null}
                >
                    <Input type="email" name="email" defaultValue={oldValue(form, 'email', staff.email || '')} placeholder="name@company.com" required />
                </Form.Item>

                {!editing && (
                    <Form.Item
                        label="Mật khẩu"
                        validateStatus={fieldError(form, 'password') ? 'error' : ''}
                        help={fieldError(form, 'password') || null}
                        extra="Để trống sẽ dùng mật khẩu mặc định 123456 như luồng hiện tại."
                    >
                        <Input.Password name="password" defaultValue={oldValue(form, 'password', '')} placeholder="Nhập tối thiểu 6 ký tự" minLength={6} />
                    </Form.Item>
                )}
                </AdminFormSection>

                <AdminFormSection title="Vai trò" description={config.canChooseRole ? 'Chọn phạm vi vai trò phù hợp. Quyền chức năng chi tiết được quản lý ở màn hình phân quyền.' : 'Tài khoản này không được phép tự chọn vai trò ở màn hình hiện tại.'}>
                {config.canChooseRole && (
                    <Form.Item
                        label="Vai trò"
                        validateStatus={fieldError(form, 'role') ? 'error' : ''}
                        help={fieldError(form, 'role') || null}
                    >
                        <Select
                            placeholder="Chọn vai trò"
                            value={editRole}
                            options={[{ value: 'staff', label: 'Staff' }, { value: 'admin', label: 'Admin' }]}
                            onChange={setEditRole}
                        />
                        <input id="staff-role-field" type="hidden" name="role" value={editRole} readOnly />
                    </Form.Item>
                )}

                {config.canChooseManager && editRole === 'staff' && config.managerCandidates?.length > 0 && (
                    <Form.Item
                        label="Admin quản lý"
                        validateStatus={fieldError(form, 'manager_id') ? 'error' : ''}
                        help={fieldError(form, 'manager_id') || 'Để trống: tất cả Admin và Own đều quản lý được nhân viên này. Khi chọn người quản lý: chỉ Admin được chọn (nếu có) và các tài khoản Own quản lý được.'}
                    >
                        <Select
                            allowClear
                            placeholder="Tất cả Admin và Own"
                            value={managerId || undefined}
                            onChange={(value) => setManagerId(value == null ? '' : String(value))}
                            options={config.managerCandidates.map((manager) => ({
                                value: String(manager.id),
                                label: manager.role === 'own'
                                    ? `${manager.full_name || manager.username} (Chủ hệ thống)`
                                    : `${manager.full_name || manager.username} (@${manager.username})`,
                            }))}
                        />
                        <input type="hidden" name="manager_id" value={managerId} />
                    </Form.Item>
                )}

                {!config.canChooseRole && !editing && <Text type="secondary">Tài khoản mới sẽ được tạo với vai trò Staff.</Text>}
                {!config.canChooseRole && editing && <Text type="secondary">Vai trò hiện tại được giữ nguyên theo quyền backend.</Text>}
                </AdminFormSection>
                <AdminFormActions>
                    <Button href={config.routes.index}>Hủy</Button>
                    <Button type="primary" htmlType="submit">{editing ? 'Cập nhật' : 'Tạo tài khoản'}</Button>
                </AdminFormActions>
            </AdminSectionCard>
        </LaravelForm>
    );
}
