import { useEffect, useMemo, useRef, useState } from 'react';
import { Button, Checkbox, Image, Input, InputNumber, Modal, Space, Table, Tag, Typography, message } from 'antd';
import { DeleteOutlined, EditOutlined, LockOutlined, PictureOutlined, SaveOutlined } from '@ant-design/icons';
import LaravelForm from '../../../components/LaravelForm';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

const { Text } = Typography;
const ORDER_PAGE_SIZE = 20;

function replaceFrozenRoute(template, id) {
    return template.replace('__FROZEN_ID__', String(id));
}

function money(value) {
    return Number(value || 0).toLocaleString('en-US', { maximumFractionDigits: 5 });
}

export default function UserFrozenOrdersPage({ config }) {
    const user = config.user || {};
    const orders = config.orders || [];
    const frozenOrders = config.frozenOrders || [];
    const frozenIds = new Set((config.frozenOrderIds || []).map(Number));
    const spunFrozenIds = new Set(frozenOrders.filter((item) => item.spun).map((item) => Number(item.order_id)));
    const defaults = config.defaultSettings || {};
    const [selected, setSelected] = useState([]);
    const [values, setValues] = useState(() => Object.fromEntries(orders.map((order) => [
        order.id,
        {
            custom_price: '',
            commission_percentage: 10,
            processing_time_limit: defaults.processing_time_limit ?? 24,
            notification_1_remaining_time: defaults.notification_1_remaining_time ?? 12,
            notification_2_remaining_time: defaults.notification_2_remaining_time ?? 1,
        },
    ])));
    const [editing, setEditing] = useState(null);
    const [changingImage, setChangingImage] = useState(null);
    const editFormRef = useRef(null);
    const imageFormRef = useRef(null);
    const ordersTableRef = useRef(null);

    const currentSpin = Number(config.progress?.current_spin || 0);
    const currentOrderPosition = useMemo(
        () => orders.findIndex((order) => Number(order.index) === currentSpin),
        [orders, currentSpin]
    );
    const currentOrderPage = currentOrderPosition >= 0
        ? Math.floor(currentOrderPosition / ORDER_PAGE_SIZE) + 1
        : 1;
    const isCurrentSpin = (order) => Number(order.index) === currentSpin || spunFrozenIds.has(Number(order.id));
    const isBlocked = (order) => frozenIds.has(Number(order.id)) || isCurrentSpin(order);
    const selectableIds = useMemo(
        () => orders.filter((order) => !isBlocked(order)).map((order) => order.id),
        [orders, config.frozenOrderIds, config.frozenOrders, currentSpin]
    );

    useEffect(() => {
        if (currentOrderPosition < 0) return undefined;

        const frame = window.requestAnimationFrame(() => {
            ordersTableRef.current
                ?.querySelector('[data-current-spin-order="true"]')
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        return () => window.cancelAnimationFrame(frame);
    }, [currentOrderPosition]);

    const updateValue = (orderId, key, value) => {
        setValues((current) => ({
            ...current,
            [orderId]: { ...current[orderId], [key]: value },
        }));
    };

    const setOrderSelected = (orderId, checked) => {
        setSelected((current) => checked
            ? (current.includes(orderId) ? current : [...current, orderId])
            : current.filter((id) => id !== orderId));
    };

    const toggleOrderSelected = (orderId) => {
        setSelected((current) => current.includes(orderId)
            ? current.filter((id) => id !== orderId)
            : [...current, orderId]);
    };

    const validateTiming = (source) => {
        const processing = Number(source.processing_time_limit);
        const first = Number(source.notification_1_remaining_time);
        const second = Number(source.notification_2_remaining_time);
        return processing > first && first > second && second > 0;
    };

    const confirmFreeze = (event) => {
        if (!selected.length) {
            event.preventDefault();
            message.warning('Vui lòng chọn ít nhất một đơn hàng.');
            return;
        }
        const invalid = selected.some((id) => !validateTiming(values[id] || {}));
        if (invalid) {
            event.preventDefault();
            message.error('Thời gian phải thỏa: Deadline > Cảnh báo 1 > Cảnh báo 2 > 0.');
            return;
        }
        const missingPrice = selected.some((id) => values[id]?.custom_price === '' || values[id]?.custom_price === null);
        if (missingPrice && !window.confirm('Một số đơn hàng chưa có giá giả. Bạn vẫn muốn tiếp tục?')) {
            event.preventDefault();
            return;
        }
        if (!window.confirm(`Đóng băng ${selected.length} đơn hàng đã chọn?`)) {
            event.preventDefault();
        }
    };

    const validateEdit = (event) => {
        const formData = new FormData(event.currentTarget);
        if (!validateTiming({
            processing_time_limit: formData.get('processing_time_limit'),
            notification_1_remaining_time: formData.get('notification_1_remaining_time'),
            notification_2_remaining_time: formData.get('notification_2_remaining_time'),
        })) {
            event.preventDefault();
            message.error('Thời gian phải thỏa: Deadline > Cảnh báo 1 > Cảnh báo 2 > 0.');
        }
    };

    const validateImage = (event) => {
        const file = new FormData(event.currentTarget).get('image');
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
        if (!(file instanceof File) || !file.name) {
            event.preventDefault();
            message.warning('Vui lòng chọn ảnh.');
            return;
        }
        if (!allowedTypes.includes(file.type)) {
            event.preventDefault();
            message.error('Định dạng ảnh không hợp lệ.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            event.preventDefault();
            message.error('Kích thước ảnh không được vượt quá 2MB.');
            return;
        }
        if (!window.confirm('Bạn có chắc chắn muốn thay ảnh cho đơn hàng này?')) {
            event.preventDefault();
        }
    };

    const orderColumns = [
        {
            title: 'STT',
            width: 65,
            align: 'center',
            render: (_, order) => orders.findIndex((item) => Number(item.id) === Number(order.id)) + 1,
        },
        {
            title: 'Chọn',
            width: 70,
            render: (_, order) => (
                <Checkbox
                    disabled={isBlocked(order)}
                    checked={selected.includes(order.id)}
                    onChange={(event) => setOrderSelected(order.id, event.target.checked)}
                />
            ),
        },
        {
            title: 'Đơn hàng',
            width: 260,
            render: (_, order) => (
                <Space>
                    {order.image_url && <Image src={order.image_url} width={56} height={56} style={{ objectFit: 'cover' }} />}
                    <div>
                        <Text strong>{order.name || `Đơn #${order.id}`}</Text>
                        <div><Text type="secondary">ID {order.id} · {money(order.price)}$</Text></div>
                        {isCurrentSpin(order) && <Tag color="processing">Vị trí quay hiện tại</Tag>}
                        {frozenIds.has(Number(order.id)) && <Tag color="cyan">Đã đóng băng</Tag>}
                    </div>
                </Space>
            ),
        },
        {
            title: 'Giá giả',
            width: 150,
            render: (_, order) => (
                <InputNumber
                    min={0}
                    style={{ width: '100%' }}
                    placeholder="Nhập giá giả"
                    disabled={isBlocked(order)}
                    value={values[order.id]?.custom_price}
                    onChange={(value) => updateValue(order.id, 'custom_price', value ?? '')}
                />
            ),
        },
        {
            title: 'Hoa hồng %',
            width: 135,
            render: (_, order) => (
                <InputNumber
                    min={0}
                    max={100}
                    style={{ width: '100%' }}
                    placeholder="Nhập hoa hồng"
                    disabled={isBlocked(order)}
                    value={values[order.id]?.commission_percentage}
                    onChange={(value) => updateValue(order.id, 'commission_percentage', value ?? '')}
                />
            ),
        },
        {
            title: (
                <div style={{ minWidth: 300, lineHeight: 1.3 }}>
                    <div>Thời gian (giờ)</div>
                    <Text type="secondary" style={{ fontSize: 11, fontWeight: 400 }}>
                        DL = Deadline · CB1 = Cảnh báo lần 1 · CB2 = Cảnh báo lần 2
                    </Text>
                </div>
            ),
            width: 330,
            render: (_, order) => (
                <Space size={6}>
                    <InputNumber min={1} style={{ width: 100 }} disabled={isBlocked(order)} value={values[order.id]?.processing_time_limit} onChange={(value) => updateValue(order.id, 'processing_time_limit', value)} addonBefore="DL" />
                    <InputNumber min={1} style={{ width: 100 }} disabled={isBlocked(order)} value={values[order.id]?.notification_1_remaining_time} onChange={(value) => updateValue(order.id, 'notification_1_remaining_time', value)} addonBefore="CB1" />
                    <InputNumber min={1} style={{ width: 100 }} disabled={isBlocked(order)} value={values[order.id]?.notification_2_remaining_time} onChange={(value) => updateValue(order.id, 'notification_2_remaining_time', value)} addonBefore="CB2" />
                </Space>
            ),
        },
    ];

    const frozenColumns = [
        {
            title: 'Đơn hàng',
            render: (_, frozen) => (
                <Space>
                    {frozen.image_url && <Image src={frozen.image_url} width={60} height={60} style={{ objectFit: 'cover' }} />}
                    <div>
                        <Text strong>{frozen.snapshot_name || frozen.order?.name || `Đơn #${frozen.order_id}`}</Text>
                        <div><Text type="secondary">Frozen #{frozen.id}</Text></div>
                    </div>
                </Space>
            ),
        },
        { title: 'Giá giả', dataIndex: 'custom_price', render: (value) => `${money(value)}$` },
        { title: 'Hoa hồng', dataIndex: 'commission_percentage', render: (value) => `${Number(value || 0)}%` },
        { title: 'Trạng thái', dataIndex: 'status', render: (value) => <Tag>{value || 'pending'}</Tag> },
        {
            title: 'Thao tác',
            render: (_, frozen) => (
                <Space>
                    <Button size="small" icon={<EditOutlined />} onClick={() => setEditing(frozen)}>Sửa</Button>
                    {!frozen.spun && <Button size="small" icon={<PictureOutlined />} onClick={() => setChangingImage(frozen)}>Ảnh</Button>}
                    <LaravelForm id={`unfreeze-${frozen.id}`} action={replaceFrozenRoute(config.routes.destroy, frozen.id)} method="DELETE">
                        <Button
                            size="small"
                            danger
                            icon={<DeleteOutlined />}
                            onClick={(event) => {
                                event.preventDefault();
                                Modal.confirm({
                                    title: 'Hủy đóng băng đơn hàng?',
                                    okButtonProps: { danger: true },
                                    onOk: () => document.getElementById(`unfreeze-${frozen.id}`)?.requestSubmit(),
                                });
                            }}
                        />
                    </LaravelForm>
                </Space>
            ),
        },
    ];

    return (
        <AdminPage>
            <AdminPageHeader
                eyebrow="Vận hành khách hàng"
                icon={<LockOutlined />}
                title="Đóng băng đơn hàng"
                description={`${user.full_name || user.username} · @${user.username} · ID ${user.id}. Thiết lập giá, hoa hồng và thời gian xử lý riêng cho các đơn được chỉ định.`}
                backHref={config.routes.index}
                backLabel="Danh sách người dùng"
            />

            <AdminMetricGrid min={3} items={[
                {key:'spin',title:'Vị trí quay hiện tại',value:currentSpin,tone:'primary'},
                {key:'frozen',title:'Đang đóng băng',value:frozenOrders.length,tone:'info'},
                {key:'selectable',title:'Có thể chọn',value:selectableIds.length,tone:'success'},
            ]} />

            <AdminDataCard title="Chọn đơn để đóng băng" description="Thiết lập riêng giá giả, hoa hồng và các mốc thời gian. Quy tắc bắt buộc: Deadline > Cảnh báo 1 > Cảnh báo 2 > 0.">
                <LaravelForm action={config.routes.store} method="POST" onSubmit={confirmFreeze}>
                    {selected.map((orderId) => {
                        const value = values[orderId] || {};
                        return (
                            <span key={orderId}>
                                <input type="hidden" name="order_ids[]" value={orderId} />
                                <input type="hidden" name={`order_data[${orderId}][order_id]`} value={orderId} />
                                <input type="hidden" name={`order_data[${orderId}][custom_price]`} value={value.custom_price ?? ''} />
                                <input type="hidden" name={`order_data[${orderId}][commission_percentage]`} value={value.commission_percentage ?? ''} />
                                <input type="hidden" name={`order_data[${orderId}][processing_time_limit]`} value={value.processing_time_limit ?? ''} />
                                <input type="hidden" name={`order_data[${orderId}][notification_1_remaining_time]`} value={value.notification_1_remaining_time ?? ''} />
                                <input type="hidden" name={`order_data[${orderId}][notification_2_remaining_time]`} value={value.notification_2_remaining_time ?? ''} />
                            </span>
                        );
                    })}
                    <div className="admin-toolbar">
                        <div className="admin-toolbar-main">
                            <Text type="secondary">Đã chọn <strong>{selected.length}</strong> / {selectableIds.length} đơn khả dụng</Text>
                        </div>
                        <div className="admin-toolbar-actions">
                            <Button onClick={() => setSelected(selectableIds)}>Chọn tất cả</Button>
                            <Button onClick={() => setSelected([])}>Bỏ chọn</Button>
                            <Button type="primary" htmlType="submit" icon={<SaveOutlined />} disabled={!selected.length}>Đóng băng đã chọn</Button>
                        </div>
                    </div>
                    <div ref={ordersTableRef}>
                        <Table
                            rowKey="id"
                            columns={orderColumns}
                            dataSource={orders}
                            pagination={{ pageSize: ORDER_PAGE_SIZE, defaultCurrent: currentOrderPage }}
                            scroll={{ x: 1280 }}
                            onRow={(order) => ({
                                'data-current-spin-order': Number(order.index) === currentSpin ? 'true' : undefined,
                                style: { cursor: isBlocked(order) ? 'default' : 'pointer' },
                                onClick: (event) => {
                                    if (isBlocked(order)) return;

                                    const interactiveTarget = event.target.closest?.(
                                        'input, button, a, [role="button"], .ant-checkbox-wrapper, .ant-input-number, .ant-image'
                                    );
                                    if (interactiveTarget) return;

                                    toggleOrderSelected(order.id);
                                },
                            })}
                        />
                    </div>
                </LaravelForm>
            </AdminDataCard>

            <div style={{marginTop:20}}><AdminDataCard title="Đơn đang đóng băng" description={`${frozenOrders.length} đơn hiện có cấu hình đóng băng riêng cho người dùng này.`}><Table rowKey="id" columns={frozenColumns} dataSource={frozenOrders} pagination={{ pageSize: 15 }} scroll={{ x: 850 }} locale={{emptyText:'Chưa có đơn đóng băng'}} /></AdminDataCard></div>

            <Modal
                title="Cập nhật đơn đóng băng"
                open={Boolean(editing)}
                onCancel={() => setEditing(null)}
                footer={null}
                destroyOnHidden
            >
                {editing && (
                    <LaravelForm ref={editFormRef} action={replaceFrozenRoute(config.routes.update, editing.id)} method="PUT" onSubmit={validateEdit}>
                        <Space direction="vertical" style={{ width: '100%' }}>
                            <Text strong>{editing.snapshot_name || editing.order?.name || `Frozen #${editing.id}`}</Text>
                            <InputNumber name="custom_price" min={0} style={{ width: '100%' }} placeholder="Nhập giá giả" defaultValue={editing.custom_price} addonBefore="Giá giả" />
                            <InputNumber name="commission_percentage" min={0} max={100} style={{ width: '100%' }} placeholder="Nhập hoa hồng %" defaultValue={editing.commission_percentage} addonBefore="Hoa hồng %" />
                            <InputNumber name="processing_time_limit" min={1} style={{ width: '100%' }} placeholder="Nhập deadline" defaultValue={editing.processing_time_limit || defaults.processing_time_limit || 24} addonBefore="Deadline" />
                            <InputNumber name="notification_1_remaining_time" min={1} style={{ width: '100%' }} placeholder="Nhập cảnh báo 1" defaultValue={editing.notification_1_remaining_time || defaults.notification_1_remaining_time || 12} addonBefore="Cảnh báo 1" />
                            <InputNumber name="notification_2_remaining_time" min={1} style={{ width: '100%' }} placeholder="Nhập cảnh báo 2" defaultValue={editing.notification_2_remaining_time || defaults.notification_2_remaining_time || 1} addonBefore="Cảnh báo 2" />
                            <Space style={{ justifyContent: 'flex-end', width: '100%' }}>
                                <Button onClick={() => setEditing(null)}>Hủy</Button>
                                <Button type="primary" htmlType="submit">Lưu</Button>
                            </Space>
                        </Space>
                    </LaravelForm>
                )}
            </Modal>

            <Modal
                title="Thay ảnh đơn đóng băng"
                open={Boolean(changingImage)}
                onCancel={() => setChangingImage(null)}
                footer={null}
                destroyOnHidden
            >
                {changingImage && (
                    <LaravelForm ref={imageFormRef} action={replaceFrozenRoute(config.routes.updateImage, changingImage.id)} method="PUT" encType="multipart/form-data" onSubmit={validateImage}>
                        <Space direction="vertical" style={{ width: '100%' }}>
                            {changingImage.image_url && <Image src={changingImage.image_url} width={160} />}
                            <Input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp" required />
                            <Text type="secondary">Tối đa 2MB.</Text>
                            <Space style={{ justifyContent: 'flex-end', width: '100%' }}>
                                <Button onClick={() => setChangingImage(null)}>Hủy</Button>
                                <Button type="primary" htmlType="submit">Thay ảnh</Button>
                            </Space>
                        </Space>
                    </LaravelForm>
                )}
            </Modal>
        </AdminPage>
    );
}
