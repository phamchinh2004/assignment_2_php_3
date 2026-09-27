import { Button, Card, Form, Input, Select, Space, Typography } from 'antd';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';

const { Text } = Typography;

export default function StaffForm({ config, mode }) {
    const staff = config.staff || {};
    const form = config.form || {};
    const editing = mode === 'edit';
    const action = editing ? config.routes.update : config.routes.store;

    return (
        <LaravelForm action={action} method={editing ? 'PUT' : 'POST'}>
            <Card>
                {editing && (
                    <Form.Item
                        label="Họ và tên"
                        validateStatus={fieldError(form, 'full_name') ? 'error' : ''}
                        help={fieldError(form, 'full_name') || null}
                    >
                        <Input name="full_name" defaultValue={oldValue(form, 'full_name', staff.full_name || '')} required />
                    </Form.Item>
                )}

                <Form.Item
                    label="Tên đăng nhập"
                    validateStatus={fieldError(form, 'username') ? 'error' : ''}
                    help={fieldError(form, 'username') || null}
                    extra={!editing ? 'Tối thiểu 6 ký tự.' : null}
                >
                    <Input name="username" defaultValue={oldValue(form, 'username', staff.username || '')} required minLength={6} />
                </Form.Item>

                <Form.Item
                    label="Email"
                    validateStatus={fieldError(form, 'email') ? 'error' : ''}
                    help={fieldError(form, 'email') || null}
                >
                    <Input type="email" name="email" defaultValue={oldValue(form, 'email', staff.email || '')} required />
                </Form.Item>

                {!editing && (
                    <Form.Item
                        label="Mật khẩu"
                        validateStatus={fieldError(form, 'password') ? 'error' : ''}
                        help={fieldError(form, 'password') || null}
                        extra="Để trống sẽ dùng mật khẩu mặc định 123456 như luồng hiện tại."
                    >
                        <Input.Password name="password" defaultValue={oldValue(form, 'password', '')} minLength={6} />
                    </Form.Item>
                )}

                {config.canChooseRole && (
                    <Form.Item
                        label="Vai trò"
                        validateStatus={fieldError(form, 'role') ? 'error' : ''}
                        help={fieldError(form, 'role') || null}
                    >
                        <Select
                            defaultValue={oldValue(form, 'role', staff.role || 'staff')}
                            options={[{ value: 'staff', label: 'Staff' }, { value: 'admin', label: 'Admin' }]}
                            onChange={(value) => {
                                const field = document.getElementById('staff-role-field');
                                if (field) field.value = value;
                            }}
                        />
                        <input id="staff-role-field" type="hidden" name="role" defaultValue={oldValue(form, 'role', staff.role || 'staff')} />
                    </Form.Item>
                )}

                {!config.canChooseRole && !editing && <Text type="secondary">Tài khoản mới sẽ được tạo với vai trò Staff.</Text>}

                <Space style={{ marginTop: 16 }}>
                    <Button type="primary" htmlType="submit">{editing ? 'Cập nhật' : 'Tạo tài khoản'}</Button>
                    <Button href={config.routes.index}>Hủy</Button>
                </Space>
            </Card>
        </LaravelForm>
    );
}
