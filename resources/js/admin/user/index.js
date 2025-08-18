document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('tbody').addEventListener('click', async function (e) {
        if (e.target.classList.contains('btn_plus_money')) {
            const id = e.target.id;

            const value = await swal("Nạp tiền:", {
                content: {
                    element: "input",
                    attributes: {
                        type: "number",
                        min: 0,
                        placeholder: "Nhập số tiền"
                    }
                }
            });

            if (value === null || value.trim() === "") {
                swal("Bạn chưa nhập số tiền!");
                return;
            }

            let numberValue = parseFloat(value);
            if (isNaN(numberValue) || numberValue < 0) {
                swal("Giá trị không hợp lệ, vui lòng nhập số hợp lệ!");
            } else {
                const isRealDeposit = await swal({
                    title: "Xác nhận",
                    text: "Đây có phải là tiền nạp thực không? Nếu chọn 'Không' sẽ là tiền thưởng, nếu là 'Có' sẽ là tiền mà khách nạp!",
                    icon: "warning",
                    buttons: {
                        no: {
                            text: "Không",
                            value: false,
                        },
                        yes: {
                            text: "Có",
                            value: true,
                        },
                    },
                    dangerMode: true,
                });
                console.log(isRealDeposit);

                if (isRealDeposit !== null) {
                    spinner.hidden = false;
                    try {
                        const result = await plus_money(numberValue, id, isRealDeposit);
                        spinner.hidden = true;

                        if (result.status === 400) {
                            swal(result.message);
                        } else if (result.status === 200) {
                            swal(result.message).then(() => location.reload());
                        } else {
                            swal("Có lỗi không mong muốn xảy ra, vui lòng thử lại");
                        }
                    } catch (err) {
                        spinner.hidden = true;
                        swal("Có lỗi xảy ra, vui lòng thử lại!");
                    }
                } else {
                    swal("Chưa xác định loại giao dịch, thao tác lại đi!");
                }

            }
        }
    });

    function plus_money(value, user_id, isRealDeposit) {
        return new Promise((resolve, reject) => {
            fetch(route_plus_money, {
                method: "POST",
                headers: {
                    'Content-Type': "application/json",
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    value: value,
                    user_id: user_id,
                    isRealDeposit: isRealDeposit,
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