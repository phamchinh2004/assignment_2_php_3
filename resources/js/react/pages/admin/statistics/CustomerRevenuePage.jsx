import { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, Button, Card, Col, Input, Row, Select, Space, Statistic, Table, Tag, Typography } from 'antd';
import { ReloadOutlined } from '@ant-design/icons';
import { requestJson } from '../../../lib/http';
import StatisticsChart from './StatisticsChart';
import { invalidDateRange, money, number, startOfMonth, today, withQuery } from './statistics';

const { Text, Title } = Typography;

export default function CustomerRevenuePage({ config }) {
    const [startDate, setStartDate] = useState(startOfMonth());
    const [endDate, setEndDate] = useState(today());
    const [type, setType] = useState('daily');
    const [data, setData] = useState({ overview: {}, chart: {}, top: {}, distribution: {}, details: [] });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const loadData = useCallback(async () => {
        if (invalidDateRange(startDate, endDate)) {
            setError('Từ ngày không được lớn hơn đến ngày.');
            return;
        }

        setLoading(true);
        setError('');
        const base = { start_date: startDate, end_date: endDate };
        try {
            const [overview, chart, top, distribution, details] = await Promise.all([
                requestJson(withQuery(config.routes.overview, base)),
                requestJson(withQuery(config.routes.chart, { ...base, type })),
                requestJson(withQuery(config.routes.topCustomers, { ...base, limit: 10 })),
                requestJson(withQuery(config.routes.distribution, base)),
                requestJson(withQuery(config.routes.customerDetail, base)),
            ]);
            setData({ overview: overview?.data || {}, chart: chart?.data || {}, top: top?.data || {}, distribution: distribution?.data || {}, details: details?.data || [] });
        } catch (exception) {
            setError(exception.message || 'Không thể tải thống kê khách hàng.');
        } finally {
            setLoading(false);
        }
    }, [config.routes, startDate, endDate, type]);

    useEffect(() => { loadData(); }, [loadData]);

    const revenueChart = useMemo(() => ({ type: 'line', data: { labels: data.chart.labels || [], datasets: [{ label: 'Doanh thu nạp', data: data.chart.values || [], borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.12)', fill: true, tension: .3 }] }, options: { responsive: true, maintainAspectRatio: false } }), [data.chart]);
    const topChart = useMemo(() => ({ type: 'bar', data: { labels: data.top.labels || [], datasets: [{ label: 'Doanh thu', data: data.top.values || [], backgroundColor: '#0ea5e9', borderRadius: 6 }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } } }), [data.top]);
    const distributionChart = useMemo(() => ({ type: 'doughnut', data: { labels: data.distribution.labels || [], datasets: [{ data: data.distribution.values || [], backgroundColor: ['#4f46e5', '#0ea5e9', '#16a34a', '#f59e0b', '#ef4444', '#64748b'] }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '62%' } }), [data.distribution]);

    const columns = [
        { title: 'Khách hàng', dataIndex: 'full_name', render: (value) => <Text strong>{value || '—'}</Text> },
        { title: 'Số điện thoại', dataIndex: 'phone' },
        { title: 'Giao dịch', dataIndex: 'transaction_count', align: 'right', render: number },
        { title: 'Doanh thu', dataIndex: 'total_revenue', align: 'right', render: money },
        { title: 'Giao dịch gần nhất', dataIndex: 'last_transaction', render: (value) => value ? new Date(value).toLocaleString('vi-VN') : '—' },
    ];

    const overview = data.overview || {};

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Row justify="space-between" align="middle" gutter={[16, 16]}><Col><Title level={2} style={{ margin: 0 }}>Doanh thu từ khách hàng</Title><Text type="secondary">Phân tích dòng tiền nạp, khách hàng nổi bật và phân bổ doanh số.</Text></Col><Col><Button icon={<ReloadOutlined />} loading={loading} onClick={loadData}>Làm mới</Button></Col></Row>
            {error && <Alert type="error" showIcon message={error} />}
            <Card><Row gutter={[12, 12]} align="bottom"><Col xs={24} md={6}><Text strong>Chế độ biểu đồ</Text><Select style={{ width: '100%' }} value={type} onChange={setType} options={[{ value: 'daily', label: 'Theo ngày' }, { value: 'monthly', label: 'Theo tháng' }, { value: 'yearly', label: 'Theo năm' }]} /></Col><Col xs={12} md={6}><Text strong>Từ ngày</Text><Input type="date" value={startDate} onChange={(event) => setStartDate(event.target.value)} /></Col><Col xs={12} md={6}><Text strong>Đến ngày</Text><Input type="date" value={endDate} onChange={(event) => setEndDate(event.target.value)} /></Col><Col xs={24} md={6}><Button block type="primary" loading={loading} onClick={loadData}>Áp dụng</Button></Col></Row></Card>
            <Row gutter={[16, 16]}><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="Tổng doanh thu" value={overview.total_revenue || 0} formatter={(value) => money(value)} /><Tag color={Number(overview.revenue_growth) >= 0 ? 'success' : 'error'}>{Number(overview.revenue_growth || 0)}%</Tag></Card></Col><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="Giao dịch" value={overview.total_transactions || 0} formatter={number} /></Card></Col><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="Khách phát sinh" value={overview.total_customers || 0} formatter={number} /></Card></Col><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="TB giao dịch" value={overview.avg_transaction || 0} formatter={(value) => money(value)} /><Text type="secondary">Top: {overview.top_customer_name || 'Chưa có'} · {money(overview.top_customer_amount)}</Text></Card></Col></Row>
            <Row gutter={[16, 16]}><Col xs={24} xl={14}><Card title="Xu hướng doanh thu"><StatisticsChart config={revenueChart} height={320} /></Card></Col><Col xs={24} xl={10}><Card title="Phân bổ doanh thu"><StatisticsChart config={distributionChart} height={320} /></Card></Col><Col xs={24} xl={10}><Card title="Top khách hàng"><StatisticsChart config={topChart} height={360} /></Card></Col><Col xs={24} xl={14}><Card title="Chi tiết khách hàng"><Table rowKey={(row) => `${row.phone}-${row.full_name}`} loading={loading} columns={columns} dataSource={data.details} pagination={{ pageSize: 10 }} scroll={{ x: 800 }} /></Card></Col></Row>
        </Space>
    );
}
