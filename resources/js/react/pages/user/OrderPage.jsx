import { useEffect } from 'react';
import '../../../../css/user/order.css';

const filters = [
    ['tat-ca', 'btn_tat_ca', 'fa-border-all', 'all', ''],
    ['cho-xu-ly', 'btn_cho_xu_ly', 'fa-clock', 'pending', ''],
    ['da-xac-nhan', 'btn_da_xac_nhan', 'fa-check', 'confirmed', ''],
    ['dang-chuan-bi', 'btn_dang_chuan_bi', 'fa-box', 'preparing', ''],
    ['dang-trung-chuyen', 'btn_dang_trung_chuyen', 'fa-route', 'transit', ''],
    ['dang-van-chuyen', 'btn_dang_van_chuyen', 'fa-truck', 'shipping', ''],
    ['da-giao-hang', 'btn_da_giao_hang', 'fa-box-open', 'delivered', ''],
    ['hoan-thanh', 'btn_hoan_thanh', 'fa-circle-check', 'completed', ''],
    ['da-huy', 'btn_da_huy', 'fa-ban', 'cancelled', ''],
    ['dong-bang', 'btn_dong_bang', 'fa-gem', 'highValue', 'history-filter-chip--hvo'],
    ['bi-phat', 'btn_bi_phat', 'fa-triangle-exclamation', null, 'history-filter-chip--danger'],
];

export default function OrderPage({ config }) {
    useEffect(() => {
        let cleanup = null;
        let cancelled = false;
        window.orderHistoryConfig = {
            routes: { list: config.routes.list, order: config.routes.order },
            csrf: config.csrf,
            userBalance: config.userBalance,
            labels: config.labels,
        };
        import('../../../user/order.js').then(() => {
            if (!cancelled) cleanup = window.__initOrderHistory?.() || null;
        });

        return () => {
            cancelled = true;
            cleanup?.();
        };
    }, [config]);

    return (
        <main className="order-history-page" data-order-history>
            <header className="order-history-nav">
                <a className="order-history-back" href={config.routes.distribution} aria-label="Quay lại trang phân phối"><i className="fas fa-arrow-left" /></a>
                <div className="order-history-heading"><span className="order-history-eyebrow">Distribution history</span><h1>{config.labels.historyTitle}</h1></div>
                <div className="order-history-balance" aria-label={config.labels.currentBalance}><span>{config.labels.currentBalance}</span><strong id="so_du_user">${config.userBalanceFormatted}</strong></div>
            </header>

            <section className="order-history-intro" aria-labelledby="order-history-title">
                <div className="order-history-intro__copy"><span className="order-history-live"><i /> Lịch sử phân phối</span><h2 id="order-history-title">Theo dõi từng đơn.<br /><em>Nhìn nhanh, hiểu ngay.</em></h2><p>{config.labels.description}</p></div>
                <div className="order-history-intro__legend" aria-label="Chú thích trạng thái"><span><i className="is-success" /> Hoàn thành</span><span><i className="is-progress" /> Đang xử lý</span><span><i className="is-warning" /> Cần chú ý</span></div>
            </section>

            <section className="order-history-filter" aria-label="Lọc lịch sử đơn hàng">
                <div className="order-history-filter__rail" id="tabNavigation">
                    {filters.map(([tab, id, icon, labelKey, extra], index) => <button key={id} type="button" data-tab={tab} data-filter-id={id} className={`history-filter-chip ${index === 0 ? 'is-active' : ''} ${extra}`}><i className={`fas ${icon}`} /><span id={id}>{labelKey ? config.labels[labelKey] : 'Bị phạt'}</span></button>)}
                </div>
            </section>

            <section className="order-history-feed" aria-labelledby="history-feed-title">
                <div className="order-history-feed__head"><div><span className="order-history-section-kicker">Danh sách đơn hàng</span><h2 id="history-feed-title">Tất cả đơn hàng</h2></div><span className="order-history-count" id="historyResultCount" aria-live="polite">Đang tải</span></div>
                <div className="order-history-list" id="list_orders" aria-live="polite" aria-busy="true"><Loading /></div>
            </section>
        </main>
    );
}

function Loading() {
    return <div className="history-loading" aria-label="Đang tải lịch sử đơn hàng">{[0, 1, 2].map((index) => <div className="history-skeleton" key={index}><span className="history-skeleton__line history-skeleton__line--short" /><span className="history-skeleton__block" /><span className="history-skeleton__line" /></div>)}</div>;
}
