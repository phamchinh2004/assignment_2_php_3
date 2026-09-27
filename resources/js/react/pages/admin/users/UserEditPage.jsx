import { useRef, useState } from 'react';
import { Button, Card, Checkbox, Col, Descriptions, Input, InputNumber, Modal, Row, Select, Space, Tag, Typography } from 'antd';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';

const { Text, Title } = Typography;

function dateTime(value) {
    if (!value) return 'Chưa ghi nhận';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString('vi-VN');
}

export default function UserEditPage({ config }) {
    const user = config.user || {};
    const form = config.form || {};
    const permissions = config.permissions || {};
    const updateFormRef = useRef(null);
    const deleteLocationRef = useRef(null);
    const [rank, setRank] = useState(String(oldValue(form, 'rank', user.rank_id || '')));
    const [status, setStatus] = useState(String(oldValue(form, 'status', user.status || 'activated')));
    const [referrer, setReferrer] = useState(String(oldValue(form, 'referrer_id', user.referrer_id || '')));
    const [role, setRole] = useState(String(oldValue(form, 'role', user.role || 'member')));
    const [bankName, setBankName] = useState(String(oldValue(form, 'bank_name', user.bank_name || '')));
    const [cloneAccount, setCloneAccount] = useState(Boolean(Number(oldValue(form, 'clone_account', user.clone_account ? 1 : 0))));
    const [resetProgress, setResetProgress] = useState(false);

    const bankOptions = Object.entries(config.banks || {}).map(([label, banks]) => ({
        label,
        options: banks.map((bank) => ({ label: bank, value: bank })),
    }));

    const submitWithConfirmation = () => {
        Modal.confirm({
            title: 'Lưu thay đổi?',
            content: 'Thông tin tài khoản sẽ được cập nhật ngay sau khi xác nhận.',
            okText: 'Lưu',
            cancelText: 'Hủy',
            onOk: () => updateFormRef.current?.requestSubmit(),
        });
    };

    const hasLocation = Boolean(
        user.location_country ||
        user.location_country_code ||
        user.location_latitude !== null && user.location_latitude !== undefined ||
        user.approx_location_country ||
        user.approx_location_country_code
    );

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <div>
                <Title level={2} style={{ marginBottom: 4 }}>Chỉnh sửa người dùng #{user.id}</Title>
                <Text type="secondary">@{user.username}</Text>
            </div>

            <Row gutter={[20, 20]}>
                <Col xs={24} xl={16}>
                    <Card>
                        <LaravelForm ref={updateFormRef} action={config.routes.update} method="PUT">
                            <Row gutter={[16, 16]}>
                                <Col xs={24} md={12}>
                                    <Text strong>Họ và tên</Text>
                                    <Input name="full_name" defaultValue={oldValue(form, 'full_name', user.full_name)} status={fieldError(form, 'full_name') ? 'error' : ''} />
                                    {fieldError(form, 'full_name') && <Text type="danger">{fieldError(form, 'full_name')}</Text>}
                                </Col>
                                <Col xs={24} md={12}>
                                    <Text strong>Tên đăng nhập</Text>
                                    <Input name="username" defaultValue={oldValue(form, 'username', user.username)} status={fieldError(form, 'username') ? 'error' : ''} />
                                    {fieldError(form, 'username') && <Text type="danger">{fieldError(form, 'username')}</Text>}
                                </Col>
                                <Col xs={24} md={12}>
                                    <Text strong>Email</Text>
                                    <Input name="email" defaultValue={oldValue(form, 'email', user.email)} status={fieldError(form, 'email') ? 'error' : ''} />
                                    {fieldError(form, 'email') && <Text type="danger">{fieldError(form, 'email')}</Text>}
                                </Col>
                                <Col xs={24} md={12}>
                                    <Text strong>Số điện thoại</Text>
                                    <Input name="phone" defaultValue={oldValue(form, 'phone', user.phone)} status={fieldError(form, 'phone') ? 'error' : ''} />
                                    {fieldError(form, 'phone') && <Text type="danger">{fieldError(form, 'phone')}</Text>}
                                </Col>
                                <Col xs={24} md={12}>
                                    <Text strong>Khu vực kho</Text>
                                    <Input name="warehouse_area" defaultValue={oldValue(form, 'warehouse_area', user.warehouse_area)} />
                                </Col>
                                <Col xs={24} md={12}>
                                    <Text strong>Địa chỉ kho</Text>
                                    <Input name="warehouse_address" defaultValue={oldValue(form, 'warehouse_address', user.warehouse_address)} />
                                </Col>
                                {permissions.changeReferrer && (
                                    <Col xs={24}>
                                        <Text strong>Người quản lý / giới thiệu</Text>
                                        <Select
                                            allowClear
                                            showSearch
                                            optionFilterProp="label"
                                            style={{ width: '100%' }}
                                            value={referrer || undefined}
                                            onChange={(value) => setReferrer(value ? String(value) : '')}
                                            options={(config.referrerCandidates || []).map((item) => ({
                                                value: String(item.id),
                                                label: `${item.full_name || item.username} (@${item.username}) - ${item.role}`,
                                            }))}
                                        />
                                        <input type="hidden" name="referrer_id" value={referrer} />
                                    </Col>
                                )}
                            </Row>

                            <Title level={4} style={{ marginTop: 28 }}>Ngân hàng</Title>
                            <Row gutter={[16, 16]}>
                                <Col xs={24}>
                                    <Text strong>Tên tài khoản ngân hàng</Text>
                                    <Input name="username_bank" defaultValue={oldValue(form, 'username_bank', user.username_bank)} />
                                </Col>
                                <Col xs={24} md={12}>
                                    <Text strong>Ngân hàng</Text>
                                    <Select
                                        allowClear
                                        showSearch
                                        optionFilterProp="label"
                                        style={{ width: '100%' }}
                                        value={bankName || undefined}
                                        options={bankOptions}
                                        onChange={(value) => setBankName(value || '')}
                                    />
                                    <input type="hidden" name="bank_name" value={bankName} />
                                </Col>
                                <Col xs={24} md={12}>
                                    <Text strong>Số tài khoản</Text>
                                    <Input name="account_number" defaultValue={oldValue(form, 'account_number', user.account_number)} />
                                </Col>
                            </Row>

                            <Title level={4} style={{ marginTop: 28 }}>Cài đặt tài khoản</Title>
                            <Row gutter={[16, 16]}>
                                {permissions.adjustBalance && (
                                    <>
                                        <Col xs={24} md={12}>
                                            <Text strong>Số dư</Text>
                                            <InputNumber name="balance" style={{ width: '100%' }} min={0} defaultValue={oldValue(form, 'balance', user.balance || 0)} />
                                        </Col>
                                        <Col xs={24} md={12}>
                                            <Text strong>Số dư đóng băng</Text>
                                            <InputNumber name="frozen_balance" style={{ width: '100%' }} min={0} step={0.00000001} defaultValue={oldValue(form, 'frozen_balance', user.frozen_balance || 0)} />
                                        </Col>
                                    </>
                                )}
                                {permissions.changeStatus && (
                                    <Col xs={24} md={12}>
                                        <Text strong>Trạng thái</Text>
                                        <Select style={{ width: '100%' }} value={status} onChange={setStatus} options={[
                                            { value: 'activated', label: 'Đã kích hoạt' },
                                            { value: 'inactivated', label: 'Chưa kích hoạt' },
                                            { value: 'banned', label: 'Bị khóa' },
                                        ]} />
                                        <input type="hidden" name="status" value={status} />
                                    </Col>
                                )}
                                {permissions.chooseRole && (
                                    <Col xs={24} md={12}>
                                        <Text strong>Vai trò</Text>
                                        <Select style={{ width: '100%' }} value={role} onChange={setRole} options={[
                                            { value: 'member', label: 'Member' },
                                            { value: 'staff', label: 'Staff' },
                                            { value: 'admin', label: 'Admin' },
                                        ]} />
                                        <input type="hidden" name="role" value={role} />
                                    </Col>
                                )}
                                {permissions.manageSpin && (
                                    <>
                                        <Col xs={24} md={12}>
                                            <Text strong>Cấp độ</Text>
                                            <Select
                                                allowClear
                                                style={{ width: '100%' }}
                                                value={rank || undefined}
                                                onChange={(value) => setRank(value ? String(value) : '')}
                                                options={(config.ranks || []).map((item) => ({
                                                    value: String(item.id),
                                                    label: `${item.name} - ${item.spin_count} đơn hàng`,
                                                }))}
                                            />
                                            <input type="hidden" name="rank" value={rank} />
                                        </Col>
                                        <Col xs={24} md={12}>
                                            <Text strong>Lượt quay may mắn được cấp còn lại</Text>
                                            <InputNumber name="lucky_wheel_bonus_spins" style={{ width: '100%' }} min={0} step={1} defaultValue={oldValue(form, 'lucky_wheel_bonus_spins', user.lucky_wheel_bonus_spins || 0)} />
                                        </Col>
                                    </>
                                )}
                            </Row>
                            <Space direction="vertical" style={{ marginTop: 20 }}>
                                {permissions.manageSpin && (
                                    <Checkbox checked={resetProgress} onChange={(event) => setResetProgress(event.target.checked)}>Làm mới tiến trình vòng quay</Checkbox>
                                )}
                                {resetProgress && <input type="hidden" name="reset_progress" value="1" />}
                                <Checkbox checked={cloneAccount} onChange={(event) => setCloneAccount(event.target.checked)}>Tài khoản clone</Checkbox>
                                {cloneAccount && <input type="hidden" name="clone_account" value="1" />}
                            </Space>
                            <Space style={{ marginTop: 24 }}>
                                <Button href={config.routes.index}>Hủy</Button>
                                <Button type="primary" onClick={submitWithConfirmation}>Lưu thay đổi</Button>
                            </Space>
                        </LaravelForm>
                    </Card>
                </Col>

                <Col xs={24} xl={8}>
                    <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                        <Card title="Vị trí hiện tại">
                            {hasLocation ? (
                                <Descriptions column={1} size="small">
                                    <Descriptions.Item label="Thành phố">{user.location_city || '—'}</Descriptions.Item>
                                    <Descriptions.Item label="Quốc gia">{user.location_country || user.approx_location_country || '—'}</Descriptions.Item>
                                    <Descriptions.Item label="Tọa độ">
                                        {user.location_latitude !== null && user.location_latitude !== undefined
                                            ? `${user.location_latitude}, ${user.location_longitude}`
                                            : '—'}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Độ chính xác">{user.location_accuracy ? `${user.location_accuracy} m` : '—'}</Descriptions.Item>
                                    <Descriptions.Item label="Cập nhật">{dateTime(user.location_updated_at || user.approx_location_updated_at)}</Descriptions.Item>
                                </Descriptions>
                            ) : <Text type="secondary">Chưa có dữ liệu vị trí.</Text>}
                            {permissions.manageLocation && (
                                <Space wrap style={{ marginTop: 16 }}>
                                    <LaravelForm action={config.routes.locationRefresh} method="POST">
                                        <Button htmlType="submit">Cập nhật vị trí tương đối</Button>
                                    </LaravelForm>
                                    <LaravelForm ref={deleteLocationRef} action={config.routes.locationDestroy} method="DELETE">
                                        <Button
                                            danger
                                            disabled={!hasLocation}
                                            onClick={(event) => {
                                                event.preventDefault();
                                                Modal.confirm({
                                                    title: 'Xóa dữ liệu vị trí?',
                                                    content: 'Toàn bộ vị trí hiện tại của người dùng sẽ bị xóa.',
                                                    okButtonProps: { danger: true },
                                                    onOk: () => deleteLocationRef.current?.requestSubmit(),
                                                });
                                            }}
                                        >
                                            Xóa vị trí
                                        </Button>
                                    </LaravelForm>
                                </Space>
                            )}
                            <div style={{ marginTop: 12 }}>
                                <Tag color={user.is_online ? 'success' : 'default'}>{user.is_online ? 'Online' : 'Offline'}</Tag>
                            </div>
                        </Card>
                        <Card title="Thông tin hệ thống">
                            <Descriptions column={1} size="small">
                                <Descriptions.Item label="Mã giới thiệu">{user.referral_code || 'Chưa có'}</Descriptions.Item>
                                <Descriptions.Item label="Người quản lý">{user.referrer ? (user.referrer.full_name || user.referrer.username) : 'Chưa có'}</Descriptions.Item>
                                <Descriptions.Item label="Ngày tạo">{dateTime(user.created_at)}</Descriptions.Item>
                                <Descriptions.Item label="Hoạt động gần nhất">{dateTime(user.last_seen)}</Descriptions.Item>
                                <Descriptions.Item label="IP đăng ký">{user.register_ip || 'Chưa ghi nhận'}</Descriptions.Item>
                            </Descriptions>
                        </Card>
                    </Space>
                </Col>
            </Row>
        </Space>
    );
}
