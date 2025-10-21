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
        let fake_price = null;
        let is_order_special = false;
        let order_id = null;
        let frozen_id = null;
        const check_frozen = await check_frozen_order();
        let can_spin = false;
        if (check_frozen.status == 200 && check_frozen.is_frozen == true && check_frozen.is_order_special == false && check_frozen.is_new_order == false) {
            swal({
                title: trans.donHangChuaXuLy,
                text: check_frozen.message,
                icon: "warning",
                button: "OK",
                dangerMode: true,
            })
            spinner.hidden = true;
        } else if (check_frozen.status == 200 && check_frozen.is_frozen == true && check_frozen.is_order_special == true && check_frozen.is_new_order == false) {
            swal({
                title: trans.DonHangDangBiDongBang,
                text: check_frozen.message,
                icon: "warning",
                button: "OK",
                dangerMode: true,
            })
            spinner.hidden = true;
        } else if (check_frozen.status == 200 && check_frozen.is_frozen == true && check_frozen.is_order_special == true && check_frozen.is_new_order == true) {
            is_order_special = true;
            fake_price = check_frozen.custom_price;
            can_spin = true;
            order_id = check_frozen.order_id;
            frozen_id = check_frozen.frozen_id;
        } else if (check_frozen.status == 400) {
            swal({
                title: trans.HetLuotQuay,
                text: check_frozen.message,
                icon: "warning",
                button: "OK",
                dangerMode: true,
            })
            spinner.hidden = true;
        } else if (check_frozen.status == 200 && check_frozen.is_frozen == false && check_frozen.is_order_special == false && check_frozen.is_new_order == true) {
            can_spin = true;
            order_id = check_frozen.order_id;
            frozen_id = check_frozen.frozen_id;
        } else if (check_frozen.status == 500) {
            swal({
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
            }
            
            // Hiển thị loading modal cho tìm kiếm
            showSearchingModal();
            
            setTimeout(() => {
                spinner.hidden = true;
                closeSearchingModal();
                
                // Xử lý giao diện cho đơn thường hoặc đặc biệt
                const orderModal = document.getElementById('order');
                const headerNormal = document.querySelector('.order-header-normal');
                const headerSpecial = document.querySelector('.order-header-special');
                const specialTag = document.querySelector('.special-tag');
                const imageShine = document.querySelector('.image-shine');
                
                if (!is_order_special) {
                    // Đơn thường - hiển thị header thường, ẩn header đặc biệt
                    orderModal.classList.remove('special-order');
                    headerNormal.style.display = 'block';
                    headerSpecial.style.display = 'none';
                    if (specialTag) specialTag.style.display = 'none';
                    if (imageShine) imageShine.style.display = 'none';
                    const order_details_price_formatted = format_currency(selectedOrder.price);
                    const order_details_end_value_total_price_formatted = format_currency(selectedOrder.quantity * selectedOrder.price);
                    const order_details_end_value_price_rose_formatted = format_currency((selectedOrder.quantity * selectedOrder.price) * selectedOrder.commission_percentage);
                    const order_details_end_value_total_formatted = format_currency((selectedOrder.quantity * selectedOrder.price) + ((selectedOrder.quantity * selectedOrder.price) * selectedOrder.commission_percentage));

                    const randomTime = getRandomTimeYesterday();
                    const formattedTime = randomTime.toLocaleString();

                    order_details_time.innerText = trans.ThoiGianDatPhanPhoi + formattedTime;
                    order_details_img.src = `/storage/${selectedOrder.image}`;
                    order_details_name.innerText = selectedOrder.name;
                    order_details_price.innerText = order_details_price_formatted;
                    order_details_quantity.innerText = "x" + selectedOrder.quantity;
                    order_details_end_value_total_price.innerText = order_details_end_value_total_price_formatted;
                    order_details_end_value_price_rose.innerText = order_details_end_value_price_rose_formatted;
                    order_details_end_value_total.innerText = order_details_end_value_total_formatted;
                } else {
                    // Đơn đặc biệt - hiển thị header đặc biệt, ẩn header thường
                    orderModal.classList.add('special-order');
                    headerNormal.style.display = 'none';
                    headerSpecial.style.display = 'block';
                    if (specialTag) specialTag.style.display = 'flex';
                    if (imageShine) imageShine.style.display = 'block';
                    const order_details_price_formatted = format_currency(fake_price / selectedOrder.quantity);
                    const order_details_end_value_total_price_formatted = format_currency(fake_price);
                    const order_details_end_value_price_rose_formatted = format_currency(fake_price * selectedOrder.commission_percentage);
                    const order_details_end_value_total_formatted = format_currency(fake_price + (fake_price * selectedOrder.commission_percentage));

                    const randomTime = getRandomTimeYesterday();
                    const formattedTime = randomTime.toLocaleString();

                    order_details_time.innerText = trans.ThoiGianDatPhanPhoi + formattedTime;
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
                    return resolve(data);
                })
                .catch(error => {
                    console.log(error);
                    reject(error);
                })
        })
    }
    // Random thời gian phân phối
    function getRandomTimeYesterday() {
        const now = new Date();

        // Lấy ngày hôm qua
        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        yesterday.setHours(0, 0, 0, 0); // Đặt thời gian bắt đầu là 00:00:00

        // Thời gian cuối cùng trong ngày hôm qua (23:59:59)
        const endOfYesterday = new Date(yesterday);
        endOfYesterday.setHours(23, 59, 59, 999);

        // Random thời gian giữa khoảng này
        const randomTimestamp = Math.floor(
            Math.random() * (endOfYesterday.getTime() - yesterday.getTime()) + yesterday.getTime()
        );

        return new Date(randomTimestamp);
    }
    // ==================================================Xử lý bấm nút phân phối==================================================
    const btn_phan_phoi_ngay = document.getElementById('btn_phan_phoi_ngay');
    btn_phan_phoi_ngay.addEventListener('click', async function () {
        spinner.hidden = false;
        let frozen_id = this.dataset.frozenId;
        let result = await handle_distribution(frozen_id);
        if (result.status === 200) {
            const profit = result.profit;
            const totalAmount = result.total_amount || 0;
            const commission = result.commission || 0;
            const penaltyAmount = result.penalty_amount || 0;
            
            // Hiển thị loading modal phân phối
            showDistributionModal();
            
            // Sau 1.5 giây đóng loading và hiển thị kết quả
            setTimeout(() => {
                closeDistributionModal();
                setTimeout(() => {
                    showSuccessModal(profit, totalAmount, commission, penaltyAmount);
                    // Cập nhật tiến độ phân phối
                    updateProgress();
                }, 300);
            }, 1500);
        } else if (result.status === 409) {
            notification('warning', result.message, trans.CanhBao);
        } else {
            notification('error', result.message, trans.Loi);
        }
        setTimeout(() => {
            order_award.hidden = true;
            spinner.hidden = true;
        }, 2000);
    })
    
    // Hàm hiển thị modal thành công với thiết kế đẹp
    function showSuccessModal(profit, totalAmount, commission, penaltyAmount = 0) {
        // Tính tổng tiền hoàn nhập = Giá trị đơn hàng + Hoa hồng (chưa trừ phạt)
        const totalRefund = totalAmount + commission;
        
        // Lấy modal và cập nhật nội dung
        const modal = document.getElementById('successModalOverlay');
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