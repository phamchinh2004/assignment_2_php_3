import { formatAdminDateTime } from '../../../../shared/datetime';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { Alert, Button, Card, Col, Descriptions, Modal, Row, Select, Space, Statistic, Table, Tag, Typography } from 'antd';
import { DownloadOutlined, EyeOutlined, ReloadOutlined } from '@ant-design/icons';
import { requestJson } from '../../../lib/http';
import { AdminDatePicker } from '../../../components/admin/AdminDatePicker';
import StatisticsChart from './StatisticsChart';
import { CustomerIdentity } from '../../../components/admin/OperationsUi';
import { invalidDateRange, money, number, startOfMonth, today, withQuery } from './statistics';

const { Text, Title } = Typography;

export default function StaffRevenuePage({ config }) {
    const [startDate, setStartDate] = useState(startOfMonth());
    const [endDate, setEndDate] = useState(today());
    const [staffId, setStaffId] = useState('');
    const [staff, setStaff] = useState([]);
    const [payload, setPayload] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [detail, setDetail] = useState(null);
    const [detailLoading, setDetailLoading] = useState(false);

    useEffect(() => {
        requestJson(config.routes.staffList).then((result) => setStaff(result?.data || [])).catch((exception) => setError(exception.message));
    }, [config.routes.staffList]);

    const loadData = useCallback(async () => {
        if (invalidDateRange(startDate, endDate)) {
            setError('Từ ngày không được lớn hơn đến ngày.');
            return;
        }

        setLoading(true);
        setError('');
        try {
            setPayload(await requestJson(withQuery(config.routes.revenueByStaff, { date_from: startDate, date_to: endDate, staff_id: staffId })));
        } catch (exception) {
            setError(exception.message || 'Không thể tải thống kê nhân viên.');
        } finally {
            setLoading(false);
        }
    }, [config.routes.revenueByStaff, startDate, endDate, staffId]);

    useEffect(() => { loadData(); }, [loadData]);

    const openDetail = async (row) => {
        setDetailLoading(true);
        setDetail({ staff: { full_name: row.staff_name }, transactions: [] });
        try {
            setDetail(await requestJson(withQuery(config.routes.detail, { staff_id: row.staff_id, date_from: startDate, date_to: endDate })));
        } catch (exception) {
            setError(exception.message || 'Không thể tải chi tiết nhân viên.');
            setDetail(null);
        } finally {
            setDetailLoading(false);
        }
    };

    const chartConfig = useMemo(() => ({
        type: 'bar',
        data: { labels: payload?.chart_data?.top_labels || [], datasets: [{ label: 'Doanh thu', data: payload?.chart_data?.top_data || [], backgroundColor: '#4f46e5', borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
    }), [payload]);

    const columns = [
        { title: '#', dataIndex: 'rank', width: 60 },
        { title: 'Nhân viên', dataIndex: 'staff_name', render: (value, row) => <div><Text strong>{value}</Text><div><Text type="secondary">{row.staff_email || '—'}</Text></div></div> },
        { title: 'Khách quản lý', dataIndex: 'invited_users', align: 'right', render: number },
        { title: 'Giao dịch', dataIndex: 'total_transactions', align: 'right', render: number },
        { title: 'Doanh thu', dataIndex: 'total_revenue', align: 'right', render: money },
        { title: 'Tỷ trọng', dataIndex: 'percent_share', align: 'right', render: (value) => <Tag color="blue">{Number(value || 0)}%</Tag> },
        { title: '', width: 100, render: (_, row) => <Button size="small" icon={<EyeOutlined />} onClick={() => openDetail(row)}>Chi tiết</Button> },
    ];

    const transactionColumns = [
        { title: 'Mã GD', dataIndex: 'id', render: (value) => `#${value}` },
        { title: 'Khách hàng', render: (_, row) => <CustomerIdentity user={row.user} /> },
        { title: 'Email', render: (_, row) => row.user?.email || '—' },
        { title: 'Số tiền', dataIndex: 'value', align: 'right', render: money },
        { title: 'Thời gian', dataIndex: 'created_at', render: (value) => value ? formatAdminDateTime(value, { dateStyle: 'short', timeStyle: 'medium' }) : '—' },
    ];

    const summary = payload?.summary || {};

    return (
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
            <Row justify="space-between" align="middle" gutter={[16, 16]}>
                <Col><Title level={2} style={{ margin: 0 }}>Doanh thu theo nhân viên</Title><Text type="secondary">Theo dõi doanh số từ nhóm khách hàng do từng nhân viên quản lý.</Text></Col>
                <Col><Space><Button icon={<ReloadOutlined />} loading={loading} onClick={loadData}>Làm mới</Button>{config.permissions?.export && <Button type="primary" icon={<DownloadOutlined />} disabled={invalidDateRange(startDate, endDate)} onClick={() => window.location.assign(withQuery(config.routes.export, { date_from: startDate, date_to: endDate, staff_id: staffId }))}>Xuất CSV</Button>}</Space></Col>
            </Row>
            {error && <Alert type="error" showIcon message={error} />}
            {Number(summary.legacy_transactions || 0) > 0 && (
                <Alert
                    type="warning"
                    showIcon
                    message={`${number(summary.legacy_transactions)} giao dịch cũ chưa có snapshot nhân viên phụ trách`}
                    description="Các giao dịch này vẫn giữ cách tính cũ theo người quản lý hiện tại. Giao dịch mới được cố định theo nhân viên phụ trách tại thời điểm phát sinh."
                />
            )}
            <Card><Row gutter={[12, 12]} align="bottom"><Col xs={24} md={8}><Text strong>Nhân viên</Text><Select allowClear showSearch optionFilterProp="label" style={{ width: '100%' }} placeholder="Tất cả nhân viên" value={staffId || undefined} onChange={(value) => setStaffId(value ? String(value) : '')} options={staff.map((item) => ({ value: String(item.id), label: `${item.full_name} - ${item.email || item.phone || ''}` }))} /></Col><Col xs={12} md={5}><Text strong>Từ ngày</Text><AdminDatePicker value={startDate} onChange={setStartDate} /></Col><Col xs={12} md={5}><Text strong>Đến ngày</Text><AdminDatePicker value={endDate} onChange={setEndDate} /></Col><Col xs={24} md={6}><Button block type="primary" loading={loading} onClick={loadData}>Áp dụng</Button></Col></Row></Card>
            <Row gutter={[16, 16]}><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="Nhân viên" value={summary.total_staff || 0} formatter={number} /></Card></Col><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="Tổng doanh thu" value={summary.total_revenue || 0} formatter={(value) => money(value)} /></Card></Col><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="Giao dịch" value={summary.total_transactions || 0} formatter={number} /></Card></Col><Col xs={24} md={12} xl={6}><Card loading={loading}><Statistic title="NV có doanh thu" value={summary.active_staff || 0} formatter={number} /></Card></Col></Row>
            <Row gutter={[16, 16]}><Col xs={24} xl={9}><Card title="Top nhân viên theo doanh thu"><StatisticsChart config={chartConfig} height={360} /></Card></Col><Col xs={24} xl={15}><Card title="Chi tiết theo nhân viên"><Table rowKey="staff_id" loading={loading} columns={columns} dataSource={payload?.table_data || []} pagination={{ pageSize: 12 }} scroll={{ x: 900 }} /></Card></Col></Row>
            <Modal title={`Chi tiết ${detail?.staff?.full_name || ''}`} open={Boolean(detail)} onCancel={() => setDetail(null)} footer={null} width={900}>
                <Card loading={detailLoading} size="small" style={{ marginBottom: 16 }}><Descriptions size="small" column={{ xs: 1, sm: 3 }}><Descriptions.Item label="Khách quản lý">{number(detail?.statistics?.invited_users)}</Descriptions.Item><Descriptions.Item label="Giao dịch">{number(detail?.statistics?.total_transactions)}</Descriptions.Item><Descriptions.Item label="Doanh thu">{money(detail?.statistics?.total_revenue)}</Descriptions.Item></Descriptions></Card>
                <Table rowKey="id" size="small" loading={detailLoading} columns={transactionColumns} dataSource={detail?.transactions || []} pagination={{ pageSize: 8 }} scroll={{ x: 700 }} />
            </Modal>
        </Space>
    );
}
