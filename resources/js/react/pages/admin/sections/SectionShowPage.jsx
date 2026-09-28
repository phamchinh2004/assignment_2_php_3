import { Button, Empty, Space, Tag } from 'antd';
import { EditOutlined, FileTextOutlined } from '@ant-design/icons';
import { AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

export default function SectionShowPage({ config }) {
    const section = config.section || {};
    const languages = config.languages || [];
    const contentMap = new Map((section.section_languages || []).map((item) => [String(item.language_id), item.content || '']));

    return <AdminPage width="content">
        <AdminPageHeader
            eyebrow="Chi tiết nội dung"
            icon={<FileTextOutlined />}
            title={section.name}
            description="Xem trước dữ liệu đang lưu theo từng ngôn ngữ trước khi chỉnh sửa hoặc bật/tắt section."
            backHref={config.routes.index}
            backLabel="Danh sách nội dung"
            meta={<><Tag>{section.code}</Tag><Tag color={Number(section.status) === 1 ? 'success' : 'default'}>{Number(section.status) === 1 ? 'Đang hoạt động' : 'Đã tắt'}</Tag></>}
            actions={config.permissions?.update && <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa nội dung</Button>}
        />
        <Space direction="vertical" size="large" className="w-100">
            {languages.map((language) => {
                const content = contentMap.get(String(language.id)) || '';
                return <AdminSectionCard key={language.id} title={<Space>{language.image && <img width="22" height="15" src={`${String(config.storageBaseUrl || '/storage').replace(/\/$/, '')}/${language.image}`} alt={language.name} style={{ objectFit: 'cover', borderRadius: 2 }} />}<span>{language.name} ({String(language.code || '').toUpperCase()})</span></Space>} extra={content ? <Tag color="success">Đã có nội dung</Tag> : <Tag>Chưa có nội dung</Tag>}>
                    {content ? <div className="rendered-html-box" dangerouslySetInnerHTML={{ __html: content }} /> : <Empty description="Chưa nhập nội dung cho ngôn ngữ này" />}
                </AdminSectionCard>;
            })}
        </Space>
    </AdminPage>;
}
