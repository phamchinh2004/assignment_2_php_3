document.addEventListener('DOMContentLoaded', function () {
    const rank = document.getElementById('rank');
    const images = document.getElementById('images');
    const btn_generate_auto = document.getElementById('btn_generate_auto');
    const btn_submit = document.getElementById('btn_submit');
    // Lắng nghe sự kiện bấm nút tạo tự động đơn hàng
    btn_generate_auto.addEventListener('click', function () {
        const btn_div_submit = document.getElementById('submit');
        spinner.hidden = false;
        setTimeout(() => {
            if (!rank.value) {
                notification('warning', 'Vui lòng chọn cấp độ!', 'Cảnh báo!');
                spinner.hidden = true;
                return;
            }
            if (!images.value) {
                notification('warning', 'Vui lòng chọn hình ảnh!', 'Cảnh báo!');
                spinner.hidden = true;
                return;
            }
            const selectedOption = rank.options[rank.selectedIndex];
            const quantity = parseInt(selectedOption.dataset.quantity);
            const value = parseFloat(selectedOption.dataset.value);
            let spin_count = parseFloat(selectedOption.dataset.spin_count);
            const start = parseFloat(selectedOption.dataset.start);

            const list_orders = document.getElementById('list_orders');
            list_orders.innerHTML = "";
            function generateRandomSplit(total, count, decimals = 2) {
                let raw = [];
                for (let i = 0; i < count; i++) {
                    raw.push(Math.random() + 0.1); // tránh 0
                }

                const sum = raw.reduce((a, b) => a + b, 0);

                // Scale để tổng = total
                let scaled = raw.map(n => (n / sum) * total);

                // Làm tròn đến số thập phân mong muốn
                scaled = scaled.map(n => parseFloat(n.toFixed(decimals)));

                // Tính lại tổng và điều chỉnh sai số
                const currentSum = scaled.reduce((a, b) => a + b, 0);
                const diff = parseFloat((total - currentSum).toFixed(decimals));
                scaled[scaled.length - 1] = parseFloat((scaled[scaled.length - 1] + diff).toFixed(decimals));

                return scaled;
            }
            const randomValues = generateRandomSplit(value, quantity, 2);
            const files = images.files;
            let y = 0;
            let remainingOrders = spin_count - start;
            let filesToProcess = Math.min(files.length, remainingOrders, 20);
            let end = start + filesToProcess;
            for (let i = start; i < end; i++) {
                let file = files[y];
                let imageURL = URL.createObjectURL(file);
                const quantity = Math.floor(Math.random() * (7 - 1 + 1)) + 1;
                let price = randomValues[y] / quantity;
                const div = document.createElement('div');
                div.className = 'order_item position-relative';
                div.innerHTML = `
                <div class="div_img">
                    <img src="${imageURL}" alt="Ảnh đơn hàng ${i + 1}">
                </div>
                <div class="form-floating div_name">
                    <input type="text" class="form-control input_name" id="input_name_${i}" placeholder="Nhập tên đơn hàng">
                    <label class="label_name" for="input_name_${i}">Nhập tên đơn hàng ${i + 1}</label>
                </div>
                <span class="badge bg-primary index" data-index="${i + 1}">${i + 1}</span>
                <span class="price" data-price="${price}">${format_currency(price)}</span>
                <span class="quantity" data-quantity="${quantity}">
            `;
                list_orders.appendChild(div);
                y++;
            }
            spinner.hidden = true;
            btn_div_submit.hidden = false;
        }, 100);
    })
    // Lắng nghe sự kiện thay đổi cấp độ
    rank.addEventListener('change', function () {
        const list_orders = document.getElementById('list_orders');
        list_orders.innerHTML = "";
    })
    // Lắng nghe sự kiện nhập tên đơn hàng để xử lý hiển thị nút xong
    const list_orders = document.getElementById('list_orders');
    const input_names = document.getElementsByClassName('input_name');

    list_orders.addEventListener('input', function (event) {
        if (event.target && event.target.classList.contains('input_name')) {
            let check = true;
            const inputs = document.getElementsByClassName('input_name');
            for (const input of inputs) {
                if (!input.value) {
                    check = false;
                    break;
                }
            }
            if (check) {
                btn_submit.classList.remove('btn-secondary', 'csdf');
                btn_submit.classList.add('btn-success');
            } else {
                btn_submit.classList.remove('btn-success');
                btn_submit.classList.add('btn-secondary', 'csdf');
            }
        }
    });

    // Lắng nghe sự kiện bấm nút tạo đơn hàng
    btn_submit.addEventListener('click', function () {
        if (btn_submit.classList.contains('btn-success')) {
            swal({
                title: "Xác nhận",
                text: "Bạn đã chắc chắn muốn tạo đơn hàng?",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
                .then((isConfirmed) => {
                    if (isConfirmed) {

                        handleCreateOrder();

                    }
                });
        } else {
            notification('warning', 'Có một số đơn hàng chưa nhập tên!', 'Thông báo!');
        }
    })
    async function handleCreateOrder() {
        spinner.hidden = false;
        const selectedOption = rank.options[rank.selectedIndex];
        const rank_id = selectedOption.value;
        const commission_percentage = parseFloat(selectedOption.dataset.commission_percentage);
        const invalid_item_ids = [];
        const formData = new FormData();
        let check = true;
        let index = 0;
        for (const item of input_names) {
            const parent = item.closest('.order_item');
            const order_name = item.value || '';
            const order_price = parent.querySelector('.price')?.dataset.price || '';
            const order_quantity = parent.querySelector('.quantity')?.dataset.quantity || '';
            const order_index = parent.querySelector('.index')?.dataset.index || '';
            const order_img = parent.querySelector('img');

            const file = await blobUrlToFile(order_img.src, `image_${index + 1}.jpg`);
            formData.append(`orders[${index}][name]`, order_name);
            formData.append(`orders[${index}][price]`, order_price);
            formData.append(`orders[${index}][quantity]`, order_quantity);
            formData.append(`orders[${index}][index]`, order_index);
            formData.append(`orders[${index}][image]`, file);

            index++;

            const id = item.id;
            if (!item.value) {
                const match = id.match(/\d+$/); // lấy số ở cuối id
                if (match) {
                    const lastNumber = parseInt(match[0], 10);
                    invalid_item_ids.push(lastNumber + 1);
                }
                check = false;
            }
        }
        formData.append('rank_id', rank_id);
        formData.append('commission_percentage', commission_percentage);
        formData.append('_token', csrf);
        if (invalid_item_ids.length > 0) {
            notification('warning', `Đơn hàng ${invalid_item_ids.join(', ')} không hợp lệ!`, 'Cảnh báo!');
            return;
        }
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) {
                console.log(`${key}:`, value.name, value.type, value.size + ' bytes');
            } else {
                console.log(`${key}:`, value);
            }
        }
        if (check) {
            const result_store_order = await storeOrder(formData);
            if (result_store_order.status == 200) {
                spinner.hidden = true;
                localStorage.setItem("success", "Tạo đơn hàng thành công!");
                localStorage.setItem("order_index_filter_rank", rank_id);
                window.location.href = result_store_order.redirect_url;
            } else {
                console.log(result_store_order.data);
            }
        }
    }
    function storeOrder(data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: route_create_order,
                method: 'POST',
                data: data,
                processData: false,        // Không xử lý dữ liệu (serialize)
                contentType: false,        // Để trình duyệt tự đặt content-type
                success: function (response) {
                    if (response.status === 400) {
                        notification('warning', response.message || 'Dữ liệu không hợp lệ!', 'Cảnh báo!');
                        return reject(response);
                    }
                    resolve(response);
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Có lỗi xảy ra khi tạo đơn hàng, vui lòng thử lại!';
                    notification('error', message, 'Lỗi!');
                    reject(xhr);
                }
            })
        });
    }
    async function blobUrlToFile(blobUrl, filename) {
        const response = await fetch(blobUrl);
        const blob = await response.blob();
        return new File([blob], filename, { type: blob.type });
    }

})