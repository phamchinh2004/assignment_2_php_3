import { useCallback, useEffect, useRef, useState } from 'react';
import { Background, Disclaimer, FieldError, PasswordToggle, ServerNotice, Turnstile, postCheck } from './AuthShared';

const emptyLocation = { permission: 'prompt', latitude: '', longitude: '', accuracy: '', countryCode: '', country: '', city: '' };

export default function RegisterPage({ config }) {
    const formRef = useRef(null);
    const mountedRef = useRef(false);
    const locationRequestRef = useRef(null);
    const [passwordVisible, setPasswordVisible] = useState(false);
    const [repeatVisible, setRepeatVisible] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [notice, setNotice] = useState(null);
    const [errors, setErrors] = useState(() => config.form?.errors || {});
    const [location, setLocation] = useState(() => ({
        ...emptyLocation,
        permission: config.form?.old?.location_permission || 'prompt',
        latitude: config.form?.old?.location_latitude || '',
        longitude: config.form?.old?.location_longitude || '',
        accuracy: config.form?.old?.location_accuracy || '',
        countryCode: config.form?.old?.location_country_code || '',
        country: config.form?.old?.location_country || '',
        city: config.form?.old?.location_city || '',
    }));
    const old = config.form?.old || {};

    const clearFieldError = (name) => {
        setErrors((current) => {
            if (!current?.[name]) return current;
            const next = { ...current };
            delete next[name];
            return next;
        });
    };

    const showFieldError = (name, message) => {
        setNotice(null);
        setErrors({ [name]: [message] });
        window.requestAnimationFrame(() => formRef.current?.elements?.[name]?.focus?.());
    };

    const collectLocation = useCallback(() => {
        if (locationRequestRef.current) return locationRequestRef.current;

        const request = new Promise((resolve) => {
            if (!navigator.geolocation) {
                const next = { ...emptyLocation, permission: 'denied' };
                if (mountedRef.current) setLocation(next);
                resolve(next);
                return;
            }

            navigator.geolocation.getCurrentPosition(async (position) => {
                const next = { ...emptyLocation, permission: 'granted', latitude: String(position.coords.latitude), longitude: String(position.coords.longitude), accuracy: String(position.coords.accuracy || '') };
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${position.coords.latitude}&lon=${position.coords.longitude}&zoom=10`, { headers: { 'Accept-Language': document.documentElement.lang || 'vi' } });
                    const address = (await response.json()).address || {};
                    next.countryCode = (address.country_code || '').toUpperCase();
                    next.country = address.country || '';
                    next.city = address.city || address.town || address.village || '';
                } catch { /* coordinates are sufficient */ }
                if (mountedRef.current) setLocation(next);
                resolve(next);
            }, () => {
                const next = { ...emptyLocation, permission: 'denied' };
                if (mountedRef.current) setLocation(next);
                resolve(next);
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
        });

        const trackedRequest = request.finally(() => {
            if (locationRequestRef.current === trackedRequest) locationRequestRef.current = null;
        });
        locationRequestRef.current = trackedRequest;
        return trackedRequest;
    }, []);

    useEffect(() => {
        mountedRef.current = true;
        collectLocation();
        return () => { mountedRef.current = false; };
    }, [collectLocation]);

    const submit = async (event) => {
        event.preventDefault();
        if (submitting) return;
        const form = formRef.current;
        const values = Object.fromEntries(new FormData(form).entries());
        setNotice(null);
        setErrors({});
        if (!values.username) return showFieldError('username', 'Tên người dùng không được để trống!');
        if (!values.phone) return showFieldError('phone', 'Số điện thoại không được để trống!');
        if (!values.email) return showFieldError('email', 'Email không được để trống!');
        if (!values.password) return showFieldError('password', 'Mật khẩu không được để trống!');
        if (!values.repassword) return showFieldError('repassword', 'Vui lòng nhập lại mật khẩu!');
        if (!values.accept_terms) return showFieldError('accept_terms', 'Vui lòng chấp nhận điều khoản của chúng tôi!');
        if (values.username.length < 6 || values.username.length > 255) return showFieldError('username', 'Tên đăng nhập tối thiểu 6 ký tự và tối đa 255 ký tự!');
        if (!/^(0|\+84)[0-9]{9,10}$/.test(values.phone)) return showFieldError('phone', 'Số điện thoại không hợp lệ');
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) return showFieldError('email', 'Email không hợp lệ');
        if (values.password.length < 6) return showFieldError('password', 'Mật khẩu phải từ 6 ký tự trở lên!');
        if (values.password !== values.repassword) return showFieldError('repassword', 'Mật khẩu không khớp!');

        setSubmitting(true);
        setNotice(null);
        try {
            let submitLocation = location;
            if (location.permission !== 'granted' || !location.latitude || !location.longitude) {
                submitLocation = await collectLocation();
            }
            if (values.referral_code) {
                const referral = await postCheck(config.routes.checkReferral, config.csrf, { referral_code: values.referral_code });
                if (!referral?.success) {
                    showFieldError('referral_code', 'Mã mời không hợp lệ, vui lòng thử lại!');
                    setSubmitting(false);
                    return;
                }
            }
            const email = await postCheck(config.routes.checkEmail, config.csrf, { email: values.email });
            if (email?.success) {
                showFieldError('email', 'Email đã tồn tại, vui lòng thử lại!');
                setSubmitting(false);
                return;
            }
            const formData = new FormData(form);
            formData.set('location_permission', submitLocation.permission || 'denied');
            formData.set('location_latitude', submitLocation.latitude || '');
            formData.set('location_longitude', submitLocation.longitude || '');
            formData.set('location_accuracy', submitLocation.accuracy || '');
            formData.set('location_country_code', submitLocation.countryCode || '');
            formData.set('location_country', submitLocation.country || '');
            formData.set('location_city', submitLocation.city || '');

            const response = await fetch(config.routes.submit, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': config.csrf,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                if (data.errors && Object.keys(data.errors).length) {
                    setErrors(data.errors);
                    setNotice(null);
                } else {
                    setNotice({ type: 'warning', message: data.message || 'Đăng ký thất bại. Vui lòng thử lại!' });
                }
                setSubmitting(false);
                return;
            }

            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }

            setSubmitting(false);
        } catch {
            setNotice({ type: 'error', message: 'Không thể kiểm tra dữ liệu. Vui lòng thử lại!' });
            setSubmitting(false);
        }
    };

    return (
        <div className="container_register">
            <Background src={config.assets.background} />
            <main className="auth-page"><section className="auth-card auth-card-wide">
                <img className="auth-brand-logo auth-logo" src={config.assets.logo} alt="TT Affiliate" />
                <nav className="auth-tabs"><a href={config.routes.login}>Đăng nhập</a><a className="active" href={config.routes.register}>Đăng ký</a></nav>
                <div className="auth-heading"><h1 className="auth-title">Tạo tài khoản</h1><p className="auth-subtitle">Tham gia hệ thống và bắt đầu hành trình của bạn</p></div>
                <ServerNotice flash={config.flash} local={notice} />
                <form ref={formRef} action={config.routes.submit} method="post" onSubmit={submit}>
                    <input type="hidden" name="_token" value={config.csrf} />
                    <AuthField icon="fa-user" label="Họ và tên" id="full_name_register"><input className="form-control auth-input" id="full_name_register" defaultValue={old.full_name || ''} name="full_name" type="text" placeholder="Nhập họ và tên thật của bạn" autoComplete="name" /></AuthField>
                    <AuthField icon="fa-at" label="Tên đăng nhập" id="username_register" error={<FieldError errors={errors} name="username" />}><input className="form-control auth-input" id="username_register" defaultValue={old.username || ''} name="username" type="text" placeholder="Nhập tên tài khoản" autoComplete="username" onChange={() => clearFieldError('username')} /></AuthField>
                    <AuthField icon="fa-phone" label="Số điện thoại" id="phone_register" error={<FieldError errors={errors} name="phone" />}><input className="form-control auth-input" id="phone_register" defaultValue={old.phone || ''} name="phone" type="tel" placeholder="Nhập số điện thoại" autoComplete="tel" onChange={() => clearFieldError('phone')} /></AuthField>
                    <AuthField icon="fa-envelope" label="Email" id="email_register" error={<FieldError errors={errors} name="email" />}><input className="form-control auth-input" id="email_register" defaultValue={old.email || ''} name="email" type="email" placeholder="you@example.com" autoComplete="email" onChange={() => clearFieldError('email')} /></AuthField>
                    <AuthField icon="fa-lock" label="Mật khẩu" id="password_register" error={<FieldError errors={errors} name="password" />}><input className="form-control auth-input" id="password_register" name="password" type={passwordVisible ? 'text' : 'password'} placeholder="Nhập mật khẩu" onChange={() => clearFieldError('password')} /><PasswordToggle visible={passwordVisible} onClick={() => setPasswordVisible((value) => !value)} /></AuthField>
                    <AuthField icon="fa-lock" label="Nhập lại mật khẩu" id="repassword_register" error={<FieldError errors={errors} name="repassword" />}><input className="form-control auth-input" id="repassword_register" name="repassword" type={repeatVisible ? 'text' : 'password'} placeholder="Nhập lại mật khẩu" onChange={() => clearFieldError('repassword')} /><PasswordToggle visible={repeatVisible} onClick={() => setRepeatVisible((value) => !value)} /></AuthField>
                    <AuthField icon="fa-gift" label={<>Mã giới thiệu <span className="text-white-50">(tuỳ chọn)</span></>} id="referral_code_register" error={<FieldError errors={errors} name="referral_code" />}><input className="form-control auth-input" id="referral_code_register" defaultValue={old.referral_code || ''} name="referral_code" type="text" placeholder="Nhập mã giới thiệu" onChange={() => clearFieldError('referral_code')} /></AuthField>
                    <input type="hidden" name="location_permission" value={location.permission} /><input type="hidden" name="location_latitude" value={location.latitude} /><input type="hidden" name="location_longitude" value={location.longitude} /><input type="hidden" name="location_accuracy" value={location.accuracy} /><input type="hidden" name="location_country_code" value={location.countryCode} /><input type="hidden" name="location_country" value={location.country} /><input type="hidden" name="location_city" value={location.city} />
                    <div className="auth-helper form-check mt-2"><input className="form-check-input p-2" type="checkbox" name="accept_terms" value="1" id="accept_terms" onChange={() => clearFieldError('accept_terms')} /><label className="form-check-label" htmlFor="accept_terms">Đồng ý với <span className="text-decoration-underline">điều khoản</span> của chúng tôi.</label></div>
                    <FieldError errors={errors} name="accept_terms" />
                    <Turnstile config={config.turnstile} action="register" /><FieldError errors={errors} name="cf-turnstile-response" />
                    <div className="d-grid mt-4"><button type="submit" className="btn auth-submit" disabled={submitting}>{submitting ? 'Đang xử lý...' : <>Tạo tài khoản <i className="fa-solid fa-arrow-right ms-2" /></>}</button></div>
                </form>
                <div className="auth-switch text-center mt-4">Bạn đã có tài khoản? <a href={config.routes.login}>Đăng nhập ngay</a></div>
                <div className="auth-social"><img src={config.assets.facebook} alt="Facebook" /><img src={config.assets.google} alt="Google" /></div>
            </section></main>
            <Disclaimer />
        </div>
    );
}

function AuthField({ icon, label, id, children, error }) {
    return <div className="auth-field"><label htmlFor={id} className="auth-label">{label}</label><div className="auth-input-wrap"><i className={`fa-solid ${icon} auth-input-icon`} />{children}</div>{error}</div>;
}
