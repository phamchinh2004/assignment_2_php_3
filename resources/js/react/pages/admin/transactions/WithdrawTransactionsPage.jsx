import { formatAdminDateTime } from '../../../../shared/datetime';
import { Button, Modal, Radio, Space, Table, Tag, Typography } from 'antd';
import { BankOutlined, CheckCircleOutlined, CheckOutlined, ClockCircleOutlined, CloseCircleOutlined, CloseOutlined, TransactionOutlined } from '@ant-design/icons';
import { useMemo, useState } from 'react';
import LaravelForm from '../../../components/LaravelForm';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';
import { CustomerIdentity, OperationsIdentity, OperationsSummary, OperationsToolbar } from '../../../components/admin/OperationsUi';
import { matchesOperationsSearch, operationsMoney as money } from '../../../lib/operations';

const { Text } = Typography;
const routeFor = (template, token, id) => String(template || '').replace(token, encodeURIComponent(String(id)));
const dateTime = (value) => value ? formatAdminDateTime(value, {dateStyle:'short',timeStyle:'short'}) : '—';

export default function WithdrawTransactionsPage({ config }) {
    const [filter, setFilter] = useState('all');
    const [search, setSearch] = useState('');
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
    const filtered = transactions.filter((item) => (filter === 'all' || item.status === filter)
        && matchesOperationsSearch([item.id, item.user?.id, item.user?.full_name, item.user?.username, item.user?.phone, item.bank_name, item.account_number, item.username_bank, item.by_user?.username], search));
    const totalAmount = (items) => items.reduce((sum, item) => sum + Number(item.value || 0), 0);

    const columns = [
        { title:'Khách hàng / Yêu cầu', width:270, render:(_,item)=><OperationsIdentity avatarUrl={item.user?.avatar_url} name={item.user?.full_name||item.user?.username||'Tài khoản đã xóa'} secondary={item.user ? `@${item.user.username || '—'} · ${item.user.phone || 'Chưa có SĐT'}` : undefined} meta={`Yêu cầu #${item.id}`} href={item.user&&config.permissions?.viewCustomerDetail ? routeFor(config.routes.customerShow,'__USER_ID__',item.user.id) : undefined} />},
        { title:'Ngân hàng nhận tiền', width:220, render:(_,item)=><div><Text strong>{item.bank_name || '—'}</Text><div><Text code>{item.account_number || '—'}</Text></div><div className="operations-cell-note">Chủ TK: {item.username_bank || '—'}</div></div>},
        { title:'Số tiền', dataIndex:'value', width:150, align:'right', sorter:(a,b)=>Number(a.value)-Number(b.value), render:(value)=><span className="operations-money operations-money--negative">-{money(value)}</span>},
        { title:'Số dư trước', dataIndex:'balance_before', width:150, align:'right', render:(value)=>value===null||value===undefined?<Text type="secondary">—</Text>:<span className="operations-money">{money(value)}</span>},
        { title:'Số dư sau', dataIndex:'balance_after', width:150, align:'right', render:(value)=>value===null||value===undefined?<Text type="secondary">—</Text>:<span className="operations-money"><strong>{money(value)}</strong></span>},
        { title:'Trạng thái', dataIndex:'status', render:(value)=><Tag color={value==='processing'?'warning':value==='completed'?'success':'error'}>{value==='processing'?'Chờ xác nhận':value==='completed'?'Hoàn thành':'Đã hủy'}</Tag>},
        { title:'Loại GD', render:(_,item)=>item.user ? (Number(item.user.clone_account)===1 ? <Tag>Rút ảo</Tag> : <Tag color="success">Rút thực</Tag>) : <Text type="secondary">—</Text>},
        { title:'Duyệt bởi', width:150, render:(_,item)=>item.by_user?.username || (item.status==='processing'?'Chờ xử lý':'—') },
        { title:'Thời gian', dataIndex:'created_at', width:175, render:dateTime },
        { title:'Thao tác', width:195, align:'right', render:(_,item)=> item.status==='processing'&&(config.permissions?.confirm||config.permissions?.cancel)?<Space className="operations-row-actions">{config.permissions?.confirm&&<Button type="primary" size="small" icon={<CheckOutlined />} onClick={()=>{setConfirming(item);setTransactionType('normal');}}>Duyệt</Button>}{config.permissions?.cancel&&<LaravelForm action={routeFor(config.routes.cancel,'__TRANSACTION_ID__',item.id)} method="POST" onSubmit={(event)=>{if(!window.confirm('Từ chối giao dịch này và hoàn tiền vào tài khoản khách hàng?')) event.preventDefault();}}><Button htmlType="submit" danger size="small" icon={<CloseOutlined />}>Từ chối</Button></LaravelForm>}</Space>:<Text type="secondary">{item.status==='processing'?'—':'Đã xử lý'}</Text>},
    ];

    return <AdminPage className="admin-operations-page">
        <AdminPageHeader eyebrow="Tài chính · Rút tiền" icon={<BankOutlined />} title="Quản lý rút tiền" description="Theo dõi yêu cầu rút tiền, kiểm tra ngân hàng nhận và duyệt giao dịch." />
        <AdminMetricGrid items={[
            {key:'total',title:'Tổng yêu cầu',value:stats.total,hint:money(stats.totalAmount),tone:'primary',icon:<TransactionOutlined />},
            {key:'pending',title:'Chờ xác nhận',value:stats.pending.length,hint:money(totalAmount(stats.pending)),tone:'warning',icon:<ClockCircleOutlined />},
            {key:'completed',title:'Đã hoàn thành',value:stats.completed.length,hint:money(totalAmount(stats.completed)),tone:'success',icon:<CheckCircleOutlined />},
            {key:'cancelled',title:'Đã từ chối',value:stats.cancelled.length,hint:money(totalAmount(stats.cancelled)),tone:'danger',icon:<CloseCircleOutlined />},
        ]} />
        <AdminDataCard
            title="Danh sách yêu cầu rút tiền"
            description="Lọc yêu cầu chờ xác nhận để kiểm tra thông tin và xử lý giao dịch."
            extra={<Tag color={stats.pending.length?'orange':'default'}>{stats.pending.length} chờ xác nhận</Tag>}
            toolbar={<OperationsToolbar value={filter} onChange={setFilter} options={[{label:`Tất cả (${stats.total})`,value:'all'},{label:`Chờ xác nhận (${stats.pending.length})`,value:'processing'},{label:`Hoàn thành (${stats.completed.length})`,value:'completed'},{label:`Đã hủy (${stats.cancelled.length})`,value:'cancelled'}]} search={search} onSearch={setSearch} placeholder="Tìm khách hàng, ngân hàng, số tài khoản..." />}
        >
            <OperationsSummary shown={filtered.length} total={transactions.length} unit="yêu cầu">Tổng tiền theo bộ lọc: <strong>{money(totalAmount(filtered))}</strong></OperationsSummary>
            <Table rowKey="id" dataSource={filtered} columns={columns} scroll={{x:1800}} pagination={{pageSize:20,showSizeChanger:false,showTotal:(total)=>`${total} yêu cầu`}} locale={{emptyText:'Không có yêu cầu rút tiền phù hợp với bộ lọc'}} />
        </AdminDataCard>

        <Modal className="operations-withdraw-modal" title="Xác nhận giao dịch rút tiền" open={Boolean(confirming)} onCancel={()=>setConfirming(null)} onOk={()=>document.getElementById('confirm-withdraw-form')?.requestSubmit()} okText="Xác nhận duyệt" cancelText="Quay lại">
            {confirming && <div className="operations-confirm-summary">
                <div><span>Yêu cầu #{confirming.id}</span><strong>{money(confirming.value)}</strong></div>
                <dl><dt>Khách hàng</dt><dd><CustomerIdentity user={confirming.user} name={confirming.user?.full_name || confirming.user?.username || 'Tài khoản đã xóa'} /></dd><dt>Ngân hàng</dt><dd>{confirming.bank_name || '—'}</dd><dt>Số tài khoản</dt><dd>{confirming.account_number || '—'}</dd><dt>Chủ tài khoản</dt><dd>{confirming.username_bank || '—'}</dd></dl>
            </div>}
            <Text strong>Loại giao dịch</Text>
            <Radio.Group className="operations-withdraw-types" value={transactionType} onChange={(event)=>setTransactionType(event.target.value)}>
                <Radio value="normal"><strong>Rút thực</strong><span className="operations-radio-description">Tiền được chuyển thật cho khách</span></Radio>
                <Radio value="virtual_withdraw"><strong>Rút ảo</strong><span className="operations-radio-description">Chỉ ghi nhận trên hệ thống</span></Radio>
            </Radio.Group>
            {confirming && <LaravelForm id="confirm-withdraw-form" action={routeFor(config.routes.confirm,'__TRANSACTION_ID__',confirming.id)} method="POST" style={{display:'none'}}><input type="hidden" name="transaction_type" value={transactionType}/></LaravelForm>}
        </Modal>
    </AdminPage>;
}
