let revenueChart, topCustomersChart, revenueDistributionChart;

$(document).ready(function () {
    // Khởi tạo DataTable
    $('#customerRevenueTable').DataTable({
        "language": {
            "url": "/js/datatables/vi.json"
        },
        "pageLength": 25,
        "order": [[4, "desc"]]
    });

    // Load dữ liệu ban đầu
    loadData();

    // Xử lý sự kiện lọc
    $('#btnFilter').click(function () {
        loadData();
    });

    // Xử lý thay đổi loại thống kê
    $('#filterType').change(function () {
        loadData();
    });
});

function loadData() {
    showLoading();

    const params = {
        type: $('#filterType').val(),
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val()
    };

    // Load tổng quan
    $.ajax({
        url: '/api/revenue-overview',
        method: 'GET',
        data: params,
        success: function (response) {
            updateOverview(response.data);
        },
        error: function (xhr) {
            console.error('Error loading overview:', xhr);
            showError('Không thể tải dữ liệu tổng quan');
        }
    });

    // Load biểu đồ doanh thu theo thời gian
    $.ajax({
        url: '/api/revenue-chart',
        method: 'GET',
        data: params,
        success: function (response) {
            updateRevenueChart(response.data);
        },
        error: function (xhr) {
            console.error('Error loading revenue chart:', xhr);
            showError('Không thể tải biểu đồ doanh thu');
        }
    });

    // Load top khách hàng
    $.ajax({
        url: '/api/top-customers',
        method: 'GET',
        data: params,
        success: function (response) {
            updateTopCustomersChart(response.data);
        },
        error: function (xhr) {
            console.error('Error loading top customers:', xhr);
            showError('Không thể tải top khách hàng');
        }
    });

    // Load phân bố doanh thu
    $.ajax({
        url: '/api/revenue-distribution',
        method: 'GET',
        data: params,
        success: function (response) {
            updateRevenueDistributionChart(response.data);
        },
        error: function (xhr) {
            console.error('Error loading revenue distribution:', xhr);
            showError('Không thể tải phân bố doanh thu');
        }
    });

    // Load bảng chi tiết
    $.ajax({
        url: '/api/customer-revenue-detail',
        method: 'GET',
        data: params,
        success: function (response) {
            console.log(response.data);            
            updateCustomerTable(response.data);
        },
        error: function (xhr) {
            console.error('Error loading customer details:', xhr);
            showError('Không thể tải chi tiết khách hàng');
        },
        complete: function () {
            hideLoading();
        }
    });
}

function updateOverview(data) {
    $('#totalRevenue').text(format_currency(data.total_revenue));
    $('#totalTransactions').text(data.total_transactions.toLocaleString());
    $('#totalCustomers').text(data.total_customers.toLocaleString());
    $('#avgTransaction').text(format_currency(data.avg_transaction));
}

function updateRevenueChart(data) {
    const ctx = document.getElementById('revenueChart').getContext('2d');

    if (revenueChart) {
        revenueChart.destroy();
    }

    revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Doanh thu',
                data: data.values,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
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

function updateTopCustomersChart(data) {
    const ctx = document.getElementById('topCustomersChart').getContext('2d');

    if (topCustomersChart) {
        topCustomersChart.destroy();
    }

    topCustomersChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Doanh thu',
                data: data.values,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                ]
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

function updateRevenueDistributionChart(data) {
    const ctx = document.getElementById('revenueDistributionChart').getContext('2d');

    if (revenueDistributionChart) {
        revenueDistributionChart.destroy();
    }

    revenueDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
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

function updateCustomerTable(data) {
    let table = $('#customerRevenueTable').DataTable();
    table.clear();
    table.rows.add(data.map((item, index) => [
        index + 1,
        item.full_name,
        item.phone,
        item.transaction_count,
        format_currency(item.total_revenue),
        formatDate(item.last_transaction)
    ]));
    table.draw();
}




function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

function showLoading() {
    $('#loadingOverlay').show();
}

function hideLoading() {
    $('#loadingOverlay').hide();
}

function showError(message) {
    alert(message);
}