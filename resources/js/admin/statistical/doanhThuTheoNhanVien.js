/**
 * Statistical Dashboard - Doanh thu theo nhân viên
 * Modern ranking & performance analytics
 */

$(document).ready(function () {
    let revenueChart = null;
    let topStaffChart = null;

    // Load danh sách nhân viên cho dropdown
    loadStaffList();

    // Setup events
    setupEventListeners();

    // Load dữ liệu ban đầu
    loadRevenueData();

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
            } else if (preset === '7_days') {
                from.setDate(today.getDate() - 6);
                to = today;
                label = '7 ngày qua';
            } else if (preset === 'this_month') {
                from = new Date(today.getFullYear(), today.getMonth(), 1);
                to = today;
                label = 'Tháng này';
            } else if (preset === 'last_month') {
                from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                to = new Date(today.getFullYear(), today.getMonth(), 0);
                label = 'Tháng trước';
            } else if (preset === 'year') {
                from = new Date(today.getFullYear(), 0, 1);
                to = today;
                label = 'Năm nay';
            }

            $('#dateFrom').val(formatDateInput(from));
            $('#dateTo').val(formatDateInput(to));
            $('#currentFilterText').text(`Khoảng thời gian: ${label}`);

            loadRevenueData();
        });

        // Filter button
        $('#filterBtn').on('click', function () {
            $('.preset-btn').removeClass('active');
            const from = $('#dateFrom').val();
            const to = $('#dateTo').val();
            if (from && to && new Date(from) > new Date(to)) {
                alert('Từ ngày không được lớn hơn đến ngày!');
                return;
            }
            $('#currentFilterText').text(`Tùy chỉnh: ${from} đến ${to}`);
            loadRevenueData();
        });

        // Refresh button
        $('#refreshBtn').on('click', function () {
            loadRevenueData();
        });

        // Export CSV button
        $('#exportBtn').on('click', function () {
            const dateFrom = $('#dateFrom').val();
            const dateTo = $('#dateTo').val();
            const staffId = $('#staffSelect').val();

            let url = `/admin/export?date_from=${dateFrom}&date_to=${dateTo}`;
            if (staffId) {
                url += `&staff_id=${staffId}`;
            }
            window.location.href = url;
        });
    }

    function formatDateInput(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    // Load danh sách nhân viên
    function loadStaffList() {
        $.ajax({
            url: route_api_staff_list,
            method: 'GET',
            success: function (response) {
                if (response.success && response.data) {
                    let options = '<option value="">Tất cả nhân viên</option>';
                    response.data.forEach(function (staff) {
                        options += `<option value="${staff.id}">${staff.full_name} (${staff.email})</option>`;
                    });
                    $('#staffSelect').html(options);
                }
            },
            error: function (xhr) {
                console.error('Lỗi khi load danh sách nhân viên:', xhr);
            }
        });
    }

    // Load dữ liệu doanh thu
    function loadRevenueData() {
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();
        const staffId = $('#staffSelect').val();

        $('#revenueTableBody').html(`
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fas fa-spinner fa-spin me-2"></i> Đang tải dữ liệu...
                </td>
            </tr>
        `);

        $.ajax({
            url: route_api_revenue_by_staff,
            method: 'GET',
            data: {
                date_from: dateFrom,
                date_to: dateTo,
                staff_id: staffId
            },
            success: function (response) {
                if (response.success) {
                    updateSummary(response.summary);
                    updateCharts(response.chart_data);
                    updateTable(response.table_data);
                }
            },
            error: function (xhr) {
                console.error('Lỗi khi load dữ liệu doanh thu:', xhr);
                $('#revenueTableBody').html(`
                    <tr>
                        <td colspan="8" class="text-center py-4 text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i> Không thể tải dữ liệu. Vui lòng thử lại.
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Cập nhật thẻ tóm tắt
    function updateSummary(summary) {
        $('#totalStaff').text(summary.total_staff || 0);
        $('#totalRevenue').text(format_currency(summary.total_revenue || 0));
        $('#totalTransactions').text(new Intl.NumberFormat('vi-VN').format(summary.total_transactions || 0));
        $('#avgRevenue').text(format_currency(summary.avg_revenue || 0));
    }

    // Cập nhật biểu đồ
    function updateCharts(chartData) {
        // 1. Biểu đồ doanh thu theo nhân viên
        if (revenueChart) {
            revenueChart.destroy();
        }

        const canvas1 = document.getElementById('revenueChart');
        if (canvas1) {
            const ctx1 = canvas1.getContext('2d');
            revenueChart = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: chartData.labels || [],
                    datasets: [{
                        label: 'Doanh thu (USD)',
                        data: chartData.revenue_data || [],
                        backgroundColor: 'rgba(13, 148, 136, 0.85)',
                        hoverBackgroundColor: '#0f766e',
                        borderRadius: 6,
                        maxBarThickness: 45
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
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(226, 232, 240, 0.6)' },
                            ticks: {
                                callback: (v) => format_currency(v),
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }

        // 2. Biểu đồ Top 5 nhân viên
        if (topStaffChart) {
            topStaffChart.destroy();
        }

        const canvas2 = document.getElementById('topStaffChart');
        if (canvas2) {
            const ctx2 = canvas2.getContext('2d');
            const palette = ['#0d9488', '#4f46e5', '#f59e0b', '#0ea5e9', '#8b5cf6'];

            topStaffChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: chartData.top_labels || [],
                    datasets: [{
                        data: chartData.top_data || [],
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
    }

    // Cập nhật bảng dữ liệu
    function updateTable(tableData) {
        const tbody = $('#revenueTableBody');
        $('#tableCountBadge').text(`${tableData ? tableData.length : 0} nhân viên`);

        if (!tableData || tableData.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="fas fa-user-slash fa-2x mb-2 d-block text-gray-300"></i>
                        Không có dữ liệu nhân viên nào trong khoảng thời gian này
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        tableData.forEach(function (staff, index) {
            const rank = index + 1;
            let rankClass = 'rank-other';
            let rankContent = rank;

            if (rank === 1) {
                rankClass = 'rank-1';
                rankContent = '<i class="fas fa-crown"></i>';
            } else if (rank === 2) {
                rankClass = 'rank-2';
                rankContent = '2';
            } else if (rank === 3) {
                rankClass = 'rank-3';
                rankContent = '3';
            }

            const firstLetter = staff.staff_name.charAt(0).toUpperCase();
            const share = staff.percent_share || 0;

            html += `
                <tr>
                    <td class="text-center">
                        <span class="rank-badge ${rankClass}">${rankContent}</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="user-avatar-bubble">${firstLetter}</span>
                            <div>
                                <span class="fw-bold text-dark d-block">${staff.staff_name}</span>
                                <small class="text-muted">ID: #${staff.staff_id}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted small">${staff.staff_email}</td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark px-2 py-1 rounded-pill">${staff.invited_users}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark px-2 py-1 rounded-pill">${staff.total_transactions}</span>
                    </td>
                    <td class="text-end fw-bold text-dark">
                        ${format_currency(staff.total_revenue)}
                    </td>
                    <td>
                        <div class="share-bar-container">
                            <div class="share-bar-track">
                                <div class="share-bar-fill" style="width: ${Math.min(100, share)}%;"></div>
                            </div>
                            <span class="share-bar-text">${share}%</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-detail-sm" onclick="showStaffDetail(${staff.staff_id})">
                            <i class="fas fa-eye me-1"></i> Chi tiết
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.html(html);
    }

    // Modal chi tiết nhân viên (global function)
    window.showStaffDetail = function (staffId) {
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();

        $('#modalStaffTitle').html('<i class="fas fa-spinner fa-spin text-primary"></i> Đang tải thông tin...');
        $('#detailContent').html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                <p>Đang tải chi tiết giao dịch của nhân viên...</p>
            </div>
        `);
        $('#detailModal').modal('show');

        $.ajax({
            url: route_api_revenue_detail,
            method: 'GET',
            data: {
                staff_id: staffId,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function (response) {
                if (response.success && response.staff) {
                    $('#modalStaffTitle').html(`
                        <i class="fas fa-user-circle text-teal"></i> ${response.staff.full_name} 
                        <span class="badge bg-light text-muted fw-normal ms-2 small">${response.staff.email}</span>
                    `);

                    const stats = response.statistics || {};
                    let html = `
                        <div class="row g-3 mb-4">
                            <div class="col-4">
                                <div class="modal-stat-pill">
                                    <div class="label">Khách hàng mời</div>
                                    <div class="value text-primary">${stats.invited_users || 0}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="modal-stat-pill">
                                    <div class="label">Tổng giao dịch</div>
                                    <div class="value text-warning">${stats.total_transactions || 0}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="modal-stat-pill">
                                    <div class="label">Tổng doanh thu</div>
                                    <div class="value text-success">${format_currency(stats.total_revenue || 0)}</div>
                                </div>
                            </div>
                        </div>
                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-history me-1"></i> Danh sách giao dịch phát sinh:</h6>
                        <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-sm table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Khách hàng</th>
                                        <th class="text-end">Số tiền</th>
                                        <th class="text-center">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    if (response.transactions && response.transactions.length > 0) {
                        response.transactions.forEach(function (t) {
                            const uName = t.user ? t.user.full_name : 'Khách';
                            const tDate = t.created_at ? new Date(t.created_at).toLocaleString('vi-VN') : '--';
                            html += `
                                <tr>
                                    <td class="small text-muted">${tDate}</td>
                                    <td class="fw-semibold text-dark">${uName}</td>
                                    <td class="text-end fw-bold text-success">+${format_currency(t.value)}</td>
                                    <td class="text-center"><span class="badge badge-success">Hoàn thành</span></td>
                                </tr>
                            `;
                        });
                    } else {
                        html += `
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">Chưa có giao dịch nạp tiền nào</td>
                            </tr>
                        `;
                    }

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;

                    $('#detailContent').html(html);
                }
            },
            error: function (xhr) {
                console.error('Lỗi khi load chi tiết nhân viên:', xhr);
                $('#detailContent').html(`
                    <div class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p>Có lỗi xảy ra khi tải dữ liệu chi tiết!</p>
                    </div>
                `);
            }
        });
    };
});