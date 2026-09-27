import { Button, Card, Empty, Space, Tag, Typography } from 'antd';
import { ArrowLeftOutlined, EditOutlined } from '@ant-design/icons';

const { Text, Title } = Typography;

export default function SectionShowPage({ config }) {
    const section = config.section || {};
    const languages = config.languages || [];
    const contentMap = new Map((section.section_languages || []).map((item) => [String(item.language_id), item.content || '']));

    return <div className="container-fluid px-4 pb-5">
        <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách section</Button>
        <div className="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div><Space wrap><Title level={2} style={{ margin: 0 }}>{section.name}</Title><Tag>{section.code}</Tag><Tag color={Number(section.status) === 1 ? 'success' : 'default'}>{Number(section.status) === 1 ? 'Đang hoạt động' : 'Đã tắt'}</Tag></Space></div>
            {config.permissions?.update && <Button type="primary" icon={<EditOutlined />} href={config.routes.edit}>Chỉnh sửa nội dung</Button>}
        </div>
        <Space direction="vertical" size="large" className="w-100">
            {languages.map((language) => {
                const content = contentMap.get(String(language.id)) || '';
                return <Card key={language.id} title={<Space>{language.image && <img width="22" height="15" src={`${String(config.storageBaseUrl || '/storage').replace(/\/$/, '')}/${language.image}`} alt={language.name} style={{ objectFit: 'cover' }} />}<span>{language.name} ({String(language.code || '').toUpperCase()})</span>{content ? <Tag color="success">Đã có nội dung</Tag> : <Tag>Chưa có nội dung</Tag>}</Space>}>
                    {content ? <div className="rendered-html-box" dangerouslySetInnerHTML={{ __html: content }} /> : <Empty description="Chưa nhập nội dung cho ngôn ngữ này" />}
                </Card>;
            })}
        </Space>
    </div>;
}
