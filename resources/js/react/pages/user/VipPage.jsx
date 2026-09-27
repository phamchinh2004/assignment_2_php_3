import '../../../../css/user/vip.css';

const tierIcons = ['fa-star', 'fa-gem', 'fa-crown', 'fa-trophy'];

function plus(value, formatter = (item) => item) {
    return Number(value || 0) > 0 ? `+${formatter(value)}` : null;
}

function formatMoney(value) {
    const number = Number(value || 0);
    const digits = Math.abs(number) >= 1 ? 4 : 8;
    return number.toLocaleString('en-US', { maximumFractionDigits: digits });
}

export default function VipPage({ config }) {
    const { user, rank, ranks = [], currentRankIndex, nextRankId, routes, assets, labels } = config;
    const nextRank = ranks.find((item) => Number(item.id) === Number(nextRankId)) || null;
    const hasCurrentRank = Boolean(rank);

    const gains = nextRank && rank ? {
        commission: nextRank.commissionPercentage - rank.commissionPercentage,
        task: nextRank.spinCount - rank.spinCount,
        withdrawal: nextRank.maximumNumberOfWithdrawals - rank.maximumNumberOfWithdrawals,
        limit: nextRank.maximumWithdrawalAmount - rank.maximumWithdrawalAmount,
    } : null;

    return (
        <main className="membership-page">
            <header className="membership-header">
                <a href={routes.me} className="membership-back" aria-label="Quay lại tài khoản">
                    <i className="fa-solid fa-arrow-left" />
                </a>
                <div>
                    <p className="membership-eyebrow">Membership</p>
                    <h1>{labels.title}</h1>
                    <p>Khám phá quyền lợi và giới hạn thực tế của từng cấp độ.</p>
                </div>
            </header>

            <section className="membership-overview" aria-labelledby="current-membership-title">
                <div className="overview-orb overview-orb--one" aria-hidden="true" />
                <div className="overview-orb overview-orb--two" aria-hidden="true" />
                <div className="overview-main">
                    <div className="member-identity">
                        <div className="member-avatar">
                            <img src={user.avatarUrl} alt={user.fullName || user.username} onError={(event) => { event.currentTarget.src = assets.defaultAvatar; }} />
                            <span><i className="fa-solid fa-crown" /></span>
                        </div>
                        <div className="member-copy">
                            <p>Cấp độ hiện tại</p>
                            <h2 id="current-membership-title">{rank?.name || labels.noRank}</h2>
                            <span>{user.fullName} · @{user.username}</span>
                        </div>
                    </div>

                    {rank ? (
                        <div className="current-benefits">
                            <div><span>Hoa hồng</span><strong>{rank.commissionPercentageFormatted}%</strong></div>
                            <div><span>Nhiệm vụ/ngày</span><strong>{rank.spinCount}</strong></div>
                            <div><span>Rút tiền/ngày</span><strong>{rank.maximumNumberOfWithdrawals} lượt</strong></div>
                            <div><span>Hạn mức/lần</span><strong>{rank.maximumWithdrawalAmountFormatted}$</strong></div>
                        </div>
                    ) : (
                        <div className="membership-empty"><i className="fa-regular fa-gem" /><span>Tài khoản chưa được gán cấp độ thành viên.</span></div>
                    )}
                </div>

                {nextRank ? (
                    <aside className="next-tier-panel">
                        <div className="next-tier-heading"><span>Cấp tiếp theo</span><i className="fa-solid fa-arrow-trend-up" /></div>
                        <h3>{nextRank.name}</h3>
                        <p className="next-tier-price"><small>Điều kiện cấp độ</small><strong>{nextRank.upgradeFeeFormatted}$</strong></p>
                        {gains ? (
                            <div className="upgrade-gains">
                                {gains.commission > 0 && <span><i className="fa-solid fa-plus" />{formatMoney(gains.commission)}% hoa hồng</span>}
                                {gains.task > 0 && <span><i className="fa-solid fa-plus" />{gains.task} nhiệm vụ/ngày</span>}
                                {gains.withdrawal > 0 && <span><i className="fa-solid fa-plus" />{gains.withdrawal} lượt rút/ngày</span>}
                                {gains.limit > 0 && <span><i className="fa-solid fa-plus" />{formatMoney(gains.limit)}$ hạn mức/lần</span>}
                            </div>
                        ) : <p className="next-tier-note">Cấp độ đầu tiên trong hệ thống.</p>}
                    </aside>
                ) : (
                    <aside className="next-tier-panel next-tier-panel--max">
                        <div className="max-tier-icon"><i className="fa-solid fa-trophy" /></div>
                        <span>Cấp cao nhất</span>
                        <h3>Bạn đang ở cấp độ cao nhất</h3>
                        <p>Toàn bộ giới hạn của cấp hiện tại đang được áp dụng.</p>
                    </aside>
                )}
            </section>

            <section className="benefit-section" aria-labelledby="benefit-title">
                <div className="section-heading"><div><p className="membership-eyebrow">Quyền lợi cốt lõi</p><h2 id="benefit-title">Giá trị theo từng cấp</h2></div></div>
                <div className="benefit-grid">
                    <Benefit icon="fa-percent" title="Hoa hồng theo cấp">Tỷ lệ hoa hồng của đơn hoàn thành được cấu hình riêng cho từng cấp.</Benefit>
                    <Benefit icon="fa-list-check" title="Nhiệm vụ mỗi ngày">Số lượt phân phối trong ngày phụ thuộc trực tiếp vào cấp hiện tại.</Benefit>
                    <Benefit icon="fa-wallet" title="Giới hạn rút tiền">Số lượt và số tiền tối đa mỗi lần rút được áp dụng theo cấu hình cấp.</Benefit>
                </div>
            </section>

            <section className="tiers-section" aria-labelledby="tiers-title">
                <div className="section-heading"><div><p className="membership-eyebrow">Danh sách cấp độ</p><h2 id="tiers-title">So sánh quyền lợi</h2></div><span>{ranks.length} cấp độ</span></div>
                {ranks.length === 0 ? (
                    <div className="tiers-empty"><i className="fa-regular fa-folder-open" /><strong>Chưa có cấp độ</strong><span>Hệ thống chưa cấu hình dữ liệu thành viên.</span></div>
                ) : (
                    <div className="tier-grid">
                        {ranks.map((item, index) => {
                            const isCurrent = rank && Number(item.id) === Number(rank.id);
                            const isHigher = hasCurrentRank && currentRankIndex !== null && index > currentRankIndex;
                            const commissionDelta = isHigher ? item.commissionPercentage - rank.commissionPercentage : 0;
                            const taskDelta = isHigher ? item.spinCount - rank.spinCount : 0;
                            const withdrawalDelta = isHigher ? item.maximumNumberOfWithdrawals - rank.maximumNumberOfWithdrawals : 0;
                            const limitDelta = isHigher ? item.maximumWithdrawalAmount - rank.maximumWithdrawalAmount : 0;

                            return (
                                <article key={item.id} className={`tier-card tier-card--${(index % 4) + 1} ${isCurrent ? 'is-current' : ''}`}>
                                    <div className="tier-card__header">
                                        <div className="tier-mark">{item.imageUrl ? <img src={item.imageUrl} alt={item.name} /> : <i className={`fa-solid ${tierIcons[index % 4]}`} />}</div>
                                        <div className="tier-title"><span>Cấp {String(index + 1).padStart(2, '0')}</span><h3>{item.name}</h3></div>
                                        {isCurrent && <span className="current-tier-badge"><i className="fa-solid fa-check" />Cấp hiện tại</span>}
                                    </div>
                                    <div className="tier-price"><span>Điều kiện cấp độ</span><strong>{item.upgradeFeeFormatted}<small>$</small></strong></div>
                                    <div className="commission-highlight">
                                        <span>Hoa hồng</span><strong>{item.commissionPercentageFormatted}%</strong>
                                        {commissionDelta > 0 && <small>+{formatMoney(commissionDelta)}% so với cấp hiện tại</small>}
                                    </div>
                                    <div className="tier-features">
                                        <Feature icon="fa-list-check" label="Nhiệm vụ mỗi ngày" value={`${item.spinCount} nhiệm vụ`} delta={plus(taskDelta)} />
                                        <Feature icon="fa-arrow-up-from-bracket" label="Lượt rút mỗi ngày" value={`${item.maximumNumberOfWithdrawals} lượt`} delta={plus(withdrawalDelta)} />
                                        <Feature icon="fa-coins" label="Hạn mức mỗi lần rút" value={`${item.maximumWithdrawalAmountFormatted}$`} delta={limitDelta > 0 ? `+${formatMoney(limitDelta)}$` : null} />
                                        <Feature icon="fa-layer-group" label="Giá trị cấp độ" value={`${item.valueFormatted}$`} />
                                        <Feature icon="fa-regular fa-calendar" label="Thời hạn" value="Vĩnh viễn" regular />
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                )}
            </section>
        </main>
    );
}

function Benefit({ icon, title, children }) {
    return <article className="benefit-item"><span className="benefit-icon"><i className={`fa-solid ${icon}`} /></span><div><h3>{title}</h3><p>{children}</p></div></article>;
}

function Feature({ icon, label, value, delta, regular = false }) {
    return <div className="tier-feature"><span className="feature-icon"><i className={`${regular ? '' : 'fa-solid '}${icon}`} /></span><span><small>{label}</small><strong>{value}</strong></span>{delta && <em>{delta}</em>}</div>;
}
