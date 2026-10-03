import { Avatar, Input, Segmented } from 'antd';
import { SearchOutlined, UserOutlined } from '@ant-design/icons';

export function OperationsIdentity({ name, secondary, meta, href, avatarUrl }) {
    const content = <>
        <Avatar className="operations-identity__avatar" size={38} src={avatarUrl} alt="" icon={<UserOutlined />} />
        <div className="operations-identity__copy">
            <strong>{name}</strong>
            {secondary && <span>{secondary}</span>}
            {meta && <div className="operations-identity__meta">{meta}</div>}
        </div>
    </>;
    return href
        ? <a className="operations-identity" href={href}>{content}</a>
        : <div className="operations-identity">{content}</div>;
}

export function CustomerIdentity({ user, name, secondary, ...props }) {
    return <OperationsIdentity
        {...props}
        name={name ?? (user?.full_name || user?.username || 'Tài khoản không còn tồn tại')}
        secondary={secondary ?? (user?.username ? `@${user.username}` : user?.id ? `ID #${user.id}` : undefined)}
        avatarUrl={user?.avatar_url}
    />;
}

export function OperationsToolbar({ options, value, onChange, search, onSearch, placeholder, extra }) {
    return <div className="operations-toolbar">
        <div className="operations-status-filters">
            <Segmented aria-label="Lọc danh sách" options={options} value={value} onChange={onChange} />
        </div>
        <div className="operations-toolbar__search">
            {extra}
            {onSearch && <Input
                allowClear
                prefix={<SearchOutlined aria-hidden="true" />}
                value={search}
                onChange={(event) => onSearch(event.target.value)}
                placeholder={placeholder}
                aria-label={placeholder}
            />}
        </div>
    </div>;
}

export function OperationsSummary({ shown, total, unit = 'giao dịch', children }) {
    return <div className="operations-summary">
        <span>Kết quả: <strong>{shown}</strong> / {total} {unit}</span>
        {children && <span>{children}</span>}
    </div>;
}
