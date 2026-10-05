import { MorphIcon } from 'morphicons/react';
import { Eye, EyeOff } from 'lucide';

export default function AdminMorphIcon({ size = 16, style, ...props }) {
    return <MorphIcon
        {...props}
        size={size}
        spring="snappy"
        reducedMotion="user"
        style={{ display: 'inline-block', verticalAlign: 'middle', flexShrink: 0, ...style }}
    />;
}

// Ant Design supplies the click handler through iconRender and keeps input focus.
export function PasswordVisibilityIcon({ visible, onClick, ...props }) {
    return <span
        {...props}
        role="button"
        tabIndex={0}
        aria-label={visible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'}
        aria-pressed={visible}
        onClick={onClick}
        onKeyDown={(event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                onClick?.(event);
            }
        }}
        style={{ display: 'inline-flex', alignItems: 'center' }}
    >
        <AdminMorphIcon icon={visible ? Eye : EyeOff} />
    </span>;
}
