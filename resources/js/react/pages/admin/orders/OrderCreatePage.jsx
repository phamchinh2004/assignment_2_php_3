import { useMemo, useState } from 'react';
import {
    Alert,
    Button,
    Card,
    Form,
    Select,
    Space,
    Typography,
    Upload,
    message,
} from 'antd';
import {
    ArrowLeftOutlined,
    CloudUploadOutlined,
    ThunderboltOutlined,
} from '@ant-design/icons';
import { requestJson } from '../../../lib/http';
import { spaNavigate } from '../../../navigation';
import './order-create.css';

const { Dragger } = Upload;
const { Text, Title } = Typography;

function generateRandomSplit(total, count, decimals = 2) {
    if (count <= 0) return [];

    const raw = Array.from({ length: count }, () => Math.random() + 0.1);
    const sum = raw.reduce((current, value) => current + value, 0);
    const scaled = raw.map((value) => Number(((value / sum) * total).toFixed(decimals)));
    const currentSum = scaled.reduce((current, value) => current + value, 0);
    const diff = Number((total - currentSum).toFixed(decimals));
    scaled[scaled.length - 1] = Number((scaled[scaled.length - 1] + diff).toFixed(decimals));

    return scaled;
}

export default function OrderCreatePage({ config }) {
    const [rankId, setRankId] = useState();
    const [fileList, setFileList] = useState([]);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');
    const [messageApi, contextHolder] = message.useMessage();

    const ranks = config.ranks || [];
    const selectedRank = useMemo(
        () => ranks.find((rank) => String(rank.id) === String(rankId)),
        [rankId, ranks]
    );

    const handleCreate = async () => {
        if (!selectedRank) {
            messageApi.warning('Vui lòng chọn cấp độ.');
            return;
        }

        if (fileList.length === 0) {
            messageApi.warning('Vui lòng chọn hình ảnh.');
            return;
        }

        const remainingOrders = Math.max(0, Number(selectedRank.spin_count) - Number(selectedRank.start));
        const filesToProcess = Math.min(fileList.length, remainingOrders, 20);

        if (filesToProcess === 0) {
            messageApi.warning('Cấp độ này không còn đơn hàng cần tạo.');
            return;
        }

        setSubmitting(true);
        setError('');

        try {
            const values = generateRandomSplit(Number(selectedRank.value || 0), Number(selectedRank.quantity || 0), 2);
            const formData = new FormData();

            for (let index = 0; index < filesToProcess; index += 1) {
                const file = fileList[index]?.originFileObj || fileList[index];
                const orderQuantity = Math.floor(Math.random() * 7) + 1;
                const price = Number(values[index] || 0) / orderQuantity;
                const orderIndex = Number(selectedRank.start || 0) + index + 1;

                formData.append(`orders[${index}][name]`, '');
                formData.append(`orders[${index}][price]`, String(price));
                formData.append(`orders[${index}][quantity]`, String(orderQuantity));
                formData.append(`orders[${index}][index]`, String(orderIndex));
                formData.append(`orders[${index}][image]`, file);
            }

            formData.append('rank_id', String(selectedRank.id));
            formData.append('commission_percentage', String(selectedRank.commission_percentage || 0));

            const payload = await requestJson(config.routes.store, {
                method: 'POST',
                body: formData,
            });

            if (payload?.status !== 200) {
                throw new Error(payload?.message || 'Tạo đơn hàng không thành công.');
            }

            localStorage.setItem('success', 'Tạo đơn hàng thành công!');
            localStorage.setItem('order_index_filter_rank', String(selectedRank.id));
            messageApi.success('Tạo đơn hàng thành công!');
            spaNavigate(payload.redirect_url || config.routes.index);
        } catch (exception) {
            console.error(exception);
            setError(exception?.message || 'Có lỗi xảy ra khi tạo đơn hàng.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="order-create-react-page">
            {contextHolder}

            <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="order-create-back">
                Quay lại danh sách đơn hàng
            </Button>

            <Card>
                <Space direction="vertical" size={4} className="order-create-heading">
                    <Title level={3}>Tạo tự động đơn hàng</Title>
                    <Text type="secondary">
                        Chọn cấp độ và tải ảnh để hệ thống tự động sinh các đơn hàng.
                    </Text>
                </Space>

                {error && <Alert type="error" showIcon message={error} className="order-create-alert" />}

                <Form layout="vertical" className="order-create-form">
                    <Form.Item label="Cấp độ (Rank)" required>
                        <Select
                            value={rankId}
                            onChange={setRankId}
                            placeholder="Chọn cấp độ"
                            options={ranks.map((rank) => ({
                                value: String(rank.id),
                                label: `${rank.name} — Cần ${rank.quantity} hình ảnh`,
                                disabled: Number(rank.quantity) === 0,
                            }))}
                        />
                    </Form.Item>

                    {selectedRank && (
                        <Alert
                            type="info"
                            showIcon
                            message={`${selectedRank.name}: còn ${selectedRank.quantity} đơn cần tạo, tối đa 20 đơn/lần.`}
                            className="order-create-alert"
                        />
                    )}

                    <Form.Item label="Hình ảnh đơn hàng" required>
                        <Dragger
                            multiple
                            accept="image/*"
                            fileList={fileList}
                            beforeUpload={() => false}
                            onChange={({ fileList: nextFiles }) => setFileList(nextFiles.slice(0, 20))}
                        >
                            <p className="ant-upload-drag-icon"><CloudUploadOutlined /></p>
                            <p className="ant-upload-text">Chọn hoặc kéo thả nhiều ảnh vào đây</p>
                            <p className="ant-upload-hint">Mỗi ảnh tương ứng một đơn hàng, tối đa 20 ảnh mỗi lần.</p>
                        </Dragger>
                    </Form.Item>

                    <Button
                        type="primary"
                        size="large"
                        icon={<ThunderboltOutlined />}
                        onClick={handleCreate}
                        loading={submitting}
                        disabled={!rankId || fileList.length === 0}
                    >
                        Bắt đầu tạo tự động
                    </Button>
                </Form>
            </Card>
        </div>
    );
}
