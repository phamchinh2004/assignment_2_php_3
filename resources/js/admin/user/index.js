document.addEventListener('DOMContentLoaded', function () {
    let currentUserId = null;
    let currentUserBalance = 0;
    
    // Get modal elements
    const depositModalElement = document.getElementById('depositModal');
    const confirmModalElement = document.getElementById('confirmModal');
    const successModalElement = document.getElementById('successModal');
    const errorModalElement = document.getElementById('errorModal');
    
    // Initialize modals with options to prevent backdrop issues
    const depositModal = new bootstrap.Modal(depositModalElement, {
        backdrop: 'static',
        keyboard: false
    });
    
    const depositAmountInput = document.getElementById('depositAmount');
    const amountPreview = document.getElementById('amountPreview');
    const confirmDepositBtn = document.getElementById('confirmDepositBtn');
    
    // Auto-scroll to user if hash exists in URL
    function scrollToUserFromHash() {
        const hash = window.location.hash;
        if (hash && hash.startsWith('#user-')) {
            const userId = hash.replace('#user-', '');
            
            // Kiểm tra xem DataTable đã được khởi tạo chưa
            const table = $('#dataTable').DataTable();
            
            if (table) {
                // Search cho user ID trong tất cả các trang
                table.search('').draw(); // Clear search trước
                
                // Tìm index của row có ID tương ứng
                let rowIndex = -1;
                table.rows().every(function(index) {
                    const row = this.node();
                    if (row && row.id === `user-${userId}`) {
                        rowIndex = index;
                        return false; // break loop
                    }
                });
                
                if (rowIndex !== -1) {
                    // Tính trang chứa row này
                    const pageLength = table.page.len();
                    const pageNumber = Math.floor(rowIndex / pageLength);
                    
                    // Chuyển đến trang đó
                    table.page(pageNumber).draw(false);
                    
                    // Đợi DataTable render xong
                    setTimeout(() => {
                        const userRow = document.getElementById(`user-${userId}`);
                        
                        if (userRow) {
                            // Scroll to user row with smooth animation
                            userRow.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            
                            // Add highlight class
                            userRow.classList.add('highlight');
                            
                            // Remove highlight after 3 seconds
                            setTimeout(() => {
                                userRow.classList.remove('highlight');
                            }, 3000);
                        }
                    }, 300);
                }
            } else {
                // Fallback nếu không dùng DataTable
                const userRow = document.getElementById(`user-${userId}`);
                if (userRow) {
                    setTimeout(() => {
                        userRow.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        userRow.classList.add('highlight');
                        setTimeout(() => {
                            userRow.classList.remove('highlight');
                        }, 3000);
                    }, 500);
                }
            }
        }
    }
    
    // Đợi DataTable khởi tạo xong
    $(document).ready(function() {
        // Đợi một chút để DataTable render
        setTimeout(() => {
            scrollToUserFromHash();
        }, 800);
    });
    
    // Re-check hash when URL changes
    window.addEventListener('hashchange', scrollToUserFromHash);
    
    // Event listener cho nút cộng tiền
    document.getElementById('tbody').addEventListener('click', function (e) {
        if (e.target.classList.contains('btn_plus_money')) {
            const userId = e.target.id;
            const row = e.target.closest('tr');
            
            // Lấy thông tin user từ row
            const userName = row.querySelector('.user-link').textContent.trim();
            const userUsername = row.querySelectorAll('.info-value')[1].textContent.trim();
            const userBalance = row.querySelector('.balance-highlight').textContent.replace('$', '').trim();
            
            // Cập nhật modal
            currentUserId = userId;
            currentUserBalance = parseFloat(userBalance);
            
            document.getElementById('modalUserName').textContent = userName;
            document.getElementById('modalUserUsername').textContent = userUsername;
            document.getElementById('modalUserBalance').textContent = userBalance + '$';
            document.getElementById('summaryCurrentBalance').textContent = userBalance + '$';
            
            // Reset form
            depositAmountInput.value = '';
            document.getElementById('depositTypeReal').checked = true;
            updateSummary();
            
            // Hiển thị modal
            depositModal.show();
            
            // Focus vào input
            setTimeout(() => depositAmountInput.focus(), 300);
        }
    });
    
    // Cập nhật preview khi nhập số tiền
    depositAmountInput.addEventListener('input', function() {
        const amount = parseFloat(this.value) || 0;
        
        if (amount > 0) {
            amountPreview.innerHTML = `
                <i class="fas fa-info-circle me-1"></i>
                Số tiền bằng chữ: <strong>${numberToWords(amount)} đô la</strong>
            `;
        } else {
            amountPreview.innerHTML = '';
        }
        
        updateSummary();
    });
    
    // Cập nhật summary
    function updateSummary() {
        const amount = parseFloat(depositAmountInput.value) || 0;
        const newBalance = currentUserBalance + amount;
        
        document.getElementById('summaryDepositAmount').textContent = '+' + amount.toFixed(2) + '$';
        document.getElementById('summaryNewBalance').textContent = newBalance.toFixed(2) + '$';
    }
    
    // Initialize modals with lazy initialization to prevent conflicts
    let confirmModal = null;
    let successModal = null;
    let errorModal = null;
    
    function getConfirmModal() {
        if (!confirmModal) {
            confirmModal = new bootstrap.Modal(confirmModalElement, {
                backdrop: 'static',
                keyboard: true
            });
        }
        return confirmModal;
    }
    
    function getSuccessModal() {
        if (!successModal) {
            successModal = new bootstrap.Modal(successModalElement, {
                backdrop: true,
                keyboard: true
            });
        }
        return successModal;
    }
    
    function getErrorModal() {
        if (!errorModal) {
            errorModal = new bootstrap.Modal(errorModalElement, {
                backdrop: true,
                keyboard: true
            });
        }
        return errorModal;
    }
    
    // Cleanup backdrop khi đóng modal
    function cleanupBackdrop() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    // Xác nhận nạp tiền
    confirmDepositBtn.addEventListener('click', function() {
        const amount = parseFloat(depositAmountInput.value);
        
        // Validation
        if (!amount || amount <= 0) {
            showError("Vui lòng nhập số tiền hợp lệ!");
                return;
            }

        const isRealDeposit = document.getElementById('depositTypeReal').checked;
        const depositType = isRealDeposit ? 'Tiền nạp thực' : 'Tiền thưởng';
        
        // Hiển thị modal xác nhận
        document.getElementById('confirmMessage').innerHTML = `
            Bạn chắc chắn muốn nạp <strong class="text-primary">${amount}$</strong> 
            (<strong class="text-info">${depositType}</strong>) cho khách hàng này?
        `;
        
        // Cleanup trước khi hiện modal mới
        cleanupBackdrop();
        
        // Đợi một chút để cleanup xong
        setTimeout(() => {
            getConfirmModal().show();
        }, 100);
    });
    
    // Xử lý khi xác nhận
    document.getElementById('confirmYesBtn').addEventListener('click', async function() {
        getConfirmModal().hide();
        
        // Cleanup backdrop sau khi đóng
        setTimeout(() => cleanupBackdrop(), 300);
        
        const amount = parseFloat(depositAmountInput.value);
        const isRealDeposit = document.getElementById('depositTypeReal').checked;
        
        // Disable button và hiển thị loading
        confirmDepositBtn.disabled = true;
        confirmDepositBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
                    spinner.hidden = false;
        
                    try {
            const result = await plus_money(amount, currentUserId, isRealDeposit);
                        spinner.hidden = true;

                        if (result.status === 400) {
                confirmDepositBtn.disabled = false;
                confirmDepositBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận nạp tiền';
                showError(result.message);
                        } else if (result.status === 200) {
                depositModal.hide();
                // Đợi deposit modal đóng xong
                setTimeout(() => {
                    cleanupBackdrop();
                    showSuccess(result.message);
                }, 300);
                        } else {
                confirmDepositBtn.disabled = false;
                confirmDepositBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận nạp tiền';
                showError("Có lỗi không mong muốn xảy ra, vui lòng thử lại");
                        }
                    } catch (err) {
                        spinner.hidden = true;
            confirmDepositBtn.disabled = false;
            confirmDepositBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận nạp tiền';
            showError("Có lỗi xảy ra, vui lòng thử lại!");
        }
    });
    
    // Hàm hiển thị thông báo thành công
    function showSuccess(message) {
        cleanupBackdrop();
        document.getElementById('successMessage').textContent = message;
        setTimeout(() => {
            getSuccessModal().show();
        }, 100);
    }
    
    // Hàm hiển thị thông báo lỗi
    function showError(message) {
        cleanupBackdrop();
        document.getElementById('errorMessage').textContent = message;
        setTimeout(() => {
            getErrorModal().show();
        }, 100);
    }
    
    // Reload trang khi đóng modal thành công
    document.getElementById('successOkBtn').addEventListener('click', function() {
        location.reload();
    });
    
    // Event listeners để cleanup backdrop khi đóng modal
    confirmModalElement.addEventListener('hidden.bs.modal', cleanupBackdrop);
    successModalElement.addEventListener('hidden.bs.modal', cleanupBackdrop);
    errorModalElement.addEventListener('hidden.bs.modal', cleanupBackdrop);
    depositModalElement.addEventListener('hidden.bs.modal', function() {
        cleanupBackdrop();
        confirmDepositBtn.disabled = false;
        confirmDepositBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận nạp tiền';
    });
    
    // Hàm chuyển số thành chữ (tiếng Anh đơn giản)
    function numberToWords(num) {
        const ones = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        const tens = ['', '', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
        const hundreds = ['', 'một trăm', 'hai trăm', 'ba trăm', 'bốn trăm', 'năm trăm', 'sáu trăm', 'bảy trăm', 'tám trăm', 'chín trăm'];
        
        if (num === 0) return 'không';
        
        let integer = Math.floor(num);
        let decimal = Math.round((num - integer) * 100);
        
        let result = '';
        
        if (integer >= 1000) {
            result += ones[Math.floor(integer / 1000)] + ' nghìn ';
            integer %= 1000;
        }
        
        if (integer >= 100) {
            result += hundreds[Math.floor(integer / 100)] + ' ';
            integer %= 100;
        }
        
        if (integer >= 20) {
            result += tens[Math.floor(integer / 10)] + ' ';
            integer %= 10;
        } else if (integer >= 10) {
            result += 'mười ';
            integer %= 10;
        }
        
        if (integer > 0) {
            result += ones[integer];
        }
        
        if (decimal > 0) {
            result += ' phẩy ' + decimal;
        }
        
        return result.trim();
    }

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