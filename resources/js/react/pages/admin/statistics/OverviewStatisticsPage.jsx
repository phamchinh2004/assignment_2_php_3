import { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, Button, Card, Col, Input, Row, Segmented, Space, Statistic, Table, Tag, Typography } from 'antd';
import { DownloadOutlined, ReloadOutlined } from '@ant-design/icons';
import { requestJson } from '../../../lib/http';
import StatisticsChart from './StatisticsChart';
import { daysAgo, inclusiveDays, invalidDateRange, money, number, statusTag, today, withQuery } from './statistics';

const { Text, Title } = Typography;

export default function OverviewStatisticsPage({ config }) {
    const [period, setPeriod] = useState(30);
    const [startDate, setStartDate] = useState(daysAgo(29));
    const [endDate, setEndDate] = useState(today());
    const [payload, setPayload] = useState(null);
    const [statusData, setStatusData] = useState({});
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const loadData = useCallback(async () => {
        if (invalidDateRange(startDate, endDate)) {
            setError('Ngày bắt đầu không được lớn hơn ngày kết thúc.');
            return;
        }

        setLoading(true);
        setError('');
        try {
            const effectivePeriod = inclusiveDays(startDate, endDate, period);
            const params = { period: effectivePeriod, start_date: startDate, end_date: endDate };
            const [revenue, statuses] = await Promise.all([
                requestJson(withQuery(config.routes.revenueData, params)),
                requestJson(withQuery(config.routes.statusStats, params)),
            ]);
            setPayload(revenue);
            setStatusData(statuses?.data || {});
        } catch (exception) {
            setError(exception.message || 'Không thể tải dữ liệu doanh thu.');
        } finally {
            setLoading(false);
        }
    }, [config.routes.revenueData, config.routes.statusStats, period, startDate, endDate]);

    useEffect(() => { loadData(); }, [loadData]);

    const setPreset = (days) => {
        const numericDays = Number(days);
        setPeriod(numericDays);
        setStartDate(daysAgo(Math.max(0, numericDays - 1)));
        setEndDate(today());
    };

    const summary = payload?.summary || {};
    const chart = payload?.chart_data || {};
    const statusEntries = Object.entries(statusData || {}).map(([status, items]) => ({
        status,
        count: (Array.isArray(items) ? items : []).reduce((total, item) => total + Number(item.count || 0), 0),
    }));

    const revenueChart = useMemo(() => ({
        type: 'line',
        data: {
            labels: chart.labels || [],
            datasets: [
                { label: 'Nạp tiền', data: chart.deposit_data || [], borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.12)', tension: .3, fill: true },
                { label: 'Rút tiền', data: chart.withdraw_data || [], borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.08)', tension: .3 },
                { label: 'Doanh thu ròng', data: chart.revenue_data || [], borderColor: '#4f46e5', backgroundColor: 'rgba(79,70,229,.1)', tension: .3 },
            ],
        },
        options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false } },
    }), [chart]);

    const flowChart = useMemo(() => ({
        type: 'doughnut',
        data: {
            labels: ['Nạp tiền', 'Rút tiền'],
            datasets: [{ data: [summary.total_deposit || 0, summary.total_withdraw || 0], backgroundColor: ['#16a34a', '#f59e0b'] }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%' },
    }), [summary.total_deposit, summary.total_withdraw]);

    const statusChart = useMemo(() => ({
        type: 'doughnut',
        data: {
            labels: statusEntries.map((item) => statusTag(item.status).label),
            datasets: [{ data: statusEntries.map((item) => item.count), backgroundColor: ['#16a34a', '#f59e0b', '#ef4444', '#3b82f6', '#64748b'] }],
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '60%' },
    }), [statusEntries]);

    const transactionColumns = [
        { title: 'Mã GD', dataIndex: 'id', width: 90, render: (value) => `#${value}` },
        { title: 'Khách hàng', render: (_, row) => row.user?.full_name || '—' },
        { title: 'Số điện thoại', render: (_, row) => row.user?.phone || '—' },
        { title: 'Loại', dataIndex: 'type', render: (value) => <Tag color={value === 'deposit' ? 'success' : 'warning'}>{value === 'deposit' ? 'Nạp tiền' : 'Rút tiền'}</Tag> },
        { title: 'Số tiền', dataIndex: 'value', align: 'right', render: money },
        { title: 'Trạng thái', dataIndex: 'status', render: (value) => { const item = statusTag(value); return <Tag color={item.color}>{item.label}</Tag>; } },
        { title: 'Thời gian', dataIndex: 'created_at', render: (value) => value ? new Date(value).toLocaleString('vi-VN') : '—' },
    ];

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Row justify="space-between" align="middle" gutter={[16, 16]}>
                <Col><Title level={2} style={{ margin: 0 }}>Thống kê tổng doanh thu</Title><Text type="secondary">Dòng tiền nạp, rút và doanh thu ròng toàn hệ thống.</Text></Col>
                <Col><Space><Button icon={<ReloadOutlined />} loading={loading} onClick={loadData}>Làm mới</Button>{config.permissions?.export && <Button type="primary" icon={<DownloadOutlined />} disabled={invalidDateRange(startDate, endDate)} onClick={() => window.location.assign(withQuery(config.routes.export, { period: inclusiveDays(startDate, endDate, period), start_date: startDate, end_date: endDate }))}>Xuất CSV</Button>}</Space></Col>
            </Row>

            {error && <Alert type="error" showIcon message={error} />}

            <Card>
                <Row gutter={[12, 12]} align="bottom">
                    <Col xs={24} lg={8}><Text strong>Khoảng nhanh</Text><div style={{ marginTop: 6 }}><Segmented block value={period} options={[{ label: 'Hôm nay', value: 1 }, { label: '7 ngày', value: 7 }, { label: '30 ngày', value: 30 }, { label: '1 năm', value: 365 }]} onChange={setPreset} /></div></Col>
                    <Col xs={12} lg={5}><Text strong>Từ ngày</Text><Input type="date" value={startDate} onChange={(event) => setStartDate(event.target.value)} /></Col>
                    <Col xs={12} lg={5}><Text strong>Đến ngày</Text><Input type="date" value={endDate} onChange={(event) => setEndDate(event.target.value)} /></Col>
                    <Col xs={24} lg={6}><Button block type="primary" loading={loading} onClick={loadData}>Áp dụng</Button></Col>
                </Row>
            </Card>

            <Row gutter={[16, 16]}>
                <Col xs={24} md={12} xl={8}><Card loading={loading}><Statistic title="Doanh thu ròng" value={summary.total_revenue || 0} formatter={(value) => money(value)} /><Tag color={Number(summary.revenue_growth) >= 0 ? 'success' : 'error'}>{Number(summary.revenue_growth || 0)}% so kỳ trước</Tag></Card></Col>
                <Col xs={24} md={12} xl={8}><Card loading={loading}><Statistic title="Tổng nạp tiền" value={summary.total_deposit || 0} formatter={(value) => money(value)} /><Text type="secondary">{number(summary.deposit_count)} lệnh nạp</Text></Card></Col>
                <Col xs={24} md={12} xl={8}><Card loading={loading}><Statistic title="Tổng rút tiền" value={summary.total_withdraw || 0} formatter={(value) => money(value)} /><Text type="secondary">{number(summary.withdraw_count)} lệnh rút</Text></Card></Col>
                <Col xs={24} md={12} xl={8}><Card loading={loading}><Statistic title="Tổng giao dịch" value={summary.total_transactions || 0} formatter={number} /></Card></Col>
                <Col xs={24} md={12} xl={8}><Card loading={loading}><Statistic title="TB mỗi lệnh nạp" value={summary.avg_deposit || 0} formatter={(value) => money(value)} /></Card></Col>
                <Col xs={24} md={12} xl={8}><Card loading={loading}><Statistic title="Khách phát sinh nạp" value={summary.unique_customers || 0} formatter={number} /></Card></Col>
            </Row>

            <Row gutter={[16, 16]}>
                <Col xs={24} xl={16}><Card title="Xu hướng doanh thu"><StatisticsChart config={revenueChart} height={330} /></Card></Col>
                <Col xs={24} xl={8}><Card title="Cơ cấu nạp / rút"><StatisticsChart config={flowChart} height={330} /></Card></Col>
                <Col xs={24} xl={8}><Card title="Trạng thái giao dịch"><StatisticsChart config={statusChart} height={280} /></Card></Col>
                <Col xs={24} xl={16}><Card title="Giao dịch gần đây"><Table rowKey="id" size="small" loading={loading} columns={transactionColumns} dataSource={payload?.recent_transactions || []} pagination={false} scroll={{ x: 900 }} /></Card></Col>
            </Row>
        </Space>
    );
}
