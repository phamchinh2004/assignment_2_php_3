import { useEffect, useMemo, useState } from 'react';
import {
    Alert,
    Button,
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
    SaveOutlined,
    ShoppingOutlined,
    UploadOutlined,
} from '@ant-design/icons';
import { requestJson } from '../../../lib/http';
import { spaNavigate } from '../../../navigation';
import { AdminFormActions, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';
import './order-edit.css';

const { Text } = Typography;

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
        <AdminPage className="order-edit-react-page">
            {contextHolder}
            <AdminPageHeader
                eyebrow="Vận hành đơn hàng"
                icon={<ShoppingOutlined />}
                title="Chỉnh sửa đơn hàng"
                description="Cập nhật thông tin sản phẩm, người nhận và trạng thái thanh toán của đơn hàng."
                backHref={config.routes.index}
                backLabel="Danh sách đơn hàng"
                meta={<><Tag>{order.order_code}</Tag><Tag color={Number(order.status) === 1 ? 'success' : 'error'}>{Number(order.status) === 1 ? 'Đang hoạt động' : 'Ngừng hoạt động'}</Tag><Text type="secondary">Tạo: {config.createdAt || '—'}</Text></>}
            />

            {error && <Alert type="error" showIcon message={error} className="order-edit-alert" />}

            <Form form={form} layout="vertical" initialValues={initialValues}>
                <Row gutter={[16, 16]}>
                    <Col xs={24} xl={12}>
                        <AdminSectionCard title="Thông tin sản phẩm" description="Ảnh, mã, giá và số lượng của đơn hàng mẫu." className="order-edit-card">
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
                                <Input maxLength={255} placeholder="Nhập tên đơn hàng" />
                            </Form.Item>

                            <Form.Item name="order_code" label="Mã đơn hàng" rules={[{ required: true, message: 'Vui lòng nhập mã đơn hàng.' }]}>
                                <Input maxLength={255} placeholder="Nhập mã đơn hàng" />
                            </Form.Item>

                            <Row gutter={12}>
                                <Col span={12}>
                                    <Form.Item name="price" label="Giá ($)" rules={[{ required: true }]}>
                                        <InputNumber min={0} precision={2} placeholder="Nhập giá" className="order-edit-full" />
                                    </Form.Item>
                                </Col>
                                <Col span={12}>
                                    <Form.Item name="quantity" label="Số lượng" rules={[{ required: true }]}>
                                        <InputNumber min={1} precision={0} placeholder="Nhập số lượng" className="order-edit-full" />
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
                        </AdminSectionCard>
                    </Col>

                    <Col xs={24} xl={12}>
                        <AdminSectionCard title="Thông tin khách hàng" description="Thông tin người nhận dùng cho vận hành giao hàng." className="order-edit-card">
                            <Form.Item name="customer_name" label="Họ tên khách hàng">
                                <Input maxLength={255} placeholder="Nhập họ tên khách hàng" />
                            </Form.Item>
                            <Form.Item name="customer_phone" label="Số điện thoại">
                                <Input maxLength={50} placeholder="Nhập số điện thoại" />
                            </Form.Item>
                            <Form.Item name="customer_address" label="Địa chỉ giao hàng">
                                <Input.TextArea rows={3} maxLength={500} placeholder="Nhập địa chỉ giao hàng" showCount />
                            </Form.Item>
                            <Form.Item name="customer_note" label="Ghi chú từ khách hàng">
                                <Input.TextArea rows={2} maxLength={1000} placeholder="Nhập ghi chú của khách hàng" showCount />
                            </Form.Item>
                        </AdminSectionCard>

                        <AdminSectionCard title="Thanh toán & nền tảng" description="Nguồn đơn, phương thức thanh toán và mã tracking tích hợp." className="order-edit-card order-edit-card-spaced">
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
                                <Select allowClear placeholder="Chọn hình thức thanh toán" options={paymentOptions} />
                            </Form.Item>
                            <Form.Item name="is_paid" label="Đã thanh toán" valuePropName="checked">
                                <Switch disabled={paymentMethod === 'COD'} />
                            </Form.Item>
                            {paymentMethod === 'COD' && (
                                <Text type="secondary">COD không thể đánh dấu đã thanh toán.</Text>
                            )}
                            <Form.Item name="api" label="API Tracking String" className="order-edit-api-field">
                                <Input maxLength={255} placeholder="Nhập chuỗi API tracking" />
                            </Form.Item>
                        </AdminSectionCard>
                    </Col>
                </Row>

                <AdminFormActions>
                    <Button href={config.routes.index}>Hủy bỏ</Button>
                    <Button type="primary" icon={<SaveOutlined />} loading={submitting} onClick={handleSubmit}>
                        Lưu thay đổi
                    </Button>
                </AdminFormActions>
            </Form>
        </AdminPage>
    );
}
