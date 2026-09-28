import { SimpleMediaForm } from '../../../components/admin/SimpleMediaResource';
const labels = { key: 'language', create: 'Thêm ngôn ngữ mới', edit: 'Chỉnh sửa ngôn ngữ', formSubtitle: 'Thiết lập tên, mã ISO và biểu tượng ngôn ngữ', image: 'Quốc kỳ / Biểu tượng', name: 'Tên ngôn ngữ' };
export default function LanguageEditPage({ config }) { return <SimpleMediaForm config={config} labels={labels} secondaryKey="code" secondaryLabel="Mã định danh (ISO Code)" editing />; }
