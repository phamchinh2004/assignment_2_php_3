import { useEffect, useMemo, useState } from 'react';
import {
    Alert,
    Button,
    Card,
    Col,
    Form,
    Image,
    Input,
    InputNumber,
    Modal,
    Row,
    Select,
    Space,
    Switch,
    Tag,
    Typography,
    Upload,
    message,
} from 'antd';
import {
    ArrowLeftOutlined,
    SaveOutlined,
    UploadOutlined,
} from '@ant-design/icons';
import { requestJson } from '../../../lib/http';
import { spaNavigate } from '../../../navigation';
import './order-edit.css';

const { Text, Title } = Typography;

const paymentOptions = [
    { value: 'COD', label: 'COD (Thanh toán khi nhận hàng)' },
    { value: 'vnpay', label: 'VNPay' },
    { value: 'momo', label: 'MoMo' },
    { value: 'paypal', label: 'PayPal' },
    { value: 'bank_transfer', label: 'Chuyển khoản ngân hàng' },
    { value: 'other', label: 'Khác' },
];

function appendNullable(formData, key, value) {
    formData.append(key, value === null || value === undefined ? '' : String(value));
}

export default function OrderEditPage({ config }) {
    const [form] = Form.useForm();
    const [fileList, setFileList] = useState([]);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const [messageApi, contextHolder] = message.useMessage();
    const order = config.order || {};
    const paymentMethod = Form.useWatch('payment_method', form);

    const previewUrl = useMemo(() => {
        const file = fileList[0]?.originFileObj;
        return file ? URL.createObjectURL(file) : config.imageUrl;
    }, [config.imageUrl, fileList]);

    useEffect(() => {
        return () => {
            if (previewUrl?.startsWith('blob:')) URL.revokeObjectURL(previewUrl);
        };
    }, [previewUrl]);

    useEffect(() => {
        if (paymentMethod === 'COD') {
            form.setFieldValue('is_paid', false);
        }
    }, [form, paymentMethod]);

    const initialValues = {
        name: order.name || '',
        order_code: order.order_code || '',
        price: Number(order.price || 0),
        quantity: Number(order.quantity || 1),
        customer_name: order.customer_name || '',
        customer_phone: order.customer_phone || '',
        customer_address: order.customer_address || '',
        customer_note: order.customer_note || '',
        partner_id: order.partner_id ? String(order.partner_id) : undefined,
        payment_method: order.payment_method || undefined,
        is_paid: order.is_paid === true || Number(order.is_paid) === 1,
        api: order.api || '',
    };

    const handleSubmit = async () => {
        setError('');

        try {
            const values = await form.validateFields();
            const confirmed = await new Promise((resolve) => {
                Modal.confirm({
                    title: 'Xác nhận lưu thay đổi',
                    content: `Cập nhật đơn hàng ${order.order_code || `#${order.id}`}?`,
                    okText: 'Lưu thay đổi',
                    cancelText: 'Hủy',
                    onOk: () => resolve(true),
                    onCancel: () => resolve(false),
                });
            });

            if (!confirmed) return;

            setSubmitting(true);
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('name', values.name);
            formData.append('order_code', values.order_code);
            formData.append('price', String(values.price));
            formData.append('quantity', String(values.quantity));
            appendNullable(formData, 'customer_name', values.customer_name);
            appendNullable(formData, 'customer_phone', values.customer_phone);
            appendNullable(formData, 'customer_address', values.customer_address);
            appendNullable(formData, 'customer_note', values.customer_note);
            appendNullable(formData, 'payment_method', values.payment_method);
            appendNullable(formData, 'partner_id', values.partner_id);
            appendNullable(formData, 'api', values.api);

            if (values.is_paid && values.payment_method !== 'COD') {
                formData.append('is_paid', '1');
            }

            const image = fileList[0]?.originFileObj;
            if (image) formData.append('image', image);

            const payload = await requestJson(config.routes.update, {
                method: 'POST',
                body: formData,
            });

            messageApi.success(payload?.message || 'Cập nhật đơn hàng thành công!');
            spaNavigate(payload?.redirect_url || config.routes.index);
        } catch (exception) {
            if (exception?.errorFields) return;
            console.error(exception);
            setError(exception?.message || 'Không thể cập nhật đơn hàng.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="order-edit-react-page">
            {contextHolder}

            <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="order-edit-back">
                Quay lại danh sách đơn hàng
            </Button>

            <div className="order-edit-heading">
                <div>
                    <Title level={3}>Chỉnh sửa đơn hàng</Title>
                    <Space wrap>
                        <Tag>{order.order_code}</Tag>
                        <Tag color={Number(order.status) === 1 ? 'success' : 'error'}>
                            {Number(order.status) === 1 ? 'Đang hoạt động' : 'Ngừng hoạt động'}
                        </Tag>
                    </Space>
                </div>
                <Text type="secondary">Tạo: {config.createdAt || '—'}</Text>
            </div>

            {error && <Alert type="error" showIcon message={error} className="order-edit-alert" />}

            <Form form={form} layout="vertical" initialValues={initialValues}>
                <Row gutter={[16, 16]}>
                    <Col xs={24} xl={12}>
                        <Card title="Thông tin sản phẩm" className="order-edit-card">
                            <div className="order-edit-image-row">
                                <Image width={150} height={150} src={previewUrl} fallback="/theme/admin/img/undraw_profile.svg" />
                                <Upload
                                    accept="image/*"
                                    maxCount={1}
                                    fileList={fileList}
                                    beforeUpload={() => false}
                                    onChange={({ fileList: next }) => setFileList(next.slice(-1))}
                                >
                                    <Button icon={<UploadOutlined />}>Thay hình ảnh</Button>
                                </Upload>
                            </div>

                            <Form.Item name="name" label="Tên đơn hàng" rules={[{ required: true, message: 'Vui lòng nhập tên đơn hàng.' }]}>
                                <Input maxLength={255} />
                            </Form.Item>

                            <Form.Item name="order_code" label="Mã đơn hàng" rules={[{ required: true, message: 'Vui lòng nhập mã đơn hàng.' }]}>
                                <Input maxLength={255} />
                            </Form.Item>

                            <Row gutter={12}>
                                <Col span={12}>
                                    <Form.Item name="price" label="Giá ($)" rules={[{ required: true }]}>
                                        <InputNumber min={0} precision={2} className="order-edit-full" />
                                    </Form.Item>
                                </Col>
                                <Col span={12}>
                                    <Form.Item name="quantity" label="Số lượng" rules={[{ required: true }]}>
                                        <InputNumber min={1} precision={0} className="order-edit-full" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            {(order.commission_percentage !== null || order.rank) && (
                                <Alert
                                    type="success"
                                    showIcon
                                    message={`Hoa hồng: ${order.commission_percentage || 0}%${order.rank?.name ? ` · Cấp độ: ${order.rank.name}` : ''}`}
                                />
                            )}
                        </Card>
                    </Col>

                    <Col xs={24} xl={12}>
                        <Card title="Thông tin khách hàng" className="order-edit-card">
                            <Form.Item name="customer_name" label="Họ tên khách hàng">
                                <Input maxLength={255} />
                            </Form.Item>
                            <Form.Item name="customer_phone" label="Số điện thoại">
                                <Input maxLength={50} />
                            </Form.Item>
                            <Form.Item name="customer_address" label="Địa chỉ giao hàng">
                                <Input.TextArea rows={3} maxLength={500} showCount />
                            </Form.Item>
                            <Form.Item name="customer_note" label="Ghi chú từ khách hàng">
                                <Input.TextArea rows={2} maxLength={1000} showCount />
                            </Form.Item>
                        </Card>

                        <Card title="Thanh toán & nền tảng" className="order-edit-card order-edit-card-spaced">
                            <Form.Item name="partner_id" label="Nền tảng bán hàng">
                                <Select
                                    allowClear
                                    placeholder="Chọn nền tảng"
                                    options={(config.partners || []).map((partner) => ({
                                        value: String(partner.id),
                                        label: partner.name,
                                    }))}
                                />
                            </Form.Item>
                            <Form.Item name="payment_method" label="Hình thức thanh toán">
                                <Select allowClear options={paymentOptions} />
                            </Form.Item>
                            <Form.Item name="is_paid" label="Đã thanh toán" valuePropName="checked">
                                <Switch disabled={paymentMethod === 'COD'} />
                            </Form.Item>
                            {paymentMethod === 'COD' && (
                                <Text type="secondary">COD không thể đánh dấu đã thanh toán.</Text>
                            )}
                            <Form.Item name="api" label="API Tracking String" className="order-edit-api-field">
                                <Input maxLength={255} />
                            </Form.Item>
                        </Card>
                    </Col>
                </Row>

                <div className="order-edit-actions">
                    <Button href={config.routes.index}>Hủy bỏ</Button>
                    <Button type="primary" icon={<SaveOutlined />} loading={submitting} onClick={handleSubmit}>
                        Lưu thay đổi
                    </Button>
                </div>
            </Form>
        </div>
    );
}
