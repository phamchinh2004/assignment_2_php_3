import { useEffect, useRef, useState } from 'react';
import { Button, Checkbox, Col, Descriptions, Input, InputNumber, Modal, Row, Select, Space, Tag, Typography } from 'antd';
import { SaveOutlined, UserOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import { AdminFormActions, AdminFormSection, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;

function FormFieldError({ form, name }) {
    const message = fieldError(form, name);
    if (!message) return null;

    return <Text type="danger" role="alert" style={{ display: 'block', marginTop: 4 }}>{message}</Text>;
}

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

    useEffect(() => {
        const firstErrorName = Object.keys(form.errors || {})[0];
        const formElement = updateFormRef.current;
        if (!firstErrorName || !formElement) return;

        const errorField = Array.from(formElement.querySelectorAll('[data-error-field]'))
            .find((element) => element.dataset.errorField === firstErrorName);
        const fallbackField = formElement.querySelector(`[name="${firstErrorName}"]`);
        const target = errorField || fallbackField;

        if (!target) return;

        window.requestAnimationFrame(() => {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }, [form.errors]);

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
        <AdminPage>
            <AdminPageHeader
                eyebrow="Khách hàng"
                icon={<UserOutlined />}
                title={`Chỉnh sửa người dùng #${user.id}`}
                description={`@${user.username} · Chỉ hiển thị các nhóm nghiệp vụ mà tài khoản quản trị hiện tại được phép thay đổi.`}
                backHref={config.routes.index}
                backLabel="Danh sách người dùng"
                meta={<Tag color={user.is_online ? 'success' : 'default'}>{user.is_online ? 'Online' : 'Offline'}</Tag>}
            />

            <Row gutter={[20, 20]}>
                <Col xs={24} xl={16}>
                    <AdminSectionCard title="Thông tin có thể chỉnh sửa" description="Các trường nhạy cảm chỉ xuất hiện khi quyền backend tương ứng đang được cấp.">
                        <LaravelForm ref={updateFormRef} action={config.routes.update} method="PUT">
                            <AdminFormSection title="Hồ sơ & liên hệ" description="Thông tin nhận diện, liên hệ và địa chỉ kho của thành viên.">
                            <Row gutter={[16, 16]}>
                                <Col xs={24} md={12} data-error-field="full_name">
                                    <Text strong>Họ và tên</Text>
                                    <Input name="full_name" placeholder="Nhập họ và tên" defaultValue={oldValue(form, 'full_name', user.full_name)} status={fieldError(form, 'full_name') ? 'error' : ''} />
                                    <FormFieldError form={form} name="full_name" />
                                </Col>
                                <Col xs={24} md={12} data-error-field="username">
                                    <Text strong>Tên đăng nhập</Text>
                                    <Input name="username" placeholder="Nhập tên đăng nhập" defaultValue={oldValue(form, 'username', user.username)} status={fieldError(form, 'username') ? 'error' : ''} />
                                    <FormFieldError form={form} name="username" />
                                </Col>
                                <Col xs={24} md={12} data-error-field="email">
                                    <Text strong>Email</Text>
                                    <Input name="email" placeholder="Nhập email" defaultValue={oldValue(form, 'email', user.email)} status={fieldError(form, 'email') ? 'error' : ''} />
                                    <FormFieldError form={form} name="email" />
                                </Col>
                                <Col xs={24} md={12} data-error-field="phone">
                                    <Text strong>Số điện thoại</Text>
                                    <Input name="phone" placeholder="Nhập số điện thoại" defaultValue={oldValue(form, 'phone', user.phone)} status={fieldError(form, 'phone') ? 'error' : ''} />
                                    <FormFieldError form={form} name="phone" />
                                </Col>
                                <Col xs={24} md={12} data-error-field="warehouse_area">
                                    <Text strong>Khu vực kho</Text>
                                    <Input name="warehouse_area" placeholder="Nhập khu vực kho" defaultValue={oldValue(form, 'warehouse_area', user.warehouse_area)} status={fieldError(form, 'warehouse_area') ? 'error' : ''} />
                                    <FormFieldError form={form} name="warehouse_area" />
                                </Col>
                                <Col xs={24} md={12} data-error-field="warehouse_address">
                                    <Text strong>Địa chỉ kho</Text>
                                    <Input name="warehouse_address" placeholder="Nhập địa chỉ kho" defaultValue={oldValue(form, 'warehouse_address', user.warehouse_address)} status={fieldError(form, 'warehouse_address') ? 'error' : ''} />
                                    <FormFieldError form={form} name="warehouse_address" />
                                </Col>
                                {permissions.changeReferrer && (
                                    <Col xs={24} data-error-field="referrer_id">
                                        <Text strong>Người quản lý / giới thiệu</Text>
                                        <Select
                                            allowClear
                                            showSearch
                                            optionFilterProp="label"
                                            style={{ width: '100%' }}
                                            status={fieldError(form, 'referrer_id') ? 'error' : ''}
                                            value={referrer || undefined}
                                            placeholder="Chọn người quản lý / giới thiệu"
                                            onChange={(value) => setReferrer(value ? String(value) : '')}
                                            options={(config.referrerCandidates || []).map((item) => ({
                                                value: String(item.id),
                                                label: `${item.full_name || item.username} (@${item.username}) - ${item.role}`,
                                            }))}
                                        />
                                        <input type="hidden" name="referrer_id" value={referrer} />
                                        <FormFieldError form={form} name="referrer_id" />
                                    </Col>
                                )}
                            </Row>
                            </AdminFormSection>

                            <AdminFormSection title="Thông tin ngân hàng" description="Dùng cho các nghiệp vụ rút tiền và đối soát tài khoản.">
                            <Row gutter={[16, 16]}>
                                <Col xs={24} data-error-field="username_bank">
                                    <Text strong>Tên tài khoản ngân hàng</Text>
                                    <Input name="username_bank" placeholder="Nhập tên chủ tài khoản" defaultValue={oldValue(form, 'username_bank', user.username_bank)} status={fieldError(form, 'username_bank') ? 'error' : ''} />
                                    <FormFieldError form={form} name="username_bank" />
                                </Col>
                                <Col xs={24} md={12} data-error-field="bank_name">
                                    <Text strong>Ngân hàng</Text>
                                    <Select
                                        allowClear
                                        showSearch
                                        optionFilterProp="label"
                                        style={{ width: '100%' }}
                                        status={fieldError(form, 'bank_name') ? 'error' : ''}
                                        value={bankName || undefined}
                                        placeholder="Chọn ngân hàng"
                                        options={bankOptions}
                                        onChange={(value) => setBankName(value || '')}
                                    />
                                    <input type="hidden" name="bank_name" value={bankName} />
                                    <FormFieldError form={form} name="bank_name" />
                                </Col>
                                <Col xs={24} md={12} data-error-field="account_number">
                                    <Text strong>Số tài khoản</Text>
                                    <Input name="account_number" placeholder="Nhập số tài khoản" defaultValue={oldValue(form, 'account_number', user.account_number)} status={fieldError(form, 'account_number') ? 'error' : ''} />
                                    <FormFieldError form={form} name="account_number" />
                                </Col>
                            </Row>
                            </AdminFormSection>

                            <AdminFormSection title="Cài đặt vận hành" description="Trạng thái, số dư, cấp độ và lượt quay chỉ xuất hiện khi bạn có đúng quyền nghiệp vụ.">
                            <Row gutter={[16, 16]}>
                                {permissions.adjustBalance && (
                                    <>
                                        <Col xs={24} md={12} data-error-field="balance">
                                            <Text strong>Số dư</Text>
                                            <InputNumber name="balance" style={{ width: '100%' }} min={0} placeholder="Nhập số dư" defaultValue={oldValue(form, 'balance', user.balance || 0)} status={fieldError(form, 'balance') ? 'error' : ''} />
                                            <FormFieldError form={form} name="balance" />
                                        </Col>
                                        <Col xs={24} md={12} data-error-field="frozen_balance">
                                            <Text strong>Số dư đóng băng</Text>
                                            <InputNumber name="frozen_balance" style={{ width: '100%' }} min={0} step={0.00000001} placeholder="Nhập số dư đóng băng" defaultValue={oldValue(form, 'frozen_balance', user.frozen_balance || 0)} status={fieldError(form, 'frozen_balance') ? 'error' : ''} />
                                            <FormFieldError form={form} name="frozen_balance" />
                                        </Col>
                                    </>
                                )}
                                {permissions.changeStatus && (
                                    <Col xs={24} md={12} data-error-field="status">
                                        <Text strong>Trạng thái</Text>
                                        <Select style={{ width: '100%' }} status={fieldError(form, 'status') ? 'error' : ''} value={status} placeholder="Chọn trạng thái" onChange={setStatus} options={[
                                            { value: 'activated', label: 'Đã kích hoạt' },
                                            { value: 'inactivated', label: 'Chưa kích hoạt' },
                                            { value: 'banned', label: 'Bị khóa' },
                                        ]} />
                                        <input type="hidden" name="status" value={status} />
                                        <FormFieldError form={form} name="status" />
                                    </Col>
                                )}
                                {permissions.chooseRole && (
                                    <Col xs={24} md={12} data-error-field="role">
                                        <Text strong>Vai trò</Text>
                                        <Select style={{ width: '100%' }} status={fieldError(form, 'role') ? 'error' : ''} value={role} placeholder="Chọn vai trò" onChange={setRole} options={[
                                            { value: 'member', label: 'Member' },
                                            { value: 'staff', label: 'Staff' },
                                            { value: 'admin', label: 'Admin' },
                                        ]} />
                                        <input type="hidden" name="role" value={role} />
                                        <FormFieldError form={form} name="role" />
                                    </Col>
                                )}
                                {permissions.manageSpin && (
                                    <>
                                        <Col xs={24} md={12} data-error-field="rank">
                                            <Text strong>Cấp độ</Text>
                                            <Select
                                                allowClear
                                                style={{ width: '100%' }}
                                                status={fieldError(form, 'rank') ? 'error' : ''}
                                                value={rank || undefined}
                                                placeholder="Chọn cấp độ"
                                                onChange={(value) => setRank(value ? String(value) : '')}
                                                options={(config.ranks || []).map((item) => ({
                                                    value: String(item.id),
                                                    label: `${item.name} - ${item.spin_count} đơn hàng`,
                                                }))}
                                            />
                                            <input type="hidden" name="rank" value={rank} />
                                            <FormFieldError form={form} name="rank" />
                                        </Col>
                                        <Col xs={24} md={12} data-error-field="lucky_wheel_bonus_spins">
                                            <Text strong>Lượt quay may mắn được cấp còn lại</Text>
                                            <InputNumber name="lucky_wheel_bonus_spins" style={{ width: '100%' }} min={0} step={1} placeholder="Nhập số lượt quay" defaultValue={oldValue(form, 'lucky_wheel_bonus_spins', user.lucky_wheel_bonus_spins || 0)} status={fieldError(form, 'lucky_wheel_bonus_spins') ? 'error' : ''} />
                                            <FormFieldError form={form} name="lucky_wheel_bonus_spins" />
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
                            </AdminFormSection>
                            <AdminFormActions>
                                <Button href={config.routes.index}>Hủy</Button>
                                <Button type="primary" icon={<SaveOutlined />} onClick={submitWithConfirmation}>Lưu thay đổi</Button>
                            </AdminFormActions>
                        </LaravelForm>
                    </AdminSectionCard>
                </Col>

                <Col xs={24} xl={8}>
                    <Space direction="vertical" size="middle" style={{ width: '100%' }}>
                        <AdminSectionCard title="Vị trí hiện tại" description="Dữ liệu vị trí gần nhất đã được hệ thống ghi nhận.">
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
                        </AdminSectionCard>
                        <AdminSectionCard title="Thông tin hệ thống" description="Thông tin theo dõi và định danh không cần chỉnh trực tiếp tại form.">
                            <Descriptions column={1} size="small">
                                <Descriptions.Item label="Mã giới thiệu">{user.referral_code || 'Chưa có'}</Descriptions.Item>
                                <Descriptions.Item label="Người quản lý">{user.referrer ? (user.referrer.full_name || user.referrer.username) : 'Chưa có'}</Descriptions.Item>
                                <Descriptions.Item label="Ngày tạo">{dateTime(user.created_at)}</Descriptions.Item>
                                <Descriptions.Item label="Hoạt động gần nhất">{dateTime(user.last_seen)}</Descriptions.Item>
                                <Descriptions.Item label="IP đăng ký">{user.register_ip || 'Chưa ghi nhận'}</Descriptions.Item>
                            </Descriptions>
                        </AdminSectionCard>
                    </Space>
                </Col>
            </Row>
        </AdminPage>
    );
}
