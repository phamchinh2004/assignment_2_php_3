/**
 * Statistical Dashboard - Tổng doanh thu
 * Modern Analytics Dashboard with Chart.js & Dynamic Filters
 */

class StatisticalDashboard {
    constructor() {
        this.charts = {};
        this.currentPeriod = 30;
        this.isLoading = false;
        this.init();
    }

    init() {
        this.initDatePickers();
        this.setupEventListeners();
        this.initializeCharts();
        this.loadData();
    }

    initDatePickers() {
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 29);

        $('#startDate').val(this.formatDateForInput(thirtyDaysAgo));
        $('#endDate').val(this.formatDateForInput(today));
    }

    formatDateForInput(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    setupEventListeners() {
        // Quick Presets
        $('.preset-btn').on('click', (e) => {
            $('.preset-btn').removeClass('active');
            const btn = $(e.currentTarget);
            btn.addClass('active');

            const days = btn.data('days');
            const today = new Date();
            let start = new Date();
            let end = new Date();

            if (days === 0) {
                // Hôm nay
                start = today;
                end = today;
                this.currentPeriod = 1;
            } else if (days === 'this_month') {
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = today;
                this.currentPeriod = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1);
            } else if (days === 'last_month') {
                start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                end = new Date(today.getFullYear(), today.getMonth(), 0);
                this.currentPeriod = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            } else {
                const dayCount = parseInt(days, 10);
                start.setDate(today.getDate() - (dayCount - 1));
                end = today;
                this.currentPeriod = dayCount;
            }

            $('#startDate').val(this.formatDateForInput(start));
            $('#endDate').val(this.formatDateForInput(end));

            this.loadData();
        });

        // Apply custom range
        $('#applyFilterBtn').on('click', () => {
            $('.preset-btn').removeClass('active');
            const startVal = $('#startDate').val();
            const endVal = $('#endDate').val();

            if (startVal && endVal) {
                const s = new Date(startVal);
                const e = new Date(endVal);
                if (s > e) {
                    alert('Ngày bắt đầu không được lớn hơn ngày kết thúc!');
                    return;
                }
                this.currentPeriod = Math.max(1, Math.ceil((e - s) / (1000 * 60 * 60 * 24)) + 1);
            }
            this.loadData();
        });

        // Refresh
        $('#refreshBtn').on('click', () => {
            this.loadData();
        });

        // Export CSV
        $('#exportBtn').on('click', () => {
            const start = $('#startDate').val();
            const end = $('#endDate').val();
            let exportUrl = `/api/statistical/export-revenue-data?period=${this.currentPeriod}`;
            if (start && end) {
                exportUrl += `&start_date=${start}&end_date=${end}`;
            }
            window.location.href = exportUrl;
        });
    }

    initializeCharts() {
        const primaryColor = '#4f46e5';
        const successColor = '#10b981';
        const warningColor = '#f59e0b';
        const dangerColor = '#ef4444';

        // 1. Revenue Area Chart
        const revCanvas = document.getElementById('revenueChart');
        if (revCanvas) {
            const ctx = revCanvas.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(79, 70, 229, 0.28)');
            gradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

            this.charts.revenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Doanh thu ròng',
                        data: [],
                        borderColor: primaryColor,
                        borderWidth: 2.5,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: primaryColor,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#ffffff',
                            bodyColor: '#e2e8f0',
                            padding: 10,
                            borderRadius: 8,
                            callbacks: {
                                label: (context) => ` Doanh thu: ${format_currency(context.parsed.y)}`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 }, color: '#64748b' }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(226, 232, 240, 0.6)' },
                            ticks: {
                                font: { size: 11 },
                                color: '#64748b',
                                callback: (v) => format_currency(v)
                            }
                        }
                    }
                }
            });
        }

        // 2. Pie / Donut Chart (Nạp vs Rút)
        const pieCanvas = document.getElementById('pieChart');
        if (pieCanvas) {
            this.charts.pie = new Chart(pieCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Nạp tiền', 'Rút tiền'],
                    datasets: [{
                        data: [0, 0],
                        backgroundColor: [successColor, warningColor],
                        hoverBackgroundColor: ['#059669', '#d97706'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 16,
                                font: { weight: '600', size: 12 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const val = context.parsed || 0;
                                    return ` ${context.label}: ${format_currency(val)}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Bar Chart (So sánh Nạp vs Rút theo mốc thời gian)
        const barCanvas = document.getElementById('barChart');
        if (barCanvas) {
            this.charts.bar = new Chart(barCanvas, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Nạp tiền',
                            data: [],
                            backgroundColor: 'rgba(16, 185, 129, 0.85)',
                            borderRadius: 4
                        },
                        {
                            label: 'Rút tiền',
                            data: [],
                            backgroundColor: 'rgba(245, 158, 11, 0.85)',
                            borderRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: { boxWidth: 12, font: { weight: '600', size: 11 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => ` ${context.dataset.label}: ${format_currency(context.parsed.y)}`
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(226, 232, 240, 0.6)' },
                            ticks: { callback: (v) => format_currency(v), font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // 4. Status Chart (Phân bố trạng thái giao dịch)
        const statusCanvas = document.getElementById('statusChart');
        if (statusCanvas) {
            this.charts.status = new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Hoàn thành', 'Đang xử lý', 'Đã hủy'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: [successColor, warningColor, dangerColor],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 14, font: { size: 11, weight: '600' } }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => ` ${context.label}: ${context.parsed} giao dịch`
                            }
                        }
                    }
                }
            });
        }
    }

    async loadData() {
        if (this.isLoading) return;
        this.isLoading = true;

        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        let url = `/api/statistical/revenue-data?period=${this.currentPeriod}`;
        let statusUrl = `/api/statistical/transaction-status-stats?period=${this.currentPeriod}`;

        if (startDate && endDate) {
            url += `&start_date=${startDate}&end_date=${endDate}`;
            statusUrl += `&start_date=${startDate}&end_date=${endDate}`;
            $('#chartPeriodBadge').text(`${startDate} đến ${endDate}`);
        } else {
            $('#chartPeriodBadge').text(`${this.currentPeriod} ngày qua`);
        }

        try {
            const [revRes, statusRes] = await Promise.all([
                fetch(url).then(r => r.json()),
                fetch(statusUrl).then(r => r.json())
            ]);

            if (revRes.success) {
                this.updateSummaryCards(revRes.summary);
                this.updateCharts(revRes.chart_data, revRes.summary);
                this.updateRecentTransactions(revRes.recent_transactions);
            } else {
                console.error('Lỗi tải dữ liệu doanh thu:', revRes.message);
            }

            if (statusRes.success && statusRes.data) {
                this.updateStatusChart(statusRes.data);
            }
        } catch (error) {
            console.error('Lỗi khi fetch dữ liệu dashboard:', error);
        } finally {
            this.isLoading = false;
        }
    }

    updateSummaryCards(summary) {
        // 1. Doanh thu ròng
        $('#totalRevenue').text(format_currency(summary.total_revenue));
        this.renderTrendBadge('#revenueTrendBadge', summary.revenue_growth);

        // 2. Tổng nạp
        $('#totalDeposit').text(format_currency(summary.total_deposit));
        this.renderTrendBadge('#depositTrendBadge', summary.deposit_growth);
        $('#depositCountLabel').text(`${summary.deposit_count || 0} lệnh nạp`);

        // 3. Tổng rút
        $('#totalWithdraw').text(format_currency(summary.total_withdraw));
        this.renderTrendBadge('#withdrawTrendBadge', summary.withdraw_growth);
        $('#withdrawCountLabel').text(`${summary.withdraw_count || 0} lệnh rút`);

        // 4. Tổng giao dịch
        $('#totalTransactions').text(new Intl.NumberFormat('vi-VN').format(summary.total_transactions || 0));

        // 5. Giá trị nạp TB
        $('#avgDeposit').text(format_currency(summary.avg_deposit || 0));

        // 6. Khách nạp tiền
        $('#uniqueCustomers').text(new Intl.NumberFormat('vi-VN').format(summary.unique_customers || 0));
    }

    renderTrendBadge(selector, growth) {
        const el = $(selector);
        if (growth === undefined || growth === null) {
            el.attr('class', 'trend-badge neutral').text('--');
            return;
        }

        const num = parseFloat(growth);
        if (num > 0) {
            el.attr('class', 'trend-badge positive').html(`<i class="fas fa-arrow-up"></i> +${num}%`);
        } else if (num < 0) {
            el.attr('class', 'trend-badge negative').html(`<i class="fas fa-arrow-down"></i> ${num}%`);
        } else {
            el.attr('class', 'trend-badge neutral').html(`<i class="fas fa-minus"></i> 0%`);
        }
    }

    updateCharts(chartData, summary) {
        // Revenue Chart
        if (this.charts.revenue && chartData) {
            this.charts.revenue.data.labels = chartData.labels || [];
            this.charts.revenue.data.datasets[0].data = chartData.revenue_data || [];
            this.charts.revenue.update();
        }

        // Pie Chart
        if (this.charts.pie && summary) {
            const dep = summary.total_deposit || 0;
            const wit = summary.total_withdraw || 0;
            this.charts.pie.data.datasets[0].data = [dep, wit];
            this.charts.pie.update();

            const total = dep + wit;
            if (total > 0) {
                const depPct = ((dep / total) * 100).toFixed(1);
                const witPct = ((wit / total) * 100).toFixed(1);
                $('#pieSummaryText').html(`Nạp tiền chiếm <strong>${depPct}%</strong> • Rút tiền chiếm <strong>${witPct}%</strong>`);
            } else {
                $('#pieSummaryText').text('Chưa phát sinh giao dịch nạp rút');
            }
        }

        // Bar Chart
        if (this.charts.bar && chartData) {
            this.charts.bar.data.labels = chartData.labels || [];
            this.charts.bar.data.datasets[0].data = chartData.deposit_data || [];
            this.charts.bar.data.datasets[1].data = chartData.withdraw_data || [];
            this.charts.bar.update();
        }
    }

    updateStatusChart(statusData) {
        if (!this.charts.status) return;

        let completedCount = 0;
        let processingCount = 0;
        let cancelledCount = 0;

        // format của statusStats là grouped theo status: { completed: [...], processing: [...], cancelled: [...] }
        if (statusData.completed) {
            statusData.completed.forEach(item => completedCount += parseInt(item.count, 10));
        }
        if (statusData.processing) {
            statusData.processing.forEach(item => processingCount += parseInt(item.count, 10));
        }
        if (statusData.cancelled) {
            statusData.cancelled.forEach(item => cancelledCount += parseInt(item.count, 10));
        }

        this.charts.status.data.datasets[0].data = [completedCount, processingCount, cancelledCount];
        this.charts.status.update();

        const total = completedCount + processingCount + cancelledCount;
        let html = '';
        if (total > 0) {
            html = `
                <div class="status-stat-item">
                    <span><span class="status-stat-dot" style="background:#10b981;"></span>Hoàn thành</span>
                    <strong>${completedCount} (${((completedCount / total) * 100).toFixed(1)}%)</strong>
                </div>
                <div class="status-stat-item">
                    <span><span class="status-stat-dot" style="background:#f59e0b;"></span>Đang xử lý</span>
                    <strong>${processingCount} (${((processingCount / total) * 100).toFixed(1)}%)</strong>
                </div>
                <div class="status-stat-item">
                    <span><span class="status-stat-dot" style="background:#ef4444;"></span>Đã hủy</span>
                    <strong>${cancelledCount} (${((cancelledCount / total) * 100).toFixed(1)}%)</strong>
                </div>
            `;
        } else {
            html = '<div class="text-center text-muted small py-2">Không có dữ liệu trạng thái</div>';
        }
        $('#statusSummaryList').html(html);
    }

    updateRecentTransactions(transactions) {
        const tbody = $('#recentTransactionsBody');
        if (!transactions || transactions.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block text-gray-300"></i>
                        Không có giao dịch nào trong khoảng thời gian này
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        transactions.forEach(t => {
            const isDeposit = t.type === 'deposit';
            const typeBadge = isDeposit
                ? '<span class="badge" style="background: rgba(16, 185, 129, 0.12); color: #059669; font-weight: 600;"><i class="fas fa-arrow-down me-1"></i> Nạp tiền</span>'
                : '<span class="badge" style="background: rgba(245, 158, 11, 0.12); color: #d97706; font-weight: 600;"><i class="fas fa-arrow-up me-1"></i> Rút tiền</span>';

            let statusBadge = '';
            if (t.status === 'completed') {
                statusBadge = '<span class="badge badge-success">Hoàn thành</span>';
            } else if (t.status === 'processing') {
                statusBadge = '<span class="badge badge-warning">Đang xử lý</span>';
            } else {
                statusBadge = '<span class="badge badge-danger">Đã hủy</span>';
            }

            const userName = t.user ? t.user.full_name : 'Khách';
            const userPhone = t.user && t.user.phone ? t.user.phone : '--';
            const firstLetter = userName.charAt(0).toUpperCase();

            const dateStr = t.created_at ? new Date(t.created_at).toLocaleString('vi-VN') : '--';
            const amountFormatted = (isDeposit ? '+' : '-') + format_currency(t.value);
            const amountClass = isDeposit ? 'text-success fw-bold' : 'text-warning fw-bold';

            html += `
                <tr>
                    <td class="text-muted small font-monospace">#${t.id}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="user-avatar-bubble">${firstLetter}</span>
                            <span class="fw-semibold text-dark">${userName}</span>
                        </div>
                    </td>
                    <td class="text-muted small">${userPhone}</td>
                    <td>${typeBadge}</td>
                    <td class="text-end ${amountClass}">${amountFormatted}</td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-end text-muted small">${dateStr}</td>
                </tr>
            `;
        });

        tbody.html(html);
    }
}

$(document).ready(() => {
    new StatisticalDashboard();
});