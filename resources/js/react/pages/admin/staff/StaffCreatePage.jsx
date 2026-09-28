import { UserAddOutlined } from '@ant-design/icons';
import StaffForm from './StaffForm';
import { AdminPage, AdminPageHeader } from '../../../components/admin/AdminUi';

export default function StaffCreatePage({ config }) {
    return <AdminPage width="content" className="staff-create-page">
        <AdminPageHeader
            eyebrow="Nhân sự nội bộ"
            icon={<UserAddOutlined />}
            title="Thêm tài khoản nội bộ"
            description={config.canChooseRole
                ? 'Tạo tài khoản Staff hoặc Admin mới và thiết lập thông tin đăng nhập ban đầu.'
                : 'Tạo tài khoản Staff mới và thiết lập thông tin đăng nhập ban đầu.'}
            backHref={config.routes.index}
            backLabel="Danh sách nhân sự"
        />
        <StaffForm config={config} mode="create" />
    </AdminPage>;
}
