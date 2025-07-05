$(document).ready(function () {
    let revenueChart;
    let transactionTypeChart;
    let monthlyChart;

    // Load data on page load
    loadData();

    // Time range change handler
    $('#timeRange').change(function () {
        loadData();
    });

    function loadData() {
        const timeRange = $('#timeRange').val();

        $('#loading').show();
        $('#statsCards, #revenueChart, #transactionTypeChart').hide();

        $.ajax({
            url: '/api/personal-revenue-stats',
            method: 'GET',
            data: { time_range: timeRange },
            headers: {
                'Authorization': 'Bearer ' + $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    updateStatsCards(response.data.overview_stats);
                    updateDailyChart(response.data.daily_revenue);
                    updateTransactionTypeChart(response.data.transaction_type_stats);

                    // Show monthly chart for longer periods
                    if (response.data.monthly_revenue.length > 0) {
                        updateMonthlyChart(response.data.monthly_revenue);
                        $('#monthlyChartContainer').show();
                    } else {
                        $('#monthlyChartContainer').hide();
                    }

                    loadRecentTransactions();
                }
                $('#loading').hide();
                $('#statsCards').show();
            },
            error: function (xhr, status, error) {
                console.error('Error loading data:', error);
                $('#loading').hide();
                alert('Có lỗi xảy ra khi tải dữ liệu!');
            }
        });
    }

    function updateStatsCards(stats) {
        $('#totalRevenue').text(format_currency(stats.total_revenue));
        $('#totalTransactions').text(stats.total_transactions);
        $('#avgTransaction').text(format_currency(stats.avg_transaction_value));
        $('#netRevenue').text(format_currency(stats.net_revenue));

        const growthRate = stats.growth_rate;
        $('#growthRate').text(growthRate > 0 ? '+' + growthRate + '%' : growthRate + '%');
    }

    function updateDailyChart(dailyData) {
        const ctx = document.getElementById('revenueChart').getContext('2d');

        if (revenueChart) {
            revenueChart.destroy();
        }

        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dailyData.map(item => item.formatted_date),
                datasets: [{
                    label: 'Doanh thu (VND)',
                    data: dailyData.map(item => item.total_revenue),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Số giao dịch',
                    data: dailyData.map(item => item.transaction_count),
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    yAxisID: 'y1',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function (value) {
                                return format_currency(value);
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                if (context.datasetIndex === 0) {
                                    return 'Doanh thu: ' + format_currency(context.parsed.y);
                                } else {
                                    return 'Giao dịch: ' + context.parsed.y;
                                }
                            }
                        }
                    }
                }
            }
        });
    }

    function updateTransactionTypeChart(typeData) {
        const ctx = document.getElementById('transactionTypeChart').getContext('2d');

        if (transactionTypeChart) {
            transactionTypeChart.destroy();
        }

        transactionTypeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: typeData.map(item => item.type_name),
                datasets: [{
                    data: typeData.map(item => item.total_value),
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(54, 162, 235, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' + format_currency(context.parsed);
                            }
                        }
                    }
                }
            }
        });
    }

    function updateMonthlyChart(monthlyData) {
        const ctx = document.getElementById('monthlyChart').getContext('2d');

        if (monthlyChart) {
            monthlyChart.destroy();
        }

        monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(item => item.month_name),
                datasets: [{
                    label: 'Doanh thu (VND)',
                    data: monthlyData.map(item => item.total_revenue),
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return format_currency(value);
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return 'Doanh thu: ' + format_currency(context.parsed.y);
                            }
                        }
                    }
                }
            }
        });
    }

    function loadRecentTransactions() {
        $.ajax({
            url: '/api/personal-transactions',
            method: 'GET',
            data: { per_page: 10 },
            headers: {
                'Authorization': 'Bearer ' + $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    updateTransactionsTable(response.data.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('Error loading transactions:', error);
            }
        });
    }

    function updateTransactionsTable(transactions) {
        const tbody = $('#transactionsTableBody');
        tbody.empty();

        transactions.forEach(function (transaction) {
            const statusClass = transaction.status === 'completed' ? 'success' :
                transaction.status === 'processing' ? 'warning' : 'danger';
            const statusText = transaction.status === 'completed' ? 'Hoàn thành' :
                transaction.status === 'processing' ? 'Đang xử lý' : 'Đã hủy';
            const typeText = transaction.type === 'deposit' ? 'Nạp tiền' : 'Rút tiền';
            const typeClass = transaction.type === 'deposit' ? 'success' : 'danger';

            tbody.append(`
                <tr>
                    <td>${new Date(transaction.created_at).toLocaleDateString('vi-VN')}</td>
                    <td>${transaction.user.full_name} (${transaction.user.username})</td>
                    <td><span class="badge badge-${typeClass}">${typeText}</span></td>
                    <td>${format_currency(transaction.value)}</td>
                    <td><span class="badge badge-${statusClass}">${statusText}</span></td>
                </tr>
            `);
        });
    }

});