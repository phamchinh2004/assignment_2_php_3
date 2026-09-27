import { Button, Card, Col, Popconfirm, Row, Segmented, Space, Statistic, Table, Tag, Typography } from 'antd';
import { useMemo, useState } from 'react';
import LaravelForm from '../../../components/LaravelForm';

const { Text, Title } = Typography;
const routeFor = (template, token, id) => String(template || '').replace(token, encodeURIComponent(String(id)));
const money = (value) => `${new Intl.NumberFormat('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}).format(Number(value || 0))}$`;
const dateTime = (value) => value ? new Intl.DateTimeFormat('vi-VN',{dateStyle:'short',timeStyle:'short'}).format(new Date(value)) : '—';

export default function DepositTransactionsPage({ config }) {
    const [filter,setFilter] = useState('all');
    const transactions = config.transactions || [];
    const normal = useMemo(()=>transactions.filter((item)=>item.transaction_type==='normal'),[transactions]);
    const bonus = useMemo(()=>transactions.filter((item)=>item.transaction_type==='bonus'),[transactions]);
    const filtered = filter==='all'?transactions:transactions.filter((item)=>item.transaction_type===filter);

    const columns = [
        { title:'Khách hàng', render:(_,item)=>{const user=item.user;const label=user?.full_name||user?.username||'Tài khoản đã xóa';const content=<div><Text strong>{label}</Text>{user&&<div><Text type="secondary">@{user.username || '—'} · {user.phone || '—'}</Text></div>}</div>;return user&&config.permissions?.viewCustomerDetail?<a href={routeFor(config.routes.customerShow,'__USER_ID__',user.id)}>{content}</a>:content;}},
        { title:'Người nạp (Staff)', render:(_,item)=>item.by_user?.username || 'Hệ thống' },
        { title:'Số tiền', dataIndex:'value', render:(value)=><Text strong style={{color:'#16a34a'}}>+{money(value)}</Text> },
        { title:'Số dư trước', dataIndex:'balance_before', render:(value)=>value===null||value===undefined?<Text type="secondary">—</Text>:money(value)},
        { title:'Số dư sau', dataIndex:'balance_after', render:(value)=>value===null||value===undefined?<Text type="secondary">—</Text>:<Text strong>{money(value)}</Text>},
        { title:'Loại giao dịch', dataIndex:'transaction_type', render:(value)=>value==='normal'?<Tag color="success">Nạp thực</Tag>:value==='bonus'?<Tag color="warning">Nạp thưởng</Tag>:<Tag>{value}</Tag>},
        { title:'Thời gian', dataIndex:'created_at', render:dateTime },
        { title:'Thao tác', width:230, render:(_,item)=><Space>{config.permissions?.changeType&&['normal','bonus'].includes(item.transaction_type)&&<Button size="small" href={routeFor(config.routes.changeType,'__TRANSACTION_ID__',item.id)}>{item.transaction_type==='normal'?'Sang thưởng':'Sang thực'}</Button>}{config.permissions?.delete&&<><LaravelForm id={`delete-deposit-${item.id}`} action={routeFor(config.routes.destroy,'__TRANSACTION_ID__',item.id)} method="DELETE" style={{display:'none'}}/><Popconfirm title="Xóa giao dịch?" description="Số tiền sẽ bị trừ khỏi tài khoản người dùng." okText="Xóa" cancelText="Hủy" okButtonProps={{danger:true}} onConfirm={()=>document.getElementById(`delete-deposit-${item.id}`)?.requestSubmit()}><Button size="small" danger>Xóa</Button></Popconfirm></>}</Space>},
    ];

    return <div className="container-fluid px-4 pb-5">
        <div className="mb-4"><Title level={2}>Lịch sử nạp tiền</Title><Text type="secondary">Theo dõi tiền nạp thực, tiền thưởng và snapshot số dư trước/sau giao dịch.</Text></div>
        <Row gutter={[16,16]} className="mb-4">
            <Col xs={24} lg={8}><Card><Statistic title="Tổng giao dịch nạp" value={transactions.length} suffix={<Text type="secondary">· {money(transactions.reduce((s,i)=>s+Number(i.value||0),0))}</Text>} /></Card></Col>
            <Col xs={24} lg={8}><Card><Statistic title="Tiền nạp thực" value={normal.length} suffix={<Text type="secondary">· {money(normal.reduce((s,i)=>s+Number(i.value||0),0))}</Text>} /></Card></Col>
            <Col xs={24} lg={8}><Card><Statistic title="Tiền nạp thưởng" value={bonus.length} suffix={<Text type="secondary">· {money(bonus.reduce((s,i)=>s+Number(i.value||0),0))}</Text>} /></Card></Col>
        </Row>
        <Card title="Danh sách giao dịch nạp tiền" extra={<Segmented value={filter} onChange={setFilter} options={[{label:`Tất cả (${transactions.length})`,value:'all'},{label:`Nạp thực (${normal.length})`,value:'normal'},{label:`Nạp thưởng (${bonus.length})`,value:'bonus'}]} />}>
            <Table rowKey="id" dataSource={filtered} columns={columns} scroll={{x:1150}} pagination={{pageSize:20,showSizeChanger:false}} />
        </Card>
    </div>;
}
