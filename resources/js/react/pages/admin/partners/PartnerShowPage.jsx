import { SimpleMediaShow } from '../../../components/admin/SimpleMediaResource';
const labels = { name: 'Tên đối tác' };
export default function PartnerShowPage({ config }) { return <SimpleMediaShow config={config} labels={labels} secondaryKey="link" secondaryLabel="Đường dẫn liên kết" />; }
