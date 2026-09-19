/**
 * Statistical Dashboard - Doanh thu từ khách hàng
 * Customer spend analytics, VIP ranking & flow distribution
 */

let revenueChart = null;
let topCustomersChart = null;
let revenueDistributionChart = null;
let customerTable = null;

$(document).ready(function () {
    // Khởi tạo DataTable ban đầu
    initDataTable();

    // Setup event handlers
    setupEventListeners();

    // Load dữ liệu
    loadData();
});

function initDataTable() {
    if ($.fn.DataTable.isDataTable('#customerRevenueTable')) {
        $('#customerRevenueTable').DataTable().destroy();
    }

    customerTable = $('#customerRevenueTable').DataTable({
        pageLength: 10,
        order: [[4, "desc"]], // Sắp xếp theo tổng nạp giảm dần
        language: {
            emptyTable: "Không có dữ liệu khách hàng trong khoảng thời gian này",
            info: "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ khách hàng",
            infoEmpty: "Hiển thị 0 đến 0 trong 0 khách hàng",
            infoFiltered: "(lọc từ _MAX_ khách hàng)",
            lengthMenu: "Hiển thị _MENU_ dòng",
            search: "Tìm kiếm khách:",
            zeroRecords: "Không tìm thấy khách hàng phù hợp",
            paginate: {
                first: "Đầu",
                last: "Cuối",
                next: "Sau",
                previous: "Trước"
            }
        },
        columnDefs: [
            { orderable: false, targets: [0] }
        ]
    });
}

function setupEventListeners() {
    // Quick Presets
    $('.preset-btn').on('click', function () {
        $('.preset-btn').removeClass('active');
        $(this).addClass('active');

        const preset = $(this).data('preset');
        const today = new Date();
        let from = new Date();
        let to = new Date();
        let label = '';

        if (preset === 'today') {
            from = today;
            to = today;
            label = 'Hôm nay';
            $('#filterType').val('daily');
        } else if (preset === '7_days') {
            from.setDate(today.getDate() - 6);
            to = today;
            label = '7 ngày qua';
            $('#filterType').val('daily');
        } else if (preset === 'this_month') {
            from = new Date(today.getFullYear(), today.getMonth(), 1);
            to = today;
            label = 'Tháng này';
            $('#filterType').val('daily');
        } else if (preset === 'last_month') {
            from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            to = new Date(today.getFullYear(), today.getMonth(), 0);
            label = 'Tháng trước';
            $('#filterType').val('daily');
        } else if (preset === 'year') {
            from = new Date(today.getFullYear(), 0, 1);
            to = today;
            label = 'Năm nay';
            $('#filterType').val('monthly');
        }

        $('#startDate').val(formatDateInput(from));
        $('#endDate').val(formatDateInput(to));
        $('#filterStatusLabel').text(`Thống kê theo: ${label}`);

        loadData();
    });

    // Button Filter
    $('#btnFilter').on('click', function () {
        $('.preset-btn').removeClass('active');
        const start = $('#startDate').val();
        const end = $('#endDate').val();
        if (start && end && new Date(start) > new Date(end)) {
            alert('Từ ngày không được lớn hơn đến ngày!');
            return;
        }
        $('#filterStatusLabel').text(`Tùy chỉnh: ${start} đến ${end}`);
        loadData();
    });

    // Button Refresh
    $('#btnRefresh').on('click', function () {
        loadData();
    });

    // Change filter type (daily, monthly, yearly)
    $('#filterType').on('change', function () {
        loadData();
    });
}

function formatDateInput(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function loadData() {
    const params = {
        type: $('#filterType').val(),
        start_date: $('#startDate').val(),
        end_date: $('#endDate').val()
    };

    $('#chartTimeBadge').text(`${params.start_date} đến ${params.end_date}`);

    // 1. Tổng quan KPI
    $.ajax({
        url: '/api/revenue-overview',
        method: 'GET',
        data: params,
        success: function (response) {
            if (response.success && response.data) {
                updateOverview(response.data);
            }
        },
        error: function (xhr) {
            console.error('Error loading overview:', xhr);
        }
    });

    // 2. Biểu đồ doanh thu theo thời gian
    $.ajax({
        url: '/api/revenue-chart',
        method: 'GET',
        data: params,
        success: function (response) {
            if (response.success && response.data) {
                updateRevenueChart(response.data);
            }
        },
        error: function (xhr) {
            console.error('Error loading revenue chart:', xhr);
        }
    });

    // 3. Top khách hàng
    $.ajax({
        url: '/api/top-customers',
        method: 'GET',
        data: params,
        success: function (response) {
            if (response.success && response.data) {
                updateTopCustomersChart(response.data);
            }
        },
        error: function (xhr) {
            console.error('Error loading top customers:', xhr);
        }
    });

    // 4. Phân bổ doanh thu
    $.ajax({
        url: '/api/revenue-distribution',
        method: 'GET',
        data: params,
        success: function (response) {
            if (response.success && response.data) {
                updateRevenueDistributionChart(response.data);
            }
        },
        error: function (xhr) {
            console.error('Error loading revenue distribution:', xhr);
        }
    });

    // 5. Bảng chi tiết
    $.ajax({
        url: '/api/customer-revenue-detail',
        method: 'GET',
        data: params,
        success: function (response) {
            if (response.success && response.data) {
                updateCustomerTable(response.data);
            }
        },
        error: function (xhr) {
            console.error('Error loading customer details:', xhr);
        }
    });
}

function updateOverview(data) {
    $('#totalRevenue').text(format_currency(data.total_revenue || 0));
    renderTrendBadge('#revTrendBadge', data.revenue_growth);

    $('#totalTransactions').text(new Intl.NumberFormat('vi-VN').format(data.total_transactions || 0));

    $('#totalCustomers').text(new Intl.NumberFormat('vi-VN').format(data.total_customers || 0));
    renderTrendBadge('#custTrendBadge', data.customers_growth);

    $('#avgTransaction').text(format_currency(data.avg_transaction || 0));

    // Khách hàng nạp cao nhất
    if (data.top_customer_amount && data.top_customer_amount > 0) {
        $('#topCustomerAmount').text(format_currency(data.top_customer_amount));
        $('#topCustomerName').text(data.top_customer_name || 'Khách VIP');
    } else {
        $('#topCustomerAmount').text('0 USD');
        $('#topCustomerName').text('Chưa có');
    }
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

function updateRevenueChart(data) {
    if (revenueChart) {
        revenueChart.destroy();
    }

    const canvas = document.getElementById('revenueChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(14, 165, 233, 0.28)');
    gradient.addColorStop(1, 'rgba(14, 165, 233, 0.0)');

    revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Doanh thu nạp',
                data: data.values || [],
                borderColor: '#0ea5e9',
                borderWidth: 2.5,
                backgroundColor: gradient,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#0ea5e9',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
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

function updateTopCustomersChart(data) {
    if (topCustomersChart) {
        topCustomersChart.destroy();
    }

    const canvas = document.getElementById('topCustomersChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    topCustomersChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Tổng tiền nạp',
                data: data.values || [],
                backgroundColor: 'rgba(79, 70, 229, 0.85)',
                hoverBackgroundColor: '#4338ca',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (c) => ` Nạp: ${format_currency(c.parsed.x)}`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: 'rgba(226, 232, 240, 0.6)' },
                    ticks: { callback: (v) => format_currency(v), font: { size: 11 } }
                },
                y: {
                    grid: { display: false },
                    ticks: { font: { size: 11, weight: '600' } }
                }
            }
        }
    });
}

function updateRevenueDistributionChart(data) {
    if (revenueDistributionChart) {
        revenueDistributionChart.destroy();
    }

    const canvas = document.getElementById('revenueDistributionChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const palette = ['#0ea5e9', '#6366f1', '#10b981', '#f59e0b', '#ec4899', '#94a3b8'];

    revenueDistributionChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels || [],
            datasets: [{
                data: data.values || [],
                backgroundColor: palette,
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
                    labels: { boxWidth: 10, padding: 12, font: { size: 11, weight: '600' } }
                },
                tooltip: {
                    callbacks: {
                        label: (c) => ` ${c.label}: ${format_currency(c.parsed)}`
                    }
                }
            }
        }
    });
}

function updateCustomerTable(data) {
    if (!customerTable) {
        initDataTable();
    }

    customerTable.clear();
    $('#tableCustomerCountBadge').text(`${data ? data.length : 0} khách hàng`);

    if (data && data.length > 0) {
        data.forEach(function (c, index) {
            const firstLetter = (c.full_name || 'K').charAt(0).toUpperCase();
            const totalRev = parseFloat(c.total_revenue || 0);
            const isVip = totalRev >= 1000;
            const vipBadge = isVip ? '<span class="badge badge-vip ms-1"><i class="fas fa-crown me-1"></i>VIP</span>' : '';

            const customerCol = `
                <div class="d-flex align-items-center gap-2">
                    <span class="user-avatar-bubble">${firstLetter}</span>
                    <div>
                        <span class="fw-bold text-dark d-block">${c.full_name || 'Không tên'} ${vipBadge}</span>
                    </div>
                </div>
            `;

            const phone = c.phone ? c.phone : '<span class="text-muted">--</span>';
            const count = `<span class="badge bg-light text-dark px-2 py-1 rounded-pill">${c.transaction_count || 0}</span>`;
            const revenue = `<span class="fw-bold text-success">+${format_currency(totalRev)}</span>`;
            const lastDate = c.last_transaction ? new Date(c.last_transaction).toLocaleString('vi-VN') : '<span class="text-muted">--</span>';

            customerTable.row.add([
                index + 1,
                customerCol,
                phone,
                count,
                revenue,
                lastDate
            ]);
        });
    }

    customerTable.draw();
}