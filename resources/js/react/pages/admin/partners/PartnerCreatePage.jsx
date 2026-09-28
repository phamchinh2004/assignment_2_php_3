import { SimpleMediaForm } from '../../../components/admin/SimpleMediaResource';
const labels = { key: 'partner', create: 'Thêm đối tác mới', edit: 'Chỉnh sửa đối tác', formSubtitle: 'Cập nhật thông tin nhận diện và liên kết nền tảng', image: 'Logo đối tác', name: 'Tên đối tác' };
export default function PartnerCreatePage({ config }) { return <SimpleMediaForm config={config} labels={labels} secondaryKey="link" secondaryLabel="Đường dẫn liên kết" secondaryType="url" />; }
