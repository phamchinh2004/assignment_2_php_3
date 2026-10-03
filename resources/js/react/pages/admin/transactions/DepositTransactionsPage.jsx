import { formatAdminDateTime } from '../../../../shared/datetime';
import { Button, Popconfirm, Space, Table, Tag, Typography } from 'antd';
import { DeleteOutlined, GiftOutlined, SwapOutlined, TransactionOutlined, WalletOutlined } from '@ant-design/icons';
import { useMemo, useState } from 'react';
import LaravelForm from '../../../components/LaravelForm';
import { AdminDataCard, AdminMetricGrid, AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';
import { OperationsIdentity, OperationsSummary, OperationsToolbar } from '../../../components/admin/OperationsUi';
import { matchesOperationsSearch, operationsMoney as money } from '../../../lib/operations';

const { Text } = Typography;
const routeFor = (template, token, id) => String(template || '').replace(token, encodeURIComponent(String(id)));
const dateTime = (value) => value ? formatAdminDateTime(value, {dateStyle:'short',timeStyle:'short'}) : '—';

export default function DepositTransactionsPage({ config }) {
    const [filter,setFilter] = useState('all');
    const [search, setSearch] = useState('');
    const transactions = config.transactions || [];
    const normal = useMemo(()=>transactions.filter((item)=>item.transaction_type==='normal'),[transactions]);
    const bonus = useMemo(()=>transactions.filter((item)=>item.transaction_type==='bonus'),[transactions]);
    const filtered = transactions.filter((item) => (filter === 'all' || item.transaction_type === filter)
        && matchesOperationsSearch([item.id, item.user?.id, item.user?.full_name, item.user?.username, item.user?.phone, item.by_user?.full_name, item.by_user?.username], search));
    const totalAmount = (items) => items.reduce((sum, item) => sum + Number(item.value || 0), 0);

    const columns = [
        { title:'Khách hàng / Giao dịch', width:275, render:(_,item)=><OperationsIdentity avatarUrl={item.user?.avatar_url} name={item.user?.full_name||item.user?.username||'Tài khoản đã xóa'} secondary={item.user ? `@${item.user.username || '—'} · ${item.user.phone || 'Chưa có SĐT'}` : undefined} meta={`Giao dịch #${item.id}`} href={item.user&&config.permissions?.viewCustomerDetail ? routeFor(config.routes.customerShow,'__USER_ID__',item.user.id) : undefined} />},
        { title:'Người nạp', width:170, render:(_,item)=><div><Text strong>{item.by_user?.full_name || item.by_user?.username || 'Hệ thống'}</Text>{item.by_user?.username&&<div className="operations-cell-note">@{item.by_user.username}</div>}</div> },
        { title:'Số tiền', dataIndex:'value', width:155, align:'right', sorter:(a,b)=>Number(a.value)-Number(b.value), render:(value)=><span className="operations-money operations-money--positive">+{money(value)}</span> },
        { title:'Số dư trước', dataIndex:'balance_before', width:150, align:'right', render:(value)=>value===null||value===undefined?<Text type="secondary">—</Text>:<span className="operations-money">{money(value)}</span>},
        { title:'Số dư sau', dataIndex:'balance_after', width:150, align:'right', render:(value)=>value===null||value===undefined?<Text type="secondary">—</Text>:<span className="operations-money"><strong>{money(value)}</strong></span>},
        { title:'Loại giao dịch', dataIndex:'transaction_type', render:(value)=>value==='normal'?<Tag color="success">Nạp thực</Tag>:value==='bonus'?<Tag color="warning">Nạp thưởng</Tag>:<Tag>{value}</Tag>},
        { title:'Thời gian', dataIndex:'created_at', width:175, render:dateTime },
        { title:'Thao tác', width:240, align:'right', render:(_,item)=><Space className="operations-row-actions">{config.permissions?.changeType&&['normal','bonus'].includes(item.transaction_type)&&<Button size="small" icon={<SwapOutlined />} href={routeFor(config.routes.changeType,'__TRANSACTION_ID__',item.id)}>{item.transaction_type==='normal'?'Sang thưởng':'Sang thực'}</Button>}{config.permissions?.delete&&<><LaravelForm id={`delete-deposit-${item.id}`} action={routeFor(config.routes.destroy,'__TRANSACTION_ID__',item.id)} method="DELETE" style={{display:'none'}}/><Popconfirm title="Xóa giao dịch?" description="Số tiền sẽ bị trừ khỏi tài khoản người dùng." okText="Xóa" cancelText="Hủy" okButtonProps={{danger:true}} onConfirm={()=>document.getElementById(`delete-deposit-${item.id}`)?.requestSubmit()}><Button size="small" icon={<DeleteOutlined />} danger>Xóa</Button></Popconfirm></>}{!config.permissions?.changeType&&!config.permissions?.delete&&<Text type="secondary">—</Text>}</Space>},
    ];

    return <AdminPage className="admin-operations-page">
        <AdminPageHeader eyebrow="Tài chính · Nạp tiền" icon={<WalletOutlined />} title="Lịch sử nạp tiền" description="Tra cứu giao dịch nạp, đối chiếu số tiền và số dư tại thời điểm giao dịch." />
        <AdminMetricGrid min={3} items={[
            {key:'total',title:'Tổng tiền nạp',value:money(totalAmount(transactions)),hint:`${transactions.length} giao dịch`,tone:'primary',icon:<TransactionOutlined />},
            {key:'normal',title:'Tiền nạp thực',value:money(totalAmount(normal)),hint:`${normal.length} giao dịch nạp thực`,tone:'success',icon:<WalletOutlined />},
            {key:'bonus',title:'Tiền nạp thưởng',value:money(totalAmount(bonus)),hint:`${bonus.length} giao dịch nạp thưởng`,tone:'warning',icon:<GiftOutlined />},
        ]} />
        <AdminDataCard
            title="Danh sách giao dịch nạp tiền"
            description="Số dư trước và sau được ghi nhận tại thời điểm giao dịch; dấu — là chưa có dữ liệu."
            extra={<Tag>{transactions.length} giao dịch</Tag>}
            toolbar={<OperationsToolbar value={filter} onChange={setFilter} options={[{label:`Tất cả (${transactions.length})`,value:'all'},{label:`Nạp thực (${normal.length})`,value:'normal'},{label:`Nạp thưởng (${bonus.length})`,value:'bonus'}]} search={search} onSearch={setSearch} placeholder="Tìm khách hàng, người nạp, mã giao dịch..." />}
        >
            <OperationsSummary shown={filtered.length} total={transactions.length}>Tổng tiền theo bộ lọc: <strong>{money(totalAmount(filtered))}</strong></OperationsSummary>
            <Table rowKey="id" dataSource={filtered} columns={columns} scroll={{x:1500}} pagination={{pageSize:20,showSizeChanger:false,showTotal:(total)=>`${total} giao dịch`}} locale={{emptyText:'Không có giao dịch nạp phù hợp với bộ lọc'}} />
        </AdminDataCard>
    </AdminPage>;
}
