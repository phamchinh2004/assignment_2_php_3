import { useEffect, useRef, useState } from 'react';
import '../../../../css/user/me.css';
import LaravelForm, { fieldError } from '../../components/LaravelForm';

export default function MePage({ config }) {
    const { user, rank, accountSummary, routes, languages = [], locale, assets, labels, csrf, form } = config;
    const [languageOpen, setLanguageOpen] = useState(false);
    const languageRef = useRef(null);

    useEffect(() => {
        const close = (event) => {
            if (event.key === 'Escape' || (event.type === 'click' && languageRef.current && !languageRef.current.contains(event.target))) {
                setLanguageOpen(false);
            }
        };
        document.addEventListener('click', close);
        document.addEventListener('keydown', close);
        return () => {
            document.removeEventListener('click', close);
            document.removeEventListener('keydown', close);
        };
    }, []);

    const notifyDeposit = () => {
        if (typeof window.notification === 'function') {
            window.notification('warning', labels.contactSupport, labels.notificationTitle, 5000);
            return;
        }
        window.alert(labels.contactSupport);
    };

    return (
        <>
            <main className="me-page">
                <section className="profile-hero" aria-labelledby="profile-name">
                    <div className="profile-hero__glow" aria-hidden="true" />
                    <div className="profile-identity">
                        <a href={routes.personalInformation} className="profile-avatar" aria-label="Cập nhật thông tin cá nhân">
                            <img src={user.avatarUrl} alt={`Ảnh đại diện của ${user.fullName || user.username}`} onError={(event) => { event.currentTarget.src = assets.defaultAvatar; }} />
                            <span className="profile-avatar__edit"><i className="fa-solid fa-pen" /></span>
                        </a>
                        <div className="profile-copy">
                            <div className="profile-copy__badges">
                                {rank?.name && <span className="account-badge account-badge--rank"><i className="fa-solid fa-crown" />{rank.name}</span>}
                                <span className={`account-badge account-badge--status ${user.statusClass}`}><span className="status-dot" />{user.statusLabel}</span>
                            </div>
                            <h1 id="profile-name">{user.fullName}</h1>
                            <p className="profile-username"><i className="fa-regular fa-user" />@{user.username}</p>
                            <div className="referral-code"><span>{labels.inviteCode}</span><strong>{user.referralCode}</strong></div>
                        </div>
                    </div>
                    <a href={routes.personalInformation} className="profile-edit-link"><span>Chỉnh sửa hồ sơ</span><i className="fa-solid fa-chevron-right" /></a>
                </section>

                <div className="me-layout">
                    <div className="me-main-column">
                        <section className="wallet-card" aria-labelledby="wallet-title">
                            <div className="wallet-card__top">
                                <div><p className="section-eyebrow"><i className="fa-solid fa-wallet" />{labels.accountBalance}</p><h2 id="wallet-title" className="wallet-balance"><span>{user.balanceFormatted}</span><small>USD</small></h2><p className="wallet-caption">{labels.currentBalance}</p></div>
                                <a href={routes.balanceFluctuation} className="wallet-history-link" aria-label="Xem thống kê giao dịch"><i className="fa-solid fa-chart-line" /></a>
                            </div>
                            <div className="wallet-metrics">
                                <div className="wallet-metric"><span>Hoa hồng thực nhận</span><strong>{accountSummary.receivedCommissionFormatted} USD</strong></div>
                                <div className="wallet-metric"><span>Số dư đang giữ</span><strong>{user.frozenBalanceFormatted} USD</strong></div>
                                <div className="wallet-metric"><span>{labels.todayTransactions}</span><strong>{accountSummary.todayTransactions}</strong></div>
                            </div>
                            <div className="wallet-actions">
                                <button type="button" className="wallet-action wallet-action--primary" onClick={notifyDeposit}><span className="wallet-action__icon"><i className="fa-solid fa-plus" /></span><span>{labels.deposit}</span></button>
                                <a href={routes.withdrawMoney} className="wallet-action"><span className="wallet-action__icon"><i className="fa-solid fa-arrow-up" /></span><span>{labels.withdraw}</span></a>
                                <a href={routes.balanceFluctuation} className="wallet-action"><span className="wallet-action__icon"><i className="fa-solid fa-clock-rotate-left" /></span><span>Lịch sử</span></a>
                            </div>
                        </section>

                        <section className="me-section" aria-labelledby="quick-actions-title">
                            <div className="section-heading"><div><p className="section-eyebrow">Truy cập nhanh</p><h2 id="quick-actions-title">Hoạt động của bạn</h2></div></div>
                            <div className="quick-actions">
                                <QuickAction href={routes.distribution} icon="fa-store" cls="is-pink" title={labels.distribution} note="Nhận đơn mới" />
                                <QuickAction href={routes.order} icon="fa-box" cls="is-blue" title="Đơn hàng" note="Theo dõi xử lý" />
                                <QuickAction href={routes.balanceFluctuation} icon="fa-chart-column" cls="is-green" title={labels.balanceMovement} note="Thống kê tài chính" />
                                <QuickAction href={routes.vip} icon="fa-gem" cls="is-gold" title={labels.vip} note="Quyền lợi thành viên" />
                            </div>
                        </section>
                    </div>

                    <aside className="me-side-column">
                        <section className="me-section settings-card" aria-labelledby="account-title">
                            <div className="section-heading"><div><p className="section-eyebrow">Quản lý</p><h2 id="account-title">Tài khoản</h2></div></div>
                            <nav className="settings-list" aria-label="Quản lý tài khoản">
                                <SettingsLink href={routes.personalInformation} icon="fa-regular fa-id-card" cls="is-blue" title={labels.information} note="Hồ sơ và tài khoản ngân hàng" meta={user.hasBankAccount ? 'Đã liên kết' : 'Chưa liên kết'} complete={user.hasBankAccount} />
                                <button type="button" className="settings-item" data-bs-toggle="modal" data-bs-target="#warehouseAddressModal"><span className="settings-item__icon is-green"><i className="fa-solid fa-location-dot" /></span><span className="settings-item__copy"><strong>{labels.warehouseAddress}</strong><small>{user.warehouseArea || 'Thiết lập khu vực và địa chỉ'}</small></span><span className={`settings-item__meta ${user.hasWarehouse ? 'is-complete' : ''}`}>{user.hasWarehouse ? 'Đã cập nhật' : 'Chưa có'}</span><i className="fa-solid fa-chevron-right settings-item__arrow" /></button>
                                <SettingsLink href={`${routes.balanceFluctuation}?tab=deposit`} icon="fa-solid fa-arrow-down" cls="is-violet" title={labels.depositHistory} note="Giao dịch nạp tiền" />
                                <SettingsLink href={`${routes.balanceFluctuation}?tab=withdraw`} icon="fa-solid fa-arrow-up" cls="is-orange" title={labels.withdrawHistory} note="Giao dịch rút tiền" />
                            </nav>
                        </section>

                        <section className="me-section settings-card" aria-labelledby="security-title">
                            <div className="section-heading"><div><p className="section-eyebrow">Thiết lập</p><h2 id="security-title">Bảo mật & tuỳ chọn</h2></div></div>
                            <div className="settings-list">
                                <button type="button" className="settings-item" data-bs-toggle="modal" data-bs-target="#changePasswordModal"><span className="settings-item__icon is-slate"><i className="fa-solid fa-lock" /></span><span className="settings-item__copy"><strong>Mật khẩu đăng nhập</strong><small>Thay đổi mật khẩu tài khoản</small></span><i className="fa-solid fa-chevron-right settings-item__arrow" /></button>
                                <button type="button" className="settings-item" data-bs-toggle="modal" data-bs-target="#changeTransactionPasswordModal"><span className="settings-item__icon is-pink"><i className="fa-solid fa-shield-halved" /></span><span className="settings-item__copy"><strong>Mật khẩu giao dịch</strong><small>{user.hasTransactionPassword ? 'Đã thiết lập' : 'Chưa thiết lập'}</small></span><i className="fa-solid fa-chevron-right settings-item__arrow" /></button>
                                <div className="language-setting" ref={languageRef}>
                                    <button type="button" className="settings-item" onClick={(event) => { event.stopPropagation(); setLanguageOpen((value) => !value); }} aria-expanded={languageOpen} aria-controls="languageDropdown"><span className="settings-item__icon is-violet"><i className="fa-solid fa-language" /></span><span className="settings-item__copy"><strong>{labels.language}</strong><small>{String(locale || '').toUpperCase()}</small></span><i className="fa-solid fa-chevron-right settings-item__arrow" /></button>
                                    <div id="languageDropdown" className="language-menu" hidden={!languageOpen}>
                                        <LaravelForm action={routes.languageChange} method="POST">
                                            {languages.map((language) => <button key={language.code} type="submit" name="locale" value={language.code} className={`language-option ${locale === language.code ? 'is-current' : ''}`}><img src={language.imageUrl} width="24" height="24" alt="" /><span>{language.name}</span>{locale === language.code && <i className="fa-solid fa-check" />}</button>)}
                                        </LaravelForm>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <button type="button" onClick={() => window.log_out?.()} className="logout-button"><i className="fa-solid fa-arrow-right-from-bracket" /><span>{labels.logout}</span></button>
                    </aside>
                </div>
            </main>

            <WarehouseModal config={{ user, routes, labels, csrf, form }} />
        </>
    );
}

function QuickAction({ href, icon, cls, title, note }) {
    return <a href={href} className="quick-action"><span className={`quick-action__icon ${cls}`}><i className={`fa-solid ${icon}`} /></span><span><strong>{title}</strong><small>{note}</small></span></a>;
}

function SettingsLink({ href, icon, cls, title, note, meta, complete = false }) {
    return <a href={href} className="settings-item"><span className={`settings-item__icon ${cls}`}><i className={icon} /></span><span className="settings-item__copy"><strong>{title}</strong><small>{note}</small></span>{meta && <span className={`settings-item__meta ${complete ? 'is-complete' : ''}`}>{meta}</span>}<i className="fa-solid fa-chevron-right settings-item__arrow" /></a>;
}

function WarehouseModal({ config }) {
    const { user, routes, labels, csrf, form } = config;
    const old = form?.old || {};
    const areaError = fieldError(form, 'warehouse_area');
    const addressError = fieldError(form, 'warehouse_address');

    return <div className="modal fade account-security-modal me-warehouse-modal" id="warehouseAddressModal" tabIndex="-1" aria-labelledby="warehouseAddressLabel" aria-hidden="true"><div className="modal-dialog modal-dialog-centered"><div className="modal-content"><div className="account-security-modal__header"><span className="account-security-modal__icon is-green" aria-hidden="true"><i className="fa-solid fa-location-dot" /></span><div className="account-security-modal__heading"><span className="account-security-modal__eyebrow">Thông tin nhận hàng</span><h5 className="modal-title" id="warehouseAddressLabel">{labels.warehouseModalTitle}</h5><p>Cập nhật khu vực và địa chỉ kho đang sử dụng cho tài khoản.</p></div><button type="button" className="account-security-modal__close" data-bs-dismiss="modal" aria-label="Đóng"><i className="fa-solid fa-xmark" aria-hidden="true" /></button></div><div className="modal-body"><div className="account-security-modal__notice me-warehouse-modal__notice"><i className="fa-solid fa-circle-info" aria-hidden="true" /><span>Kiểm tra lại thông tin trước khi lưu để tránh sai lệch địa chỉ.</span></div><LaravelForm action={routes.warehouseAddressUpdate} method="POST" id="warehouseAddressForm"><div className="account-security-field"><label htmlFor="warehouse_area">{labels.area}</label><div className="account-security-field__control"><i className="fa-solid fa-map" aria-hidden="true" /><input type="text" className={`form-control ${areaError ? 'is-invalid' : ''}`} id="warehouse_area" name="warehouse_area" placeholder={labels.areaPlaceholder} defaultValue={old.warehouse_area ?? user.warehouseArea ?? ''} required maxLength="191" /></div>{areaError && <div className="invalid-feedback d-block">{areaError}</div>}</div><div className="account-security-field"><label htmlFor="warehouse_address">{labels.currentAddress}</label><div className="account-security-field__control account-security-field__control--textarea"><i className="fa-solid fa-location-crosshairs" aria-hidden="true" /><textarea className={`form-control ${addressError ? 'is-invalid' : ''}`} id="warehouse_address" name="warehouse_address" rows="3" placeholder={labels.addressPlaceholder} defaultValue={old.warehouse_address ?? user.warehouseAddress ?? ''} required maxLength="1000" /></div>{addressError && <div className="invalid-feedback d-block">{addressError}</div>}</div></LaravelForm></div><div className="modal-footer"><button type="button" className="account-security-modal__button is-secondary" data-bs-dismiss="modal">{labels.close}</button><button type="submit" className="account-security-modal__button is-primary" form="warehouseAddressForm"><span>{labels.save}</span><i className="fa-solid fa-arrow-right" aria-hidden="true" /></button></div></div></div></div>;
}
