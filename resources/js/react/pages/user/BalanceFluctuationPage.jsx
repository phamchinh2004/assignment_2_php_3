import { formatLocalDateTime } from '../../../shared/datetime';
import { useEffect } from 'react';
import '../../../../css/user/balance_fluctuation.css';
import { spaNavigate } from '../../navigation';

const typeLabels = {
    deposit: 'Nạp tiền', withdraw: 'Rút tiền', order: 'Thanh toán đơn', profit: 'Hoa hồng',
    penalty: 'Tiền phạt', settlement: 'Hoàn nhập đơn', refund: 'Hoàn tiền rút',
};
const typeIcons = {
    deposit: 'fa-arrow-down', withdraw: 'fa-arrow-up', order: 'fa-bag-shopping', profit: 'fa-coins',
    penalty: 'fa-triangle-exclamation', settlement: 'fa-rotate-left', refund: 'fa-rotate-left',
};
const statusLabels = { completed: 'Hoàn thành', processing: 'Đang xử lý', cancelled: 'Đã huỷ', recorded: 'Đã ghi sổ' };
const detailLabels = { normal: 'Tiền nạp', bonus: 'Tiền thưởng', virtual_withdraw: 'Rút tiền', balance: 'Số dư khả dụng', frozen_balance: 'Số dư đóng băng' };

const money = (value, precision = 2) => `${new Intl.NumberFormat('en-US', { maximumFractionDigits: precision }).format(Number(value || 0))}$`;
const trend = (value) => `${Number(value || 0) > 0 ? '+' : ''}${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 1 }).format(Number(value || 0))}%`;
const number = (value) => new Intl.NumberFormat('vi-VN').format(Number(value || 0));
const dateTime = (value) => value ? formatLocalDateTime(value, { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';

function withQuery(url, query) {
    const params = new URLSearchParams(Object.entries(query).filter(([, value]) => value !== '' && value !== null && value !== undefined));
    return `${url}?${params.toString()}`;
}

export default function BalanceFluctuationPage({ config }) {
    const summary = config.statistics.summary;
    const transactions = config.statistics.transactions;

    const applyFilters = (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const url = new URL(config.routes.balanceFluctuation, window.location.origin);

        for (const [key, value] of formData.entries()) {
            const normalized = String(value).trim();
            if (normalized !== '') url.searchParams.set(key, normalized);
        }

        spaNavigate(url.toString());
    };

    useEffect(() => {
        let active = true;
        let cleanupCharts = () => {};
        const previousStatistics = window.transactionStatistics;

        window.transactionStatistics = {
            profitLoss: config.statistics.profitLossSeries || [],
            status: {
                completed: summary.completed_count,
                pending: summary.pending_count,
                cancelled: summary.cancelled_count,
            },
        };

        import('../../../user/balance_fluctuation.js').then(({ initTransactionCharts }) => {
            if (!active) return;
            cleanupCharts = initTransactionCharts();
        });

        return () => {
            active = false;
            cleanupCharts();
            if (previousStatistics === undefined) delete window.transactionStatistics;
            else window.transactionStatistics = previousStatistics;
        };
    }, [config.statistics.profitLossSeries, summary.cancelled_count, summary.completed_count, summary.pending_count]);

    return (
        <main className="finance-page">
            <header className="finance-header">
                <a href={config.routes.home} className="finance-back" aria-label="Quay lại"><i className="fas fa-arrow-left" /></a>
                <div><div className="finance-eyebrow">Tài chính cá nhân</div><h1>Thống kê giao dịch</h1><p>Theo dõi dòng tiền, đơn hàng và thu nhập của bạn.</p></div>
            </header>

            <section className="balance-hero">
                <div><div className="hero-label">Số dư khả dụng</div><div className="hero-balance">{money(config.user.balance, 5)}</div><div className="hero-meta"><span><i className="fas fa-snowflake" /> Đóng băng {money(config.user.frozenBalance, 5)}</span><span><i className="fas fa-clock" /> Hoa hồng chờ {money(summary.pending_commission, 5)}</span><span><i className="fas fa-receipt" /> {number(summary.transaction_count)} bút toán</span></div></div>
                <div className="hero-change"><span>Biến động kỳ này</span><strong className={summary.net_movement >= 0 ? 'positive' : 'negative'}>{summary.net_movement >= 0 ? '+' : '-'}{money(Math.abs(summary.net_movement), 5)}</strong><small className={summary.net_growth >= 0 ? 'positive' : 'negative'}>{trend(summary.net_growth)} so với kỳ trước</small></div>
            </section>

            <form className="finance-filter" method="GET" action={config.routes.balanceFluctuation} onSubmit={applyFilters}>
                <input type="hidden" name="range" defaultValue={config.selectedRange} />
                <div className="preset-scroll">
                    {[['today', 'Hôm nay'], ['7d', '7 ngày'], ['30d', '30 ngày'], ['month', 'Tháng này']].map(([value, label]) => <a key={value} href={withQuery(config.routes.balanceFluctuation, { range: value, type: config.selectedType })} className={`preset ${config.selectedRange === value ? 'active' : ''}`}>{label}</a>)}
                </div>
                <div className="filter-row"><select name="type" aria-label="Loại giao dịch" defaultValue={config.selectedType}>{[['all', 'Tất cả giao dịch'], ['wallet', 'Nạp / rút'], ['order', 'Tiền đơn'], ['profit', 'Hoa hồng'], ['penalty', 'Tiền phạt'], ['refund', 'Hoàn nhập']].map(([value, label]) => <option value={value} key={value}>{label}</option>)}</select><button className="filter-submit"><i className="fas fa-filter" /> Lọc</button></div>
                <div className="custom-range"><input type="date" name="start_date" defaultValue={config.startDate} /><input type="date" name="end_date" defaultValue={config.endDate} /><button type="submit" onClick={(event) => { event.currentTarget.form.elements.range.value = 'custom'; }}>Áp dụng ngày</button></div>
            </form>
            {config.form?.errors?.start_date?.[0] && <div className="finance-error">{config.form.errors.start_date[0]}</div>}

            <section className="kpi-grid">
                <Kpi cls="income" icon="fa-coins" label="Hoa hồng đã nhận" value={money(summary.commission_amount, 5)} note={`${trend(summary.commission_growth)} so với kỳ trước`} />
                <Kpi cls="deposit" icon="fa-arrow-down" label="Tổng tiền nạp" value={money(summary.deposit_amount)} note={`${trend(summary.deposit_growth)} so với kỳ trước`} />
                <Kpi cls="withdraw" icon="fa-arrow-up" label="Đã rút" value={money(summary.withdraw_amount)} note={`Đang chờ ${money(summary.pending_withdraw_amount)}`} />
                <Kpi cls="hvo" icon="fa-gem" label="Đơn hàng giá trị cao" value={number(summary.high_value_order_received_count)} note="Số lượng HVO đã nhận trong kỳ" />
                <Kpi cls="order" icon="fa-bag-shopping" label="Đơn đã hoàn thành" value={number(summary.completed_order_count)} note="Hoàn thành trong kỳ đang chọn" />
                <Kpi cls="penalty" icon="fa-triangle-exclamation" label="Tổng tiền phạt" value={money(summary.penalty_amount)} note="Đã ghi nhận trong sổ giao dịch" />
            </section>

            <section className="analytics-grid">
                <article className="finance-card"><div className="card-head"><div><h2>Xu hướng Lãi/Lỗ</h2><p>Hoa hồng thực nhận trừ tiền phạt thực tế, lũy kế từ đầu kỳ.</p></div></div><div id="profitLossChart" className="chart-box"><div className="chart-loading"><i className="fas fa-spinner fa-spin" /> Đang tải biểu đồ</div></div></article>
                <article className="finance-card"><div className="card-head"><div><h2>Trạng thái ví</h2><p>Nạp/rút theo trạng thái xử lý.</p></div></div><div id="statusChart" className="chart-box compact" /><div className="status-summary"><span><b>{summary.completed_count}</b> hoàn thành</span><span><b>{summary.pending_count}</b> đang chờ</span><span><b>{summary.cancelled_count}</b> đã huỷ</span></div></article>
            </section>

            <section className="finance-card breakdown-card"><div className="card-head"><div><h2>Cơ cấu giao dịch</h2><p>Mỗi nguồn được tách riêng; không cộng chéo settlement với commission/phạt.</p></div></div><div className="breakdown-grid">{config.statistics.breakdown.length ? config.statistics.breakdown.map((item, index) => <div className="breakdown-item" key={`${item.type}-${item.direction}-${index}`}><span className={`transaction-icon ${item.direction}`}><i className={`fas ${typeIcons[item.type] || 'fa-receipt'}`} /></span><div><strong>{typeLabels[item.type] || item.type}</strong><small>{number(item.transaction_count)} giao dịch</small></div><b>{money(item.total_amount, 5)}</b></div>) : <div className="empty-state">Chưa có dữ liệu trong kỳ.</div>}</div></section>

            <section className="finance-card history-card">
                <div className="card-head"><div><h2>Lịch sử giao dịch</h2><p>{number(transactions.total)} bản ghi theo filter.</p></div></div>
                <div className="transaction-list">{transactions.data.length ? transactions.data.map((item) => <Transaction key={`${item.sourceId}-${item.createdAt}-${item.type}`} item={item} />) : <div className="empty-state"><i className="fas fa-receipt" /><strong>Chưa có giao dịch</strong><span>Thử chọn khoảng thời gian hoặc loại giao dịch khác.</span></div>}</div>
                {transactions.lastPage > 1 && <div className="pagination-wrap"><nav className="finance-pagination" aria-label="Phân trang lịch sử giao dịch">{transactions.previousPageUrl ? <a className="page-control" href={transactions.previousPageUrl} rel="prev" aria-label="Trang trước"><i className="fas fa-chevron-left" /></a> : <span className="page-control disabled" aria-disabled="true"><i className="fas fa-chevron-left" /></span>}<span className="page-position">{transactions.currentPage} / {transactions.lastPage}</span>{transactions.nextPageUrl ? <a className="page-control" href={transactions.nextPageUrl} rel="next" aria-label="Trang sau"><i className="fas fa-chevron-right" /></a> : <span className="page-control disabled" aria-disabled="true"><i className="fas fa-chevron-right" /></span>}</nav></div>}
            </section>
            <div className="calculation-note"><i className="fas fa-circle-info" /><span>Hoàn nhập chỉ ghi nhận khi có settlement bất biến, bộ bút toán hoàn tất đối chiếu được, hoặc khoản rút đã bị huỷ và thực tế cộng lại ví. Biến động kỳ tính từng dòng tiền tại đúng thời điểm phát sinh nên không cộng trùng hoa hồng, phạt hay hoàn nhập.</span></div>
        </main>
    );
}

function Kpi({ cls, icon, label, value, note }) {
    return <article className={`kpi-card ${cls}`}><span className="kpi-icon"><i className={`fas ${icon}`} /></span><div className="kpi-label">{label}</div><div className="kpi-value">{value}</div><div className="kpi-note">{note}</div></article>;
}

function Transaction({ item }) {
    const cancelled = item.status === 'cancelled';
    const incoming = item.direction === 'in';
    const info = item.direction === 'info';
    const directionClass = cancelled ? 'cancelled' : incoming ? 'in' : info ? 'info' : 'out';
    const moneyClass = cancelled ? 'muted' : incoming ? 'positive' : info ? 'info' : 'negative';
    const sign = cancelled ? '' : incoming || info ? '+' : '-';
    return <article className="transaction-row"><span className={`transaction-icon ${directionClass}`}><i className={`fas ${typeIcons[item.type] || 'fa-receipt'}`} /></span><div className="transaction-main"><div className="transaction-title">{typeLabels[item.type] || item.type} <span className={`status-pill ${item.status}`}>{statusLabels[item.status] || item.status}</span></div><div className="transaction-meta">{dateTime(item.createdAt)}{item.note ? ` · ${item.note}` : ''}</div>{item.detail && <div className="transaction-detail">{detailLabels[item.detail] || item.detail}</div>}</div><div className={`transaction-money ${moneyClass}`}>{sign}{money(item.value, 5)}</div></article>;
}
