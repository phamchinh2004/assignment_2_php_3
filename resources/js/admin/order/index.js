document.addEventListener("DOMContentLoaded", async function () {
    let status = localStorage.getItem("order_index_filter_status") ?? "";
    let rank = localStorage.getItem("order_index_filter_rank") ?? "";

    // ---------- Thông báo từ localStorage ----------
    if (localStorage.getItem("success")) {
        notification("success", localStorage.getItem("success"));
        localStorage.removeItem("success");
    }

    // ---------- Status filter pills (top header) ----------
    const btnActiveTop    = document.getElementById("btn_active_top");
    const btnInactiveTop  = document.getElementById("btn_inactive_top");
    const btnAllStatus    = document.getElementById("btn_all_status");

    function syncStatusPills(currentStatus) {
        [btnActiveTop, btnInactiveTop, btnAllStatus].forEach(b => {
            if (b) {
                b.classList.remove("active");
                b.classList.remove("selected");
            }
        });
        if (currentStatus === "1") {
            if (btnActiveTop) {
                btnActiveTop.classList.add("active");
                btnActiveTop.classList.add("selected");
            }
        } else if (currentStatus === "0") {
            if (btnInactiveTop) {
                btnInactiveTop.classList.add("active");
                btnInactiveTop.classList.add("selected");
            }
        } else {
            if (btnAllStatus) {
                btnAllStatus.classList.add("active");
                btnAllStatus.classList.add("selected");
            }
        }
    }
    syncStatusPills(status);

    if (btnActiveTop) {
        btnActiveTop.addEventListener("click", async function () {
            if (status === "1") return;
            spinner.hidden = false;
            status = "1";
            localStorage.setItem("order_index_filter_status", status);
            syncStatusPills(status);
            await updateListOrders(status, rank);
            spinner.hidden = true;
        });
    }
    if (btnInactiveTop) {
        btnInactiveTop.addEventListener("click", async function () {
            if (status === "0") return;
            spinner.hidden = false;
            status = "0";
            localStorage.setItem("order_index_filter_status", status);
            syncStatusPills(status);
            await updateListOrders(status, rank);
            spinner.hidden = true;
        });
    }
    if (btnAllStatus) {
        btnAllStatus.addEventListener("click", async function () {
            if (status === "") return;
            spinner.hidden = false;
            status = "";
            localStorage.setItem("order_index_filter_status", status);
            syncStatusPills(status);
            await updateListOrders(status, rank);
            spinner.hidden = true;
        });
    }

    // ---------- Rank filter chips ----------
    const all_ranks    = document.getElementById("all_ranks");
    const filter_ranks = document.getElementsByClassName("filter_rank");

    function syncRankChips(currentRank) {
        if (all_ranks) {
            if (currentRank === "" || currentRank === "all") {
                all_ranks.classList.add("selected");
            } else {
                all_ranks.classList.remove("selected");
            }
        }
        for (const item of filter_ranks) {
            if (item.id == currentRank) {
                item.classList.add("selected");
            } else {
                item.classList.remove("selected");
            }
        }
    }
    syncRankChips(rank);

    for (const item of filter_ranks) {
        item.addEventListener("click", async function () {
            spinner.hidden = false;
            rank = item.id;
            localStorage.setItem("order_index_filter_rank", rank);
            syncRankChips(rank);
            await updateListOrders(status, rank);
            spinner.hidden = true;
        });
    }
    if (all_ranks) {
        all_ranks.addEventListener("click", async function () {
            spinner.hidden = false;
            rank = "";
            localStorage.setItem("order_index_filter_rank", rank);
            syncRankChips(rank);
            await updateListOrders(status, rank);
            spinner.hidden = true;
        });
    }

    // ---------- Copy to Clipboard Helper ----------
    document.addEventListener("click", function (e) {
        const copyBtn = e.target.closest(".btn-copy-text");
        if (!copyBtn) return;
        const textToCopy = copyBtn.getAttribute("data-copy");
        if (!textToCopy) return;

        navigator.clipboard.writeText(textToCopy).then(() => {
            const originalHtml = copyBtn.innerHTML;
            copyBtn.classList.add("copied");
            copyBtn.innerHTML = `<span>Đã chép!</span> <i class="fas fa-check ms-1"></i>`;
            setTimeout(() => {
                copyBtn.classList.remove("copied");
                copyBtn.innerHTML = originalHtml;
            }, 1500);
        }).catch(() => {
            notification("error", "Không thể sao chép vào bộ nhớ tạm");
        });
    });

    // ---------- Helpers ----------
    function formatDateTime(datetime) {
        if (!datetime) return "—";
        const d = new Date(datetime);
        return (
            d.getFullYear() + "-" +
            String(d.getMonth() + 1).padStart(2, "0") + "-" +
            String(d.getDate()).padStart(2, "0") +
            " " +
            String(d.getHours()).padStart(2, "0") + ":" +
            String(d.getMinutes()).padStart(2, "0")
        );
    }

    function paymentLabel(method) {
        const map = {
            "COD":           { label: "COD",          cls: "cod"     },
            "vnpay":         { label: "VNPay",        cls: "vnpay"   },
            "momo":          { label: "MoMo",         cls: "momo"    },
            "paypal":        { label: "PayPal",       cls: "paypal"  },
            "bank_transfer": { label: "Ngân hàng",    cls: "bank"    },
            "other":         { label: "Khác",         cls: ""        },
        };
        const m = map[method] || { label: method || "—", cls: "" };
        return `<span class="payment-chip ${m.cls}">${m.label}</span>`;
    }

    function paidBadge(isPaid, paymentMethod) {
        if (paymentMethod === "COD") {
            return `<span class="badge-unpaid"><i class="fas fa-truck" style="font-size:9px;"></i> COD</span>`;
        }
        return isPaid
            ? `<span class="badge-paid"><i class="fas fa-check-circle" style="font-size:9px;"></i> Đã TT</span>`
            : `<span class="badge-unpaid"><i class="fas fa-clock" style="font-size:9px;"></i> Chưa TT</span>`;
    }

    function truncate(str, len) {
        if (!str) return "";
        return str.length > len ? str.slice(0, len) + "…" : str;
    }

    // ---------- Build table rows ----------
    async function updateListOrders(status, rank) {
        const dataTable = $("#dataTable_list_orders").DataTable();
        dataTable.clear();

        const result = await loadListOrders(status, rank);
        if (result.status == 200) {
            const list = result.data;
            if (list.length === 0) {
                dataTable.draw();
                return;
            }

            list.forEach((item, idx) => {
                const imageUrl = `/storage/${item.image}`;

                // Col 1: # (STT chip)
                const colIndex = `<span class="user-id-chip">#${idx + 1}</span>`;

                // Col 2: Sản phẩm & Đơn hàng (user-identity-cell style)
                const colProduct = `
                <div class="user-identity-cell">
                    <div class="entity-thumbnail" title="${item.name}">
                        <img src="${imageUrl}" alt="${item.name}" loading="lazy"/>
                    </div>
                    <div class="user-details">
                        <div class="d-flex align-items-center gap-2">
                            <a class="user-link text-truncate" href="/admin/order/${item.id}" title="${item.name}" style="max-width: 220px;">
                                ${item.name}
                            </a>
                            <span class="user-id-chip">#${item.id}</span>
                        </div>
                        <div class="user-meta-row">
                            <span class="copy-badge btn-copy-text" data-copy="${item.order_code}" title="Nhấp để sao chép mã đơn">
                                <code>${item.order_code}</code>
                                <i class="fas fa-copy ms-1"></i>
                            </span>
                            ${item.rank && item.rank.name ? `
                                <span class="rank-pill">
                                    <i class="fas fa-crown"></i> ${item.rank.name}
                                </span>
                            ` : `
                                <span class="rank-pill no-rank">
                                    <i class="fas fa-minus"></i> Mặc định
                                </span>
                            `}
                            ${item.partner && item.partner.name ? `
                                <span class="user-id-chip" style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;">
                                    <i class="fas fa-store"></i> ${item.partner.name}
                                </span>
                            ` : ""}
                        </div>
                    </div>
                </div>`;

                // Col 3: Giá bán & Hoa hồng (finance-box style)
                const priceFormatted = parseFloat(item.price).toLocaleString("en-US", {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const totalFormatted = (parseFloat(item.price) * parseInt(item.quantity || 1)).toLocaleString("en-US", {minimumFractionDigits: 2, maximumFractionDigits: 2});
                const colFinance = `
                <div class="finance-box">
                    <div>
                        <span class="balance-highlight" title="Đơn giá x Số lượng">
                            <i class="fas fa-dollar-sign text-success"></i>
                            ${priceFormatted}$
                        </span>
                        <span class="user-id-chip font-weight-bold" style="margin-left: 4px;">x${item.quantity}</span>
                    </div>
                    <div>
                        <span class="frozen-balance-chip" title="Tỷ lệ hoa hồng chiết khấu">
                            <i class="fas fa-percent"></i>
                            Hoa hồng: ${item.commission_percentage ?? 0}%
                        </span>
                        ${item.quantity > 1 ? `<small class="text-muted d-block mt-1">Tổng: <b>${totalFormatted}$</b></small>` : ""}
                    </div>
                </div>`;

                // Col 4: Khách hàng & Giao hàng (location-box style)
                let colCustomer = `<div class="location-box">`;
                if (item.customer_name) {
                    colCustomer += `
                    <div class="location-place">
                        <i class="fas fa-user-circle text-primary"></i>
                        <span class="font-weight-bold">${truncate(item.customer_name, 22)}</span>
                    </div>`;
                }
                if (item.customer_phone) {
                    colCustomer += `
                    <div>
                        <span class="copy-badge btn-copy-text" data-copy="${item.customer_phone}" title="Nhấp để sao chép SĐT">
                            <i class="fas fa-phone"></i> ${item.customer_phone}
                            <i class="fas fa-copy ms-1"></i>
                        </span>
                    </div>`;
                }
                if (item.customer_address) {
                    colCustomer += `
                    <div class="time-item mt-1" title="${item.customer_address}">
                        <i class="fas fa-location-dot text-danger"></i>
                        <span class="text-truncate" style="max-width: 220px;">${truncate(item.customer_address, 28)}</span>
                    </div>`;
                }
                if (item.payment_method) {
                    colCustomer += `
                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                        ${paymentLabel(item.payment_method)}
                        ${paidBadge(item.is_paid, item.payment_method)}
                    </div>`;
                }
                colCustomer += `</div>`;

                // Col 5: Trạng thái (status-container style)
                const colStatus = `
                <div class="status-container">
                    ${item.status == 1 ? `
                        <span class="status-badge active">
                            <span class="status-dot"></span> Đã kích hoạt
                        </span>
                    ` : `
                        <span class="status-badge danger">
                            <span class="status-dot"></span> Bị khóa
                        </span>
                    `}
                    ${item.api ? `
                        <span class="tag-pill tag-frozen" title="Mã API: ${item.api}">
                            <i class="fas fa-code"></i> API
                        </span>
                    ` : ""}
                </div>`;

                // Col 6: Lịch sử (time-box style)
                const colDate = `
                <div class="time-box">
                    <div class="time-item" title="Ngày tạo: ${formatDateTime(item.created_at)}">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Tạo: ${formatDateTime(item.created_at)}</span>
                    </div>
                    <div class="time-item" title="Cập nhật lần cuối: ${formatDateTime(item.updated_at)}">
                        <i class="fas fa-clock-rotate-left"></i>
                        <span>Sửa: ${formatDateTime(item.updated_at)}</span>
                    </div>
                </div>`;

                // Col 7: Thao tác (action-icon-group style)
                const toggleBtn = item.status == 1
                    ? `<a href="/admin/order/change-status-order/${item.id}" class="btn-icon-modern lock" title="Khóa đơn hàng"><i class="fas fa-lock"></i></a>`
                    : `<a href="/admin/order/change-status-order/${item.id}" class="btn-icon-modern unlock" title="Kích hoạt đơn hàng"><i class="fas fa-lock-open"></i></a>`;

                const colActions = `
                <div class="action-icon-group justify-content-center">
                    <a href="/admin/order/${item.id}" class="btn-icon-modern view" title="Xem chi tiết đơn hàng">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="/admin/order/${item.id}/edit" class="btn-icon-modern edit" title="Chỉnh sửa đơn hàng">
                        <i class="fas fa-pen-to-square"></i>
                    </a>
                    ${toggleBtn}
                </div>`;

                dataTable.row.add([
                    colIndex,
                    colProduct,
                    colFinance,
                    colCustomer,
                    colStatus,
                    colDate,
                    colActions,
                ]);
            });
        }
        dataTable.draw();
    }

    // ---------- AJAX load ----------
    function loadListOrders(status, rank) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: route_index_order,
                method: "GET",
                data: { status, rank },
                success: function (response) {
                    if (response.status === 400) {
                        notification("error", response.message || "Có lỗi xảy ra, vui lòng thử lại!", "Lỗi!");
                        return reject(response);
                    }
                    resolve(response);
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || "Có lỗi xảy ra khi tải danh sách đơn hàng!";
                    notification("error", message, "Lỗi!");
                    reject(xhr);
                },
            });
        });
    }

    // ---------- Bulk action spinner ----------
    const update_order_rose = document.getElementById("update_order_rose");
    if (update_order_rose) {
        update_order_rose.addEventListener("click", () => { spinner.hidden = false; });
    }
    const add_customer_info = document.getElementById("add_customer_info");
    if (add_customer_info) {
        add_customer_info.addEventListener("click", () => { spinner.hidden = false; });
    }

    // ---------- Initial load ----------
    spinner.hidden = false;
    await updateListOrders(status, rank);
    spinner.hidden = true;
});
