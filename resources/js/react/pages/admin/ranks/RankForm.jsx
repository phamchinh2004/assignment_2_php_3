import { Button, Col, Form, Image, Input, InputNumber, Row, Typography } from 'antd';
import { CrownOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import { AdminFormActions, AdminFormSection, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;

function ErrorText({ form, name }) {
    const error = fieldError(form, name);
    return error ? <Text type="danger" className="d-block mt-1">{error}</Text> : null;
}

function numberValue(form, name, fallback) {
    const value = oldValue(form, name, fallback);
    return value === '' || value === null || value === undefined ? undefined : Number(value);
}

export default function RankForm({ config, editing = false }) {
    const rank = config.rank || {};
    const form = config.form || {};

    return (
        <AdminPage width="form">
            <AdminPageHeader
                eyebrow="Cấu hình thành viên"
                icon={<CrownOutlined />}
                title={editing ? `Chỉnh sửa cấp độ: ${rank.name}` : 'Tạo cấp độ mới'}
                description="Thiết lập điều kiện cấp độ, hoa hồng và hạn mức tài chính áp dụng cho thành viên."
                backHref={config.routes.index}
                backLabel="Danh sách cấp độ"
            />
            <AdminSectionCard title="Cấu hình cấp độ" description="Các thay đổi tại đây tác động trực tiếp tới quyền lợi và giới hạn của thành viên ở cấp này.">
                <LaravelForm action={editing ? config.routes.update : config.routes.store} method={editing ? 'PUT' : 'POST'} encType="multipart/form-data">
                    <AdminFormSection title="Nhận diện & quyền lợi" description="Tên cấp độ, phí nâng cấp và tỷ lệ hoa hồng là thông tin cốt lõi của cấp bậc.">
                        <Row gutter={[16, 8]}>
                            <Col xs={24}>
                            <Form.Item label="Tên cấp độ" required>
                                <Input name="name" defaultValue={oldValue(form, 'name', rank.name || '')} placeholder="Ví dụ: VIP 1" required maxLength={255} />
                                <ErrorText form={form} name="name" />
                            </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item label="Phí nâng cấp ($)" required>
                                    <InputNumber name="upgrade_fee" min={0} step={0.01} defaultValue={numberValue(form, 'upgrade_fee', rank.upgrade_fee)} placeholder="Nhập phí nâng cấp" className="w-100" required />
                                    <ErrorText form={form} name="upgrade_fee" />
                                </Form.Item>
                            </Col>
                            <Col xs={24} md={12}>
                                <Form.Item label="Hoa hồng (%)" required>
                                    <InputNumber name="commission_percentage" min={0} step={0.01} defaultValue={numberValue(form, 'commission_percentage', rank.commission_percentage)} placeholder="Nhập % hoa hồng" className="w-100" required />
                                    <ErrorText form={form} name="commission_percentage" />
                                </Form.Item>
                            </Col>
                        </Row>
                    </AdminFormSection>

                    <AdminFormSection title="Chu kỳ đơn hàng" description="Xác định số đơn cần hoàn thành trong một vòng và tổng giá trị đơn tương ứng.">
                            <Row gutter={16}>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Số lượng đơn hàng" required>
                                        <InputNumber name="spin_count" min={1} precision={0} defaultValue={numberValue(form, 'spin_count', rank.spin_count)} placeholder="Nhập số lượng đơn" className="w-100" required />
                                        <ErrorText form={form} name="spin_count" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Tổng giá trị đơn ($)" required>
                                        <InputNumber name="value" min={0} step={0.01} defaultValue={numberValue(form, 'value', rank.value)} placeholder="Nhập tổng giá trị đơn" className="w-100" required />
                                        <ErrorText form={form} name="value" />
                                    </Form.Item>
                                </Col>
                            </Row>
                    </AdminFormSection>

                    <AdminFormSection title="Hạn mức rút tiền" description="Giới hạn tần suất và số tiền tối đa cho mỗi lần rút của thành viên thuộc cấp này.">
                            <Row gutter={16}>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Số lần rút tối đa / ngày" required>
                                        <InputNumber name="maximum_number_of_withdrawals" min={1} precision={0} defaultValue={numberValue(form, 'maximum_number_of_withdrawals', rank.maximum_number_of_withdrawals)} placeholder="Nhập số lần rút" className="w-100" required />
                                        <ErrorText form={form} name="maximum_number_of_withdrawals" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Số tiền rút tối đa / lượt ($)" required>
                                        <InputNumber name="maximum_withdrawal_amount" min={editing ? 100 : 1} step={1} defaultValue={numberValue(form, 'maximum_withdrawal_amount', rank.maximum_withdrawal_amount)} placeholder="Nhập số tiền tối đa" className="w-100" required />
                                        <ErrorText form={form} name="maximum_withdrawal_amount" />
                                    </Form.Item>
                                </Col>
                            </Row>
                    </AdminFormSection>
                    <AdminFormSection title="Biểu tượng cấp độ" description="Dùng hình ảnh dễ nhận diện, phù hợp khi hiển thị cấp độ ở các màn hình người dùng.">
                            {editing && rank.image && (
                                <div className="mb-3">
                                    <Text type="secondary">Ảnh biểu tượng hiện tại</Text>
                                    <div className="mt-2"><Image src={`${String(config.storageBaseUrl || '/storage').replace(/\/$/, '')}/${rank.image}`} width={130} /></div>
                                </div>
                            )}
                            <Form.Item label={editing ? 'Thay ảnh biểu tượng' : 'Ảnh biểu tượng cấp độ'}>
                                <input type="file" name="image" accept="image/*" className="form-control" />
                                <ErrorText form={form} name="image" />
                            </Form.Item>
                    </AdminFormSection>
                    <AdminFormActions>
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" htmlType="submit">{editing ? 'Cập nhật cấp độ' : 'Tạo cấp độ'}</Button>
                    </AdminFormActions>
                </LaravelForm>
            </AdminSectionCard>
        </AdminPage>
    );
}
