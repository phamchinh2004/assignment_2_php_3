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
    const btn_status = document.getElementsByClassName('tab-btn');
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
                    
                    // Kiểm tra đơn hàng đặc biệt (có custom_price)
                    const isSpecialOrder = frozen_order.custom_price != null && frozen_order.custom_price > 0;
                    
                    // Kiểm tra đơn bị phạt (có penalty_amount)
                    const isPenalized = frozen_order.penalty_amount != null && frozen_order.penalty_amount > 0;
                    
                    // Ưu tiên: PHẠT > ĐẶC BIỆT
                    // Đơn bị phạt PHẢI được highlight dù có phải đơn đặc biệt hay không
                    if (isPenalized) {
                        if (frozen_order.is_frozen == 1) {
                            // Đơn bị phạt CHƯA hoàn thành - cảnh báo mạnh (ĐỎ)
                            order_item.classList.add('penalized');
                        } else {
                            // Đơn bị phạt ĐÃ hoàn thành - thông báo nhẹ (CAM)
                            order_item.classList.add('penalized-completed');
                        }
                    } else if (isSpecialOrder) {
                        // Chỉ add special-order nếu KHÔNG bị phạt
                        order_item.classList.add('special-order');
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

                    // Tính thời gian đếm ngược cho đơn chưa hoàn thành
                    let countdownHTML = '';
                    if (frozen_order.is_frozen == 1 && frozen_order.spun == 1) {
                        const receivedTime = new Date(frozen_order.updated_at); // Thời điểm nhận đơn
                        const deadline = new Date(receivedTime.getTime() + 24 * 60 * 60 * 1000); // +24 giờ
                        const now = new Date();
                        const timeLeft = deadline - now;
                        
                        if (timeLeft > 0) {
                            const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                            
                            // Xác định màu sắc dựa trên thời gian còn lại
                            let countdownClass = 'countdown-safe'; // > 6 giờ: xanh
                            if (hours < 1) {
                                countdownClass = 'countdown-critical'; // < 1 giờ: đỏ
                            } else if (hours < 3) {
                                countdownClass = 'countdown-danger'; // < 3 giờ: cam
                            } else if (hours < 6) {
                                countdownClass = 'countdown-warning'; // < 6 giờ: vàng
                            }
                            
                            countdownHTML = `
                                <div class="countdown-container ${countdownClass}" data-deadline="${deadline.toISOString()}" data-order-id="${frozen_order.id}">
                                    <div class="countdown-icon">⏰</div>
                                    <div class="countdown-text">
                                        <div class="countdown-label">Thời gian còn lại:</div>
                                        <div class="countdown-timer">
                                            <span class="countdown-hours">${hours.toString().padStart(2, '0')}</span>:
                                            <span class="countdown-minutes">${minutes.toString().padStart(2, '0')}</span>:
                                            <span class="countdown-seconds">${seconds.toString().padStart(2, '0')}</span>
                                        </div>
                                    </div>
                                </div>`;
                        } else {
                            countdownHTML = `
                                <div class="countdown-container countdown-expired">
                                    <div class="countdown-icon">⚠️</div>
                                    <div class="countdown-text">
                                        <div class="countdown-label text-danger fw-bold">ĐÃ QUÁ HẠN!</div>
                                    </div>
                                </div>`;
                        }
                    }
                    
                    order_item.innerHTML = `
                        <div class="d-flex flex-column">
                            <span class="order_time">${trans.ThoiGianDatPhanPhoi} ${formatDateTime(frozen_order.updated_at)}</span>
                            <span class="order_code">${trans.MaDonHang} ${frozen_order.order.order_code}</span>
                            ${countdownHTML}
                            <div class="order_status">
                                <img class="order_status_image" src="${image_status}" alt="">
                            </div>
                            ${isPenalized ? `<div class="penalty_badge">BỊ PHẠT</div>` : (isSpecialOrder ? `<div class="special_badge">✨ ĐẶC BIỆT</div>` : '')}
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
                                ${isPenalized ? `
                                <tr class="penalty_row">
                                    <td>⚠ Tiền phạt (30%)</td>
                                    <th class="penalty_amount">-${penalty_amount_formatted}</th>
                                </tr>` : ''}
                                <tr>
                                    <td>${trans.SoTienHoanNhap}</td>
                                    <th class="total ${isSpecialOrder ? 'special-total' : ''}">${isPenalized ? total_after_penalty_formatted : order_details_end_value_total_formatted}</th>
                                </tr>
                            </tbody>
                        </table>
                        ${isPenalized && frozen_order.is_frozen == 1 ? `
                        <div class="penalty_info_warning">
                            <p class="penalty_text_danger mb-1"><strong>⚠ Đơn hàng bị phạt do quá thời hạn phân phối!</strong></p>
                            <p class="penalty_text_danger mb-1">• Bạn đã nhận thông báo qua email</p>
                            <p class="penalty_text_danger mb-1">• Tiền phạt: <strong>${penalty_amount_formatted}</strong> (30% giá trị đơn)</p>
                            ${money_need_to_deposit > 0 ? `<p class="penalty_text_danger mb-1">• <strong>Cần nạp thêm: ${money_need_to_deposit_formatted}</strong> để có thể phân phối</p>` : ''}
                            <p class="penalty_text_danger mb-0">• Vui lòng ${money_need_to_deposit > 0 ? 'nạp tiền và ' : ''}hoàn thành phân phối sớm nhất</p>
                        </div>` : ''}
                        ${isPenalized && frozen_order.is_frozen == 0 ? `
                        <div class="penalty_info">
                            <p class="penalty_text mb-1"><strong>ℹ️ Thông tin phạt:</strong></p>
                            <p class="penalty_text mb-1">• Đơn hàng đã phân phối nhưng bị phạt do quá hạn</p>
                            <p class="penalty_text mb-0">• Tiền phạt <strong>${penalty_amount_formatted}</strong> đã được trừ khỏi số tiền hoàn nhập</p>
                        </div>` : ''}
                        ${isSpecialOrder && frozen_order.is_frozen == 1 && !isPenalized ? `
                        <div class="special_info">
                            <p class="special_text mb-1"><strong>🎉 Chúc mừng! Đơn hàng may mắn!</strong></p>
                            <p class="special_text mb-1">• Bạn đã quay trúng đơn hàng đặc biệt trong chương trình sự kiện cặp đôi</p>
                            <p class="special_text mb-0">• <strong>Được thưởng 10%</strong> từ hệ thống khi hoàn thành phân phối</p>
                        </div>` : ''}
                        ${isSpecialOrder && frozen_order.is_frozen == 0 && !isPenalized ? `
                        <div class="special_info">
                            <p class="special_text mb-1"><strong>✅ Đã hoàn thành đơn hàng đặc biệt!</strong></p>
                            <p class="special_text mb-1">• Đơn hàng may mắn từ chương trình sự kiện cặp đôi</p>
                            <p class="special_text mb-0">• <strong>Được thưởng 10%</strong> từ hệ thống</p>
                        </div>` : ''}
                        ${isSpecialOrder && isPenalized ? `
                        <div class="alert alert-warning mt-2 mb-0 py-2 px-3" style="font-size: 12px; border-left: 3px solid #fbbf24;">
                            <p class="mb-1"><strong>ℹ️ Lưu ý:</strong></p>
                            <p class="mb-0">• Đây là đơn hàng đặc biệt nhưng đã bị phạt do quá hạn. Bạn đã hoàn thành phân phối và đã bị xử lý tiền phạt.</p>
                        </div>` : ''}
                        ${frozen_order.is_frozen == 1 ? `<div class="mt-2 d-flex justify-content-center">
                            <button class="btn btn-outline-dark btn-sm btn_phan_phoi w-50 ${isPenalized ? 'penalty-btn' : (isSpecialOrder ? 'special-btn' : '')}">${trans.PhanPhoiNgay}</button>
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
    // Function để cập nhật countdown
    function updateCountdowns() {
        const countdownElements = document.querySelectorAll('.countdown-container[data-deadline]');
        
        countdownElements.forEach(countdown => {
            const deadline = new Date(countdown.getAttribute('data-deadline'));
            const now = new Date();
            const timeLeft = deadline - now;
            
            if (timeLeft > 0) {
                const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                // Cập nhật số
                const hoursEl = countdown.querySelector('.countdown-hours');
                const minutesEl = countdown.querySelector('.countdown-minutes');
                const secondsEl = countdown.querySelector('.countdown-seconds');
                
                if (hoursEl) hoursEl.textContent = hours.toString().padStart(2, '0');
                if (minutesEl) minutesEl.textContent = minutes.toString().padStart(2, '0');
                if (secondsEl) secondsEl.textContent = seconds.toString().padStart(2, '0');
                
                // Cập nhật class màu sắc
                countdown.classList.remove('countdown-safe', 'countdown-warning', 'countdown-danger', 'countdown-critical');
                if (hours < 1) {
                    countdown.classList.add('countdown-critical');
                } else if (hours < 3) {
                    countdown.classList.add('countdown-danger');
                } else if (hours < 6) {
                    countdown.classList.add('countdown-warning');
                } else {
                    countdown.classList.add('countdown-safe');
                }
            } else {
                // Hết thời gian - chuyển sang trạng thái expired
                countdown.classList.remove('countdown-safe', 'countdown-warning', 'countdown-danger', 'countdown-critical');
                countdown.classList.add('countdown-expired');
                countdown.innerHTML = `
                    <div class="countdown-icon">⚠️</div>
                    <div class="countdown-text">
                        <div class="countdown-label text-danger fw-bold">ĐÃ QUÁ HẠN!</div>
                    </div>`;
            }
        });
    }
    
    // Cập nhật countdown mỗi giây
    setInterval(updateCountdowns, 1000);
    
    document.getElementById('list_orders').addEventListener('click', async function (e) {
        if (e.target.classList.contains('btn_phan_phoi')) {
            // Hiển thị modal loading phân phối
            showDistributionModal();
            
            let frozen_id = e.target.closest('.order_item').id;
            let result = await handle_distribution(frozen_id);
            
            // Đóng modal loading
            closeDistributionModal();
            
            if (result.status === 200) {
                const profit = result.profit;
                const totalAmount = result.total_amount || 0;
                const commission = result.commission || 0;
                const penaltyAmount = result.penalty_amount || 0;
                
                // Hiển thị modal thành công với animation
                showSuccessModal(profit, totalAmount, commission, penaltyAmount);
                
                // Refresh tab hiện tại
                if (tab === 'tat-ca') {
                    activeTab('btn_tat_ca');
                } else if (tab === 'cho-xu-ly') {
                    activeTab('btn_cho_xu_ly');
                } else if (tab === 'hoan-thanh') {
                    activeTab('btn_hoan_thanh');
                } else if (tab === 'dong-bang') {
                    activeTab('btn_dong_bang');
                }
                
                // Cập nhật số dư
                const so_du_user = document.getElementById('so_du_user');
                so_du_user.innerHTML = trans.SoDuHienTai + format_currency(result.balance);
            } else if (result.status === 409) {
                // Refresh tab
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
            } else {
                notification('warning', result.message, trans.Loi);
            }
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

// ======================= MODAL FUNCTIONS =======================

// Hàm hiển thị modal phân phối
window.showDistributionModal = function() {
    const modal = document.getElementById('distributionModalOverlay');
    if (!modal) return;
    
    // Reset trạng thái các step
    document.getElementById('dist-step-1').className = 'loading-step active';
    document.getElementById('dist-step-2').className = 'loading-step';
    document.getElementById('dist-step-3').className = 'loading-step';
    document.getElementById('dist-progress-bar').style.width = '0%';
    
    // Hiển thị modal
    modal.classList.add('show');
    
    setTimeout(() => {
        document.getElementById('dist-progress-bar').style.width = '33%';
        
        setTimeout(() => {
            document.getElementById('dist-step-1').classList.remove('active');
            document.getElementById('dist-step-1').classList.add('completed');
            document.getElementById('dist-step-2').classList.add('active');
            document.getElementById('dist-progress-bar').style.width = '66%';
            
            setTimeout(() => {
                document.getElementById('dist-step-2').classList.remove('active');
                document.getElementById('dist-step-2').classList.add('completed');
                document.getElementById('dist-step-3').classList.add('active');
                document.getElementById('dist-progress-bar').style.width = '100%';
            }, 400);
        }, 400);
    }, 10);
}

// Hàm đóng modal phân phối
window.closeDistributionModal = function() {
    const modal = document.getElementById('distributionModalOverlay');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Hàm hiển thị modal thành công
window.showSuccessModal = function(profit, totalAmount, commission, penaltyAmount = 0) {
    // Tính tổng tiền hoàn nhập = Giá trị đơn hàng + Hoa hồng - Tiền phạt (nếu có)
    const totalRefund = totalAmount + commission - penaltyAmount;
    
    // Lấy modal và cập nhật nội dung
    const modal = document.getElementById('successModalOverlay');
    if (!modal) return;
    
    document.getElementById('success_profit_amount').textContent = '+' + format_currency(profit, 4, 4);
    document.getElementById('success_total_amount').textContent = '' + format_currency(totalAmount, 4, 4);
    document.getElementById('success_commission').textContent = '+' + format_currency(commission, 4, 4);
    document.getElementById('success_total_refund').textContent = '+' + format_currency(totalRefund, 4, 4);
    document.getElementById('success_time').textContent = new Date().toLocaleString('vi-VN');
    
    // Hiển thị/ẩn dòng tiền phạt
    const penaltyRow = document.getElementById('success_penalty_row');
    if (penaltyAmount > 0) {
        document.getElementById('success_penalty_amount').textContent = '-' + format_currency(penaltyAmount, 4, 4);
        penaltyRow.style.display = 'flex';
    } else {
        penaltyRow.style.display = 'none';
    }
    
    // Hiển thị modal
    modal.classList.add('show');
}

// Hàm đóng modal thành công
window.closeSuccessModal = function() {
    const modal = document.getElementById('successModalOverlay');
    if (modal) {
        modal.classList.remove('show');
    }
}