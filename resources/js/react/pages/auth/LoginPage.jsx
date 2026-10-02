import { useEffect, useRef, useState } from 'react';
import { Background, Disclaimer, FieldError, PasswordToggle, SecurityAccessNotice, ServerNotice, Turnstile, postCheck } from './AuthShared';

export default function LoginPage({ config }) {
    const formRef = useRef(null);
    const [passwordVisible, setPasswordVisible] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [notice, setNotice] = useState(null);
    const old = config.form?.old || {};
    const errors = config.form?.errors || {};

    useEffect(() => {
        localStorage.removeItem('remember_password');
        localStorage.removeItem('username');
        localStorage.removeItem('password');
        const pageshow = (event) => { if (event.persisted) window.location.reload(); };
        window.addEventListener('pageshow', pageshow);
        if (config.clearLoginForm) formRef.current?.reset();
        return () => window.removeEventListener('pageshow', pageshow);
    }, [config.clearLoginForm]);

    const submit = async (event) => {
        event.preventDefault();
        if (submitting) return;
        const form = formRef.current;
        const username = form?.elements?.username?.value?.trim();
        const password = form?.elements?.password?.value;
        if (!username || !password) {
            setNotice({ type: 'warning', message: 'Vui lòng điền đầy đủ thông tin!' });
            return;
        }

        setSubmitting(true);
        setNotice(null);
        try {
            const checked = await postCheck(config.routes.checkUsername, config.csrf, { username });
            if (checked?.refresh) {
                setNotice({ type: 'warning', message: checked.message || 'Tài khoản chưa sẵn sàng để đăng nhập.' });
                setSubmitting(false);
                return;
            }
            form.submit();
        } catch (error) {
            if (error.status === 419) {
                window.location.replace(config.routes.login);
                return;
            }
            setNotice({ type: 'error', message: 'Không thể đăng nhập. Vui lòng thử lại!' });
            setSubmitting(false);
        }
    };

    return (
        <div className="container_login_register">
            <SecurityAccessNotice />
            <Background src={config.assets.background} />
            <main className="auth-page">
                <section className="auth-card">
                    <img className="auth-brand-logo auth-logo" src={config.assets.logo} alt={config.brandName || 'Dropshipping'} />
                    <nav className="auth-tabs" aria-label="Điều hướng tài khoản">
                        <a className="active" href={config.routes.login}>Đăng nhập</a>
                        <a href={config.routes.register}>Đăng ký</a>
                    </nav>
                    <div className="auth-heading"><h1 className="auth-title">Chào mừng trở lại</h1><p className="auth-subtitle">Đăng nhập để tiếp tục quản lý tài khoản của bạn</p></div>
                    <ServerNotice flash={config.flash} local={notice} />
                    <form ref={formRef} method="post" action={config.routes.submit} onSubmit={submit} autoComplete={config.clearLoginForm ? 'off' : 'on'}>
                        <input type="hidden" name="_token" value={config.csrf} />
                        <div className="auth-field">
                            <label htmlFor="username_login" className="auth-label">Tên đăng nhập</label>
                            <div className="auth-input-wrap"><i className="fa-solid fa-user auth-input-icon" /><input className="form-control auth-input" id="username_login" defaultValue={old.username || ''} name="username" type="text" placeholder="Nhập tên tài khoản" autoComplete={config.clearLoginForm ? 'off' : 'username'} /></div>
                            <FieldError errors={errors} name="username" />
                        </div>
                        <div className="auth-field">
                            <label htmlFor="password_login" className="auth-label">Mật khẩu</label>
                            <div className="auth-input-wrap"><i className="fa-solid fa-lock auth-input-icon" /><input className="form-control auth-input" id="password_login" name="password" type={passwordVisible ? 'text' : 'password'} placeholder="Nhập mật khẩu" autoComplete={config.clearLoginForm ? 'new-password' : 'current-password'} /><PasswordToggle visible={passwordVisible} onClick={() => setPasswordVisible((value) => !value)} /></div>
                            <FieldError errors={errors} name="password" />
                        </div>
                        <div className="auth-helper d-flex justify-content-between align-items-center mt-2">
                            <div className="form-check"><input className="form-check-input p-2" name="remember_password" defaultChecked={Boolean(old.remember_password)} type="checkbox" value="1" id="remember_password" /><label className="form-check-label" htmlFor="remember_password">Nhớ mật khẩu</label></div>
                            <a href={config.routes.forgot}>Quên mật khẩu?</a>
                        </div>
                        <Turnstile config={config.turnstile} action="login" />
                        <FieldError errors={errors} name="cf-turnstile-response" />
                        <div className="d-grid mt-4"><button className="btn auth-submit" type="submit" disabled={submitting}>{submitting ? 'Đang đăng nhập...' : <>Đăng nhập <i className="fa-solid fa-arrow-right ms-2" /></>}</button></div>
                    </form>
                    <div className="auth-switch text-center mt-4">Bạn chưa có tài khoản? <a href={config.routes.register}>Đăng ký ngay</a></div>
                </section>
            </main>
            <Disclaimer brandName={config.brandName} routes={config.legalRoutes} />
        </div>
    );
}
