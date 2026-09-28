import { Button, Card, Col, Modal, Radio, Row, Segmented, Space, Statistic, Table, Tag, Typography } from 'antd';
import { useMemo, useState } from 'react';
import LaravelForm from '../../../components/LaravelForm';
import { AdminDataCard, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

const { Text, Title } = Typography;
const routeFor = (template, token, id) => String(template || '').replace(token, encodeURIComponent(String(id)));
const money = (value) => `${new Intl.NumberFormat('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}).format(Number(value || 0))}$`;
const dateTime = (value) => value ? new Intl.DateTimeFormat('vi-VN',{dateStyle:'short',timeStyle:'short'}).format(new Date(value)) : '—';

export default function WithdrawTransactionsPage({ config }) {
    const [filter, setFilter] = useState('all');
    const [confirming, setConfirming] = useState(null);
    const [transactionType, setTransactionType] = useState('normal');
    const transactions = config.transactions || [];
    const stats = useMemo(() => ({
        total: transactions.length,
        totalAmount: transactions.reduce((sum,item)=>sum+Number(item.value||0),0),
        pending: transactions.filter((item)=>item.status==='processing'),
        completed: transactions.filter((item)=>item.status==='completed'),
        cancelled: transactions.filter((item)=>item.status==='cancelled'),
    }), [transactions]);
    const filtered = filter === 'all' ? transactions : transactions.filter((item)=>item.status===filter);

    const columns = [
        { title:'Khách hàng', render:(_,item)=>{const user=item.user; const label=user?.full_name||user?.username||'Tài khoản đã xóa'; const content=<div><Text strong>{label}</Text>{user&&<div><Text type="secondary">@{user.username || '—'} · {user.phone || '—'}</Text></div>}</div>; return user&&config.permissions?.viewCustomerDetail?<a href={routeFor(config.routes.customerShow,'__USER_ID__',user.id)}>{content}</a>:content;}},
        { title:'Ngân hàng nhận tiền', render:(_,item)=><div><Text strong>{item.bank_name || '—'}</Text><div><Text code>{item.account_number || '—'}</Text></div><Text type="secondary">Chủ TK: {item.username_bank || '—'}</Text></div>},
        { title:'Số tiền', dataIndex:'value', render:(value)=><Text strong type="danger">-{money(value)}</Text>},
        { title:'Số dư trước', dataIndex:'balance_before', render:(value)=>value===null||value===undefined?<Text type="secondary">—</Text>:money(value)},
        { title:'Số dư sau', dataIndex:'balance_after', render:(value)=>value===null||value===undefined?<Text type="secondary">—</Text>:<Text strong>{money(value)}</Text>},
        { title:'Trạng thái', dataIndex:'status', render:(value)=><Tag color={value==='processing'?'warning':value==='completed'?'success':'error'}>{value==='processing'?'Chờ xác nhận':value==='completed'?'Hoàn thành':'Đã hủy'}</Tag>},
        { title:'Loại GD', dataIndex:'transaction_type', render:(value)=>value==='normal'?<Tag color="success">Rút thực</Tag>:value==='virtual_withdraw'?<Tag>Rút ảo</Tag>:<Text type="secondary">—</Text>},
        { title:'Duyệt bởi', render:(_,item)=>item.by_user?.username || 'Chờ xử lý' },
        { title:'Thời gian', dataIndex:'created_at', render:dateTime },
        { title:'Thao tác', width:170, render:(_,item)=> item.status==='processing'&&(config.permissions?.confirm||config.permissions?.cancel)?<Space>{config.permissions?.confirm&&<Button type="primary" size="small" onClick={()=>{setConfirming(item);setTransactionType('normal');}}>Duyệt</Button>}{config.permissions?.cancel&&<LaravelForm action={routeFor(config.routes.cancel,'__TRANSACTION_ID__',item.id)} method="POST" onSubmit={(event)=>{if(!window.confirm('Từ chối giao dịch này và hoàn tiền vào tài khoản khách hàng?')) event.preventDefault();}}><Button htmlType="submit" danger size="small">Từ chối</Button></LaravelForm>}</Space>:<Text type="secondary">Đã xử lý</Text>},
    ];

    return <AdminPage>
        <AdminPageHeader eyebrow="Tài chính" title="Quản lý rút tiền" description="Kiểm duyệt yêu cầu rút tiền và giữ nguyên snapshot số dư tại thời điểm giao dịch." />
        <Row gutter={[16,16]} className="mb-4">
            <Col xs={12} lg={6}><Card><Statistic title="Tổng yêu cầu" value={stats.total} suffix={<Text type="secondary">· {money(stats.totalAmount)}</Text>} /></Card></Col>
            <Col xs={12} lg={6}><Card><Statistic title="Chờ xác nhận" value={stats.pending.length} suffix={<Text type="secondary">· {money(stats.pending.reduce((s,i)=>s+Number(i.value||0),0))}</Text>} /></Card></Col>
            <Col xs={12} lg={6}><Card><Statistic title="Đã hoàn thành" value={stats.completed.length} suffix={<Text type="secondary">· {money(stats.completed.reduce((s,i)=>s+Number(i.value||0),0))}</Text>} /></Card></Col>
            <Col xs={12} lg={6}><Card><Statistic title="Đã từ chối" value={stats.cancelled.length} /></Card></Col>
        </Row>
        <AdminDataCard
            title="Danh sách yêu cầu rút tiền"
            description="Ưu tiên yêu cầu chờ duyệt; hành động chỉ xuất hiện khi tài khoản có quyền."
            extra={<Segmented value={filter} onChange={setFilter} options={[{label:`Tất cả (${stats.total})`,value:'all'},{label:`Chờ xác nhận (${stats.pending.length})`,value:'processing'},{label:`Hoàn thành (${stats.completed.length})`,value:'completed'},{label:`Đã hủy (${stats.cancelled.length})`,value:'cancelled'}]} />}
        >
            <Table rowKey="id" dataSource={filtered} columns={columns} scroll={{x:1250}} pagination={{pageSize:20,showSizeChanger:false}} />
        </AdminDataCard>

        <Modal title="Xác nhận giao dịch rút tiền" open={Boolean(confirming)} onCancel={()=>setConfirming(null)} onOk={()=>document.getElementById('confirm-withdraw-form')?.requestSubmit()} okText="Xác nhận" cancelText="Hủy">
            <Text>Chọn loại giao dịch trước khi duyệt:</Text>
            <div style={{marginTop:16}}><Radio.Group value={transactionType} onChange={(event)=>setTransactionType(event.target.value)} options={[{label:'Rút thực — tiền được chuyển thật cho khách',value:'normal'},{label:'Rút ảo — chỉ ghi nhận trên hệ thống',value:'virtual_withdraw'}]} /></div>
            {confirming && <LaravelForm id="confirm-withdraw-form" action={routeFor(config.routes.confirm,'__TRANSACTION_ID__',confirming.id)} method="POST" style={{display:'none'}}><input type="hidden" name="transaction_type" value={transactionType}/></LaravelForm>}
        </Modal>
    </AdminPage>;
}
