import { Typography } from 'antd';
import StaffForm from './StaffForm';

const { Text, Title } = Typography;

export default function StaffCreatePage({ config }) {
    return <div className="container-fluid px-4 pb-5">
        <div className="mb-4"><Title level={2}>Thêm tài khoản nội bộ</Title><Text type="secondary">{config.canChooseRole ? 'Tạo tài khoản Staff hoặc Admin.' : 'Tạo tài khoản Staff mới.'}</Text></div>
        <StaffForm config={config} mode="create" />
    </div>;
}
