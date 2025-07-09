document.addEventListener('DOMContentLoaded', function () {
    const bankSelect = document.getElementById('select_bank_name');
    const currentValue = bankSelect.getAttribute('value') || bankSelect.dataset.value;

    if (currentValue) {
        bankSelect.value = currentValue;
    }

    const withdraw_all = document.getElementById('withdraw_all');
    const set_up_amount_input_field = new AutoNumeric('#amount_input_field', {
        currencySymbol: '$',
        decimalCharacter: '.',
        digitGroupSeparator: ',',
        minimumValue: '0'
    });
    const temple_amount = document.getElementById('temple_amount').value;
    const amount_input_field = document.getElementById('amount_input_field');
    amount_input_field.addEventListener('input', function () {
        let unformatted_value = this.value.replace(/[^0-9.]/g, '');
        let parts = unformatted_value.split('.');
        if (parts.length > 1) {
            unformatted_value = parts.shift() + '.' + parts.join('');
        }

        let numeric_value = parseFloat(unformatted_value) || 0;

        if (numeric_value > temple_amount) {
            this.value = temple_amount;
            set_up_amount_input_field.set(this.value);
        }
    })
    withdraw_all.addEventListener('click', function () {
        document.getElementById('amount_input_field').value = temple_amount;
        set_up_amount_input_field.set(temple_amount);
    })

    const btn_withdraw_now = document.getElementById('btn_withdraw_now');
    btn_withdraw_now.addEventListener('click', async function () {
        spinner.hidden = false;
        if (amount_input_field.value == "") {
            notification('warning', trans.VuiLongNhapSoTienRut, trans.CanhBao);
            spinner.hidden = true;
            return;
        }
        const username_bank = document.getElementById('username_bank');
        // const bank_name = document.getElementById('bank_name');
        const account_number = document.getElementById('account_number');
        const transaction_password = document.getElementById('transaction_password');
        const confirm_transaction_password = document.getElementById('confirm_transaction_password');
        if (username_bank.value == "" ||  account_number.value == "" || transaction_password.value == "") {
            notification('warning', trans.VuiLongNhapDayDuThongTinNganHang, trans.CanhBao);
            spinner.hidden = true;
            return;
        }

        const has_password = document.getElementById('has_password');
        if (has_password.value === false) {
            if (confirm_transaction_password.value != "" && confirm_transaction_password.value != transaction_password.value) {
                notification('warning', trans.XacNhanMatKhauGiaoDichKhongKhop, trans.CanhBao);
                spinner.hidden = true;
                return;
            }
        }
        let unformatted_value = amount_input_field.value.replace(/[^0-9.]/g, '');
        let parts = unformatted_value.split('.');
        if (parts.length > 1) {
            unformatted_value = parts.shift() + '.' + parts.join('');
        }

        let numeric_value = parseFloat(unformatted_value) || 0;

        let result = await handle_withdraw(numeric_value, username_bank.value, bankSelect.value, account_number.value, transaction_password.value, confirm_transaction_password.value);
        if (result.status == 400) {
            notification('warning', result.message, trans.CanhBao);

        } else if (result.status == 200) {
            swal({
                title: trans.ThanhCong,
                text: result.message,
                icon: "success",
                button: "OK",
                dangerMode: true,
            }).then((isConfirmed) => {
                if (isConfirmed) {
                    location.reload();
                }
            });
        }
        spinner.hidden = true;
    })
    function handle_withdraw(amount, username_bank, bankSelect, account_number, transaction_password, confirm_transaction_password) {
        return new Promise((resolve, reject) => {
            fetch(route_handle_withdraw, {
                method: "POST",
                headers: {
                    'Content-Type': "application/json",
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    amount: amount,
                    username_bank: username_bank,
                    bank_name: bankSelect,
                    account_number: account_number,
                    transaction_password: transaction_password,
                    confirm_transaction_password: confirm_transaction_password,
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