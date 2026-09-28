import { Button, Card, Input, Space, Tag, Typography } from 'antd';
import { FileTextOutlined } from '@ant-design/icons';
import LaravelForm, { fieldError, oldValue } from '../../../components/LaravelForm';
import RichTextArea from '../../../components/RichTextArea';
import { AdminFormActions, AdminFormSection, AdminPage, AdminPageHeader, AdminSectionCard } from '../../../components/admin/AdminUi';

const { Text } = Typography;

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

    return <AdminPage width="content">
        <AdminPageHeader
            eyebrow="Nội dung website"
            icon={<FileTextOutlined />}
            title={editing ? `Chỉnh sửa: ${section.name}` : 'Tạo section mới'}
            description="Quản lý cùng một khối nội dung theo từng ngôn ngữ để cấu trúc website luôn đồng bộ."
            backHref={config.routes.index}
            backLabel="Danh sách nội dung"
            meta={editing && section.code ? <Tag>{section.code}</Tag> : null}
        />
        <AdminSectionCard title="Nội dung section" description="Tên dùng để nhận diện trong quản trị; phần nội dung bên dưới được hiển thị theo ngôn ngữ tương ứng.">
            <LaravelForm action={editing ? config.routes.update : config.routes.store} method={editing ? 'PUT' : 'POST'}>
                <AdminFormSection title="Thông tin chung" description="Đặt tên mô tả đúng vị trí hoặc mục đích của section để dễ quản lý về sau.">
                    <label className="form-label-modern" htmlFor="section-name">Tên Section <span className="text-danger">*</span></label>
                    <Input id="section-name" name="name" defaultValue={oldValue(config.form, 'name', section.name || '')} placeholder="Ví dụ: Giới thiệu trang chủ" required maxLength={255} />
                    {nameError && <Text type="danger" className="d-block mt-1">{nameError}</Text>}
                    {editing && <div className="mt-1"><Text type="secondary">Mã định danh: <Text code>{section.code}</Text></Text></div>}
                </AdminFormSection>
                <AdminFormSection title="Nội dung theo ngôn ngữ" description="Chỉ nhập nội dung cho ngôn ngữ cần hỗ trợ; nội dung trống sẽ không được giả lập bằng bản dịch khác.">
                    <Space direction="vertical" size="middle" className="w-100">
                    {languages.map((language) => {
                        const fallback = existing.get(String(language.id)) || '';
                        const error = fieldError(config.form, `content.${language.id}`);
                        return <Card key={language.id} size="small" title={<Space>{language.image && <img width="22" height="15" src={`${String(config.storageBaseUrl || '/storage').replace(/\/$/, '')}/${language.image}`} alt={language.name} style={{ objectFit: 'cover', borderRadius: 2 }} />}<span>{language.name} ({String(language.code || '').toUpperCase()})</span></Space>}>
                            <RichTextArea id={`section-content-${language.id}`} name={`content[${language.id}]`} defaultValue={contentFor(config, language.id, fallback)} />
                            {error && <Text type="danger" className="d-block mt-1">{error}</Text>}
                        </Card>;
                    })}
                    </Space>
                </AdminFormSection>
                <AdminFormActions>
                    <Button href={config.routes.index}>Hủy</Button>
                    <Button type="primary" htmlType="submit">{editing ? 'Cập nhật Section' : 'Lưu Section'}</Button>
                </AdminFormActions>
            </LaravelForm>
        </AdminSectionCard>
    </AdminPage>;
}
