document.addEventListener('DOMContentLoaded', function () {
    const confirmButtons = document.querySelectorAll('.btn_confirm_transaction');
    const cancelButtons = document.querySelectorAll('.btn_cancel_transaction');

    function submitAction(url, fields = {}) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            AppDialog.alert('Không thể xác thực thao tác. Vui lòng tải lại trang và thử lại.');
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;

        Object.entries({ _token: csrfToken, ...fields }).forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }

    function registerActionHandler(buttons, options) {
        buttons.forEach(button => {
            button.addEventListener('click', async function (event) {
                event.preventDefault();

                const isConfirmed = await AppDialog.confirm({
                    title: options.title,
                    text: options.text,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                });

                if (!isConfirmed) {
                    return;
                }

                if (!options.chooseTransactionType) {
                    submitAction(this.dataset.url);
                    return;
                }

                const transactionType = await AppDialog.choose({
                    title: 'Xác nhận loại giao dịch',
                    text: 'Chọn rút thực nếu tiền được chuyển thật cho khách; chọn rút ảo nếu chỉ ghi nhận trên hệ thống.',
                    icon: 'warning',
                    choices: [
                        { text: 'Rút ảo', value: 'virtual_withdraw' },
                        { text: 'Rút thực', value: 'normal' },
                    ],
                    dangerMode: true,
                });

                if (transactionType !== null) {
                    submitAction(this.dataset.url, { transaction_type: transactionType });
                }
            });
        });
    }

    registerActionHandler(confirmButtons, {
        title: 'Xác nhận',
        text: 'Bạn chắc chắn muốn xác nhận giao dịch này không?',
        chooseTransactionType: true,
    });

    registerActionHandler(cancelButtons, {
        title: 'Hủy giao dịch',
        text: 'Bạn chắc chắn muốn hủy giao dịch này không?',
        chooseTransactionType: false,
    });
});
