import { Button, Card, Col, Empty, Row, Space, Statistic, Typography } from 'antd';
import { ArrowLeftOutlined } from '@ant-design/icons';

const { Text, Title } = Typography;

export function AdminPage({ children, width = 'wide', className = '' }) {
    return <div className={`admin-page admin-page--${width} ${className}`.trim()}>{children}</div>;
}

export function AdminPageHeader({
    title,
    description,
    eyebrow,
    icon,
    backHref,
    backLabel = 'Quay lại',
    actions,
    meta,
}) {
    return (
        <header className="admin-page-header admin-page-header--modern">
            <div className="admin-page-heading">
                {backHref && (
                    <Button className="admin-page-back" type="text" icon={<ArrowLeftOutlined />} href={backHref}>
                        {backLabel}
                    </Button>
                )}
                <div className="admin-page-heading-row">
                    {icon && <span className="admin-page-heading-icon" aria-hidden="true">{icon}</span>}
                    <div className="admin-page-heading-copy">
                        {eyebrow && <Text className="admin-page-eyebrow">{eyebrow}</Text>}
                        <Title level={1}>{title}</Title>
                        {description && <p className="admin-page-description">{description}</p>}
                        {meta && <div className="admin-page-meta">{meta}</div>}
                    </div>
                </div>
            </div>
            {actions && <div className="admin-page-actions">{actions}</div>}
        </header>
    );
}

export function AdminMetricCard({
    title,
    value,
    suffix,
    prefix,
    precision,
    hint,
    tone = 'neutral',
    icon,
    ...statisticProps
}) {
    return (
        <Card className={`admin-metric-card admin-metric-card--${tone}`} bordered>
            <div className="admin-metric-card__content">
                {icon && <span className="admin-metric-card__icon" aria-hidden="true">{icon}</span>}
                <Statistic
                    title={title}
                    value={value}
                    suffix={suffix}
                    prefix={prefix}
                    precision={precision}
                    {...statisticProps}
                />
            </div>
            {hint && <div className="admin-metric-card__hint">{hint}</div>}
        </Card>
    );
}

export function AdminMetricGrid({ items, min = 4, className = '' }) {
    return (
        <Row gutter={[14, 14]} className={`admin-metric-grid ${className}`.trim()}>
            {items.filter(Boolean).map((item) => (
                <Col xs={24} sm={12} xl={24 / Math.max(1, Math.min(min, items.filter(Boolean).length))} key={item.key || item.title}>
                    <AdminMetricCard {...item} />
                </Col>
            ))}
        </Row>
    );
}

export function AdminDataCard({ title, description, extra, toolbar, children, className = '' }) {
    return (
        <Card
            className={`admin-data-card ${className}`.trim()}
            title={(
                <div className="admin-card-heading">
                    <strong>{title}</strong>
                    {description && <span>{description}</span>}
                </div>
            )}
            extra={extra}
        >
            {toolbar && <div className="admin-data-toolbar">{toolbar}</div>}
            {children}
        </Card>
    );
}

export function AdminSectionCard({ title, description, extra, children, className = '', danger = false }) {
    return (
        <Card
            className={`admin-section-card${danger ? ' admin-section-card--danger' : ''} ${className}`.trim()}
            title={(
                <div className="admin-card-heading">
                    <strong>{title}</strong>
                    {description && <span>{description}</span>}
                </div>
            )}
            extra={extra}
        >
            {children}
        </Card>
    );
}

export function AdminFormSection({ title, description, children, className = '' }) {
    return (
        <section className={`admin-form-block ${className}`.trim()}>
            <div className="admin-form-block__heading">
                <strong>{title}</strong>
                {description && <span>{description}</span>}
            </div>
            <div className="admin-form-block__body">{children}</div>
        </section>
    );
}

export function AdminFormActions({ children }) {
    return <div className="admin-form-actions admin-form-actions--sticky"><Space wrap>{children}</Space></div>;
}

export function AdminEmptyState({ title, description, action }) {
    return (
        <div className="admin-empty-state">
            <Empty description={false} />
            <strong>{title}</strong>
            {description && <span>{description}</span>}
            {action && <div>{action}</div>}
        </div>
    );
}
