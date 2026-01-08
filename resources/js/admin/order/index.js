document.addEventListener("DOMContentLoaded", async function () {
    let status = localStorage.getItem("order_index_filter_status") ?? "";
    let rank = localStorage.getItem("order_index_filter_rank") ?? "";
    // Thông báo
    if (localStorage.getItem("success")) {
        notification("success", localStorage.getItem("success"));
        localStorage.removeItem("success");
    }
    // Làm sáng nút nếu danh sách đơn hàng đang là trạng thái của nó
    const active = document.getElementById("active");
    const inactive = document.getElementById("inactive");
    if (status == 1) {
        if (active.classList.contains("btn-outline-primary")) {
            active.classList.remove("btn-outline-primary");
            active.classList.add("btn-primary");
        }
        if (inactive.classList.contains("btn-danger")) {
            inactive.classList.remove("btn-danger");
            inactive.classList.add("btn-outline-danger");
        }
    } else if (status == 0) {
        if (inactive.classList.contains("btn-outline-danger")) {
            inactive.classList.remove("btn-outline-danger");
            inactive.classList.add("btn-danger");
        }
        if (active.classList.contains("btn-primary")) {
            active.classList.remove("btn-primary");
            active.classList.add("btn-outline-primary");
        }
    }
    // Nút cấp độ
    const filter_ranks = document.getElementsByClassName("filter_rank");
    for (const item of filter_ranks) {
        item.addEventListener("click", async function () {
            spinner.hidden = false;
            for (const item_2 of filter_ranks) {
                if (item_2.classList.contains("btn-primary")) {
                    item_2.classList.remove("btn-primary");
                    item_2.classList.add("btn-outline-primary");
                }
            }
            if (item.classList.contains("btn-outline-primary")) {
                item.classList.remove("btn-outline-primary");
                item.classList.add("btn-primary");
            }
            if (all_ranks.classList.contains("btn-primary")) {
                all_ranks.classList.remove("btn-primary");
                all_ranks.classList.add("btn-outline-primary");
            }
            const id = item.id;
            localStorage.setItem("order_index_filter_rank", id);
            rank = localStorage.getItem("order_index_filter_rank");
            await updateListOrders(status, rank);
            spinner.hidden = true;
        });
    }
    // Nút tất cả
    const all_ranks = document.getElementById("all_ranks");
    all_ranks.addEventListener("click", async function () {
        spinner.hidden = false;
        for (const item of filter_ranks) {
            if (item.classList.contains("btn-primary")) {
                item.classList.remove("btn-primary");
                item.classList.add("btn-outline-primary");
            }
        }
        if (all_ranks.classList.contains("btn-outline-primary")) {
            all_ranks.classList.remove("btn-outline-primary");
            all_ranks.classList.add("btn-primary");
        }
        localStorage.setItem("order_index_filter_rank", "");
        rank = localStorage.getItem("order_index_filter_rank");
        await updateListOrders(status, rank);
        spinner.hidden = true;
    });
    // Làm sáng nút cấp độ nếu danh sách đơn hàng đang là trạng thái của nó
    for (const item of filter_ranks) {
        const id = item.id;
        if (id == rank) {
            if (item.classList.contains("btn-outline-primary")) {
                item.classList.remove("btn-outline-primary");
                item.classList.add("btn-primary");
            }
        }
    }
    if (rank == "") {
        if (all_ranks.classList.contains("btn-outline-primary")) {
            all_ranks.classList.remove("btn-outline-primary");
            all_ranks.classList.add("btn-primary");
        }
    }
    // Cập nhật danh sách đơn hàng
    async function updateListOrders(status, rank) {
        let dataTable = $("#dataTable_list_orders").DataTable();

        // Xóa toàn bộ dữ liệu cũ
        dataTable.clear();

        // Luôn luôn load dữ liệu, bất kể có filter hay không
        // Controller sẽ xử lý filter theo status và rank
        let result = await loadListOrders(status, rank);
        if (result.status == 200) {
            let list_orders = result.data;
            let i = 0;
            if (list_orders.length > 0) {
                list_orders.forEach((item) => {
                    let nameShort =
                        item.name.length > 30
                            ? item.name.slice(0, 30) + "..."
                            : item.name;
                    let imageUrl = `/storage/${item.image}`;

                    let statusBadge =
                        item.status == 1
                            ? `<span class="text-white badge badge-success">Đang hoạt động</span>`
                            : `<span class="text-white badge badge-danger">Ngừng hoạt động</span>`;
                    let toggleButton =
                        item.status == 1
                            ? `<a href="/admin/order/change-status-order/${item.id}" class="btn btn-danger btn-sm mb-1"><i class="fas fa-lock fa-sm p-2"></i></a>`
                            : `<a href="/admin/order/change-status-order/${item.id}" class="btn btn-success btn-sm mb-1"><i class="fas fa-lock-open fa-sm p-2"></i></a>`;

                    let created_at = formatDateTime(item.created_at);
                    let updated_at = formatDateTime(item.updated_at);

                    // Tạo HTML cho thông tin cơ bản
                    let basicInfoHtml = `<div class="d-flex flex-column">
                        <span>Tên: <b><a class="cspt" href="#">${nameShort}</a></b></span>
                        <span>Giá: <b>${item.price}$</b></span>
                        <span>Số lượng: <b>${item.quantity}</b></span>
                        <span>Hoa hồng: <b>${item.commission_percentage}%</b></span>
                    </div>`;

                    // Tạo HTML cho thông tin đơn hàng
                    let orderInfoHtml = `<div class="d-flex flex-column">`;

                    if (item.customer_name) {
                        orderInfoHtml += `<span class="text-primary"><i class="fas fa-user"></i> Khách hàng: <b>${item.customer_name}</b></span>`;
                    }
                    if (item.customer_phone) {
                        orderInfoHtml += `<span class="text-info"><i class="fas fa-phone"></i> SĐT: <b>${item.customer_phone}</b></span>`;
                    }
                    if (item.customer_address) {
                        let addressShort =
                            item.customer_address.length > 40
                                ? item.customer_address.slice(0, 40) + "..."
                                : item.customer_address;
                        orderInfoHtml += `<span class="text-secondary"><i class="fas fa-map-marker-alt"></i> Địa chỉ: <b>${addressShort}</b></span>`;
                    }
                    if (item.partner && item.partner.name) {
                        orderInfoHtml += `<span class="text-success"><i class="fas fa-store"></i> Nền tảng: <b>${item.partner.name}</b></span>`;
                    }
                    if (item.payment_method) {
                        let paidBadge = item.is_paid
                            ? `<span class="badge badge-success">Đã thanh toán</span>`
                            : `<span class="badge badge-warning">Chưa thanh toán</span>`;
                        orderInfoHtml += `<span class="text-warning"><i class="fas fa-credit-card"></i> Thanh toán: <b>${item.payment_method}</b> ${paidBadge}</span>`;
                    }
                    if (item.customer_note) {
                        let noteShort =
                            item.customer_note.length > 30
                                ? item.customer_note.slice(0, 30) + "..."
                                : item.customer_note;
                        orderInfoHtml += `<span class="text-muted"><i class="fas fa-sticky-note"></i> Ghi chú: <b>${noteShort}</b></span>`;
                    }
                    if (item.api) {
                        let apiShort =
                            item.api.length > 20
                                ? item.api.slice(0, 20) + "..."
                                : item.api;
                        orderInfoHtml += `<span class="text-dark" style="font-family: monospace; font-size: 11px;"><i class="fas fa-code"></i> API: <b>${apiShort}</b></span>`;
                    }

                    orderInfoHtml += `</div>`;

                    // Gộp ngày tạo và cập nhật
                    let dateHtml = `<div class="d-flex flex-column">
                        <small><i class="fas fa-calendar-plus"></i> Tạo: ${created_at}</small>
                        <small><i class="fas fa-calendar-edit"></i> Cập nhật: ${updated_at}</small>
                    </div>`;

                    // Tạo HTML cho cột Mã (có cả order_code và hình ảnh)
                    let orderCodeHtml = `<div class="d-flex flex-column justify-content-center align-items-center">
                        <span>${item.order_code}</span>
                        <img class="order_image" src="${imageUrl}" alt="" />
                    </div>`;

                    dataTable.row.add([
                        ++i,
                        item.id,
                        orderCodeHtml,
                        basicInfoHtml,
                        orderInfoHtml,
                        statusBadge,
                        dateHtml,
                        `<div class="d-flex flex-column ">
                            <a href="#" class="btn btn-secondary btn-sm mb-1"><i class="fas fa-eye fa-sm p-2"></i></a>
                            ${toggleButton}
                            <a href="/admin/order/${item.id}/edit" class="btn btn-warning btn-sm"><i class="fas fa-pen-to-square fa-sm p-2"></i></a>
                    </div>`,
                    ]);
                });
            }
            dataTable.draw();
        }
    }
    // Hàm định dạng ngày tháng chuẩn
    function formatDateTime(datetime) {
        let date = new Date(datetime);
        return (
            date.getFullYear() +
            "-" +
            String(date.getMonth() + 1).padStart(2, "0") +
            "-" +
            String(date.getDate()).padStart(2, "0") +
            " " +
            String(date.getHours()).padStart(2, "0") +
            ":" +
            String(date.getMinutes()).padStart(2, "0") +
            ":" +
            String(date.getSeconds()).padStart(2, "0")
        );
    }
    spinner.hidden = false;
    await updateListOrders(status, rank);
    spinner.hidden = true;
    function loadListOrders(status, rank) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: route_index_order,
                method: "GET",
                data: {
                    status: status,
                    rank: rank,
                },
                success: function (response) {
                    if (response.status === 400) {
                        notification(
                            "error",
                            response.message ||
                                "Có lỗi xảy ra, vui lòng thử lại!",
                            "Lỗi!"
                        );
                        return reject(response);
                    }
                    resolve(response);
                },
                error: function (xhr) {
                    const message =
                        xhr.responseJSON?.message ||
                        "Có lỗi xảy ra khi tải danh sách đơn hàng, vui lòng thử lại!";
                    notification("error", message, "Lỗi!");
                    reject(xhr);
                },
            });
        });
    }

    active.addEventListener("click", async function () {
        if (active.classList.contains("btn-outline-primary")) {
            spinner.hidden = false;
            active.classList.remove("btn-outline-primary");
            active.classList.add("btn-primary");
            localStorage.setItem("order_index_filter_status", "1");
            status = localStorage.getItem("order_index_filter_status");
            await updateListOrders(status, rank);
            spinner.hidden = true;
        }
        if (inactive.classList.contains("btn-danger")) {
            inactive.classList.remove("btn-danger");
            inactive.classList.add("btn-outline-danger");
        }
    });
    inactive.addEventListener("click", async function () {
        if (inactive.classList.contains("btn-outline-danger")) {
            spinner.hidden = false;
            inactive.classList.remove("btn-outline-danger");
            inactive.classList.add("btn-danger");
            localStorage.setItem("order_index_filter_status", "0");
            status = localStorage.getItem("order_index_filter_status");
            await updateListOrders(status, rank);
            spinner.hidden = true;
        }
        if (active.classList.contains("btn-primary")) {
            active.classList.remove("btn-primary");
            active.classList.add("btn-outline-primary");
        }
    });

    const update_order_rose = document.getElementById("update_order_rose");
    if (update_order_rose) {
        update_order_rose.addEventListener("click", function () {
            spinner.hidden = false;
        });
    }

    const add_customer_info = document.getElementById("add_customer_info");
    if (add_customer_info) {
        add_customer_info.addEventListener("click", function () {
            spinner.hidden = false;
        });
    };
});
