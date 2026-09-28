import { TeamOutlined } from '@ant-design/icons';
import StaffForm from './StaffForm';
import { AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

export default function StaffEditPage({ config }) {
    return <AdminPage width="form">
        <AdminPageHeader eyebrow="Nhân sự nội bộ" icon={<TeamOutlined />} title={`Chỉnh sửa ${config.staff?.full_name || config.staff?.username || 'tài khoản'}`} description="Cập nhật thông tin nhận diện và vai trò trong phạm vi tài khoản hiện tại được phép quản lý." backHref={config.routes.index} backLabel="Danh sách nhân sự" />
        <StaffForm config={config} mode="edit" />
    </AdminPage>;
}
