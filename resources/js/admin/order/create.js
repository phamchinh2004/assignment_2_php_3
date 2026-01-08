// Hàm tạo chuỗi ngẫu nhiên (dùng để preview tên đơn hàng)
function generateRandomString(length = 20) {
    const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    return result;
}

document.addEventListener('DOMContentLoaded', function () {
    const rank = document.getElementById('rank');
    const images = document.getElementById('images');
    const btn_generate_auto = document.getElementById('btn_generate_auto');
    const btn_submit = document.getElementById('btn_submit');
    // Lắng nghe sự kiện bấm nút tạo tự động đơn hàng - Tạo luôn không cần preview
    btn_generate_auto.addEventListener('click', async function () {
            if (!rank.value) {
                notification('warning', 'Vui lòng chọn cấp độ!', 'Cảnh báo!');
                return;
            }
            if (!images.value) {
                notification('warning', 'Vui lòng chọn hình ảnh!', 'Cảnh báo!');
                return;
            }
        
        spinner.hidden = false;
        
            const selectedOption = rank.options[rank.selectedIndex];
            const quantity = parseInt(selectedOption.dataset.quantity);
            const value = parseFloat(selectedOption.dataset.value);
            let spin_count = parseFloat(selectedOption.dataset.spin_count);
            const start = parseFloat(selectedOption.dataset.start);

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
        
        // Tạo FormData để gửi lên server
        const formData = new FormData();
        const rank_id = selectedOption.value;
        const commission_percentage = parseFloat(selectedOption.dataset.commission_percentage);
        
        for (let i = start; i < end; i++) {
            let file = files[y];
            const order_quantity = Math.floor(Math.random() * (7 - 1 + 1)) + 1;
            let price = randomValues[y] / order_quantity;
            
            formData.append(`orders[${y}][name]`, ''); // Backend sẽ tự tạo
            formData.append(`orders[${y}][price]`, price);
            formData.append(`orders[${y}][quantity]`, order_quantity);
            formData.append(`orders[${y}][index]`, i + 1);
            formData.append(`orders[${y}][image]`, file);

            y++;
        }
        
        formData.append('rank_id', rank_id);
        formData.append('commission_percentage', commission_percentage);
        formData.append('_token', csrf);
        
        // Gọi API tạo đơn hàng luôn
        try {
            const result_store_order = await storeOrder(formData);
            if (result_store_order.status == 200) {
                spinner.hidden = true;
                notification('success', 'Tạo đơn hàng thành công!', 'Thành công!');
                localStorage.setItem("success", "Tạo đơn hàng thành công!");
                localStorage.setItem("order_index_filter_rank", rank_id);
                setTimeout(() => {
                window.location.href = result_store_order.redirect_url;
                }, 1000);
            } else {
                spinner.hidden = true;
                console.log(result_store_order.data);
            }
        } catch (error) {
            spinner.hidden = true;
            console.error('Error creating orders:', error);
        }
    })
    // Lắng nghe sự kiện thay đổi cấp độ
    rank.addEventListener('change', function () {
        const list_orders = document.getElementById('list_orders');
        list_orders.innerHTML = "";
        // Tắt nút submit khi xóa đơn hàng
        btn_submit.classList.remove('btn-success');
        btn_submit.classList.add('btn-secondary', 'csdf');
    })

    // Bỏ phần xử lý nút submit vì không cần preview nữa
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