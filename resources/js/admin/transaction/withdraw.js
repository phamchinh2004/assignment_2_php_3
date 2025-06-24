document.addEventListener('DOMContentLoaded', function () {
    const confirm_buttons = document.querySelectorAll('.btn_confirm_transaction');
    const cancel_buttons = document.querySelectorAll('.btn_cancel_transaction');

    // Hàm đăng ký xử lý sự kiện xác nhận
    function registerConfirmHandler(buttons, options) {
        buttons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();
                swal({
                    title: options.title,
                    text: options.text,
                    icon: options.icon,
                    buttons: true,
                    dangerMode: options.dangerMode,
                }).then((isConfirmed) => {
                    if (isConfirmed) {
                        window.location.href = this.dataset.url;
                    }
                });
            });
        });
    }

    // Đăng ký cho nút xác nhận
    registerConfirmHandler(confirm_buttons, {
        title: "Xác nhận",
        text: "Bạn chắc chắn muốn xác nhận giao dịch này không?",
        icon: "warning",
        dangerMode: true,
    });

    // Đăng ký cho nút huỷ
    registerConfirmHandler(cancel_buttons, {
        title: "Huỷ giao dịch",
        text: "Bạn chắc chắn muốn huỷ giao dịch này không?",
        icon: "warning",
        dangerMode: true,
    });


})