import { useEffect, useRef, useState } from 'react';
import { Alert, Modal } from 'antd';
import './auth.css';

export function Background({ src }) {
    return (
        <div className="background-overlay">
            <div className="background-blur" style={{ backgroundImage: `url(${src})` }} />
        </div>
    );
}

export function ServerNotice({ flash, local }) {
    const notice = local?.message
        ? local
        : flash?.error
            ? { type: 'error', message: flash.error }
            : flash?.warning
                ? { type: 'warning', message: flash.warning }
                : flash?.success
                    ? { type: 'success', message: flash.success }
                    : null;

    if (!notice) return null;
    return <Alert className="react-auth-notice" showIcon type={notice.type || 'info'} message={notice.message} />;
}

export function SecurityAccessNotice() {
    const [open, setOpen] = useState(true);

    return (
        <Modal
            centered
            open={open}
            onCancel={() => setOpen(false)}
            footer={null}
            width={640}
            rootClassName="auth-security-modal-root"
            title={(
                <div className="auth-security-modal-title">
                    <span className="auth-security-modal-icon" aria-hidden="true">
                        <i className="fa-solid fa-shield-halved" />
                    </span>
                    Thông báo
                </div>
            )}
        >
            <div className="auth-security-modal-content">
                <p>Chào các bạn,</p>
                <p>Hiện tại website của chúng tôi đang bị trình duyệt hiển thị cảnh báo bảo mật khi truy cập.</p>
                <p>Nguyên nhân chúng tôi đang ghi nhận có liên quan đến việc website yêu cầu quyền truy cập vị trí của thiết bị. Tính năng này được sử dụng nhằm tăng tính minh bạch trong quá trình các bạn làm việc với hệ thống, hỗ trợ xác minh thông tin truy cập và hạn chế các trường hợp sử dụng bất thường.</p>
                <p>Chúng tôi đang tiếp tục kiểm tra và xử lý cảnh báo này để đảm bảo quá trình sử dụng website được ổn định và rõ ràng hơn.</p>
                <p>Cảm ơn các bạn đã thông cảm và đồng hành cùng chúng tôi.</p>
            </div>
            <button type="button" className="auth-security-modal-confirm" onClick={() => setOpen(false)}>
                Tôi đã hiểu
            </button>
        </Modal>
    );
}

export function FieldError({ errors, name }) {
    const message = errors?.[name]?.[0];
    return message ? <span className="react-auth-error">{message}</span> : null;
}

export function PasswordToggle({ visible, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={visible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'}
            style={{ position: 'absolute', top: '50%', right: 12, transform: 'translateY(-50%)', border: 0, background: 'transparent', color: '#9ca3af', padding: 4 }}
        >
            <i className={`fa-regular ${visible ? 'fa-eye-slash' : 'fa-eye'}`} />
        </button>
    );
}

export function Turnstile({ config, action }) {
    const hostRef = useRef(null);
    const widgetRef = useRef(null);

    useEffect(() => {
        if (!config?.enabled || !config?.siteKey) return undefined;

        let cancelled = false;
        const render = () => {
            if (cancelled || !hostRef.current || !window.turnstile || widgetRef.current !== null) return;
            widgetRef.current = window.turnstile.render(hostRef.current, {
                sitekey: config.siteKey,
                action,
                theme: 'dark',
                size: 'flexible',
            });
        };

        const existing = document.querySelector('script[data-react-turnstile]');
        if (window.turnstile) {
            render();
        } else if (existing) {
            existing.addEventListener('load', render, { once: true });
        } else {
            const script = document.createElement('script');
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
            script.async = true;
            script.defer = true;
            script.dataset.reactTurnstile = '1';
            script.addEventListener('load', render, { once: true });
            document.head.appendChild(script);
        }

        return () => {
            cancelled = true;
            if (widgetRef.current !== null && window.turnstile) {
                try { window.turnstile.remove(widgetRef.current); } catch { /* noop */ }
            }
            widgetRef.current = null;
        };
    }, [action, config?.enabled, config?.siteKey]);

    if (!config?.enabled) return null;
    return <div className="auth-turnstile"><div ref={hostRef} style={{ width: '100%' }} /></div>;
}

export function Disclaimer({ brandName = 'Dropshipping', routes = {} }) {
    return (
        <footer className="auth-affiliation-disclaimer" role="note">
            <nav className="auth-trust-links" aria-label="Website information">
                {routes.about && <a href={routes.about}>About Us</a>}
                {routes.contact && <a href={routes.contact}>Contact Us</a>}
                {routes.privacy && <a href={routes.privacy}>Privacy Policy</a>}
                {routes.terms && <a href={routes.terms}>Terms & Conditions</a>}
                {routes.paymentRefund && <a href={routes.paymentRefund}>Payment & Refund Policy</a>}
            </nav>
            <p><strong>Disclaimer:</strong></p>
            <p>{brandName} is an independent platform and is not affiliated with or endorsed by third-party marketplaces or social platforms.<br />Third-party brand names, logos, and trademarks remain the property of their respective owners.</p>
            <p><strong>Affiliate Disclosure:</strong></p>
            <p>This website may contain affiliate links. We may receive a commission if you make a purchase or sign up for a service through those links, at no additional cost to you.</p>
        </footer>
    );
}

export async function postCheck(url, csrf, values) {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        },
        body: new URLSearchParams(values),
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(payload?.message || `HTTP ${response.status}`);
        error.status = response.status;
        throw error;
    }
    return payload;
}
