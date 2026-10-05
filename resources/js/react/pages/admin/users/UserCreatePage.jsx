import { useRef, useState } from 'react';
import { Alert, Button, Col, Input, Modal, Row, Select, Typography, message } from 'antd';
import { UserAddOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import { requestJson } from '../../../lib/http';
import { spaSubmitForm } from '../../../navigation';
import { AdminFormActions, AdminFormSection, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';
import { PasswordVisibilityIcon } from '../../../components/admin/AdminMorphIcon';

const { Text } = Typography;

export default function UserCreatePage({ config }) {
    const form = config.form || {};
    const ranks = config.ranks || [];
    const createFormRef = useRef(null);
    const [rank, setRank] = useState(String(oldValue(form, 'rank', '')));
    const [username, setUsername] = useState(String(oldValue(form, 'username', '')));
    const [email, setEmail] = useState(String(oldValue(form, 'email', '')));
    const [password, setPassword] = useState(String(oldValue(form, 'password', '123456')));
    const [passwordConfirmation, setPasswordConfirmation] = useState(String(oldValue(form, 'password_confirmation', '123456')));

    const checkExists = async (url, field, value) => {
        const body = new FormData();
        body.append(field, value);
        return requestJson(url, { method: 'POST', body });
    };

    const handleUsernameBlur = async () => {
        if (!username.trim()) return;
        try {
            const result = await checkExists(config.routes.checkUsername, 'username', username.trim());
            if (result?.success === true) {
                message.warning(`Tên đăng nhập "${username}" đã tồn tại, vui lòng chọn tên khác.`);
                setUsername('');
            }
        } catch (error) {
            message.error(error.message || 'Không thể kiểm tra tên đăng nhập.');
        }
    };

    const handleEmailBlur = async () => {
        if (!email.trim()) return;
        try {
            const result = await checkExists(config.routes.checkEmail, 'email', email.trim());
            if (result?.success === true) {
                message.warning(`Email "${email}" đã tồn tại, vui lòng dùng email khác.`);
                setEmail('');
            }
        } catch (error) {
            message.error(error.message || 'Không thể kiểm tra email.');
        }
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        if (!String(createFormRef.current?.elements?.full_name?.value || '').trim() || !username.trim() || !email.trim()) {
            message.warning('Vui lòng không để trống họ tên, tên đăng nhập và email.');
            return;
        }
        if (!rank) {
            message.warning('Vui lòng chọn cấp độ thành viên.');
            return;
        }
        if (password !== passwordConfirmation) {
            message.warning('Mật khẩu xác nhận không khớp.');
            return;
        }

        try {
            const [usernameResult, emailResult] = await Promise.all([
                checkExists(config.routes.checkUsername, 'username', username.trim()),
                checkExists(config.routes.checkEmail, 'email', email.trim()),
            ]);

            if (usernameResult?.success === true) {
                message.warning(`Tên đăng nhập "${username}" đã tồn tại, vui lòng chọn tên khác.`);
                setUsername('');
                return;
            }
            if (emailResult?.success === true) {
                message.warning(`Email "${email}" đã tồn tại, vui lòng chọn email khác.`);
                setEmail('');
                return;
            }
        } catch (error) {
            message.error(error.message || 'Không thể kiểm tra dữ liệu tài khoản.');
            return;
        }

        Modal.confirm({
            title: 'Xác nhận tạo tài khoản?',
            content: 'Tài khoản người dùng sẽ được tạo ngay sau khi xác nhận.',
            okText: 'Tạo tài khoản',
            cancelText: 'Hủy',
            onOk: async () => {
                if (!createFormRef.current) return;
                try {
                    await spaSubmitForm(createFormRef.current);
                } catch (error) {
                    console.error('Unable to create user.', error);
                    message.error('Không thể tạo tài khoản. Vui lòng thử lại.');
                    throw error;
                }
            },
        });
    };

    return (
        <AdminPage width="form">
            <AdminPageHeader eyebrow="Khách hàng" icon={<UserAddOutlined />} title="Thêm người dùng" description="Tạo tài khoản thành viên, gán cấp độ ban đầu và kiểm tra trùng username/email trước khi lưu." backHref={config.routes.index} backLabel="Danh sách người dùng" />
            <AdminSectionCard title="Thông tin tài khoản" description="Nhập dữ liệu nhận diện và thông tin đăng nhập cần thiết cho tài khoản mới.">
                <LaravelForm ref={createFormRef} action={config.routes.store} method="POST" onSubmit={handleSubmit}>
                    <AdminFormSection title="Thông tin thành viên" description="Các trường này dùng để nhận diện tài khoản và xác định cấp độ vận hành.">
                    <Row gutter={[16, 16]}>
                        <Col xs={24} md={12}>
                            <label className="admin-field-label" htmlFor="create-user-full-name">Họ và tên</label>
                            <Input id="create-user-full-name" name="full_name" autoComplete="name" placeholder="Nhập họ và tên" defaultValue={oldValue(form, 'full_name')} status={fieldError(form, 'full_name') ? 'error' : ''} />
                            {fieldError(form, 'full_name') && <Text type="danger">{fieldError(form, 'full_name')}</Text>}
                        </Col>
                        <Col xs={24} md={12}>
                            <label className="admin-field-label" htmlFor="create-user-username">Tên đăng nhập</label>
                            <Input id="create-user-username" name="username" autoComplete="off" placeholder="Nhập tên đăng nhập" value={username} onChange={(event) => setUsername(event.target.value)} onBlur={handleUsernameBlur} status={fieldError(form, 'username') ? 'error' : ''} />
                            <span className="admin-field-help">Tối thiểu 6 ký tự và không được trùng tài khoản khác.</span>
                            {fieldError(form, 'username') && <Text type="danger">{fieldError(form, 'username')}</Text>}
                        </Col>
                        <Col xs={24} md={12}>
                            <label className="admin-field-label" htmlFor="create-user-email">Email</label>
                            <Input id="create-user-email" name="email" type="email" autoComplete="email" placeholder="Nhập email" value={email} onChange={(event) => setEmail(event.target.value)} onBlur={handleEmailBlur} status={fieldError(form, 'email') ? 'error' : ''} />
                            {fieldError(form, 'email') && <Text type="danger">{fieldError(form, 'email')}</Text>}
                        </Col>
                        <Col xs={24} md={12}>
                            <label className="admin-field-label">Cấp độ</label>
                            <Select
                                style={{ width: '100%' }}
                                value={rank || undefined}
                                onChange={(value) => setRank(String(value))}
                                placeholder="Chọn cấp độ thành viên"
                                options={ranks.map((item) => ({ value: String(item.id), label: `${item.name} - ${item.spin_count} đơn hàng` }))}
                            />
                            <input type="hidden" name="rank" value={rank} />
                            {fieldError(form, 'rank') && <Text type="danger">{fieldError(form, 'rank')}</Text>}
                        </Col>
                    </Row>
                    </AdminFormSection>

                    <AdminFormSection title="Mật khẩu đăng nhập" description="Nhập cùng một mật khẩu ở hai ô. Hệ thống hiện yêu cầu tối thiểu 6 ký tự khi có nhập mật khẩu.">
                        <Alert type="warning" showIcon message="Mật khẩu mặc định hiện tại là 123456. Nên thay bằng mật khẩu riêng trước khi tạo tài khoản." style={{ marginBottom: 16 }} />
                        <Row gutter={[16, 16]}>
                        <Col xs={24} md={12}>
                            <label className="admin-field-label" htmlFor="create-user-password">Mật khẩu</label>
                            <Input.Password id="create-user-password" name="password" autoComplete="new-password" placeholder="Nhập mật khẩu" value={password} onChange={(event) => setPassword(event.target.value)} status={fieldError(form, 'password') ? 'error' : ''} iconRender={(visible) => <PasswordVisibilityIcon visible={visible} />} />
                            {fieldError(form, 'password') && <Text type="danger">{fieldError(form, 'password')}</Text>}
                        </Col>
                        <Col xs={24} md={12}>
                            <label className="admin-field-label" htmlFor="create-user-password-confirmation">Xác nhận mật khẩu</label>
                            <Input.Password id="create-user-password-confirmation" name="password_confirmation" autoComplete="new-password" placeholder="Nhập lại mật khẩu" value={passwordConfirmation} onChange={(event) => setPasswordConfirmation(event.target.value)} iconRender={(visible) => <PasswordVisibilityIcon visible={visible} />} />
                        </Col>
                    </Row>
                    </AdminFormSection>
                    <AdminFormActions>
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" htmlType="submit">Tạo tài khoản</Button>
                    </AdminFormActions>
                </LaravelForm>
            </AdminSectionCard>
        </AdminPage>
    );
}
