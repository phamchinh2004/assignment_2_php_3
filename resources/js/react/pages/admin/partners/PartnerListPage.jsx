import { SimpleMediaList } from '../../../components/admin/SimpleMediaResource';
const labels = { key: 'partner', singular: 'Đối tác', title: 'Quản lý đối tác', subtitle: 'Quản lý các nền tảng và đường dẫn đối tác', create: 'Thêm đối tác', image: 'Logo', name: 'Tên đối tác', date: 'Ngày hợp tác' };
export default function PartnerListPage({ config }) { return <SimpleMediaList config={config} labels={labels} token="__PARTNER_ID__" secondaryKey="link" secondaryLabel="Đường dẫn liên kết" allowDelete />; }
