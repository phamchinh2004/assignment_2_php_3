document.addEventListener('DOMContentLoaded', function () {
    const confirm_buttons = document.querySelectorAll('.btn_confirm_transaction');
    const cancel_buttons = document.querySelectorAll('.btn_cancel_transaction');

    // Hàm đăng ký xử lý sự kiện xác nhận
    function registerConfirmHandler(buttons, options) {
        buttons.forEach(button => {
            button.addEventListener('click', async function (e) {
                e.preventDefault();

                const isConfirmed = await swal({
                    title: options.title,
                    text: options.text,
                    icon: options.icon,
                    buttons: true,
                    dangerMode: options.dangerMode,
                });

                if (isConfirmed) {
                    const isRealWithdraw = await swal({
                        title: "Xác nhận",
                        text: "Đây có phải là tiền rút thực không? Nếu chọn 'Không' sẽ là tiền rút ảo, nếu là 'Có' sẽ là tiền rút thực!",
                        icon: "warning",
                        buttons: {
                            no: { text: "Không", value: false },
                            yes: { text: "Có", value: true },
                        },
                        dangerMode: true,
                    });

                    if (isRealWithdraw !== null) {
                        window.location.href = this.dataset.url + '?transaction_type=' + isRealWithdraw;
                    } else {
                        swal("Chưa xác định loại giao dịch, thao tác lại đi!");
                    }
                }
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