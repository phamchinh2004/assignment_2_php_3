// Show/Hide Password
document.addEventListener('DOMContentLoaded', function () {
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


    if (localStorage.getItem("remember_password") && localStorage.getItem("remember_password") === "true" && username_login) {
        username_login.value = localStorage.getItem("username") || "";
        password_login.value = localStorage.getItem("password") || "";
        remember_checkbox.checked = true;
        // const form_login = document.getElementById('form_login');
        // form_login.submit();
    }

    //Xử lý đăng ký tài khoản
    const register_btn = document.getElementById('register');
    if (register_btn) {
        register_btn.addEventListener('click', async function () {
            check_and_submit_register();
        })
    }
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
        let password_register = document.getElementById('password_register');
        let repassword_register = document.getElementById('repassword_register');
        let referral_code_register = document.getElementById('referral_code_register');
        let accept_terms = document.getElementById('accept_terms');
        if (!accept_terms.checked) {
            notification('warning', 'Vui lòng chấp nhận điều khoản của chúng tôi!', 'Cảnh báo!');
            valid = false;
            spinner.hidden = true;
            return;
        }
        if (username_register.value && phone_register.value && password_register.value && repassword_register.value) {
            if (referral_code_register.value) {
                let check_user_existed = await check_referral_code(referral_code_register.value);
                if (!check_user_existed) {
                    notification('warning', 'Mã mời không hợp lệ, vui lòng thử lại!', 'Cảnh báo!');
                    valid = false;
                    spinner.hidden = true;
                    return;
                }
            }
            if (username_register.value.length < 6) {
                notification('warning', 'Tên đăng nhập phải từ 6 ký tự trở lên!', 'Cảnh báo!');
                valid = false;
                spinner.hidden = true;
                return;
            }
            if (phone_register.value.length < 10) {
                notification('warning', 'Số điện thoại phải từ 10 số trở lên!', 'Cảnh báo!');
                valid = false;
                spinner.hidden = true;
                return;
            }
            if (!phone_register.value.trim() || isNaN(phone_register.value)) {
                notification('warning', 'Số điện thoại phải là số!', 'Cảnh báo!');
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

    //Xử lý đăng nhập tài khoản
    const login_btn = document.getElementById('login');
    if (login_btn) {
        login_btn.addEventListener('click', async function () {
            check_and_submit_login();
        })
    }
    if (password_login) {
        password_login.addEventListener('keydown', async function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                await check_and_submit_login();
            }
        })
    }
    async function check_and_submit_login() {
        spinner.hidden = false;
        let valid = true;
        const form_login = document.getElementById('form_login');
        if (username_login.value && password_login.value) {
            let check_username_existed = await check_username(username_login.value);
            if (check_username_existed.refresh) {
                localStorage.removeItem("remember_password");
                localStorage.removeItem("username");
                localStorage.removeItem("password");
                notification('warning', check_username_existed.message, 'Cảnh báo!');
                valid = false;
                spinner.hidden = true;
                return;
            }
            if (remember_checkbox.checked) {
                localStorage.setItem("remember_password", "true");
                localStorage.setItem("username", username_login.value);
                localStorage.setItem("password", password_login.value);
            } else {
                localStorage.removeItem("remember_password");
                localStorage.removeItem("username");
                localStorage.removeItem("password");
            }
            form_login.submit();
        } else {
            notification('warning', 'Vui lòng điền đầy đủ thông tin!', 'Cảnh báo!');
        }
        spinner.hidden = true;
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
});

window.log_out = function () {
    localStorage.removeItem("remember_password");
    localStorage.removeItem("username");
    localStorage.removeItem("password");
    const form_logout = document.getElementById('form_logout');
    if (form_logout) {
        form_logout.submit();
    }
}