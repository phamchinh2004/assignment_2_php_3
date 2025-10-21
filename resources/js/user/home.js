document.addEventListener('DOMContentLoaded', function () {
    // Chỉnh nút theo kích cỡ màn hình
    if (window.innerWidth <= 768) {
        document.querySelectorAll('.btn').forEach(btn => {
            btn.classList.add('btn-sm');
        });
    }
    // Tắt focus cho modal
    document.getElementById('bankLinkModal').addEventListener('shown.bs.modal', function () {
        const modal = bootstrap.Modal.getInstance(this);
        if (modal && modal._focustrap) {
            modal._focustrap.deactivate();
        }
    });
    // Tạo slimselect
    const bankSelect = new SlimSelect({
        select: '#bankName',
        settings: {
            searchPlaceholder: 'Tìm kiếm ngân hàng...',
            showSearch: true
        },
        events: {
            afterOpen: () => {
                setTimeout(() => {
                    const searchInput = document.querySelector('.ss-search input');
                    if (searchInput) {
                        searchInput.focus();
                    }
                }, 50);
            }
        }
    });
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
    
    // Vòng quay may mắn đã được chuyển sang lucky-wheel.js

    // Code phân phối đơn hàng đã được xóa - chức năng quay vòng quay giải thưởng đã được chuyển sang lucky-wheel.js
    //==================================================Các thành viên khác cũng phân phối==================================================
    const phones = ['097', '098', '016', '093', '090', '091'];
    const getRandomPhone = () => {
        const prefix = phones[Math.floor(Math.random() * phones.length)];
        const suffix = Math.floor(100 + Math.random() * 900); // 3 chữ số
        return `${prefix}**${suffix}`;
    };

    const getRandomAmount = () => {
        const amounts = [10, 25, 50, 75, 100, 150, 200, 250, 300, 400, 500, 750, 1000];
        const amount = amounts[Math.floor(Math.random() * amounts.length)];
        return `$${amount}`;
    };

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
        <div class="distribution-item mb-3 p-3 rounded-3 shadow-sm border-0" style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border-left: 4px solid #FF9500 !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="user-avatar me-3">
                        <div class="avatar-circle" style="width: 40px; height: 40px; background: linear-gradient(135deg, #FF9500 0%, #FF8C00 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                            ${phone.charAt(3)}
                        </div>
                    </div>
                    <div>
                        <div class="user-phone fw-bold text-dark mb-1" style="font-size: 14px;">${phone}</div>
                        <div class="success-text text-muted" style="font-size: 12px;">${trans.successText}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-end me-3">
                        <div class="amount-value fw-bold text-success mb-1" style="font-size: 16px; color: #FF9500 !important;">${amount}</div>
                        <div class="time-ago text-muted" style="font-size: 11px;">${time}</div>
                    </div>
                    <div class="success-icon" style="width: 30px; height: 30px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">
                        ✓
                    </div>
                </div>
            </div>
        </div>
    `;
    };

    const listContainer = document.getElementById('distribution-list');

    // Thêm CSS cho animation
    const style = document.createElement('style');
    style.textContent = `
        .distribution-item {
            transition: all 0.3s ease;
            animation: slideInFromRight 0.5s ease-out;
        }
        
        .distribution-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 149, 0, 0.15) !important;
        }
        
        @keyframes slideInFromRight {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .avatar-circle {
            transition: all 0.3s ease;
        }
        
        .distribution-item:hover .avatar-circle {
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(255, 149, 0, 0.4);
        }
        
        .success-icon {
            transition: all 0.3s ease;
        }
        
        .distribution-item:hover .success-icon {
            transform: scale(1.1) rotate(360deg);
        }
        
        .amount-value {
            transition: all 0.3s ease;
        }
        
        .distribution-item:hover .amount-value {
            transform: scale(1.05);
            text-shadow: 1px 1px 2px rgba(255, 149, 0, 0.3);
        }
    `;
    document.head.appendChild(style);

    setInterval(() => {
        const items = [];
        for (let i = 0; i < 4; i++) {
            items.push(generateItem());
        }
        
        // Thêm animation stagger cho các items
        listContainer.innerHTML = items.join('');
        
        // Thêm animation delay cho từng item
        const distributionItems = listContainer.querySelectorAll('.distribution-item');
        distributionItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
        });
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
        const dontShowAgain = document.getElementById('dontShowAgain');
        
        // Kiểm tra nếu người dùng đã tích vào checkbox
        if (dontShowAgain && dontShowAgain.checked) {
            localStorage.setItem('notificationDismissed', 'true');
        }
        
        overlay.classList.remove('show');
        notificationShown = false;
    }

    // Xử lý sự kiện tham gia
    window.participateEvent = function () {
        closeNotification();
    }

    // Phát âm thanh thông báo (tùy chọn)

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

    // Xử lý khi người dùng click vào checkbox
    document.addEventListener('DOMContentLoaded', function() {
        const dontShowAgain = document.getElementById('dontShowAgain');
        if (dontShowAgain) {
            dontShowAgain.addEventListener('change', function() {
                // Có thể thêm logic bổ sung ở đây nếu cần
                console.log('Checkbox changed:', this.checked);
            });
        }
    });

    // Hàm để reset trạng thái thông báo (để test hoặc admin sử dụng)
    window.resetNotificationStatus = function() {
        localStorage.removeItem('notificationDismissed');
        console.log('Notification status has been reset. The notification will show again on next page load.');
    };

    // Tự động hiển thị thông báo khi trang được tải (giả lập đăng nhập)
    window.addEventListener('load', function () {
        // Giả lập việc kiểm tra trạng thái đăng nhập
        setTimeout(function () {
            // Trong thực tế, bạn sẽ kiểm tra từ server hoặc session
            const isLoggedIn = true; // Giả lập đã đăng nhập
            const notificationDismissed = localStorage.getItem('notificationDismissed');

            if (isLoggedIn && !notificationShown && !notificationDismissed) {
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
        console.log(username_bank_input.value);
        if (!username_bank_input.value) {
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

// Testimonials - Simple & Stable
let currentTestimonial = 0;
const totalTestimonials = 6;

function showTestimonial(index) {
    const slides = document.querySelectorAll('.testimonial-slide');
    const dots = document.querySelectorAll('.testimonial-dots .dot');
    
    // Add slide-out animation to current slide
    const currentSlide = slides[currentTestimonial];
    if (currentSlide && currentSlide.classList.contains('active')) {
        currentSlide.classList.add('slide-out');
        
        // Wait for slide-out animation to complete
        setTimeout(() => {
            currentSlide.classList.remove('active', 'slide-out');
            
            // Show new slide
            if (slides[index]) {
                slides[index].classList.add('active');
            }
            
            // Update dots
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
            
            currentTestimonial = index;
        }, 400); // Match CSS transition duration
    } else {
        // Direct transition for first load or immediate changes
        slides.forEach(slide => slide.classList.remove('active', 'slide-out'));
        
        if (slides[index]) {
            slides[index].classList.add('active');
        }
        
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
        
        currentTestimonial = index;
    }
}
window.showNextTestimonial = function () {
    const nextIndex = (currentTestimonial + 1) % totalTestimonials;
    showTestimonial(nextIndex);
}
window.showPrevTestimonial = function () {
    const prevIndex = (currentTestimonial - 1 + totalTestimonials) % totalTestimonials;
    showTestimonial(prevIndex);
}

// Auto-play functionality
let testimonialInterval;

function startTestimonialAutoPlay() {
    testimonialInterval = setInterval(() => {
        showNextTestimonial();
    }, 5000);
}

function stopTestimonialAutoPlay() {
    clearInterval(testimonialInterval);
}

// Initialize testimonials
document.addEventListener('DOMContentLoaded', function() {
    // Show first testimonial
    showTestimonial(0);
    
    // Start auto-play
    startTestimonialAutoPlay();
    
    // Pause auto-play on hover
    const testimonialsSection = document.querySelector('.testimonials-section');
    if (testimonialsSection) {
        testimonialsSection.addEventListener('mouseenter', stopTestimonialAutoPlay);
        testimonialsSection.addEventListener('mouseleave', startTestimonialAutoPlay);
    }
    
    // Touch/swipe support
    let startX = 0;
    let endX = 0;
    
    const wrapper = document.querySelector('.testimonials-wrapper');
    if (wrapper) {
        wrapper.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
        });
        
        wrapper.addEventListener('touchend', function(e) {
            endX = e.changedTouches[0].clientX;
            const diff = startX - endX;
            
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    showNextTestimonial();
                } else {
                    showPrevTestimonial();
                }
            }
        });
    }
});