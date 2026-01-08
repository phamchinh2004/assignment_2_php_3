document.addEventListener("DOMContentLoaded", function () {
    const selectAllBtn = document.getElementById("select_all");
    const deselectAllBtn = document.getElementById("deselect_all");
    const checkboxes = document.querySelectorAll(
        ".order-checkbox:not(:disabled)"
    );
    const form = document.getElementById("form");
    const btnSubmit = document.getElementById("btn_submit");

    // Chọn tất cả
    if (selectAllBtn) {
        selectAllBtn.addEventListener("click", function () {
            checkboxes.forEach((cb) => {
                cb.checked = true;
                togglePriceInput(cb);
            });
        });
    }

    // Bỏ chọn tất cả
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener("click", function () {
            checkboxes.forEach((cb) => {
                cb.checked = false;
                togglePriceInput(cb);
            });
        });
    }

    // Toggle input giá
    checkboxes.forEach((cb) => {
        cb.addEventListener("change", function () {
            togglePriceInput(this);
        });
    });

    function togglePriceInput(checkbox) {
        const orderId = checkbox.value;
        const priceInput = document.getElementById(`price_${orderId}`);
        const commissionInput = document.getElementById(
            `commission_${orderId}`
        );

        if (priceInput) {
            priceInput.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                priceInput.value = "";
            }
        }

        if (commissionInput) {
            commissionInput.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                // Khôi phục giá trị mặc định từ order
                const orderCommission =
                    commissionInput.getAttribute("data-default-value");
                if (orderCommission) {
                    commissionInput.value = orderCommission;
                } else {
                    commissionInput.value = "";
                }
            }
        }
    }

    // Submit form đóng băng
    if (btnSubmit) {
        btnSubmit.addEventListener("click", function () {
            const checkedBoxes = document.querySelectorAll(
                ".order-checkbox:checked"
            );
            if (checkedBoxes.length === 0) {
                alert("Vui lòng chọn ít nhất một đơn hàng!");
                return;
            }

            let missingPrice = false;
            checkedBoxes.forEach((cb) => {
                const orderId = cb.value;
                const priceInput = document.getElementById(`price_${orderId}`);
                if (priceInput && !priceInput.value) {
                    missingPrice = true;
                }
            });

            if (missingPrice) {
                if (
                    !confirm(
                        "Một số đơn hàng chưa có giá giả. Bạn có muốn tiếp tục không?"
                    )
                ) {
                    return;
                }
            }

            if (
                confirm(
                    `Bạn có chắc chắn muốn đóng băng ${checkedBoxes.length} đơn hàng?`
                )
            ) {
                form.submit();
            }
        });
    }

    // Initialize
    checkboxes.forEach((cb) => {
        togglePriceInput(cb);
    });

    // Xử lý nút sửa giá
    document.querySelectorAll(".btn-edit-price").forEach((btn) => {
        btn.addEventListener("click", function () {
            const frozenId = this.dataset.frozenId;
            const editForm = document.getElementById(`edit-form-${frozenId}`);
            if (editForm) {
                editForm.classList.toggle("active");
            }
        });
    });

    // Xử lý nút hủy sửa
    document.querySelectorAll(".btn-cancel-edit").forEach((btn) => {
        btn.addEventListener("click", function () {
            const frozenId = this.dataset.frozenId;
            const editForm = document.getElementById(`edit-form-${frozenId}`);
            if (editForm) {
                editForm.classList.remove("active");
            }
        });
    });

    // Xử lý nút hủy đóng băng
    document.querySelectorAll(".btn-unfreeze").forEach((btn) => {
        btn.addEventListener("click", function (e) {
            const orderName = this.dataset.orderName;
            if (
                !confirm(
                    `Bạn có chắc chắn muốn hủy đóng băng đơn hàng "${orderName}"?`
                )
            ) {
                e.preventDefault();
            }
        });
    });

    // Xử lý submit form sửa giá
    document.querySelectorAll(".form-edit-price").forEach((form) => {
        form.addEventListener("submit", function (e) {
            const priceInput = this.querySelector('input[name="custom_price"]');
            if (!priceInput.value || parseFloat(priceInput.value) < 0) {
                e.preventDefault();
                alert("Vui lòng nhập giá hợp lệ!");
            }
        });
    });

    // Xử lý nút thay ảnh
    document.querySelectorAll(".btn-change-image").forEach((btn) => {
        btn.addEventListener("click", function () {
            const frozenId = this.dataset.frozenId;
            const changeImageForm = document.getElementById(`change-image-form-${frozenId}`);
            const editForm = document.getElementById(`edit-form-${frozenId}`);
            
            // Ẩn form sửa giá nếu đang mở
            if (editForm) {
                editForm.classList.remove("active");
            }
            
            // Hiển thị form thay ảnh
            if (changeImageForm) {
                changeImageForm.classList.toggle("active");
            }
        });
    });

    // Xử lý nút hủy thay ảnh
    document.querySelectorAll(".btn-cancel-change-image").forEach((btn) => {
        btn.addEventListener("click", function () {
            const frozenId = this.dataset.frozenId;
            const changeImageForm = document.getElementById(`change-image-form-${frozenId}`);
            if (changeImageForm) {
                changeImageForm.classList.remove("active");
                // Reset file input
                const fileInput = changeImageForm.querySelector('input[type="file"]');
                if (fileInput) {
                    fileInput.value = "";
                }
            }
        });
    });

    // Xử lý submit form thay ảnh
    document.querySelectorAll(".form-change-image").forEach((form) => {
        form.addEventListener("submit", function (e) {
            const fileInput = this.querySelector('input[type="file"]');
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert("Vui lòng chọn ảnh!");
                return;
            }

            const file = fileInput.files[0];
            const maxSize = 2 * 1024 * 1024; // 2MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/webp'];

            if (!allowedTypes.includes(file.type)) {
                e.preventDefault();
                alert("Định dạng ảnh không hợp lệ! Chỉ chấp nhận: jpeg, png, jpg, gif, svg, webp");
                return;
            }

            if (file.size > maxSize) {
                e.preventDefault();
                alert("Kích thước ảnh không được vượt quá 2MB!");
                return;
            }

            if (!confirm("Bạn có chắc chắn muốn thay ảnh cho đơn hàng này?")) {
                e.preventDefault();
            }
        });
    });

    // Tự động cuộn đến đơn hàng đang quay đến
    function scrollToCurrentSpin() {
        const currentSpinItem = document.querySelector(".order-item.current-spin");
        if (currentSpinItem) {
            const cardBody = currentSpinItem.closest(".card-body");
            if (cardBody) {
                // Tính toán vị trí cuộn để đơn hàng hiện tại nằm ở giữa màn hình
                const cardBodyRect = cardBody.getBoundingClientRect();
                const itemRect = currentSpinItem.getBoundingClientRect();
                const scrollTop = cardBody.scrollTop;
                const itemOffsetTop = itemRect.top - cardBodyRect.top + scrollTop;
                const cardBodyHeight = cardBody.clientHeight;
                const itemHeight = currentSpinItem.offsetHeight;
                
                // Cuộn để đơn hàng nằm ở giữa container
                const targetScroll = itemOffsetTop - (cardBodyHeight / 2) + (itemHeight / 2);
                
                cardBody.scrollTo({
                    top: Math.max(0, targetScroll),
                    behavior: "smooth"
                });
            }
        }
    }

    // Cuộn khi trang được load (nếu tab "Đóng băng mới" đang active)
    const freezeTab = document.getElementById("freeze");
    if (freezeTab && freezeTab.classList.contains("show") && freezeTab.classList.contains("active")) {
        // Đợi một chút để đảm bảo DOM đã render hoàn toàn
        setTimeout(scrollToCurrentSpin, 100);
    }

    // Cuộn khi chuyển sang tab "Đóng băng mới"
    const freezeTabLink = document.getElementById("freeze-tab");
    if (freezeTabLink) {
        freezeTabLink.addEventListener("shown.bs.tab", function () {
            setTimeout(scrollToCurrentSpin, 100);
        });
    }
});
