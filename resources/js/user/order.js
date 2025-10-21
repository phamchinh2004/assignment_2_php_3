// Hàm format datetime
function formatDateTime(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    
    // Option 1: Định dạng DD/MM/YYYY HH:mm
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

window.addEventListener('DOMContentLoaded', function () {
    const tab = localStorage.getItem('tab_order') ?? "tat-ca";

    if (tab === 'tat-ca') {
        activeTab('btn_tat_ca');
    } else if (tab === 'cho-xu-ly') {
        activeTab('btn_cho_xu_ly');
    } else if (tab === 'hoan-thanh') {
        activeTab('btn_hoan_thanh');
    } else if (tab === 'dong-bang') {
        activeTab('btn_dong_bang');
    }
    const btn_status = document.getElementsByClassName('btn_status');
    for (const item of btn_status) {
        item.addEventListener('click', async function () {
            if (item.dataset.tab === 'tat-ca') {
                await activeTab('btn_tat_ca');
            } else if (item.dataset.tab === 'cho-xu-ly') {
                await activeTab('btn_cho_xu_ly');
            } else if (item.dataset.tab === 'hoan-thanh') {
                await activeTab('btn_hoan_thanh');
            } else if (item.dataset.tab === 'dong-bang') {
                await activeTab('btn_dong_bang');
            }

        })
    }
    async function activeTab(btnId) {

        const buttons = document.querySelectorAll('.btn_status_text');
        buttons.forEach(btn => btn.classList.remove('active-tab'));

        const activeBtn = document.getElementById(btnId);
        if (activeBtn) {
            activeBtn.classList.add('active-tab');
            await loadDanhSachTheoTab(btnId);

        }
    }

    async function loadDanhSachTheoTab(tabId) {
        spinner.hidden = false;
        const response = await load_orders(tabId);
        if (response.status === 404) {
            notification('warning', trans.KhongTimThayDuLieuDonHang, trans.KhongCoDuLieu);
        } else if (response.status === 200) {
            let list_orders = response.list_orders;

            let div_list_orders = document.getElementById('list_orders');
            div_list_orders.innerHTML = "";
            if (list_orders.length > 0) {
                for (let frozen_order of list_orders) {
                    let order_item = document.createElement('div');
                    order_item.classList.add('order_item');
                    
                    // Thêm class penalized nếu đơn bị phạt
                    if (frozen_order.penalty_sent == 1) {
                        order_item.classList.add('penalized');
                    }
                    
                    order_item.id = frozen_order.id;
                    let image_status = null;
                    if (frozen_order.is_frozen == 1) {
                        image_status = "images/nhan_cho_xu_ly.png";
                    } else {
                        image_status = "images/nhan_thanh_cong.png";
                    }
                    const price = frozen_order.custom_price != null ? frozen_order.custom_price / frozen_order.order.quantity : frozen_order.order.price;
                    const order_details_price_formatted = format_currency(price);
                    const order_details_end_value_total_price_formatted = format_currency(frozen_order.order.quantity * price);
                    const order_details_end_value_price_rose_formatted = format_currency((frozen_order.order.quantity * price) * frozen_order.order.commission_percentage);
                    const order_details_end_value_total_formatted = format_currency((frozen_order.order.quantity * price) + ((frozen_order.order.quantity * price) * frozen_order.order.commission_percentage));
                    
                    // Tính toán penalty nếu có
                    const penalty_amount = frozen_order.penalty_amount ? parseFloat(frozen_order.penalty_amount) : 0;
                    const penalty_amount_formatted = format_currency(penalty_amount);
                    const total_after_penalty = ((frozen_order.order.quantity * price) + ((frozen_order.order.quantity * price) * frozen_order.order.commission_percentage)) - penalty_amount;
                    const total_after_penalty_formatted = format_currency(total_after_penalty);
                    
                    // Tính số tiền cần nạp thêm cho đơn bị phạt
                    const total_payment_needed = frozen_order.order.quantity * price; // Tổng tiền cần thanh toán để phân phối
                    const money_need_to_deposit = total_payment_needed - userBalance;
                    const money_need_to_deposit_formatted = format_currency(money_need_to_deposit);

                    order_item.innerHTML = `
                        <div class="d-flex flex-column">
                            <span class="order_time">${trans.ThoiGianDatPhanPhoi} ${formatDateTime(frozen_order.order.updated_at)}</span>
                            <span class="order_code">${trans.MaDonHang} ${frozen_order.order.order_code}</span>
                            <div class="order_status">
                                <img class="order_status_image" src="${image_status}" alt="">
                            </div>
                            ${frozen_order.penalty_sent == 1 ? `<div class="penalty_badge">⚠ BỊ PHẠT</div>` : ''}
                        </div>
                        <div class="order_info d-flex flex-row">
                            <div class="p-2 order_div_image">
                                <img class="order_image" max-width="100px" src="/storage/${frozen_order.order.image}" alt="">
                            </div>
                            <div class="order_info_text p-3 w-100 d-flex flex-column">
                                <span class="order_name">${frozen_order.order.name}</span>
                                <div class="d-flex justify-content-between mt-2">
                                    <span>${order_details_price_formatted}</span>
                                    <span>x${frozen_order.order.quantity}</span>
                                </div>
                            </div>
                        </div>
                        <table>
                            <tbody>
                                <tr>
                                    <td>${trans.TongTienPhanPhoi}</td>
                                    <th>${order_details_end_value_total_price_formatted}</th>
                                </tr>
                                <tr>
                                    <td>${trans.ChietKhau}</td>
                                    <th>${order_details_end_value_price_rose_formatted}</th>
                                </tr>
                                ${frozen_order.penalty_sent == 1 ? `
                                <tr class="penalty_row">
                                    <td>⚠ Tiền phạt (30%)</td>
                                    <th class="penalty_amount">-${penalty_amount_formatted}</th>
                                </tr>` : ''}
                                <tr>
                                    <td>${trans.SoTienHoanNhap}</td>
                                    <th class="total">${frozen_order.penalty_sent == 1 ? total_after_penalty_formatted : order_details_end_value_total_formatted}</th>
                                </tr>
                            </tbody>
                        </table>
                        ${frozen_order.penalty_sent == 1 && frozen_order.is_frozen == 1 ? `
                        <div class="penalty_info_warning">
                            <p class="penalty_text_danger mb-1"><strong>⚠ Đơn hàng bị phạt do quá thời hạn phân phối!</strong></p>
                            <p class="penalty_text_danger mb-1">• Bạn đã nhận thông báo qua email</p>
                            <p class="penalty_text_danger mb-1">• Tiền phạt: <strong>${penalty_amount_formatted}</strong> (30% giá trị đơn)</p>
                            ${money_need_to_deposit > 0 ? `<p class="penalty_text_danger mb-1">• <strong>Cần nạp thêm: ${money_need_to_deposit_formatted}</strong> để có thể phân phối</p>` : ''}
                            <p class="penalty_text_danger mb-0">• Vui lòng ${money_need_to_deposit > 0 ? 'nạp tiền và ' : ''}hoàn thành phân phối sớm nhất</p>
                        </div>` : ''}
                        ${frozen_order.penalty_sent == 1 && frozen_order.is_frozen == 0 ? `
                        <div class="penalty_info">
                            <p class="penalty_text mb-1"><strong>ℹ️ Thông tin phạt:</strong></p>
                            <p class="penalty_text mb-1">• Đơn hàng đã phân phối nhưng bị phạt do quá hạn</p>
                            <p class="penalty_text mb-0">• Tiền phạt <strong>${penalty_amount_formatted}</strong> đã được trừ khỏi số tiền hoàn nhập</p>
                        </div>` : ''}
                        ${frozen_order.is_frozen == 1 ? `<div class="mt-2 d-flex justify-content-center">
                            <button class="btn btn-outline-dark btn-sm btn_phan_phoi w-50">${trans.PhanPhoiNgay}</button>
                        </div>`: ``}
                    `;
                    div_list_orders.appendChild(order_item);
                }
            } else {
                div_list_orders.innerHTML = `
                <div class="d-flex justify-content-center">
                    <span class="text-center">${trans.KhongCoDuLieu}</span>
                </div>
                `;
            }
            spinner.hidden = true;
        }
    }
    function load_orders(tabId) {
        return new Promise((resolve, reject) => {
            fetch(route_get_list_orders_by_tab, {
                method: "POST",
                headers: {
                    'Content-Type': "application/json",
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    tabId: tabId
                })
            })
                .then(response => response.json())
                .then(data => {
                    return resolve(data);
                })
                .catch(error => {
                    console.log(error);
                    reject(error);
                });
        });
    }
    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    document.getElementById('list_orders').addEventListener('click', async function (e) {
        if (e.target.classList.contains('btn_phan_phoi')) {
            spinner.hidden = false;
            let frozen_id = e.target.closest('.order_item').id;
            let result = await handle_distribution(frozen_id);
            if (result.status === 200) {
                const profit = result.profit;
                await notification('warning', "", trans.ChoXuLy2);
                await sleep(1000);
                await notification('warning', "", trans.DangPhanPhoi);
                await sleep(1000);
                await notification('success', result.message, trans.ThanhCong);
                swal({
                    title: trans.PhanPhoiThanhCong,
                    content: {
                        element: "span",
                        attributes: {
                            innerHTML: "<span style='color:green;font-weight:bold;'>+" + format_currency(profit, 4, 4) + "</span>"
                        }
                    },
                    icon: "success",
                    buttons: {
                        confirm: {
                            text: "OK",
                            value: true,
                            visible: true,
                            className: "btn-success",
                            closeModal: true
                        }
                    },
                });
                if (tab === 'tat-ca') {
                    activeTab('btn_tat_ca');
                } else if (tab === 'cho-xu-ly') {
                    activeTab('btn_cho_xu_ly');
                } else if (tab === 'hoan-thanh') {
                    activeTab('btn_hoan_thanh');
                } else if (tab === 'dong-bang') {
                    activeTab('btn_dong_bang');
                }
                const so_du_user = document.getElementById('so_du_user');
                so_du_user.innerHTML = trans.SoDuHienTai + format_currency(result.balance);
            } else if (result.status === 409) {
                if (tab === 'tat-ca') {
                    activeTab('btn_tat_ca');
                } else if (tab === 'cho-xu-ly') {
                    activeTab('btn_cho_xu_ly');
                } else if (tab === 'hoan-thanh') {
                    activeTab('btn_hoan_thanh');
                } else if (tab === 'dong-bang') {
                    activeTab('btn_dong_bang');
                }
                notification('warning', result.message, trans.CanhBao);
                // spinner.hidden = true;
            } else {
                notification('warning', result.message, trans.Loi);
            }
            spinner.hidden = true;
        }
    });

    function handle_distribution(frozen_id) {
        return new Promise((resolve, reject) => {
            fetch(route_handle_distribution, {
                method: "POST",
                headers: {
                    'Content-Type': "application/json",
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    frozen_id: frozen_id
                })
            })
                .then(response => response.json())
                .then(data => {
                    return resolve(data);
                })
                .catch(error => {
                    console.log(error);
                    reject(error);
                });
        })
    }
});