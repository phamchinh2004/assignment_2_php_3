import { Card, Typography } from 'antd';

const { Paragraph, Title } = Typography;

export default function AdminDashboardPage() {
    return (
        <div className="container-fluid px-4 pb-5">
            <Title level={2}>Dashboard</Title>
            <Card>
                <Paragraph style={{ marginBottom: 0 }}>
                    Đây là trang bảng điều khiển của bên admin.
                </Paragraph>
            </Card>
        </div>
    );
}
