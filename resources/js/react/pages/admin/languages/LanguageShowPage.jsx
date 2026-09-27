import { SimpleMediaShow } from '../../../components/admin/SimpleMediaResource';
const labels = { name: 'Tên ngôn ngữ' };
export default function LanguageShowPage({ config }) { return <SimpleMediaShow config={config} labels={labels} secondaryKey="code" secondaryLabel="Mã định danh (ISO Code)" />; }
