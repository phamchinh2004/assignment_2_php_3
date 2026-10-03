import { formatAdminDateTime } from '../../../../shared/datetime';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, Button, Card, Col, Row, Segmented, Space, Statistic, Table, Tag, Typography } from 'antd';
import { ReloadOutlined } from '@ant-design/icons';
import { requestJson } from '../../../lib/http';
import StatisticsChart from './StatisticsChart';
import { CustomerIdentity } from '../../../components/admin/OperationsUi';
import { money, number, statusTag, withQuery } from './statistics';

const { Text, Title } = Typography;

export default function PersonalRevenuePage({ config }) {
    const [range, setRange] = useState('7_days');
    const [stats, setStats] = useState(null);
    const [transactions, setTransactions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const loadData = useCallback(async () => {
        setLoading(true);
        setError('');
        try {
            const [statsResponse, transactionsResponse] = await Promise.all([
                requestJson(withQuery(config.routes.stats, { time_range: range })),
                requestJson(withQuery(config.routes.transactions, { per_page: 10 })),
            ]);
            setStats(statsResponse?.data || null);
            setTransactions(transactionsResponse?.data?.data || []);
        } catch (exception) {
            setError(exception.message || 'Không thể tải thống kê cá nhân.');
        } finally {
            setLoading(false);
        }
    }, [config.routes.stats, config.routes.transactions, range]);

    useEffect(() => { loadData(); }, [loadData]);

    const dailyChart = useMemo(() => ({ type: 'line', data: { labels: (stats?.daily_revenue || []).map((item) => item.formatted_date), datasets: [{ label: 'Doanh thu nạp', data: (stats?.daily_revenue || []).map((item) => item.total_revenue), borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.12)', fill: true, tension: .3 }, { label: 'Số giao dịch', data: (stats?.daily_revenue || []).map((item) => item.transaction_count), borderColor: '#f59e0b', tension: .3 }] }, options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false } } }), [stats]);
    const typeChart = useMemo(() => ({ type: 'doughnut', data: { labels: (stats?.transaction_type_stats || []).map((item) => item.type_name), datasets: [{ data: (stats?.transaction_type_stats || []).map((item) => item.total_value), backgroundColor: ['#16a34a', '#f59e0b', '#64748b'] }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '65%' } }), [stats]);
    const monthlyChart = useMemo(() => ({ type: 'bar', data: { labels: (stats?.monthly_revenue || []).map((item) => item.month_name), datasets: [{ label: 'Doanh thu tháng', data: (stats?.monthly_revenue || []).map((item) => item.total_revenue), backgroundColor: '#4f46e5', borderRadius: 6 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } }), [stats]);

    const columns = [
        { title: 'Mã GD', dataIndex: 'id', render: (value) => `#${value}` },
        { title: 'Khách hàng', render: (_, row) => <CustomerIdentity user={row.user} /> },
        { title: 'Loại', dataIndex: 'type', render: (value) => <Tag color={value === 'deposit' ? 'success' : 'warning'}>{value === 'deposit' ? 'Nạp tiền' : 'Rút tiền'}</Tag> },
        { title: 'Số tiền', dataIndex: 'value', align: 'right', render: money },
        { title: 'Trạng thái', dataIndex: 'status', render: (value) => { const item = statusTag(value); return <Tag color={item.color}>{item.label}</Tag>; } },
        { title: 'Thời gian', dataIndex: 'created_at', render: (value) => value ? formatAdminDateTime(value, { dateStyle: 'short', timeStyle: 'medium' }) : '—' },
    ];

    const overview = stats?.overview_stats || {};

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Row justify="space-between" align="middle" gutter={[16, 16]}><Col><Title level={2} style={{ margin: 0 }}>Doanh thu cá nhân</Title><Text type="secondary">Doanh số từ khách hàng do tài khoản hiện tại quản lý.</Text></Col><Col><Button icon={<ReloadOutlined />} loading={loading} onClick={loadData}>Làm mới</Button></Col></Row>
            {error && <Alert type="error" showIcon message={error} />}
            {Number(overview.legacy_transactions || 0) > 0 && <Alert type="warning" showIcon message={`${number(overview.legacy_transactions)} giao dịch cũ chưa có snapshot nhân viên phụ trách`} description="Các giao dịch cũ đang dùng người quản lý hiện tại làm dữ liệu dự phòng. Giao dịch mới được cố định theo người phụ trách tại thời điểm phát sinh." />}
            <Card><Segmented block value={range} onChange={setRange} options={[{ value: '7_days', label: '7 ngày' }, { value: '30_days', label: '30 ngày' }, { value: '3_months', label: '3 tháng' }, { value: '6_months', label: '6 tháng' }, { value: '1_year', label: '1 năm' }]} /></Card>
            <Row gutter={[16, 16]}><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="Doanh thu nạp" value={overview.total_revenue || 0} formatter={(value) => money(value)} /><Tag color={Number(overview.growth_rate) >= 0 ? 'success' : 'error'}>{Number(overview.growth_rate || 0)}%</Tag></Card></Col><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="Khách rút tiền" value={overview.total_withdraw || 0} formatter={(value) => money(value)} /></Card></Col><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="Dòng tiền ròng" value={overview.net_revenue || 0} formatter={(value) => money(value)} /></Card></Col><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="TB mỗi lượt nạp" value={overview.avg_transaction_value || 0} formatter={(value) => money(value)} /><Text type="secondary">{number(overview.deposit_count)} lượt nạp</Text></Card></Col></Row>
            <Row gutter={[16, 16]}><Col xs={24} xl={16}><Card title="Doanh thu theo thời gian"><StatisticsChart config={dailyChart} height={330} /></Card></Col><Col xs={24} xl={8}><Card title="Cơ cấu nạp / rút"><StatisticsChart config={typeChart} height={330} /></Card></Col>{(stats?.monthly_revenue || []).length > 0 && <Col xs={24}><Card title="Tổng hợp theo tháng"><StatisticsChart config={monthlyChart} height={280} /></Card></Col>}<Col xs={24}><Card title="Giao dịch gần đây từ khách hàng quản lý"><Table rowKey="id" loading={loading} columns={columns} dataSource={transactions} pagination={false} scroll={{ x: 850 }} /></Card></Col></Row>
        </Space>
    );
}
