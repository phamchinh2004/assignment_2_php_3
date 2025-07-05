$(document).ready(function () {
    let revenueChart, topStaffChart;

    // Load danh sách nhân viên
    loadStaffList();

    // Load dữ liệu ban đầu
    loadRevenueData();

    // Event handlers
    $('#filterBtn').click(function () {
        loadRevenueData();
    });

    $('#exportBtn').click(function () {
        exportToExcel();
    });

    // Load danh sách nhân viên cho select
    function loadStaffList() {
        $.ajax({
            url: route_api_staff_list,
            method: 'GET',
            success: function (response) {
                let options = '<option value="">Tất cả nhân viên</option>';
                response.data.forEach(function (staff) {
                    options += `<option value="${staff.id}">${staff.full_name} (${staff.email})</option>`;
                });
                $('#staffSelect').html(options);
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

        $.ajax({
            url: route_api_revenue_by_staff,
            method: 'GET',
            data: {
                date_from: dateFrom,
                date_to: dateTo,
                staff_id: staffId
            },
            success: function (response) {
                updateSummary(response.summary);
                updateCharts(response.chart_data);
                updateTable(response.table_data);
            },
            error: function (xhr) {
                console.error('Lỗi khi load dữ liệu:', xhr);
                alert('Có lỗi xảy ra khi tải dữ liệu!');
            }
        });
    }

    // Cập nhật thông tin tổng quan
    function updateSummary(summary) {
        $('#totalStaff').text(summary.total_staff);
        $('#totalRevenue').text(format_currency(summary.total_revenue));
        $('#totalTransactions').text(summary.total_transactions);
        $('#avgRevenue').text(format_currency(summary.avg_revenue));
    }

    // Cập nhật biểu đồ
    function updateCharts(chartData) {
        // Biểu đồ doanh thu theo nhân viên
        if (revenueChart) {
            revenueChart.destroy();
        }

        const ctx1 = document.getElementById('revenueChart').getContext('2d');
        revenueChart = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: chartData.revenue_data,
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

        // Biểu đồ top 5 nhân viên
        if (topStaffChart) {
            topStaffChart.destroy();
        }

        const ctx2 = document.getElementById('topStaffChart').getContext('2d');
        topStaffChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: chartData.top_labels,
                datasets: [{
                    data: chartData.top_data,
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ]
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
                            label: function (context) {
                                return context.label + ': ' + format_currency(context.parsed);
                            }
                        }
                    }
                }
            }
        });
    }

    // Cập nhật bảng
    function updateTable(tableData) {
        let html = '';
        tableData.forEach(function (item, index) {
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.staff_name}</td>
                    <td>${item.staff_email}</td>
                    <td>${item.invited_users}</td>
                    <td>${item.total_transactions}</td>
                    <td>${format_currency(item.total_revenue)}</td>
                    <td>
                        <button class="btn btn-info btn-sm" onclick="showDetail(${item.staff_id})">
                            <i class="fas fa-eye"></i> Chi tiết
                        </button>
                    </td>
                </tr>
            `;
        });
        $('#revenueTableBody').html(html);
    }

    // Hiển thị chi tiết
    window.showDetail = function (staffId) {
        const dateFrom = $('#dateFrom').val();
        const dateTo = $('#dateTo').val();

        $.ajax({
            url: route_api_revenue_detail,
            method: 'GET',
            data: {
                staff_id: staffId,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function (response) {
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Thông tin nhân viên</h5>
                            <p><strong>Tên:</strong> ${response.staff.full_name}</p>
                            <p><strong>Email:</strong> ${response.staff.email}</p>
                            <p><strong>Điện thoại:</strong> ${response.staff.phone}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Thống kê</h5>
                            <p><strong>Số người mời:</strong> ${response.statistics.invited_users}</p>
                            <p><strong>Tổng giao dịch:</strong> ${response.statistics.total_transactions}</p>
                            <p><strong>Tổng doanh thu:</strong> ${format_currency(response.statistics.total_revenue)}</p>
                        </div>
                    </div>
                    <hr>
                    <h5>Danh sách giao dịch</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Người dùng</th>
                                    <th>Số tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                response.transactions.forEach(function (transaction) {
                    html += `
                        <tr>
                            <td>${formatDate(transaction.created_at)}</td>
                            <td>${transaction.user.full_name}</td>
                            <td>${format_currency(transaction.value)}</td>
                            <td><span class="badge badge-success">${transaction.status}</span></td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                $('#detailContent').html(html);
                $('#detailModal').modal('show');
            },
            error: function (xhr) {
                console.error('Lỗi khi load chi tiết:', xhr);
                alert('Có lỗi xảy ra khi tải chi tiết!');
            }
        });
    };


    // Hàm format ngày
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }
});