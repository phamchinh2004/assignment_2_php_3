import { useRef, useState } from 'react';
import { Button, Card, Col, Input, Modal, Row, Select, Space, Typography, message } from 'antd';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import { requestJson } from '../../../lib/http';
import { spaSubmitForm } from '../../../navigation';

const { Text, Title } = Typography;

export default function UserCreatePage({ config }) {
    const form = config.form || {};
    const ranks = config.ranks || [];
    const createFormRef = useRef(null);
    const [rank, setRank] = useState(String(oldValue(form, 'rank', '')));
    const [username, setUsername] = useState(String(oldValue(form, 'username', '')));
    const [phone, setPhone] = useState(String(oldValue(form, 'phone', '')));
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

    const handleSubmit = async (event) => {
        event.preventDefault();

        if (!String(createFormRef.current?.elements?.full_name?.value || '').trim() || !username.trim() || !phone.trim()) {
            message.warning('Vui lòng không để trống họ tên, tên đăng nhập và số điện thoại.');
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
            const [usernameResult, phoneResult] = await Promise.all([
                checkExists(config.routes.checkUsername, 'username', username.trim()),
                checkExists(config.routes.checkPhone, 'phone', phone.trim()),
            ]);

            if (usernameResult?.success === true) {
                message.warning(`Tên đăng nhập "${username}" đã tồn tại, vui lòng chọn tên khác.`);
                setUsername('');
                return;
            }
            if (phoneResult?.success === true) {
                message.warning(`Số điện thoại "${phone}" đã tồn tại, vui lòng chọn số khác.`);
                setPhone('');
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
        <Space direction="vertical" size="large" style={{ width: '100%', maxWidth: 920 }}>
            <div>
                <Title level={2} style={{ marginBottom: 4 }}>Thêm người dùng</Title>
                <Text type="secondary">Tạo tài khoản thành viên mới. Nếu bỏ trống mật khẩu, hệ thống dùng mật khẩu mặc định hiện có.</Text>
            </div>
            <Card>
                <LaravelForm ref={createFormRef} action={config.routes.store} method="POST" onSubmit={handleSubmit}>
                    <Row gutter={[16, 16]}>
                        <Col xs={24} md={12}>
                            <Text strong>Họ và tên</Text>
                            <Input name="full_name" defaultValue={oldValue(form, 'full_name')} status={fieldError(form, 'full_name') ? 'error' : ''} />
                            {fieldError(form, 'full_name') && <Text type="danger">{fieldError(form, 'full_name')}</Text>}
                        </Col>
                        <Col xs={24} md={12}>
                            <Text strong>Tên đăng nhập</Text>
                            <Input name="username" value={username} onChange={(event) => setUsername(event.target.value)} onBlur={handleUsernameBlur} status={fieldError(form, 'username') ? 'error' : ''} />
                            {fieldError(form, 'username') && <Text type="danger">{fieldError(form, 'username')}</Text>}
                        </Col>
                        <Col xs={24} md={12}>
                            <Text strong>Số điện thoại</Text>
                            <Input name="phone" value={phone} onChange={(event) => setPhone(event.target.value)} status={fieldError(form, 'phone') ? 'error' : ''} />
                            {fieldError(form, 'phone') && <Text type="danger">{fieldError(form, 'phone')}</Text>}
                        </Col>
                        <Col xs={24} md={12}>
                            <Text strong>Cấp độ</Text>
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
                        <Col xs={24} md={12}>
                            <Text strong>Mật khẩu</Text>
                            <Input.Password name="password" value={password} onChange={(event) => setPassword(event.target.value)} status={fieldError(form, 'password') ? 'error' : ''} />
                            {fieldError(form, 'password') && <Text type="danger">{fieldError(form, 'password')}</Text>}
                        </Col>
                        <Col xs={24} md={12}>
                            <Text strong>Xác nhận mật khẩu</Text>
                            <Input.Password name="password_confirmation" value={passwordConfirmation} onChange={(event) => setPasswordConfirmation(event.target.value)} />
                        </Col>
                    </Row>
                    <Space style={{ marginTop: 24 }}>
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" htmlType="submit">Tạo tài khoản</Button>
                    </Space>
                </LaravelForm>
            </Card>
        </Space>
    );
}
