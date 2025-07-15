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
            notification('warning', 'Đang tìm kiếm đơn hàng...', 'Waiting!');
            setTimeout(() => {
                spinner.hidden = true;
                notification('success', 'Tìm kiếm đơn hàng thành công!', 'Successfully!', 2000);
                if (!is_order_special) {
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
            setTimeout(() => {
                notification('warning', "", trans.ChoXuLy);
                setTimeout(() => {
                    notification('warning', "", trans.DangPhanPhoi);
                    setTimeout(() => {
                        notification('success', result.message, trans.ThanhCong);
                        swal({
                            title: trans.PhanPhoiThanhCong2,
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
                    }, 1000);
                }, 1000);
            }, 0);
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