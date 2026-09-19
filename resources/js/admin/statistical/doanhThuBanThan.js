/**
 * Statistical Dashboard - Doanh thu bản thân
 * Personal revenue, managed customer deposit/withdraw & performance
 */

$(document).ready(function () {
    let revenueChart = null;
    let transactionTypeChart = null;
    let monthlyChart = null;
    let currentTimeRange = '7_days';

    // Khởi tạo
    setupEventListeners();
    loadData();

    function setupEventListeners() {
        // Quick Presets
        $('.preset-btn').on('click', function () {
            $('.preset-btn').removeClass('active');
            $(this).addClass('active');

            currentTimeRange = $(this).data('range');
            const labelMap = {
                '7_days': '7 ngày qua',
                '30_days': '30 ngày qua',
                '3_months': '3 tháng qua',
                '6_months': '6 tháng qua',
                '1_year': '1 năm qua'
            };
            $('#currentRangeText').text(`Khoảng thời gian: ${labelMap[currentTimeRange] || currentTimeRange}`);
            loadData();
        });

        // Refresh
        $('#btnRefresh').on('click', function () {
            loadData();
        });
    }

    function loadData() {
        $.ajax({
            url: '/admin/personal-revenue-stats',
            method: 'GET',
            data: { time_range: currentTimeRange },
            headers: {
                'Authorization': 'Bearer ' + $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success && response.data) {
                    updateStatsCards(response.data.overview_stats);
                    updateDailyChart(response.data.daily_revenue);
                    updateTransactionTypeChart(response.data.transaction_type_stats);

                    // Show monthly chart for longer periods
                    if (response.data.monthly_revenue && response.data.monthly_revenue.length > 0) {
                        updateMonthlyChart(response.data.monthly_revenue);
                        $('#monthlyChartContainer').slideDown(200);
                    } else {
                        $('#monthlyChartContainer').slideUp(200);
                    }

                    loadRecentTransactions();
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading personal revenue data:', error);
            }
        });
    }

    function updateStatsCards(stats) {
        if (!stats) return;

        // 1. Doanh thu nạp
        $('#totalRevenue').text(format_currency(stats.total_revenue || 0));
        renderTrendBadge('#growthRateBadge', stats.growth_rate);

        // 2. Khách rút tiền
        $('#totalWithdraw').text(format_currency(stats.total_withdraw || 0));
        $('#withdrawCountLabel').text(`${stats.withdraw_count || 0} lệnh rút`);

        // 3. Lợi nhuận ròng
        const net = (stats.total_revenue || 0) - (stats.total_withdraw || 0);
        $('#netRevenue').text(format_currency(net));

        // 4. TB mỗi giao dịch nạp
        $('#avgTransaction').text(format_currency(stats.avg_transaction_value || 0));
        $('#totalTransactionsLabel').text(`${stats.deposit_count || 0} lượt nạp`);
    }

    function renderTrendBadge(selector, growth) {
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

    function updateDailyChart(dailyData) {
        if (!dailyData) return;

        if (revenueChart) {
            revenueChart.destroy();
        }

        const canvas = document.getElementById('revenueChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(79, 70, 229, 0.28)');
        gradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dailyData.map(item => item.formatted_date),
                datasets: [
                    {
                        label: 'Doanh thu nạp (USD)',
                        data: dailyData.map(item => item.total_revenue),
                        borderColor: '#4f46e5',
                        borderWidth: 2.5,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.35,
                        yAxisID: 'y',
                        pointBackgroundColor: '#4f46e5',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 3,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Số lượt giao dịch',
                        data: dailyData.map(item => item.transaction_count),
                        borderColor: '#f59e0b',
                        borderWidth: 2,
                        backgroundColor: 'transparent',
                        borderDash: [4, 4],
                        tension: 0.2,
                        yAxisID: 'y1',
                        pointBackgroundColor: '#f59e0b',
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 12, padding: 12, font: { size: 11, weight: '600' } }
                    },
                    tooltip: {
                        callbacks: {
                            label: (c) => {
                                if (c.datasetIndex === 0) {
                                    return ` Doanh thu: ${format_currency(c.parsed.y)}`;
                                }
                                return ` Lượt giao dịch: ${c.parsed.y}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        ticks: { callback: (v) => format_currency(v), font: { size: 11 } }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: {
                            stepSize: 1,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }

    function updateTransactionTypeChart(typeStats) {
        if (!typeStats) return;

        if (transactionTypeChart) {
            transactionTypeChart.destroy();
        }

        const canvas = document.getElementById('transactionTypeChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const labels = typeStats.map(item => item.type_name);
        const data = typeStats.map(item => item.total_value);

        transactionTypeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, padding: 14, font: { size: 11, weight: '600' } }
                    },
                    tooltip: {
                        callbacks: {
                            label: (c) => ` ${c.label}: ${format_currency(c.parsed)}`
                        }
                    }
                }
            }
        });

        // Summary text
        const depositStat = typeStats.find(i => i.type === 'deposit');
        const withdrawStat = typeStats.find(i => i.type === 'withdraw');
        const depVal = depositStat ? depositStat.total_value : 0;
        const witVal = withdrawStat ? withdrawStat.total_value : 0;
        const total = depVal + witVal;

        if (total > 0) {
            const depPct = ((depVal / total) * 100).toFixed(1);
            const witPct = ((witVal / total) * 100).toFixed(1);
            $('#personalTypeSummary').html(`Nạp tiền: <strong>${depPct}%</strong> • Rút tiền: <strong>${witPct}%</strong>`);
        } else {
            $('#personalTypeSummary').text('Chưa phát sinh nạp/rút trong kỳ');
        }
    }

    function updateMonthlyChart(monthlyData) {
        if (!monthlyData) return;

        if (monthlyChart) {
            monthlyChart.destroy();
        }

        const canvas = document.getElementById('monthlyChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(item => item.month_name),
                datasets: [{
                    label: 'Doanh thu tháng (USD)',
                    data: monthlyData.map(item => item.total_revenue),
                    backgroundColor: 'rgba(79, 70, 229, 0.85)',
                    hoverBackgroundColor: '#4338ca',
                    borderRadius: 6,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (c) => ` Doanh thu: ${format_currency(c.parsed.y)}`
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

    function loadRecentTransactions() {
        $.ajax({
            url: '/admin/personal-transactions',
            method: 'GET',
            data: { per_page: 10 },
            headers: {
                'Authorization': 'Bearer ' + $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success && response.data && response.data.data) {
                    renderTransactionsTable(response.data.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading recent personal transactions:', error);
            }
        });
    }

    function renderTransactionsTable(transactions) {
        const tbody = $('#transactionsTableBody');
        if (!transactions || transactions.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block text-gray-300"></i>
                        Không có giao dịch nào từ khách hàng của bạn
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        transactions.forEach(function (t) {
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
            const username = t.user ? t.user.username : '--';
            const firstLetter = userName.charAt(0).toUpperCase();
            const dateStr = t.created_at ? new Date(t.created_at).toLocaleString('vi-VN') : '--';
            const amountFormatted = (isDeposit ? '+' : '-') + format_currency(t.value);
            const amountClass = isDeposit ? 'text-success fw-bold' : 'text-warning fw-bold';

            html += `
                <tr>
                    <td class="text-muted small font-monospace">#${t.id}</td>
                    <td class="text-muted small">${dateStr}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="user-avatar-bubble">${firstLetter}</span>
                            <span class="fw-semibold text-dark">${userName}</span>
                        </div>
                    </td>
                    <td class="text-muted small">@${username}</td>
                    <td>${typeBadge}</td>
                    <td class="text-end ${amountClass}">${amountFormatted}</td>
                    <td class="text-center">${statusBadge}</td>
                </tr>
            `;
        });

        tbody.html(html);
    }
});