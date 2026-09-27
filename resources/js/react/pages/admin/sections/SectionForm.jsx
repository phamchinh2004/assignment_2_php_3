import { Button, Card, Input, Space, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import RichTextArea from '../../../components/RichTextArea';

const { Text, Title } = Typography;

function contentFor(config, languageId, fallback = '') {
    const key = `content.${languageId}`;
    if (Object.prototype.hasOwnProperty.call(config.form?.old || {}, 'content')) {
        const oldContent = config.form.old.content || {};
        return oldContent[languageId] ?? oldContent[String(languageId)] ?? fallback;
    }
    return fallback;
}

export default function SectionForm({ config, editing = false }) {
    const section = config.section || {};
    const languages = config.languages || [];
    const existing = new Map((section.section_languages || []).map((item) => [String(item.language_id), item.content || '']));
    const nameError = fieldError(config.form, 'name');

    return <div className="container-fluid px-4 pb-5">
        <Button icon={<ArrowLeftOutlined />} href={config.routes.index} className="mb-3">Quay lại danh sách section</Button>
        <Title level={2}>{editing ? `Chỉnh sửa Section: ${section.name}` : 'Tạo Section mới'}</Title>
        <Text type="secondary">Nội dung đa ngôn ngữ được giữ ở dạng rich text như giao diện Blade cũ.</Text>

        <Card className="mt-4">
            <LaravelForm action={editing ? config.routes.update : config.routes.store} method={editing ? 'PUT' : 'POST'}>
                <div className="mb-4">
                    <label className="form-label-modern" htmlFor="section-name">Tên Section <span className="text-danger">*</span></label>
                    <Input id="section-name" name="name" defaultValue={oldValue(config.form, 'name', section.name || '')} required maxLength={255} />
                    {nameError && <Text type="danger" className="d-block mt-1">{nameError}</Text>}
                    {editing && <div className="mt-1"><Text type="secondary">Mã định danh: <Text code>{section.code}</Text></Text></div>}
                </div>

                <Space direction="vertical" size="large" className="w-100">
                    {languages.map((language) => {
                        const fallback = existing.get(String(language.id)) || '';
                        const error = fieldError(config.form, `content.${language.id}`);
                        return <Card key={language.id} size="small" title={<Space>{language.image && <img width="22" height="15" src={`${String(config.storageBaseUrl || '/storage').replace(/\/$/, '')}/${language.image}`} alt={language.name} style={{ objectFit: 'cover', borderRadius: 2 }} />}<span>{language.name} ({String(language.code || '').toUpperCase()})</span></Space>}>
                            <RichTextArea id={`section-content-${language.id}`} name={`content[${language.id}]`} defaultValue={contentFor(config, language.id, fallback)} />
                            {error && <Text type="danger" className="d-block mt-1">{error}</Text>}
                        </Card>;
                    })}
                </Space>

                <div className="d-flex justify-content-end gap-2 mt-4">
                    <Button href={config.routes.index}>Hủy</Button>
                    <Button type="primary" htmlType="submit">{editing ? 'Cập nhật Section' : 'Lưu Section'}</Button>
                </div>
            </LaravelForm>
        </Card>
    </div>;
}
