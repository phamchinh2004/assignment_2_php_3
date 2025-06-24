document.addEventListener('DOMContentLoaded', function () {
    const btn_submit = document.getElementById('btn_submit');
    const form = document.getElementById('form');
    btn_submit.addEventListener('click', function () {
        swal({
            title: "Xác nhận",
            text: "Bạn đã chắc chắn chưa?",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
            .then((isConfirmed) => {
                if (isConfirmed) {
                    handleCreateAccount();
                }
            });
    })
    const password = document.getElementById('password');
    const password_confirmation = document.getElementById('password_confirmation');
    const full_name = document.getElementById('full_name');
    const username = document.getElementById('username');
    const phone = document.getElementById('phone');

    async function handleCreateAccount() {
        spinner.hidden = false;
        if (!full_name.value || !username.value || !phone.value) {
            notification('warning', 'Vui lòng không để trống các trường nhập liệu!', 'Cảnh báo!');
            spinner.hidden = true;
            return;
        }
        if (password.value && (password.value != password_confirmation.value)) {
            notification('warning', 'Mật khẩu không khớp, vui lòng thử lại!', 'Sai mật khẩu!');
            spinner.hidden = true;
            return;
        }
        let check_phone = await function_check_phone(phone.value);
        if (check_phone.success === true) {
            notification('warning', `Số điện thoại "${phone.value}" đã tồn tại, vui lòng chọn số điện thoại khác!`, 'Cảnh báo!');
            phone.value = "";
            spinner.hidden = true;
            return;
        }
        form.submit();
        spinner.hidden = true;
    }
    username.addEventListener('change', async function () {
        let check_username_existed = await function_check_username(username.value);
        if (check_username_existed.success === true) {
            notification('warning', `Tên đăng nhập "${username.value}" đã tồn tại, vui lòng chọn tên khác!`, 'Cảnh báo!');
            username.value = "";
            return;
        }
    })
    function function_check_username(username) {
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
                    console.error(xhr.responseText);
                    notification('error', 'Không thể kiểm tra dữ liệu!', 'Lỗi');
                    reject();
                }
            });
        })
    }
    function function_check_phone(phone) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: route_check_phone,
                method: "POST",
                data: {
                    _token: csrf,
                    phone: phone
                },
                success: function (response) {
                    resolve(response);
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                    notification('error', 'Không thể kiểm tra dữ liệu!', 'Lỗi');
                    reject();
                }
            });
        })
    }
})