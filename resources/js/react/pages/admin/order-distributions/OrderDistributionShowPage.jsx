import { Alert, Button, Card, Col, Descriptions, Image, Row, Space, Tag, Timeline, Typography } from 'antd';
import LaravelForm from '../../../components/LaravelForm';
import { AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

const { Paragraph, Text, Title } = Typography;
const formatDate = (value) => value ? new Intl.DateTimeFormat('vi-VN',{dateStyle:'short',timeStyle:'medium'}).format(new Date(value)) : '—';
const money = (value, digits = 2) => value === null || value === undefined ? 'Chưa có snapshot' : `${new Intl.NumberFormat('en-US',{minimumFractionDigits:digits,maximumFractionDigits:digits}).format(Number(value))}$`;
const routeFor = (template, id) => String(template || '').replace('__ORDER_ID__', encodeURIComponent(String(id)));

export default function OrderDistributionShowPage({ config }) {
    const item = config.frozenOrder || {};
    const transition = config.transition || {};
    const labels = config.statusLabels || {};
    const currentLabel = labels[item.status] || item.status || 'Chưa ghi nhận';
    const nextLabel = transition.next ? (labels[transition.next] || transition.next) : null;
    const assignee = item.user?.full_name || item.user?.username || 'Tài khoản không còn tồn tại';
    const assigner = item.assigned_by?.full_name || item.assigned_by?.username || (item.assignment_source === 'spin' ? 'Hệ thống tự phân phối' : item.assignment_source === 'admin' ? 'Không còn thông tin người phân phối' : 'Chưa ghi nhận');

    const backUrl = new URL(config.routes.index, window.location.origin);
    new URL(window.location.href).searchParams.forEach((value,key)=>backUrl.searchParams.set(key,value));

    const history = [...(item.status_orders || [])].sort((a,b)=>Number(a.id)-Number(b.id)).map((event) => ({
        color: event.status?.color || 'blue',
        children: <div><Text strong>{event.status?.display_name || event.status?.name || 'Trạng thái không còn tồn tại'}</Text><div><Text type="secondary">{formatDate(event.created_at)} · {event.changed_by?.full_name || event.changed_by?.username || 'Hệ thống / chưa ghi nhận'}</Text></div>{event.notes && <Paragraph style={{marginTop:4,marginBottom:0}}>{event.notes}</Paragraph>}</div>,
    }));

    return <AdminPage>
        <AdminPageHeader eyebrow="Audit phân phối" title={`Audit phân phối #${item.id}`} description="Kiểm tra người nhận, tiến độ và dữ liệu snapshot." backHref={backUrl.toString()} backLabel="Danh sách phân phối" meta={<Tag color={item.status === 'completed' ? 'success' : 'processing'}>{currentLabel}</Tag>} />
        <Row gutter={[16,16]}>
            <Col xs={24} lg={16}>
                <Card title="Thông tin phân phối" extra={<Tag color={item.status === 'completed'?'success':'processing'}>{currentLabel}</Tag>} className="mb-3">
                    <Space align="start" size="large" style={{width:'100%'}}>{item.snapshot_image_url && <Image src={item.snapshot_image_url} width={120} height={120} style={{objectFit:'cover',borderRadius:8}}/>}<div style={{flex:1}}><Title level={4}>{item.snapshot_order_code || 'Chưa ghi nhận mã đơn'}</Title><Paragraph>{item.snapshot_name || 'Tên sản phẩm chưa được ghi nhận trong snapshot'}</Paragraph><Tag>{item.custom_price !== null ? 'Đơn giá trị cao' : 'Đơn thường'}</Tag></div></Space>
                    <Descriptions bordered column={{xs:1,md:2}} className="mt-3">
                        <Descriptions.Item label="Người nhận">{assignee} · User #{item.user_id}</Descriptions.Item>
                        <Descriptions.Item label="Người phân phối">{assigner}</Descriptions.Item>
                        <Descriptions.Item label="Nguồn">{item.assignment_source === 'admin' ? 'Giao thủ công' : item.assignment_source === 'spin' ? 'Người dùng tự nhận' : 'Không có dữ liệu nguồn lịch sử'}</Descriptions.Item>
                        <Descriptions.Item label="Thời gian phân phối">{formatDate(item.created_at)}</Descriptions.Item>
                        <Descriptions.Item label="Cập nhật bản ghi">{formatDate(item.updated_at)}</Descriptions.Item>
                        <Descriptions.Item label="Tình trạng nhận đơn">{item.spun ? 'Đã nhận đơn' : 'Chưa nhận đơn'}</Descriptions.Item>
                        <Descriptions.Item label="Order nguồn">{item.order ? (config.permissions?.viewOrder ? <a href={routeFor(config.routes.orderShow,item.order_id)}>Order #{item.order_id}</a> : `Order #${item.order_id}`) : `Order #${item.order_id} · Đã xóa`}</Descriptions.Item>
                    </Descriptions>
                </Card>

                <Card title={`Lịch sử trạng thái (${history.length})`} className="mb-3">{history.length ? <Timeline items={history}/> : <Alert type="info" showIcon message="Chưa có lịch sử trạng thái." />}</Card>

                <Card title="Dữ liệu snapshot">
                    {item.snapshot_state !== 'complete' && <Alert type="warning" showIcon className="mb-3" message="Snapshot chưa đầy đủ hoặc có dữ liệu bất thường. Giá trị chưa ghi nhận được để trống." />}
                    <Descriptions bordered column={{xs:1,md:2}}>
                        <Descriptions.Item label="Số lượng">{item.snapshot_quantity ?? '—'}</Descriptions.Item>
                        <Descriptions.Item label="Đơn giá snapshot">{money(item.snapshot_unit_price)}</Descriptions.Item>
                        <Descriptions.Item label="Nguồn snapshot">{item.snapshot_source || 'Chưa ghi nhận'}</Descriptions.Item>
                        <Descriptions.Item label="Ngày chụp snapshot">{formatDate(item.snapshot_captured_at)}</Descriptions.Item>
                        <Descriptions.Item label="Tình trạng dữ liệu">{item.snapshot_state || '—'}</Descriptions.Item>
                        <Descriptions.Item label="Ngày bổ sung dữ liệu cũ">{formatDate(item.snapshot_restored_at)}</Descriptions.Item>
                    </Descriptions>
                </Card>
            </Col>

            <Col xs={24} lg={8}>
                <Card title="Xử lý trạng thái" className="mb-3">
                    <Space direction="vertical" size="middle" style={{width:'100%'}}>
                        <div><Text type="secondary">Trạng thái hiện tại</Text><div><Tag color="processing">{currentLabel}</Tag></div></div>
                        {nextLabel && <div><Text type="secondary">Bước tiếp theo</Text><div><Text strong>{nextLabel}</Text></div></div>}
                        {transition.ready && config.permissions?.advance ? <LaravelForm action={config.routes.transition} method="POST" onSubmit={(event)=>{if(transition.next==='completed' && !window.confirm('Xác nhận hoàn thành và quyết toán số dư, hoa hồng cho đơn hàng này?')) event.preventDefault();}}>
                            <input type="hidden" name="expected_status" value={item.status || ''}/>
                            <input type="hidden" name="expected_updated_at" value={item.updated_at || ''}/>
                            <Button type="primary" htmlType="submit" block>Chuyển sang {nextLabel}</Button>
                        </LaravelForm> : <Alert type="info" showIcon message={transition.reason || (nextLabel ? 'Tài khoản chỉ có quyền xem bước này.' : 'Không có bước tiếp theo.')} description={transition.available_at ? `Có thể xử lý từ ${formatDate(transition.available_at)}.` : undefined}/>} 
                    </Space>
                </Card>

                <Card title="Tình trạng tài chính">
                    <Descriptions column={1} bordered size="small">
                        <Descriptions.Item label="Giá trị đơn">{money(item.snapshot_order_value)}</Descriptions.Item>
                        <Descriptions.Item label="Hoa hồng">{money(item.snapshot_commission_value,5)}</Descriptions.Item>
                        <Descriptions.Item label="Đã trả hoa hồng">{item.commission_paid ? 'Đã trả' : 'Chưa trả'}</Descriptions.Item>
                        <Descriptions.Item label="Thời điểm quyết toán">{item.settled_at ? formatDate(item.settled_at) : 'Chưa quyết toán'}</Descriptions.Item>
                    </Descriptions>
                </Card>
            </Col>
        </Row>
    </AdminPage>;
}
