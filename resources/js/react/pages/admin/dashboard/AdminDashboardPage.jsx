import { ArrowRightOutlined, BarChartOutlined, BellOutlined, ClockCircleOutlined, DollarOutlined, FileSearchOutlined, GiftOutlined, SettingOutlined, ShoppingCartOutlined, TeamOutlined, UserOutlined } from '@ant-design/icons';
import { Col, Row, Tag, Typography } from 'antd';
import { AdminEmptyState, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;

const groupMeta = {
    insight: { title: 'Tổng quan', description: 'Điểm vào nhanh cho số liệu và báo cáo.' },
    operations: { title: 'Vận hành', description: 'Các khu vực cần theo dõi và xử lý hằng ngày.' },
    finance: { title: 'Tài chính', description: 'Nạp, rút và các luồng chi thưởng.' },
    configuration: { title: 'Cấu hình', description: 'Các thiết lập tác động đến hành vi hệ thống.' },
};

const iconMap = {
    statistics: <BarChartOutlined />,
    'statistics-staff': <BarChartOutlined />,
    'statistics-customers': <BarChartOutlined />,
    'statistics-personal': <BarChartOutlined />,
    customers: <UserOutlined />,
    orders: <ShoppingCartOutlined />,
    'order-reports': <FileSearchOutlined />,
    'order-distributions': <TeamOutlined />,
    deposits: <DollarOutlined />,
    withdrawals: <DollarOutlined />,
    'lucky-wheel': <GiftOutlined />,
    announcements: <BellOutlined />,
    'order-timing': <ClockCircleOutlined />,
    'frozen-settings': <SettingOutlined />,
};

export default function AdminDashboardPage({ config }) {
    const links = config.links || [];
    const groups = Object.keys(groupMeta)
        .map((key) => ({ key, ...groupMeta[key], items: links.filter((item) => item.group === key) }))
        .filter((group) => group.items.length > 0);

    return (
        <AdminPage>
            <AdminPageHeader
                eyebrow="Trung tâm điều hành"
                title="Dashboard"
                description="Các khu vực bên dưới được hiển thị theo đúng quyền của tài khoản hiện tại để đi thẳng vào công việc cần xử lý."
                meta={<Tag color="blue">{links.length} khu vực có thể truy cập</Tag>}
            />

            {groups.length === 0 ? (
                <AdminEmptyState
                    title="Chưa có khu vực quản trị khả dụng"
                    description="Tài khoản hiện tại chưa được cấp thêm quyền vận hành."
                />
            ) : groups.map((group) => (
                <AdminSectionCard key={group.key} title={group.title} description={group.description}>
                    <Row gutter={[14, 14]}>
                        {group.items.map((item) => (
                            <Col xs={24} md={12} xl={8} key={item.key}>
                                <a className="admin-dashboard-link-card" href={item.href}>
                                    <span className="admin-dashboard-link-card__icon">{iconMap[item.key] || <SettingOutlined />}</span>
                                    <span className="admin-dashboard-link-card__body">
                                        <strong>{item.label}</strong>
                                        <Text type="secondary">{item.description}</Text>
                                    </span>
                                    <ArrowRightOutlined className="admin-dashboard-link-card__arrow" />
                                </a>
                            </Col>
                        ))}
                    </Row>
                </AdminSectionCard>
            ))}
        </AdminPage>
    );
}
