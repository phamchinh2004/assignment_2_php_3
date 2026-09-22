import Modal from 'bootstrap/js/dist/modal';

function setupAccountSecurityModals() {
    const transactionPasswordModalElement = document.getElementById('changeTransactionPasswordModal');
    const resetTransactionPasswordModalElement = document.getElementById('resetTransactionPasswordModal');
    const resetTransactionPasswordTrigger = document.querySelector('[data-reset-transaction-password]');

    if (!resetTransactionPasswordTrigger || !resetTransactionPasswordModalElement) return;

    const cleanupModalState = () => {
        if (document.querySelector('.modal.show')) return;

        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    };

    resetTransactionPasswordTrigger.addEventListener('click', () => {
        const resetModal = Modal.getOrCreateInstance(resetTransactionPasswordModalElement);

        if (!transactionPasswordModalElement) {
            resetModal.show();
            return;
        }

        const transactionModal = Modal.getOrCreateInstance(transactionPasswordModalElement);
        const openResetModal = () => resetModal.show();

        if (transactionPasswordModalElement.classList.contains('show')) {
            transactionPasswordModalElement.addEventListener('hidden.bs.modal', openResetModal, { once: true });
            transactionModal.hide();
            return;
        }

        resetModal.show();
    });

    resetTransactionPasswordModalElement.addEventListener('hidden.bs.modal', () => {
        window.setTimeout(cleanupModalState, 0);
    });
}

// Show/Hide Password
document.addEventListener('DOMContentLoaded', function () {
    setupAccountSecurityModals();
    const password_login = document.getElementById('password_login');
    const username_login = document.getElementById('username_login');
    const remember_checkbox = document.getElementById('remember_password');
    const show_password_login = document.getElementById('show_password_login');
    const hide_password_login = document.getElementById('hide_password_login');

    const password_register = document.getElementById('password_register');
    const show_password_register = document.getElementById('show_password_register');
    const hide_password_register = document.getElementById('hide_password_register');

    const repassword_register = document.getElementById('repassword_register');
    const show_repassword_register = document.getElementById('show_repassword_register');
    const hide_repassword_register = document.getElementById('hide_repassword_register');

    // Xóa dữ liệu ghi nhớ cũ từng lưu mật khẩu trong localStorage.
    localStorage.removeItem('remember_password');
    localStorage.removeItem('username');
    localStorage.removeItem('password');

    function show_hide_input(status1, status2, type, value) {
        if (status1 && status2 && type) {
            status1.addEventListener('click', function () {
                type.type = value;
                status1.hidden = true;
                status2.hidden = false;
            });
        }
    }

    show_hide_input(show_password_login, hide_password_login, password_login, 'text');
    show_hide_input(hide_password_login, show_password_login, password_login, 'password');

    show_hide_input(show_password_register, hide_password_register, password_register, 'text');
    show_hide_input(hide_password_register, show_password_register, password_register, 'password');

    show_hide_input(show_repassword_register, hide_repassword_register, repassword_register, 'text');
    show_hide_input(hide_repassword_register, show_repassword_register, repassword_register, 'password');


    //Xử lý đăng ký tài khoản
    const register_btn = document.getElementById('register');
    if (register_btn) {
        register_btn.addEventListener('click', async function () {
            check_and_submit_register();
        })
    }

    async function requestRegistrationLocation(showError = true) {
        const permission = document.getElementById('location_permission');
        const latitude = document.getElementById('location_latitude');
        const longitude = document.getElementById('location_longitude');
        if (!permission || !latitude || !longitude) {
            return true;
        }

        if (!navigator.geolocation) {
            permission.value = 'denied';
            latitude.value = '';
            longitude.value = '';
            document.getElementById('location_accuracy').value = '';
            document.getElementById('location_country_code').value = '';
            document.getElementById('location_country').value = '';
            document.getElementById('location_city').value = '';
            return true;
        }

        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(async (position) => {
                permission.value = 'granted';
                latitude.value = position.coords.latitude;
                longitude.value = position.coords.longitude;
                document.getElementById('location_accuracy').value = position.coords.accuracy || '';

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${position.coords.latitude}&lon=${position.coords.longitude}&zoom=10`, {
                        headers: { 'Accept-Language': document.documentElement.lang || 'vi' }
                    });
                    const address = (await response.json()).address || {};
                    document.getElementById('location_country_code').value = (address.country_code || '').toUpperCase();
                    document.getElementById('location_country').value = address.country || '';
                    document.getElementById('location_city').value = address.city || address.town || address.village || '';
                } catch (error) {
                    console.warn('Không thể xác định tên khu vực từ tọa độ.', error);
                }
                resolve(true);
            }, () => {
                permission.value = 'denied';
                latitude.value = '';
                longitude.value = '';
                document.getElementById('location_accuracy').value = '';
                document.getElementById('location_country_code').value = '';
                document.getElementById('location_country').value = '';
                document.getElementById('location_city').value = '';
                resolve(true);
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
        });
    }

    if (register_btn) requestRegistrationLocation(false);
    if (repassword_register) {
        repassword_register.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                check_and_submit_register();
            }
        })
    }
    async function check_and_submit_register() {
        spinner.hidden = false;
        let valid = true;
        let username_register = document.getElementById('username_register');
        let phone_register = document.getElementById('phone_register');
        let email_register = document.getElementById('email_register');
        let password_register = document.getElementById('password_register');
        let repassword_register = document.getElementById('repassword_register');
        let referral_code_register = document.getElementById('referral_code_register');
        let accept_terms = document.getElementById('accept_terms');
        if (document.getElementById('location_permission')?.value !== 'granted' ||
            !document.getElementById('location_latitude')?.value ||
            !document.getElementById('location_longitude')?.value) {
            await requestRegistrationLocation(true);
        }
        if (!accept_terms.checked) {
            notification('warning', 'Vui lòng chấp nhận điều khoản của chúng tôi!', 'Cảnh báo!');
            valid = false;
            spinner.hidden = true;
            return;
        }
        if (username_register.value && phone_register.value && password_register.value && repassword_register.value && email_register.value) {
            if (referral_code_register.value) {
                let check_user_existed = await check_referral_code(referral_code_register.value);
                if (!check_user_existed) {
                    notification('warning', 'Mã mời không hợp lệ, vui lòng thử lại!', 'Cảnh báo!');
                    valid = false;
                    spinner.hidden = true;
                    return;
                }
            }
            let check_email_existed = await check_email(email_register.value);
            if (!check_email_existed) {
                notification('warning', 'Email đã tồn tại, vui lòng thử lại!', 'Cảnh báo!');
                valid = false;
                spinner.hidden = true;
                return;
            }

            if (username_register.value.length < 6 || username_register.value.length > 255) {
                notification('warning', 'Tên đăng nhập tối thiểu 6 ký tự và tối đa 255 ký tự!', 'Cảnh báo!');
                valid = false;
                spinner.hidden = true;
                return;
            }
            function isValidPhone(phone) {
                const re = /^(0|\+84)[0-9]{9,10}$/;
                return re.test(phone);
            }
            if (!isValidPhone(phone_register.value)) {
                notification('warning', 'Số điện thoại không hợp lệ', 'Cảnh báo!');
                valid = false;
                spinner.hidden = true;
                return;
            }
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            if (!isValidEmail(email_register.value)) {
                notification('warning', 'Email không hợp lệ', 'Cảnh báo!');
                valid = false;
                spinner.hidden = true;
                return;
            }
            if (password_register.value.length < 6) {
                notification('warning', 'Mật khẩu phải từ 6 ký tự trở lên!', 'Cảnh báo!');
                valid = false;
                spinner.hidden = true;
                return;
            }
            if (password_register.value !== repassword_register.value) {
                notification('warning', 'Mật khẩu không khớp!', 'Cảnh báo!');
                valid = false;
                spinner.hidden = true;
                return;
            }
            form_register.submit();
        } else {
            notification('warning', 'Vui lòng điền đầy đủ thông tin!', 'Cảnh báo!');
        }
        spinner.hidden = true;
    }
    function check_referral_code(referral_code) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: route_check_referral_code,
                method: "POST",
                data: {
                    _token: csrf,
                    referral_code: referral_code
                },
                success: function (response) {
                    if (response.success == false) {
                        resolve(false);
                    } else {
                        resolve(true);
                    }
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    notification('error', 'Không thể kiểm tra dữ liệu!', 'Lỗi');
                    reject();
                }
            });
        })
    }
    function check_email(email) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: route_check_email,
                method: "POST",
                data: {
                    _token: csrf,
                    email: email
                },
                success: function (response) {
                    if (response.success == false) {
                        resolve(true);
                    } else {
                        resolve(false);
                    }
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    notification('error', 'Không thể kiểm tra dữ liệu!', 'Lỗi');
                    reject();
                }
            });
        })
    }

    //Xử lý đăng nhập tài khoản
    const form_login = document.getElementById('form_login');
    const login_btn = document.getElementById('login');
    let login_submitting = false;
    if (form_login) {
        form_login.addEventListener('submit', function (event) {
            event.preventDefault();
            check_and_submit_login();
        });
    }
    async function check_and_submit_login() {
        if (login_submitting) {
            return;
        }
        if (!username_login.value || !password_login.value) {
            notification('warning', 'Vui lòng điền đầy đủ thông tin!', 'Cảnh báo!');
            return;
        }

        login_submitting = true;
        login_btn.disabled = true;
        spinner.hidden = false;
        let navigating = false;
        try {
            const check_username_existed = await check_username(username_login.value);
            if (check_username_existed.refresh) {
                localStorage.removeItem("remember_password");
                localStorage.removeItem("username");
                localStorage.removeItem("password");
                notification('warning', check_username_existed.message, 'Cảnh báo!');
                return;
            }
            form_login.submit();
            navigating = true;
        } catch (error) {
            if (error?.status === 419) {
                // GET lại trang để kiểm tra phiên hiện tại và lấy token mới.
                window.location.replace(form_login.action);
                navigating = true;
                return;
            }
            notification('error', 'Không thể đăng nhập. Vui lòng thử lại!', 'Lỗi');
        } finally {
            if (!navigating) {
                login_submitting = false;
                login_btn.disabled = false;
                spinner.hidden = true;
            }
        }
    }
    function check_username(username) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: route_check_username,
                method: "POST",
                data: {
                    _token: csrf,
                    username: username
                },
                success: function (response) {
                    resolve(response);
                },
                error: function (xhr) {
                    reject(xhr);
                }
            });
        })
    }
});

window.log_out = function () {
    const form_logout = document.getElementById('form_logout');
    if (form_logout) {
        form_logout.submit();
    }
}
// ========================================================Đổi mật khẩu========================================================
window.change_password = async function () {
    spinner.hidden = false;
    const form_change_password = document.getElementById("form_change_password");
    const form = new FormData(form_change_password);
    if (!form_change_password.checkValidity()) {
        form_change_password.classList.add('was-validated');
        spinner.hidden = true;
        return;
    }
    const present_password = form.get('present_password');
    const password = form.get('new_password');
    const confirmPassword = form.get('confirm_new_password');
    if (password.length < 6 || password.length > 255) {
        notification('error', 'Mật khẩu phải từ 6 ký tự đến 255 ký tự!', 'Sai định dạng!');
        spinner.hidden = true;
        return;
    }
    if (password !== confirmPassword) {
        notification('error', 'Mật khẩu mới không khớp, vui lòng thử lại!', 'Không khớp mật khẩu!');
        spinner.hidden = true;
        return;
    }
    const result = await request_change_password(present_password, password, confirmPassword);
    if (result.status === 200) {
        notification('success', result.message, 'Successfully!');
        // Close modal
        const modalElement = document.getElementById('changePasswordModal');
        let modal = Modal.getInstance(modalElement);
        if (!modal) {
            modal = new Modal(modalElement);
        }
        modal.hide();

        setTimeout(() => {
            form_change_password.reset();
            form_change_password.classList.remove('was-validated');
        }, 300);
        spinner.hidden = true;
    } else {
        notification('error', result.message, 'Error!');
        spinner.hidden = true;
    }
}
function request_change_password(present_password, new_password, confirm_password) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: route_change_password,
            method: "POST",
            data: {
                _token: csrf,
                present_password: present_password,
                new_password: new_password,
                confirm_new_password: confirm_password,
            },
            success: function (response) {
                if (response.success == false) {
                    resolve(response);
                } else {
                    resolve(response);
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                notification('error', 'Không thể kiểm tra dữ liệu!', 'Lỗi');
                reject();
            }
        });
    })
}
// ========================================================Đổi mật khẩu giao dịch========================================================
window.change_transaction_password = async function () {
    spinner.hidden = false;
    const form_change_transaction_password = document.getElementById("form_change_transaction_password");
    const form = new FormData(form_change_transaction_password);
    if (!form_change_transaction_password.checkValidity()) {
        form_change_transaction_password.classList.add('was-validated');
        spinner.hidden = true;
        return;
    }
    const present_transaction_password = form.get('present_transaction_password');
    const password = form.get('new_transaction_password');
    const confirmPassword = form.get('confirm_new_transaction_password');
    if (password.length < 6 || password.length > 255) {
        notification('error', 'Mật khẩu phải từ 6 ký tự đến 255 ký tự!', 'Sai định dạng!');
        spinner.hidden = true;
        return;
    }
    if (password !== confirmPassword) {
        notification('error', 'Mật khẩu mới không khớp, vui lòng thử lại!', 'Không khớp mật khẩu!');
        spinner.hidden = true;
        return;
    }
    const result = await change_transaction_password(present_transaction_password, password);
    if (result.status === 200) {
        notification('success', result.message, 'Successfully!');
        // Close modal
        const modalElement = document.getElementById('changeTransactionPasswordModal');
        let modal = Modal.getInstance(modalElement);
        if (!modal) {
            modal = new Modal(modalElement);
        }
        modal.hide();

        setTimeout(() => {
            form_change_transaction_password.reset();
            form_change_transaction_password.classList.remove('was-validated');
        }, 300);
        spinner.hidden = true;
    } else {
        notification('error', result.message, 'Error!');
        spinner.hidden = true;
    }
}
function change_transaction_password(present_password, new_password) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: route_change_transaction_password,
            method: "POST",
            data: {
                _token: csrf,
                present_transaction_password: present_password,
                new_transaction_password: new_password,
            },
            success: function (response) {
                if (response.success == false) {
                    resolve(response);
                } else {
                    resolve(response);
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                notification('error', 'Không thể kiểm tra dữ liệu!', 'Lỗi');
                reject();
            }
        });
    })
}
// ========================================================Cấp lại mật khẩu giao dịch========================================================
window.reset_transaction_password = async function () {
    spinner.hidden = false;
    const form_reset_transaction_password = document.getElementById("form_reset_transaction_password");
    const form = new FormData(form_reset_transaction_password);
    if (!form_reset_transaction_password.checkValidity()) {
        form_reset_transaction_password.classList.add('was-validated');
        spinner.hidden = true;
        return;
    }
    const present_login_password = form.get('present_login_password');

    const result = await reset_transaction_password(present_login_password);
    if (result.status === 200) {
        notification('success', result.message, 'Successfully!');
        AppDialog.alert({
            title: 'Mật khẩu giao dịch mới',
            text: "Mật khẩu giao dịch mới là: " + result.data + ", bạn nên đổi nó ngay bây giờ!",
            icon: 'success',
        });
        const modalElement = document.getElementById('resetTransactionPasswordModal');
        let modal = Modal.getInstance(modalElement);
        if (!modal) {
            modal = new Modal(modalElement);
        }
        modal.hide();

        setTimeout(() => {
            form_reset_transaction_password.reset();
            form_reset_transaction_password.classList.remove('was-validated');
        }, 300);
        spinner.hidden = true;
    } else {
        notification('error', result.message, 'Error!');
        spinner.hidden = true;
    }
}
function reset_transaction_password(present_login_password) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: route_reset_transaction_password,
            method: "POST",
            data: {
                _token: csrf,
                present_login_password: present_login_password,
            },
            success: function (response) {
                if (response.success == false) {
                    resolve(response);
                } else {
                    resolve(response);
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                notification('error', 'Không thể kiểm tra dữ liệu!', 'Lỗi');
                reject();
            }
        });
    })
}
