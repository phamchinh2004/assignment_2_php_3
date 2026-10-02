import { formatAdminDateTime } from '../../../../shared/datetime';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, Button, Card, Segmented, Select, Skeleton, Table, Tag, Typography } from 'antd';
import {
    BarChartOutlined,
    CalendarOutlined,
    DollarCircleOutlined,
    DownloadOutlined,
    FallOutlined,
    FilterOutlined,
    PieChartOutlined,
    ReloadOutlined,
    RiseOutlined,
    SwapOutlined,
    TagsOutlined,
    TeamOutlined,
    WalletOutlined,
} from '@ant-design/icons';
import { requestJson } from '../../../lib/http';
import StatisticsChart from './StatisticsChart';
import { AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';
import { AdminDatePicker } from '../../../components/admin/AdminDatePicker';
import { daysAgo, inclusiveDays, invalidDateRange, money, number, statusTag, today, withQuery } from './statistics';

const { Text } = Typography;

const CHART_COLORS = {
    primary: '#4f46e5',
    success: '#10b981',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#3b82f6',
    muted: '#94a3b8',
};

const STATUS_COLORS = {
    completed: CHART_COLORS.success,
    pending: CHART_COLORS.warning,
    processing: CHART_COLORS.info,
    rejected: CHART_COLORS.danger,
    failed: CHART_COLORS.danger,
    cancelled: CHART_COLORS.danger,
};

const TOOLTIP_STYLE = {
    backgroundColor: '#ffffff',
    titleColor: '#0f172a',
    bodyColor: '#475569',
    borderColor: '#e2e8f0',
    borderWidth: 1,
    padding: 12,
};

function parseLocalDate(value) {
    if (!value) return null;
    const [year, month, day] = value.split('-').map(Number);
    return new Date(year, month - 1, day);
}

function denseChartSeries(chart, startDate, endDate, period) {
    const sourceLabels = chart?.labels || [];
    const sourceDeposit = chart?.deposit_data || [];
    const sourceWithdraw = chart?.withdraw_data || [];
    const sourceRevenue = chart?.revenue_data || [];
    const depositMap = new Map(sourceLabels.map((label, index) => [label, Number(sourceDeposit[index] || 0)]));
    const withdrawMap = new Map(sourceLabels.map((label, index) => [label, Number(sourceWithdraw[index] || 0)]));
    const revenueMap = new Map(sourceLabels.map((label, index) => [label, Number(sourceRevenue[index] || 0)]));
    const start = parseLocalDate(startDate);
    const end = parseLocalDate(endDate);

    if (!start || !end || start > end) {
        return {
            labels: sourceLabels,
            deposit: sourceDeposit.map(Number),
            withdraw: sourceWithdraw.map(Number),
            revenue: sourceRevenue.map(Number),
        };
    }

    const labels = [];
    const cursor = new Date(start);

    if (period <= 30) {
        while (cursor <= end) {
            labels.push(`${String(cursor.getDate()).padStart(2, '0')}/${String(cursor.getMonth() + 1).padStart(2, '0')}`);
            cursor.setDate(cursor.getDate() + 1);
        }
    } else {
        cursor.setDate(1);
        const lastMonth = new Date(end.getFullYear(), end.getMonth(), 1);
        while (cursor <= lastMonth) {
            labels.push(`${String(cursor.getMonth() + 1).padStart(2, '0')}/${cursor.getFullYear()}`);
            cursor.setMonth(cursor.getMonth() + 1);
        }
    }

    return {
        labels,
        deposit: labels.map((label) => depositMap.get(label) || 0),
        withdraw: labels.map((label) => withdrawMap.get(label) || 0),
        revenue: labels.map((label) => revenueMap.get(label) || 0),
    };
}

function MiniSparkline({ values = [], tone = 'primary' }) {
    const numeric = values.map((value) => Number(value || 0));
    const meaningfulPoints = numeric.filter((value) => value !== 0).length;
    if (numeric.length < 2 || meaningfulPoints < 2) return null;

    const width = 116;
    const height = 38;
    const min = Math.min(0, ...numeric);
    const max = Math.max(0, ...numeric);
    const span = Math.max(1, max - min);
    const points = numeric.map((value, index) => {
        const x = (index / Math.max(1, numeric.length - 1)) * width;
        const y = height - ((value - min) / span) * (height - 4) - 2;
        return `${x.toFixed(1)},${y.toFixed(1)}`;
    }).join(' ');

    return (
        <svg className={`revenue-kpi-sparkline is-${tone}`} viewBox={`0 0 ${width} ${height}`} aria-hidden="true">
            <polyline points={points} vectorEffect="non-scaling-stroke" />
        </svg>
    );
}

function MetricCard({ icon, tone, label, value, subtext, trend, sparkline, loading }) {
    const trendValue = Number(trend || 0);
    const hasTrend = trend !== undefined && trend !== null;
    return (
        <Card className={`revenue-kpi-card is-${tone || 'primary'}`}>
            {loading ? (
                <Skeleton active paragraph={{ rows: 1 }} title={{ width: '58%' }} />
            ) : (
                <div className="revenue-kpi-card__inner">
                    <span className="revenue-kpi-card__icon" aria-hidden="true">{icon}</span>
                    <div className="revenue-kpi-card__copy">
                        <span className="revenue-kpi-card__label">{label}</span>
                        <strong className="revenue-kpi-card__value">{value}</strong>
                        {hasTrend ? (
                            <span className={`revenue-kpi-card__trend ${trendValue < 0 ? 'is-down' : 'is-up'}`}>
                                {trendValue < 0 ? <FallOutlined /> : <RiseOutlined />}
                                {Math.abs(trendValue)}% so kỳ trước
                            </span>
                        ) : subtext ? <span className="revenue-kpi-card__subtext">{subtext}</span> : null}
                    </div>
                    {sparkline}
                </div>
            )}
        </Card>
    );
}

function ChartEmptyState({
    compact = false,
    title = 'Chưa có dữ liệu doanh thu trong khoảng thời gian này',
    description = 'Thử chọn một khoảng thời gian khác để xem xu hướng.',
}) {
    return (
        <div className={`revenue-chart-empty${compact ? ' is-compact' : ''}`}>
            <span className="revenue-chart-empty__icon" aria-hidden="true"><BarChartOutlined /></span>
            <strong>{title}</strong>
            <span>{description}</span>
        </div>
    );
}

function chartCenterPlugin(id, value, label) {
    return {
        id,
        afterDraw(instance) {
            const meta = instance.getDatasetMeta(0);
            const center = meta?.data?.[0];
            if (!center) return;
            const { ctx } = instance;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#0f172a';
            ctx.font = '700 16px Inter, system-ui, sans-serif';
            ctx.fillText(value, center.x, center.y - 7);
            ctx.fillStyle = '#64748b';
            ctx.font = '500 10px Inter, system-ui, sans-serif';
            ctx.fillText(label, center.x, center.y + 12);
            ctx.restore();
        },
    };
}

export default function OverviewStatisticsPage({ config }) {
    const initialRange = useMemo(() => ({ period: 30, startDate: daysAgo(29), endDate: today() }), []);
    const [period, setPeriod] = useState(initialRange.period);
    const [startDate, setStartDate] = useState(initialRange.startDate);
    const [endDate, setEndDate] = useState(initialRange.endDate);
    const [appliedRange, setAppliedRange] = useState(initialRange);
    const [payload, setPayload] = useState(null);
    const [statusData, setStatusData] = useState({});
    const [loading, setLoading] = useState(false);
    const [exporting, setExporting] = useState(false);
    const [error, setError] = useState('');

    const loadData = useCallback(async (range = appliedRange) => {
        if (invalidDateRange(range.startDate, range.endDate)) {
            setError('Ngày bắt đầu không được lớn hơn ngày kết thúc.');
            return;
        }

        setLoading(true);
        setError('');
        try {
            const effectivePeriod = inclusiveDays(range.startDate, range.endDate, range.period);
            const params = { period: effectivePeriod, start_date: range.startDate, end_date: range.endDate };
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
    }, [appliedRange, config.routes.revenueData, config.routes.statusStats]);

    useEffect(() => { loadData(appliedRange); }, [appliedRange, loadData]);

    const setPreset = (days) => {
        const numericDays = Number(days);
        const nextRange = {
            period: numericDays,
            startDate: daysAgo(Math.max(0, numericDays - 1)),
            endDate: today(),
        };
        setPeriod(numericDays);
        setStartDate(nextRange.startDate);
        setEndDate(nextRange.endDate);
        setAppliedRange(nextRange);
    };

    const applyRange = () => {
        if (invalidDateRange(startDate, endDate)) {
            setError('Ngày bắt đầu không được lớn hơn ngày kết thúc.');
            return;
        }
        const nextPeriod = inclusiveDays(startDate, endDate, period);
        setPeriod(nextPeriod);
        setAppliedRange({ period: nextPeriod, startDate, endDate });
    };

    const exportCsv = () => {
        if (exporting || !config.permissions?.export) return;
        setExporting(true);
        const params = {
            period: inclusiveDays(appliedRange.startDate, appliedRange.endDate, appliedRange.period),
            start_date: appliedRange.startDate,
            end_date: appliedRange.endDate,
        };
        window.location.assign(withQuery(config.routes.export, params));
        window.setTimeout(() => setExporting(false), 1500);
    };

    const summary = payload?.summary || {};
    const chart = payload?.chart_data || {};
    const effectivePeriod = inclusiveDays(appliedRange.startDate, appliedRange.endDate, appliedRange.period);
    const denseSeries = useMemo(
        () => denseChartSeries(chart, appliedRange.startDate, appliedRange.endDate, effectivePeriod),
        [chart, appliedRange.startDate, appliedRange.endDate, effectivePeriod],
    );
    const hasRevenueData = [...denseSeries.deposit, ...denseSeries.withdraw].some((value) => Number(value) !== 0);
    const flowTotal = Number(summary.total_deposit || 0) + Number(summary.total_withdraw || 0);
    const statusEntries = Object.entries(statusData || {}).map(([status, items]) => ({
        status,
        count: (Array.isArray(items) ? items : []).reduce((total, item) => total + Number(item.count || 0), 0),
    })).filter((item) => item.count > 0);
    const statusTotal = statusEntries.reduce((total, item) => total + item.count, 0);

    const revenueChart = useMemo(() => ({
        type: 'line',
        data: {
            labels: denseSeries.labels,
            datasets: [
                {
                    label: 'Nạp tiền',
                    data: denseSeries.deposit,
                    borderColor: CHART_COLORS.success,
                    backgroundColor: 'rgba(16,185,129,.05)',
                    tension: .34,
                    borderWidth: 2,
                    pointRadius: (context) => Number(context.raw || 0) !== 0 ? 3 : 0,
                    pointHoverRadius: 4,
                },
                {
                    label: 'Rút tiền',
                    data: denseSeries.withdraw,
                    borderColor: CHART_COLORS.warning,
                    backgroundColor: 'rgba(245,158,11,.05)',
                    tension: .34,
                    borderWidth: 2,
                    pointRadius: (context) => Number(context.raw || 0) !== 0 ? 3 : 0,
                    pointHoverRadius: 4,
                },
                {
                    label: 'Doanh thu ròng',
                    data: denseSeries.revenue,
                    borderColor: CHART_COLORS.primary,
                    backgroundColor: 'rgba(79,70,229,.09)',
                    tension: .34,
                    borderWidth: 2,
                    fill: true,
                    pointRadius: (context) => Number(context.raw || 0) !== 0 ? 3 : 0,
                    pointHoverRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...TOOLTIP_STYLE,
                    displayColors: true,
                    callbacks: { label: (context) => `${context.dataset.label}: ${money(context.raw)}` },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#64748b', maxTicksLimit: effectivePeriod <= 30 ? 10 : 12, font: { size: 11 } },
                },
                y: {
                    beginAtZero: true,
                    grace: '12%',
                    grid: { color: 'rgba(148,163,184,.16)', drawTicks: false },
                    border: { display: false },
                    ticks: { color: '#64748b', padding: 8, font: { size: 11 }, callback: (value) => money(value) },
                },
            },
        },
    }), [denseSeries, effectivePeriod]);

    const flowChart = useMemo(() => ({
        type: 'doughnut',
        data: {
            labels: ['Nạp tiền', 'Rút tiền'],
            datasets: [{
                data: [Number(summary.total_deposit || 0), Number(summary.total_withdraw || 0)],
                backgroundColor: [CHART_COLORS.success, CHART_COLORS.warning],
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: { ...TOOLTIP_STYLE, callbacks: { label: (context) => `${context.label}: ${money(context.raw)}` } },
            },
        },
        plugins: [chartCenterPlugin('flow-center', money(summary.total_deposit || 0), 'Tổng nạp')],
    }), [summary.total_deposit, summary.total_withdraw]);

    const statusChart = useMemo(() => ({
        type: 'doughnut',
        data: {
            labels: statusEntries.map((item) => statusTag(item.status).label),
            datasets: [{
                data: statusEntries.map((item) => item.count),
                backgroundColor: statusEntries.map((item) => STATUS_COLORS[item.status] || CHART_COLORS.muted),
                borderColor: '#ffffff',
                borderWidth: 3,
                hoverOffset: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: { ...TOOLTIP_STYLE, callbacks: { label: (context) => `${context.label}: ${number(context.raw)}` } },
            },
        },
        plugins: [chartCenterPlugin('status-center', number(statusTotal), 'Tổng giao dịch')],
    }), [statusEntries, statusTotal]);

    const transactionColumns = [
        { title: 'Mã GD', dataIndex: 'id', width: 86, render: (value) => <Text strong>#{value}</Text> },
        { title: 'Khách hàng', width: 150, render: (_, row) => <Text strong>{row.user?.full_name || '—'}</Text> },
        { title: 'Số điện thoại', width: 135, render: (_, row) => row.user?.phone || '—' },
        { title: 'Loại', dataIndex: 'type', width: 105, render: (value) => <Tag color={value === 'deposit' ? 'success' : 'warning'}>{value === 'deposit' ? 'Nạp tiền' : 'Rút tiền'}</Tag> },
        { title: 'Số tiền', dataIndex: 'value', width: 115, align: 'right', render: (value) => <Text strong>{money(value)}</Text> },
        { title: 'Trạng thái', dataIndex: 'status', width: 120, render: (value) => { const item = statusTag(value); return <Tag color={item.color}>{item.label}</Tag>; } },
        { title: 'Thời gian', dataIndex: 'created_at', width: 165, render: (value) => value ? formatAdminDateTime(value, { dateStyle: 'short', timeStyle: 'short' }) : '—' },
    ];

    const flowPercent = (value) => flowTotal > 0 ? Math.round((Number(value || 0) / flowTotal) * 100) : 0;
    const statusPercent = (value) => statusTotal > 0 ? Math.round((Number(value || 0) / statusTotal) * 100) : 0;
    const initialLoading = loading && !payload;

    return (
        <AdminPage className="revenue-dashboard-page">
            <AdminPageHeader
                eyebrow="Thống kê"
                title="Thống kê tổng doanh thu"
                description="Dòng tiền nạp, rút và doanh thu ròng toàn hệ thống."
                actions={(
                    <div className="revenue-page-actions">
                        <Button icon={<ReloadOutlined />} loading={loading} onClick={() => loadData(appliedRange)}>Làm mới</Button>
                        {config.permissions?.export && (
                            <Button type="primary" icon={<DownloadOutlined />} loading={exporting} disabled={invalidDateRange(startDate, endDate)} onClick={exportCsv}>Xuất CSV</Button>
                        )}
                    </div>
                )}
            />

            {error && <Alert className="revenue-dashboard-alert" type="error" showIcon message={error} />}

            <Card className="revenue-filter-card">
                <div className="revenue-filter-card__intro">
                    <span className="revenue-filter-card__icon" aria-hidden="true"><CalendarOutlined /></span>
                    <div><strong>Khoảng nhanh</strong><span>Chọn khoảng thời gian để xem thống kê</span></div>
                </div>
                <Segmented
                    className="revenue-range-segmented"
                    value={[1, 7, 30, 365].includes(period) ? period : null}
                    options={[{ label: 'Hôm nay', value: 1 }, { label: '7 ngày', value: 7 }, { label: '30 ngày', value: 30 }, { label: '1 năm', value: 365 }]}
                    onChange={setPreset}
                />
                <span className="revenue-filter-card__divider" aria-hidden="true" />
                <label className="revenue-date-field"><span>Từ ngày</span><AdminDatePicker value={startDate} onChange={setStartDate} /></label>
                <label className="revenue-date-field"><span>Đến ngày</span><AdminDatePicker value={endDate} onChange={setEndDate} /></label>
                <Button className="revenue-apply-button" type="primary" icon={<FilterOutlined />} loading={loading} onClick={applyRange}>Áp dụng</Button>
            </Card>

            <section className="revenue-kpi-grid" aria-label="Chỉ số doanh thu">
                <MetricCard loading={initialLoading} icon={<DollarCircleOutlined />} tone="success" label="Doanh thu ròng" value={money(summary.total_revenue)} trend={summary.revenue_growth} sparkline={<MiniSparkline tone="success" values={denseSeries.revenue} />} />
                <MetricCard loading={initialLoading} icon={<WalletOutlined />} tone="info" label="Tổng nạp tiền" value={money(summary.total_deposit)} subtext={`${number(summary.deposit_count)} lệnh nạp`} sparkline={<MiniSparkline tone="info" values={denseSeries.deposit} />} />
                <MetricCard loading={initialLoading} icon={<RiseOutlined />} tone="warning" label="Tổng rút tiền" value={money(summary.total_withdraw)} subtext={`${number(summary.withdraw_count)} lệnh rút`} sparkline={<MiniSparkline tone="warning" values={denseSeries.withdraw} />} />
                <MetricCard loading={initialLoading} icon={<SwapOutlined />} tone="primary" label="Tổng giao dịch" value={number(summary.total_transactions)} />
                <MetricCard loading={initialLoading} icon={<TagsOutlined />} tone="violet" label="TB mỗi lệnh nạp" value={money(summary.avg_deposit)} />
                <MetricCard loading={initialLoading} icon={<TeamOutlined />} tone="pink" label="Khách phát sinh nạp" value={number(summary.unique_customers)} />
            </section>

            <section className="revenue-analytics-grid">
                <Card className="revenue-dashboard-card revenue-trend-card">
                    <div className="revenue-card-header">
                        <div className="revenue-card-title"><span className="revenue-card-title__icon"><BarChartOutlined /></span><strong>Xu hướng doanh thu</strong></div>
                        <div className="revenue-chart-toolbar">
                            <div className="revenue-chart-legend" aria-label="Chú thích biểu đồ">
                                <span><i className="is-success" />Nạp tiền</span>
                                <span><i className="is-warning" />Rút tiền</span>
                                <span><i className="is-primary" />Doanh thu ròng</span>
                            </div>
                            <Select
                                className="revenue-chart-range-select"
                                size="small"
                                value={[1, 7, 30, 365].includes(period) ? period : 'custom'}
                                options={[
                                    { label: 'Hôm nay', value: 1 },
                                    { label: '7 ngày', value: 7 },
                                    { label: '30 ngày', value: 30 },
                                    { label: '1 năm', value: 365 },
                                    ...(![1, 7, 30, 365].includes(period) ? [{ label: `${effectivePeriod} ngày`, value: 'custom', disabled: true }] : []),
                                ]}
                                onChange={(value) => value !== 'custom' && setPreset(value)}
                                aria-label="Chọn khoảng thời gian biểu đồ"
                            />
                        </div>
                    </div>
                    {initialLoading ? <Skeleton.Node className="revenue-chart-skeleton" active /> : hasRevenueData ? <StatisticsChart config={revenueChart} height={286} ariaLabel="Biểu đồ xu hướng doanh thu" /> : <ChartEmptyState />}
                </Card>

                <Card className="revenue-dashboard-card revenue-flow-card">
                    <div className="revenue-card-header">
                        <div className="revenue-card-title"><span className="revenue-card-title__icon"><PieChartOutlined /></span><strong>Cơ cấu nạp / rút</strong></div>
                        <span className="revenue-range-chip">{effectivePeriod} ngày</span>
                    </div>
                    {initialLoading ? <Skeleton.Node className="revenue-chart-skeleton is-small" active /> : flowTotal > 0 ? (
                        <div className="revenue-donut-layout">
                            <div className="revenue-donut-chart"><StatisticsChart config={flowChart} height={190} ariaLabel="Biểu đồ cơ cấu nạp và rút" /></div>
                            <div className="revenue-donut-legend">
                                <div><span><i className="is-success" />Nạp tiền</span><strong>{money(summary.total_deposit)}</strong><b>{flowPercent(summary.total_deposit)}%</b></div>
                                <div><span><i className="is-warning" />Rút tiền</span><strong>{money(summary.total_withdraw)}</strong><b>{flowPercent(summary.total_withdraw)}%</b></div>
                            </div>
                        </div>
                    ) : <ChartEmptyState compact title="Chưa có dữ liệu nạp / rút trong khoảng thời gian này" description="Cơ cấu sẽ hiển thị khi có giao dịch phát sinh." />}
                </Card>
            </section>

            <section className="revenue-bottom-grid">
                <Card className="revenue-dashboard-card revenue-status-card">
                    <div className="revenue-card-header">
                        <div className="revenue-card-title"><span className="revenue-card-title__icon"><PieChartOutlined /></span><strong>Trạng thái giao dịch</strong></div>
                        <span className="revenue-range-chip">{effectivePeriod} ngày</span>
                    </div>
                    {initialLoading ? <Skeleton.Node className="revenue-chart-skeleton is-small" active /> : statusTotal > 0 ? (
                        <div className="revenue-status-layout">
                            <div className="revenue-status-chart"><StatisticsChart config={statusChart} height={178} ariaLabel="Biểu đồ trạng thái giao dịch" /></div>
                            <div className="revenue-status-legend">
                                {statusEntries.map((item) => (
                                    <div key={item.status}>
                                        <span><i style={{ background: STATUS_COLORS[item.status] || CHART_COLORS.muted }} />{statusTag(item.status).label}</span>
                                        <strong>{number(item.count)}</strong>
                                        <b>{statusPercent(item.count)}%</b>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ) : <ChartEmptyState compact title="Chưa có giao dịch trong khoảng thời gian này" description="Trạng thái giao dịch sẽ hiển thị khi có dữ liệu." />}
                </Card>

                <Card className="revenue-dashboard-card revenue-recent-card">
                    <div className="revenue-card-header">
                        <div className="revenue-card-title"><span className="revenue-card-title__icon"><SwapOutlined /></span><strong>Giao dịch gần đây</strong></div>
                        <Text type="secondary">10 giao dịch gần nhất trong kỳ</Text>
                    </div>
                    <Table
                        className="revenue-recent-table"
                        rowKey="id"
                        size="small"
                        loading={initialLoading}
                        columns={transactionColumns}
                        dataSource={payload?.recent_transactions || []}
                        pagination={false}
                        scroll={{ x: 900 }}
                        locale={{ emptyText: 'Chưa có giao dịch trong khoảng thời gian này.' }}
                    />
                </Card>
            </section>
        </AdminPage>
    );
}
