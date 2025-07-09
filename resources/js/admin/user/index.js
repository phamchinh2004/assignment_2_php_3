document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('tbody').addEventListener('click', async function (e) {
        if (e.target.classList.contains('btn_plus_money')) {
            const id = e.target.id;

            const value = await swal("Nạp thêm tiền:", {
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
                spinner.hidden = false;
                const result = await plus_money(numberValue, id);
                if (result.status === 400) {
                    spinner.hidden = true;
                    swal(result.message);
                } else if (result.status === 200) {
                    spinner.hidden = true;
                    swal(result.message)
                        .then(() => {
                            location.reload();
                        });
                }
                swal("Có lỗi không mong muốn xảy ra, vui lòng thử lại");
                spinner.hidden = true;
            }
        }
    });

    function plus_money(value, user_id) {
        return new Promise((resolve, reject) => {
            fetch(route_plus_money, {
                method: "POST",
                headers: {
                    'Content-Type': "application/json",
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    value: value,
                    user_id: user_id
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