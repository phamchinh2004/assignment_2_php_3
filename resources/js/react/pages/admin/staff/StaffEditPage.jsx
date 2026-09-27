import { Typography } from 'antd';
import StaffForm from './StaffForm';

const { Text, Title } = Typography;

export default function StaffEditPage({ config }) {
    return <div className="container-fluid px-4 pb-5">
        <div className="mb-4"><Title level={2}>Chỉnh sửa tài khoản nội bộ</Title><Text type="secondary">Cập nhật thông tin tài khoản; backend tiếp tục kiểm soát quyền đổi vai trò.</Text></div>
        <StaffForm config={config} mode="edit" />
    </div>;
}
