import ApexCharts from 'apexcharts';

const initTransactionCharts = () => {
    const data = window.transactionStatistics || { profitLoss: [], status: {} };
    const money = value => `${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 5 }).format(value)}$`;
    const axisMoney = value => `${new Intl.NumberFormat('vi-VN', { notation: 'compact', maximumFractionDigits: 2 }).format(value)}$`;
    const signedMoney = value => `${value > 0 ? '+' : ''}${money(value)}`;
    const dateTime = value => new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character]);
    const empty = target => {
        target.innerHTML = '<div class="empty-state"><i class="fas fa-chart-line"></i><strong>Chưa có dữ liệu biểu đồ</strong><span>Thử chọn khoảng thời gian khác.</span></div>';
    };
    const failed = target => {
        target.innerHTML = '<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><strong>Không thể tải biểu đồ</strong><span>Vui lòng tải lại trang.</span></div>';
    };
    const render = (target, options) => {
        try {
            target.replaceChildren();
            const initialHeight = Math.round(target.getBoundingClientRect().height);
            options.chart.height = initialHeight;
            const chart = new ApexCharts(target, options);
            chart.render().then(() => {
                if (!window.ResizeObserver) return;
                let renderedHeight = initialHeight;
                const observer = new ResizeObserver(entries => {
                    const nextHeight = Math.round(entries[0].contentRect.height);
                    if (nextHeight <= 0 || nextHeight === renderedHeight) return;
                    renderedHeight = nextHeight;
                    chart.updateOptions({ chart: { height: nextHeight } }, false, false, false);
                });
                observer.observe(target);
            }).catch(() => failed(target));
        } catch (error) {
            failed(target);
        }
    };

    const profitLossTarget = document.querySelector('#profitLossChart');
    if (profitLossTarget) {
        if (!data.profitLoss.length) {
            empty(profitLossTarget);
        } else {
            let previousTimestamp = Number.NEGATIVE_INFINITY;
            const profitLossPoints = data.profitLoss.map(item => {
                const actualTimestamp = new Date(item.occurred_at).getTime();
                const chartTimestamp = actualTimestamp <= previousTimestamp ? previousTimestamp + 1 : actualTimestamp;
                previousTimestamp = chartTimestamp;
                return { x: chartTimestamp, y: item.cumulative };
            });
            const profitLossValues = data.profitLoss.map(item => Number(item.cumulative));
            const minimumProfitLoss = Math.min(0, ...profitLossValues);
            const maximumProfitLoss = Math.max(0, ...profitLossValues);
            const profitLossPadding = (maximumProfitLoss - minimumProfitLoss) * 0.1 || 1;
            const lineColor = profitLossValues.at(-1) >= 0 ? '#10b981' : '#ef4444';
            render(profitLossTarget, {
                chart: {
                    type: 'area', height: '100%', width: '100%', parentHeightOffset: 0,
                    redrawOnParentResize: true, redrawOnWindowResize: true, fontFamily: 'inherit',
                    toolbar: {
                        show: true, autoSelected: 'zoom', offsetX: 0, offsetY: 0,
                        tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
                    },
                    zoom: { enabled: true, type: 'x', autoScaleYaxis: true, allowMouseWheelZoom: true },
                    selection: { enabled: true, type: 'x', fill: { color: '#fe2c55', opacity: 0.12 }, stroke: { color: '#fe2c55', width: 1, dashArray: 3 } },
                    animations: { enabled: true, speed: 450 },
                },
                series: [{ name: 'Lãi/Lỗ lũy kế', data: profitLossPoints }],
                xaxis: { type: 'datetime', tickAmount: Math.min(data.profitLoss.length, 6), labels: { datetimeUTC: false, hideOverlappingLabels: true, rotate: 0, style: { colors: '#758093', fontSize: '10px' } } },
                yaxis: { min: minimumProfitLoss - profitLossPadding, max: maximumProfitLoss + profitLossPadding, labels: { formatter: axisMoney, minWidth: 38, maxWidth: 82, style: { colors: '#758093', fontSize: '10px' } } },
                colors: [lineColor],
                stroke: { curve: 'smooth', width: 3 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.32, opacityTo: 0.04, stops: [0, 90, 100] } },
                markers: { size: 0, colors: [lineColor], strokeWidth: 2, strokeColors: '#fff', hover: { size: 6, sizeOffset: 3 } },
                dataLabels: { enabled: false },
                grid: { borderColor: '#edf0f4', strokeDashArray: 4, padding: { left: 4, right: 10, top: 8, bottom: 2 } },
                annotations: { yaxis: [{ y: 0, borderColor: '#98a2b3', strokeDashArray: 0, label: { text: 'Mốc 0', position: 'left', style: { background: '#667085', color: '#fff', fontSize: '9px' } } }] },
                tooltip: { custom: ({ dataPointIndex }) => {
                    const point = data.profitLoss[dataPointIndex];
                    const eventLabel = point.event_type === 'commission' ? 'Hoa hồng' : point.event_type === 'penalty' ? 'Tiền phạt' : 'Mốc bắt đầu';
                    const order = point.order_code ? `<span>Mã đơn: <b>${escapeHtml(point.order_code)}</b></span>` : '';
                    return `<div class="pnl-tooltip"><strong>${dateTime(point.occurred_at)}</strong><span>Loại: <b>${eventLabel}</b></span><span>Biến động: <b class="${point.change >= 0 ? 'positive' : 'negative'}">${signedMoney(point.change)}</b></span><span>Lãi/Lỗ lũy kế: <b>${signedMoney(point.cumulative)}</b></span>${order}</div>`;
                } },
                responsive: [{ breakpoint: 576, options: { chart: { animations: { enabled: false } }, stroke: { width: 2.5 }, markers: { size: 3 }, grid: { padding: { left: 0, right: 5 } }, yaxis: { labels: { minWidth: 30, maxWidth: 58, style: { fontSize: '9px' } } } } }],
            });
        }
    }

    const statusTarget = document.querySelector('#statusChart');
    if (statusTarget) {
        const statusValues = [data.status.completed || 0, data.status.pending || 0, data.status.cancelled || 0];
        if (statusValues.every(value => value === 0)) {
            empty(statusTarget);
        } else {
            render(statusTarget, {
                chart: { type: 'donut', height: '100%', width: '100%', parentHeightOffset: 0, redrawOnParentResize: true, redrawOnWindowResize: true, fontFamily: 'inherit' },
                series: statusValues, labels: ['Hoàn thành', 'Đang chờ', 'Đã huỷ'], colors: ['#0f8a5f', '#f4a524', '#fe2c55'],
                stroke: { width: 0 }, dataLabels: { enabled: false }, legend: { show: true, position: 'bottom', fontSize: '10px', itemMargin: { horizontal: 6, vertical: 3 } },
                plotOptions: { pie: { expandOnClick: false, donut: { size: '68%', labels: { show: true, name: { show: false }, value: { show: false }, total: { show: true, showAlways: true, label: 'Giao dịch', formatter: chart => chart.globals.seriesTotals.reduce((sum, value) => sum + value, 0) } } } } },
                responsive: [{ breakpoint: 360, options: { legend: { fontSize: '9px', itemMargin: { horizontal: 3 } } } }],
            });
        }
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTransactionCharts, { once: true });
} else {
    initTransactionCharts();
}
