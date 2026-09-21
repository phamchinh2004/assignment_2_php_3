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

function finiteNumberOrNull(value) {
    if (value === null || value === undefined || value === '') return null;

    const number = Number(value);
    return Number.isFinite(number) ? number : null;
}

document.addEventListener('DOMContentLoaded', function () {
    // ==================================================Pháo hoa===================================================
    const container = document.getElementById('fireworks-container');
    const fireworks = new Fireworks(container, {
        autoresize: true,
        opacity: 0.5,
        acceleration: 1.05,
        friction: 0.97,
        gravity: 1.5,
        particles: 50,
        traceLength: 3,
        traceSpeed: 10,
        explosion: 5,
        intensity: 30,
        flickering: 50,
        lineStyle: 'round',
        hue: {
            min: 0,
            max: 360
        },
        delay: {
            min: 20,
            max: 40
        },
        rocketsPoint: {
            min: 50,
            max: 50
        },
        lineWidth: {
            explosion: {
                min: 1,
                max: 3
            },
            trace: {
                min: 1,
                max: 2
            }
        },
        brightness: {
            min: 50,
            max: 80
        },
        decay: {
            min: 0.015,
            max: 0.03
        },
        mouse: {
            click: false,
            move: false,
            max: 1
        }
    })
    // =============================================================Phân phối=============================================================
    let orders = [];
    let currentIndex = 0;
    const order_award = document.getElementById('order_award');

    async function updateCurrentLocation() {
        if (!navigator.geolocation) {
            notification('error', 'Trình duyệt không hỗ trợ truy cập vị trí.', trans.Loi);
            return false;
        }

        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(async (position) => {
                let locationData = {
                    permission: 'granted',
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy || null,
                    country_code: null,
                    country: null,
                    city: null,
                };

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${locationData.latitude}&lon=${locationData.longitude}&zoom=10`, {
                        headers: { 'Accept-Language': document.documentElement.lang || 'vi' }
                    });
                    const address = (await response.json()).address || {};
                    locationData.country_code = (address.country_code || '').toUpperCase();
                    locationData.country = address.country || null;
                    locationData.city = address.city || address.town || address.village || null;
                } catch (error) {
                    console.warn('Không thể xác định tên khu vực từ tọa độ.', error);
                }

                try {
                    const response = await fetch(route_update_location, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify(locationData)
                    });
                    const result = await response.json();
                    if (!response.ok || result.status !== 200) {
                        notification('error', result.message || 'Không thể lưu vị trí hiện tại.', trans.Loi);
                        resolve(false);
                        return;
                    }
                    resolve(true);
                } catch (error) {
                    notification('error', 'Không thể cập nhật vị trí hiện tại.', trans.Loi);
                    resolve(false);
                }
            }, async () => {
                try {
                    await fetch(route_update_location, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ permission: 'denied' })
                    });
                } catch (error) {
                    console.warn('Không thể lưu trạng thái quyền vị trí.', error);
                }
                notification('warning', 'Bạn cần cấp quyền vị trí để nhận đơn hàng.', trans.CanhBao);
                resolve(false);
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
        });
    }

    function loadOrders() {
        fetch(route_get_10_orders_next)
            .then(response => response.json())
            .then(data => {
                if (data.status === 404) {
                    notification('error', trans.coLoiXayRa);
                } else if (data.status === 200) {
                    orders = data.orders;
                    currentIndex = data.order_next;
                }
            });
    }

    window.onload = loadOrders;
    async function distribution() {
        spinner.hidden = false;
        if (!await updateCurrentLocation()) {
            spinner.hidden = true;
            return;
        }
        let fake_price = null;
        let is_high_value_order = false;
        let order_id = null;
        let frozen_id = null;
        let frozen_updated_at = null;
        const check_frozen = await check_frozen_order();
        const snapshotOrderAmount = finiteNumberOrNull(check_frozen.order_amount);
        const snapshotCommissionPercentage = finiteNumberOrNull(check_frozen.commission_percentage);
        const snapshotCommissionAmount = finiteNumberOrNull(check_frozen.commission_amount);
        // Backward compatibility: accept the legacy API field from older deployments.
        const responseIsHighValueOrder = check_frozen.is_high_value_order ?? check_frozen.is_order_special ?? false;
        let can_spin = false;
        if (check_frozen.status == 200 && check_frozen.is_frozen == true && responseIsHighValueOrder == false && check_frozen.is_new_order == false) {
            AppDialog.alert({
                title: trans.donHangChuaXuLy,
                text: check_frozen.message,
                icon: "warning",
                button: "OK",
                dangerMode: true,
            })
            spinner.hidden = true;
        } else if (check_frozen.status == 200 && check_frozen.is_frozen == true && responseIsHighValueOrder == true && check_frozen.is_new_order == false) {
            AppDialog.alert({
                title: trans.DonHangDangBiDongBang,
                text: check_frozen.message,
                icon: "warning",
                button: "OK",
                dangerMode: true,
            })
            spinner.hidden = true;
        } else if (check_frozen.status == 200 && check_frozen.is_frozen == true && responseIsHighValueOrder == true && check_frozen.is_new_order == true) {
            is_high_value_order = true;
            fake_price = check_frozen.custom_price;
            can_spin = true;
            order_id = check_frozen.order_id;
            frozen_id = check_frozen.frozen_id;
            frozen_updated_at = check_frozen.frozen_updated_at;
        } else if (check_frozen.status == 400) {
            AppDialog.alert({
                title: trans.HetLuotQuay,
                text: check_frozen.message,
                icon: "warning",
                button: "OK",
                dangerMode: true,
            })
            spinner.hidden = true;
        } else if (check_frozen.status == 200 && check_frozen.is_frozen == false && responseIsHighValueOrder == false && check_frozen.is_new_order == true) {
            can_spin = true;
            order_id = check_frozen.order_id;
            frozen_id = check_frozen.frozen_id;
            frozen_updated_at = check_frozen.frozen_updated_at;
        } else if (check_frozen.status == 500) {
            AppDialog.alert({
                title: check_frozen.message,
                text: check_frozen.message,
                icon: "warning",
                button: "OK",
                dangerMode: true,
            })
            spinner.hidden = true;
        }
        if (can_spin) {
            const btn_phan_phoi_ngay = document.getElementById('btn_phan_phoi_ngay');
            btn_phan_phoi_ngay.dataset.frozenId = frozen_id;
            btn_phan_phoi_ngay.dataset.isHvo = is_high_value_order ? '1' : '0';

            let order_details_time = document.getElementById('order_details_time');
            let order_details_img = document.getElementById('order_details_img');
            let order_details_name = document.getElementById('order_details_name');
            let order_details_price = document.getElementById('order_details_price');
            let order_details_quantity = document.getElementById('order_details_quantity');
            let order_details_end_value_total_price = document.getElementById('order_details_end_value_total_price');
            let order_details_end_value_price_rose = document.getElementById('order_details_end_value_price_rose');
            let order_details_end_value_total = document.getElementById('order_details_end_value_total');
            let selectedOrder = null;
            for (let order of orders) {
                if (order.id == order_id) {
                    selectedOrder = order;
                    break;
                }
            }
            if (selectedOrder === null) {
                loadOrders();
                notification('error', trans.QuayLaiNhaBan, trans.LoiDanhSachDonHang);
                return; // Dừng lại nếu không tìm thấy order
            }
            
            // Hiển thị loading modal cho tìm kiếm
            showSearchingModal();
            
            setTimeout(() => {
                spinner.hidden = true;
                closeSearchingModal();
                
                // Xử lý giao diện cho đơn thường hoặc đặc biệt
                const orderModal = document.getElementById('order');
                const headerNormal = document.querySelector('.order-header-normal');
                const headerHvo = document.querySelector('.order-header-hvo');
                const hvoTag = document.querySelector('.hvo-tag');
                const imageShine = document.querySelector('.image-shine');
                
                const bonusHvoRow = document.getElementById('bonus_hvo_row');
                
                if (!is_high_value_order) {
                    // Đơn thường - hiển thị header thường, ẩn header HVO
                    orderModal.classList.remove('high-value-order');
                    headerNormal.style.display = 'block';
                    headerHvo.style.display = 'none';
                    if (hvoTag) hvoTag.style.display = 'none';
                    if (imageShine) imageShine.style.display = 'none';
                    if (bonusHvoRow) bonusHvoRow.style.display = 'none';
                    const fallbackOrderAmount = selectedOrder.quantity * selectedOrder.price;
                    const orderAmount = snapshotOrderAmount ?? fallbackOrderAmount;
                    const commissionPercentage = snapshotCommissionPercentage
                        ?? finiteNumberOrNull(selectedOrder.commission_percentage ?? selectedOrder.commission_rate)
                        ?? 0;
                    const commissionAmount = snapshotCommissionAmount
                        ?? (orderAmount * (commissionPercentage / 100));
                    const order_details_price_formatted = format_currency(selectedOrder.price);
                    const order_details_end_value_total_price_formatted = format_currency(orderAmount);
                    const order_details_end_value_price_rose_formatted = format_currency(commissionAmount, 5, 5);
                    const order_details_end_value_total_formatted = format_currency(orderAmount + commissionAmount);

                    order_details_time.innerText = trans.ThoiGianDatPhanPhoi + formatDateTime(frozen_updated_at);
                    order_details_img.src = `/storage/${selectedOrder.image}`;
                    order_details_name.innerText = selectedOrder.name;
                    order_details_price.innerText = order_details_price_formatted;
                    order_details_quantity.innerText = "x" + selectedOrder.quantity;
                    order_details_end_value_total_price.innerText = order_details_end_value_total_price_formatted;
                    order_details_end_value_price_rose.innerText = order_details_end_value_price_rose_formatted;
                    order_details_end_value_total.innerText = order_details_end_value_total_formatted;
                } else {
                    // Đơn hàng giá trị cao - hiển thị header HVO, ẩn header thường
                    orderModal.classList.add('high-value-order');
                    headerNormal.style.display = 'none';
                    headerHvo.style.display = 'block';
                    if (hvoTag) hvoTag.style.display = 'flex';
                    if (imageShine) imageShine.style.display = 'block';
                    if (bonusHvoRow) bonusHvoRow.style.display = 'flex';
                    const orderAmount = snapshotOrderAmount ?? finiteNumberOrNull(fake_price) ?? 0;
                    const commissionPercentage = snapshotCommissionPercentage
                        ?? finiteNumberOrNull(selectedOrder.commission_percentage ?? selectedOrder.commission_rate)
                        ?? 0;
                    const commissionAmount = snapshotCommissionAmount
                        ?? (orderAmount * (commissionPercentage / 100));
                    const order_details_price_formatted = format_currency(orderAmount / selectedOrder.quantity);
                    const order_details_end_value_total_price_formatted = format_currency(orderAmount);
                    const order_details_end_value_price_rose_formatted = format_currency(commissionAmount, 5, 5);
                    const order_details_end_value_total_formatted = format_currency(orderAmount + commissionAmount);

                    order_details_time.innerText = trans.ThoiGianDatPhanPhoi + formatDateTime(frozen_updated_at);
                    order_details_img.src = `/storage/${selectedOrder.image}`;
                    order_details_name.innerText = selectedOrder.name;
                    order_details_price.innerText = order_details_price_formatted;
                    order_details_quantity.innerText = "x" + selectedOrder.quantity;
                    order_details_end_value_total_price.innerText = order_details_end_value_total_price_formatted;
                    order_details_end_value_price_rose.innerText = order_details_end_value_price_rose_formatted;
                    order_details_end_value_total.innerText = order_details_end_value_total_formatted;
                    fireworks.start();
                }
                order_award.hidden = false;
                // Dừng hiệu ứng pháo hoa sau 5 giây
                setTimeout(() => fireworks.stop(), 5000);
            }, 1000);
            currentIndex += 1;
        } else {
            spinner.hidden = true;
        }
    }
    window.distribution = distribution;

    const later = document.getElementById('later');
    later.addEventListener('click', function () {
        order_award.hidden = true;
    })
    // Kiểm tra đơn hàng trước khi quay
    function check_frozen_order() {
        return new Promise((resolve, reject) => {
            fetch(route_check_frozen_order)
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    return resolve(data);
                })
                .catch(error => {
                    console.log(error);
                    reject(error);
                })
        })
    }
    // ==================================================Xử lý bấm nút nhận đơn==================================================
    const btn_phan_phoi_ngay = document.getElementById('btn_phan_phoi_ngay');
    if (btn_phan_phoi_ngay) {
        btn_phan_phoi_ngay.addEventListener('click', async function () {
            spinner.hidden = false;
            let frozen_id = this.dataset.frozenId;
            
            // Gọi API nhận đơn
            let result = await handle_accept_order(frozen_id);
            
            if (result.status === 200) {
                // Hiển thị thông báo thành công
                notification('success', result.message, trans.ThanhCong);
                
                // Redirect đến trang order sau 1 giây
                setTimeout(() => {
                    window.location.href = result.redirect || route_order;
                }, 1000);
            } else {
                notification('error', result.message || 'Có lỗi xảy ra', trans.Loi);
                spinner.hidden = true;
            }
        });
    }
    
    // Hàm xử lý nhận đơn
    async function handle_accept_order(frozen_id) {
        return new Promise((resolve, reject) => {
            fetch(route_accept_order, {
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
                    resolve(data);
                })
                .catch(error => {
                    reject(error);
                });
        });
    }
    
    // Hàm hiển thị modal thành công với thiết kế đẹp
    function showSuccessModal(profit, totalAmount, commission, penaltyAmount = 0, isHighValueOrder = false) {
        // Tính tổng tiền hoàn nhập = Giá trị đơn hàng + Hoa hồng (chưa trừ phạt)
        const totalRefund = totalAmount + commission;
        
        // Lấy modal và cập nhật nội dung
        const modal = document.getElementById('successModalOverlay');
        document.getElementById('success_profit_amount').textContent = '+' + format_currency(profit, 5, 5);
        document.getElementById('success_total_amount').textContent = '' + format_currency(totalAmount, 4, 4);
        document.getElementById('success_commission').textContent = '+' + format_currency(commission, 5, 5);
        document.getElementById('success_total_refund').textContent = '+' + format_currency(totalRefund, 4, 4);
        document.getElementById('success_time').textContent = new Date().toLocaleString('vi-VN');
        
        // Hiển thị/ẩn dòng thưởng đơn hàng giá trị cao
        const bonusRow = document.getElementById('success_bonus_row');
        if (isHighValueOrder) {
            bonusRow.style.display = 'flex';
        } else {
            bonusRow.style.display = 'none';
        }
        
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
    
    // Hàm hiển thị loading modal cho tìm kiếm đơn hàng
    function showSearchingModal() {
        const modal = document.getElementById('searchingModalOverlay');
        
        // Reset trạng thái các step
        document.getElementById('search-step-1').className = 'loading-step active';
        document.getElementById('search-step-2').className = 'loading-step';
        document.getElementById('search-step-3').className = 'loading-step';
        document.getElementById('search-progress-bar').style.width = '0%';
        
        // Hiển thị modal
        modal.classList.add('show');
        
        setTimeout(() => {
            document.getElementById('search-progress-bar').style.width = '33%';
            
            setTimeout(() => {
                document.getElementById('search-step-1').classList.remove('active');
                document.getElementById('search-step-1').classList.add('completed');
                document.getElementById('search-step-2').classList.add('active');
                document.getElementById('search-progress-bar').style.width = '66%';
                
                setTimeout(() => {
                    document.getElementById('search-step-2').classList.remove('active');
                    document.getElementById('search-step-2').classList.add('completed');
                    document.getElementById('search-step-3').classList.add('active');
                    document.getElementById('search-progress-bar').style.width = '100%';
                }, 300);
            }, 300);
        }, 10);
    }
    
    // Hàm đóng loading modal tìm kiếm
    function closeSearchingModal() {
        const modal = document.getElementById('searchingModalOverlay');
        if (modal) {
            modal.classList.remove('show');
        }
    }
    
    // Hàm hiển thị loading modal cho phân phối
    function showDistributionModal() {
        const modal = document.getElementById('distributionModalOverlay');
        
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
    
    // Hàm đóng loading modal phân phối
    function closeDistributionModal() {
        const modal = document.getElementById('distributionModalOverlay');
        if (modal) {
            modal.classList.remove('show');
        }
    }
    
    // Hàm cập nhật tiến độ phân phối
    function updateProgress() {
        const currentElement = document.getElementById('progress-current');
        const totalElement = document.getElementById('progress-total');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        
        if (!currentElement || !totalElement || !progressBar || !progressText) {
            return; // Không có progress card (user chưa có rank)
        }
        
        // Lấy giá trị hiện tại
        let current = parseInt(currentElement.textContent);
        const total = parseInt(totalElement.textContent);
        
        // Tăng current lên 1
        current = Math.min(current + 1, total);
        
        // Tính phần trăm
        const percentage = total > 0 ? (current / total * 100) : 0;
        const remaining = Math.max(0, total - current);
        
        // Cập nhật UI
        currentElement.textContent = current;
        progressBar.style.width = percentage + '%';
        progressText.textContent = `Còn lại ${remaining} đơn hàng • ${percentage.toFixed(1)}% hoàn thành`;
        
        // Animation cho số current
        currentElement.style.transform = 'scale(1.2)';
        currentElement.style.color = '#10b981';
        setTimeout(() => {
            currentElement.style.transform = 'scale(1)';
            currentElement.style.color = '';
        }, 300);
    }
    
    // Hàm cập nhật stats cards sau khi phân phối thành công
    function updateStats(result) {
        // Cập nhật Tổng số dư
        const balanceElement = document.querySelector('.balance-card .stat-value');
        if (balanceElement && result.balance !== undefined) {
            balanceElement.textContent = format_currency(result.balance);
            // Animation
            balanceElement.style.transform = 'scale(1.1)';
            balanceElement.style.color = '#10b981';
            setTimeout(() => {
                balanceElement.style.transform = 'scale(1)';
                balanceElement.style.color = '';
            }, 500);
        }
        
        // Cập nhật Phân phối hôm nay
        const distributionElement = document.querySelector('.distribution-card .stat-value');
        if (distributionElement && result.distribution_today !== undefined) {
            distributionElement.textContent = '+' + result.distribution_today;
            // Animation
            distributionElement.style.transform = 'scale(1.1)';
            distributionElement.style.color = '#3b82f6';
            setTimeout(() => {
                distributionElement.style.transform = 'scale(1)';
                distributionElement.style.color = '';
            }, 500);
        }
        
        // Cập nhật Chiết khấu hôm nay
        const commissionElement = document.querySelector('.commission-card .stat-value');
        if (commissionElement && result.todays_discount !== undefined) {
            commissionElement.textContent = format_currency(result.todays_discount);
            // Animation
            commissionElement.style.transform = 'scale(1.1)';
            commissionElement.style.color = '#f59e0b';
            setTimeout(() => {
                commissionElement.style.transform = 'scale(1)';
                commissionElement.style.color = '';
            }, 500);
        }
        
        // Cập nhật Số dư đóng băng
        const frozenElement = document.querySelector('.frozen-card .stat-value');
        if (frozenElement && result.frozen_price !== undefined) {
            frozenElement.textContent = format_currency(result.frozen_price);
            // Animation
            frozenElement.style.transform = 'scale(1.1)';
            frozenElement.style.color = '#8b5cf6';
            setTimeout(() => {
                frozenElement.style.transform = 'scale(1)';
                frozenElement.style.color = '';
            }, 500);
        }
    }
    
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

})
