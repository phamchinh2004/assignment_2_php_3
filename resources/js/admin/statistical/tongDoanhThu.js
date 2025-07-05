/**
 * Statistical Dashboard JavaScript
 * Các tính năng nâng cao cho trang thống kê
 */

class StatisticalDashboard {
    constructor() {
        this.charts = {};
        this.currentPeriod = 30;
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeCharts();
        this.loadData();
        this.setupRealTimeUpdates();
    }

    setupEventListeners() {
        // Period selector
        $('#periodSelect').on('change', (e) => {
            this.currentPeriod = parseInt(e.target.value);
            this.loadData();
        });

        // Refresh button
        $('#refreshBtn').on('click', () => {
            this.loadData();
        });

        // Export button
        $('#exportBtn').on('click', () => {
            this.exportData();
        });

        // Auto refresh toggle
        $('#autoRefreshToggle').on('change', (e) => {
            if (e.target.checked) {
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }
        });

        // Chart type toggle
        $('.chart-type-btn').on('click', (e) => {
            const chartType = $(e.target).data('chart-type');
            this.changeChartType(chartType);
        });
    }

    initializeCharts() {
        // Revenue Line Chart
        this.charts.revenue = new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Doanh thu',
                    data: [],
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Biểu đồ doanh thu theo thời gian'
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return `Doanh thu: ${format_currency(context.parsed.y)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => format_currency(value)
                        }
                    }
                }
            }
        });

        // Pie Chart
        this.charts.pie = new Chart(document.getElementById('pieChart'), {
            type: 'doughnut',
            data: {
                labels: ['Nạp tiền', 'Rút tiền'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: ['#1cc88a', '#f6c23e'],
                    hoverBackgroundColor: ['#17a673', '#f4b619'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = format_currency(context.parsed);
                                return `${label}: ${value}`;
                            }
                        }
                    }
                }
            }
        });

        // Bar Chart
        this.charts.bar = new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Nạp tiền',
                    data: [],
                    backgroundColor: '#1cc88a',
                    borderColor: '#1cc88a',
                    borderWidth: 1
                }, {
                    label: 'Rút tiền',
                    data: [],
                    backgroundColor: '#f6c23e',
                    borderColor: '#f6c23e',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'So sánh nạp/rút tiền'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return `${context.dataset.label}: ${format_currency(context.parsed.y)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => format_currency(value)
                        }
                    }
                }
            }
        });
    }

    async loadData() {
        if (this.isLoading) return;

        this.isLoading = true;
        this.showLoading();

        try {
            // const response = await fetch(`/admin/api/revenue-data?period=${this.currentPeriod}`);
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            let url = `/admin/api/revenue-data?period=${this.currentPeriod}`;

            if (startDate && endDate) {
                url += `&start_date=${startDate}&end_date=${endDate}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.updateSummaryCards(data.summary);
                this.updateCharts(data.chart_data);
                this.updateRecentTransactions(data.recent_transactions);
            } else {
                this.showError(data.message || 'Có lỗi xảy ra khi tải dữ liệu');
            }
        } catch (error) {
            console.error('Error loading data:', error);
            this.showError('Không thể tải dữ liệu. Vui lòng thử lại.');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    updateSummaryCards(summary) {
        // Animate counter
        this.animateCounter('#totalRevenue', summary.total_revenue);
        this.animateCounter('#totalDeposit', summary.total_deposit);
        this.animateCounter('#totalWithdraw', summary.total_withdraw);
        this.animateCounter('#totalTransactions', summary.total_transactions, false);
    }

    animateCounter(selector, value, isCurrency = true) {
        const element = $(selector);
        const currentValue = parseInt(element.text().replace(/[^\d]/g, '')) || 0;

        $({ count: currentValue }).animate({ count: value }, {
            duration: 1000,
            easing: 'swing',
            step: function (now) {
                if (isCurrency) {
                    element.text(format_currency(Math.floor(now)));
                } else {
                    element.text(Math.floor(now).toLocaleString('vi-VN'));
                }
            }.bind(this),
            complete: function () {
                if (isCurrency) {
                    element.text(format_currency(value));
                } else {
                    element.text(value.toLocaleString('vi-VN'));
                }
            }.bind(this)
        });
    }

    updateCharts(chartData) {
        // Update revenue chart
        this.charts.revenue.data.labels = chartData.labels;
        this.charts.revenue.data.datasets[0].data = chartData.revenue_data;
        this.charts.revenue.update('active');

        // Update pie chart
        this.charts.pie.data.datasets[0].data = [
            chartData.deposit_data.reduce((a, b) => a + b, 0),
            chartData.withdraw_data.reduce((a, b) => a + b, 0)
        ];
        this.charts.pie.update('active');

        // Update bar chart
        this.charts.bar.data.labels = chartData.labels;
        this.charts.bar.data.datasets[0].data = chartData.deposit_data;
        this.charts.bar.data.datasets[1].data = chartData.withdraw_data;
        this.charts.bar.update('active');
    }

    updateRecentTransactions(transactions) {
        const tbody = $('#recentTransactionsTable tbody');
        tbody.empty();

        if (transactions.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>');
            return;
        }

        transactions.forEach(transaction => {
            const statusClass = this.getStatusClass(transaction.status);
            const statusText = this.getStatusText(transaction.status);
            const typeClass = transaction.type === 'deposit' ? 'success' : 'warning';
            const typeText = transaction.type === 'deposit' ? 'Nạp tiền' : 'Rút tiền';

            const row = `
                <tr data-id="${transaction.id}">
                    <td>${transaction.id}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm rounded-circle bg-gradient-primary me-2">
                                <span class="text-white font-weight-bold">${transaction.user.full_name.charAt(0)}</span>
                            </div>
                            <div>
                                <h6 class="mb-0 text-sm">${transaction.user.full_name}</h6>
                                <p class="text-xs text-muted mb-0">${transaction.user.phone}</p>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-${typeClass}">${typeText}</span></td>
                    <td class="font-weight-bold">${format_currency(transaction.value)}</td>
                    <td><span class="badge badge-${statusClass}">${statusText}</span></td>
                    <td>${this.formatDate(transaction.created_at)}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    getStatusClass(status) {
        const classes = {
            'completed': 'success',
            'processing': 'warning',
            'cancelled': 'danger'
        };
        return classes[status] || 'secondary';
    }

    getStatusText(status) {
        const texts = {
            'completed': 'Hoàn thành',
            'processing': 'Đang xử lý',
            'cancelled': 'Đã hủy'
        };
        return texts[status] || 'Không xác định';
    }

    // format_currency(amount) {
    //     return new Intl.NumberFormat('vi-VN', {
    //         style: 'currency',
    //         currency: 'VND'
    //     }).format(amount);
    // }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    showLoading() {
        $('.loading-overlay').show();
        $('#refreshBtn').prop('disabled', true);
        $('#periodSelect').prop('disabled', true);
    }

    hideLoading() {
        $('.loading-overlay').hide();
        $('#refreshBtn').prop('disabled', false);
        $('#periodSelect').prop('disabled', false);
    }

    showError(message) {
        const alert = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Lỗi!</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        $('.container-fluid').prepend(alert);
    }

    async exportData() {
        try {
            const response = await fetch(`/admin/api/export-revenue-data?period=${this.currentPeriod}`);
            const blob = await response.blob();

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `thong_ke_doanh_thu_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } catch (error) {
            console.error('Export error:', error);
            this.showError('Có lỗi xảy ra khi xuất dữ liệu');
        }
    }

    setupRealTimeUpdates() {
        // Setup WebSocket or polling for real-time updates
        this.realTimeInterval = setInterval(() => {
            if (!this.isLoading) {
                this.loadData();
            }
        }, 30000); // Update every 30 seconds
    }

    startAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }

        this.autoRefreshInterval = setInterval(() => {
            this.loadData();
        }, 60000); // Auto refresh every minute
    }

    stopAutoRefresh() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
        }
    }

    changeChartType(type) {
        // Change chart type dynamically
        Object.values(this.charts).forEach(chart => {
            if (chart.config.type !== type) {
                chart.config.type = type;
                chart.update();
            }
        });
    }

    destroy() {
        // Cleanup when component is destroyed
        if (this.realTimeInterval) {
            clearInterval(this.realTimeInterval);
        }
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }

        Object.values(this.charts).forEach(chart => {
            chart.destroy();
        });
    }
}

// Initialize when document is ready
$(document).ready(function () {
    window.statisticalDashboard = new StatisticalDashboard();
});

// Cleanup on page unload
$(window).on('beforeunload', function () {
    if (window.statisticalDashboard) {
        window.statisticalDashboard.destroy();
    }
});