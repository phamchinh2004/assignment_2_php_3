import { Button, Card, Col, Form, Image, Input, InputNumber, Row, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';

const { Text, Title } = Typography;

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
        <div className="container-fluid px-4 pb-5">
            <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách cấp độ</Button>
            <Title level={2}>{editing ? `Chỉnh sửa cấp độ: ${rank.name}` : 'Tạo cấp độ mới'}</Title>
            <Text type="secondary">Cấu hình hoa hồng, đơn hàng và hạn mức rút tiền của cấp bậc.</Text>

            <Card className="mt-4">
                <LaravelForm action={editing ? config.routes.update : config.routes.store} method={editing ? 'PUT' : 'POST'} encType="multipart/form-data">
                    <Row gutter={[24, 8]}>
                        <Col xs={24} lg={12}>
                            <Form.Item label="Tên cấp độ" required>
                                <Input name="name" defaultValue={oldValue(form, 'name', rank.name || '')} required maxLength={255} />
                                <ErrorText form={form} name="name" />
                            </Form.Item>
                            <Row gutter={16}>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Phí nâng cấp ($)" required>
                                        <InputNumber name="upgrade_fee" min={0} step={0.01} defaultValue={numberValue(form, 'upgrade_fee', rank.upgrade_fee)} className="w-100" required />
                                        <ErrorText form={form} name="upgrade_fee" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Hoa hồng (%)" required>
                                        <InputNumber name="commission_percentage" min={0} step={0.01} defaultValue={numberValue(form, 'commission_percentage', rank.commission_percentage)} className="w-100" required />
                                        <ErrorText form={form} name="commission_percentage" />
                                    </Form.Item>
                                </Col>
                            </Row>
                            <Row gutter={16}>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Số lượng đơn hàng" required>
                                        <InputNumber name="spin_count" min={1} precision={0} defaultValue={numberValue(form, 'spin_count', rank.spin_count)} className="w-100" required />
                                        <ErrorText form={form} name="spin_count" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Tổng giá trị đơn ($)" required>
                                        <InputNumber name="value" min={0} step={0.01} defaultValue={numberValue(form, 'value', rank.value)} className="w-100" required />
                                        <ErrorText form={form} name="value" />
                                    </Form.Item>
                                </Col>
                            </Row>
                        </Col>
                        <Col xs={24} lg={12}>
                            <Row gutter={16}>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Số lần rút tối đa / ngày" required>
                                        <InputNumber name="maximum_number_of_withdrawals" min={1} precision={0} defaultValue={numberValue(form, 'maximum_number_of_withdrawals', rank.maximum_number_of_withdrawals)} className="w-100" required />
                                        <ErrorText form={form} name="maximum_number_of_withdrawals" />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={12}>
                                    <Form.Item label="Số tiền rút tối đa / lượt ($)" required>
                                        <InputNumber name="maximum_withdrawal_amount" min={editing ? 100 : 1} step={1} defaultValue={numberValue(form, 'maximum_withdrawal_amount', rank.maximum_withdrawal_amount)} className="w-100" required />
                                        <ErrorText form={form} name="maximum_withdrawal_amount" />
                                    </Form.Item>
                                </Col>
                            </Row>

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
                        </Col>
                    </Row>

                    <div className="d-flex justify-content-end gap-2 mt-3">
                        <Button href={config.routes.index}>Hủy</Button>
                        <Button type="primary" htmlType="submit">{editing ? 'Cập nhật cấp độ' : 'Tạo cấp độ'}</Button>
                    </div>
                </LaravelForm>
            </Card>
        </div>
    );
}
