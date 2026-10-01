import { useState } from 'react';
import { Background, FieldError, ServerNotice } from './AuthShared';

export default function ForgotPasswordPage({ config }) {
    const [submitting, setSubmitting] = useState(false);
    const old = config.form?.old || {};
    const errors = config.form?.errors || {};
    return (
        <div className="container_login_register">
            <Background src={config.assets.background} />
            <main className="forgot-page"><section className="forgot-card">
                <img className="auth-brand-logo forgot-logo" src={config.assets.logo} alt={config.brandName || 'Dropshipping'} />
                <div className="forgot-icon"><i className="fa-solid fa-key" /></div>
                <h1 className="forgot-title">Quên mật khẩu?</h1>
                <p className="forgot-description">Nhập email đã đăng ký. Chúng tôi sẽ gửi mật khẩu mới đến địa chỉ này.</p>
                <ServerNotice flash={config.flash} />
                <form action={config.routes.submit} method="post" onSubmit={() => setSubmitting(true)}>
                    <input type="hidden" name="_token" value={config.csrf} />
                    <label htmlFor="email" className="forgot-label">Email đăng ký</label>
                    <div className="forgot-input-wrap"><i className="fa-solid fa-envelope" /><input type="email" id="email" className="form-control forgot-input" defaultValue={old.email || ''} name="email" placeholder="you@example.com" autoComplete="email" required /></div>
                    <FieldError errors={errors} name="email" />
                    <div className="d-grid mt-4"><button type="submit" className="btn forgot-submit" disabled={submitting}>{submitting ? <><span className="spinner-border spinner-border-sm me-2" />Đang gửi...</> : <>Gửi mật khẩu mới <i className="fa-solid fa-arrow-right ms-2" /></>}</button></div>
                </form>
                <div className="text-center"><a className="forgot-back" href={config.routes.login}><i className="fa-solid fa-arrow-left" /> Quay lại đăng nhập</a></div>
            </section></main>
        </div>
    );
}
