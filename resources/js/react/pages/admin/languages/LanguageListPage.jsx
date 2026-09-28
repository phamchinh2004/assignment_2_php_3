import { SimpleMediaList } from '../../../components/admin/SimpleMediaResource';
const labels = { key: 'language', singular: 'Ngôn ngữ', title: 'Quản lý ngôn ngữ', subtitle: 'Quản lý ngôn ngữ hiển thị trong hệ thống', create: 'Thêm ngôn ngữ', image: 'Quốc kỳ / Biểu tượng', name: 'Tên ngôn ngữ', date: 'Ngày thiết lập' };
export default function LanguageListPage({ config }) { return <SimpleMediaList config={config} labels={labels} token="__LANGUAGE_ID__" secondaryKey="code" secondaryLabel="Mã định danh (ISO Code)" />; }
