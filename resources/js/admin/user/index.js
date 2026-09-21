import Modal from 'bootstrap/js/dist/modal';

document.addEventListener('DOMContentLoaded', function () {
    let currentUserId = null;
    let currentUserBalance = 0;
    
    // Get modal elements
    const depositModalElement = document.getElementById('depositModal');
    const confirmModalElement = document.getElementById('confirmModal');
    const successModalElement = document.getElementById('successModal');
    const errorModalElement = document.getElementById('errorModal');
    
    // Initialize modals with options to prevent backdrop issues
    const depositModal = new Modal(depositModalElement, {
        backdrop: 'static',
        keyboard: false
    });
    
    const depositAmountInput = document.getElementById('depositAmount');
    const amountPreview = document.getElementById('amountPreview');
    const confirmDepositBtn = document.getElementById('confirmDepositBtn');
    
    // =========================================================================
    // 1. Quick Status Filter Tabs for DataTables
    // =========================================================================
    const filterBar = document.getElementById('statusFilterBar');
    if (filterBar) {
        filterBar.addEventListener('click', function (e) {
            const btn = e.target.closest('.filter-tab-btn');
            if (btn) {
                filterBar.querySelectorAll('.filter-tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const filterValue = btn.getAttribute('data-filter') || '';
                const table = $('#dataTable').DataTable();
                if (table) {
                    table.search(filterValue).draw();
                }
            }
        });
    }

    // =========================================================================
    // 2. Click to Copy Text (Username / User ID)
    // =========================================================================
    document.addEventListener('click', function (e) {
        const copyBtn = e.target.closest('.btn-copy-text');
        if (copyBtn) {
            const textToCopy = copyBtn.getAttribute('data-copy');
            if (textToCopy) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    const originalHtml = copyBtn.innerHTML;
                    copyBtn.classList.add('copied');
                    copyBtn.innerHTML = `<span>Đã chép!</span> <i class="fas fa-check text-success ms-1"></i>`;
                    setTimeout(() => {
                        copyBtn.innerHTML = originalHtml;
                        copyBtn.classList.remove('copied');
                    }, 1500);
                }).catch(err => {
                    console.error('Không thể sao chép văn bản:', err);
                });
            }
        }
    });

    // =========================================================================
    // 3. Quick Amount Preset Buttons in Deposit Modal
    // =========================================================================
    document.querySelectorAll('.quick-amount-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const addAmount = parseFloat(this.getAttribute('data-add')) || 0;
            const currentAmount = parseFloat(depositAmountInput.value) || 0;
            const newTotal = currentAmount + addAmount;
            depositAmountInput.value = newTotal;
            depositAmountInput.dispatchEvent(new Event('input'));
        });
    });

    // =========================================================================
    // 4. Auto-scroll to user if hash exists in URL
    // =========================================================================
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
        setTimeout(() => {
            scrollToUserFromHash();
        }, 800);
    });
    
    // Re-check hash when URL changes
    window.addEventListener('hashchange', scrollToUserFromHash);
    
    // =========================================================================
    // 5. Event listener cho nút nạp tiền
    // =========================================================================
    const tbodyEl = document.getElementById('tbody');
    if (tbodyEl) {
        tbodyEl.addEventListener('click', function (e) {
            const depositButton = e.target.closest('.btn_plus_money');
            if (depositButton) {
                const userId = depositButton.dataset.userId || depositButton.id;
                const row = depositButton.closest('tr');
                
                // Lấy thông tin user an toàn qua dataset hoặc selector
                const userName = depositButton.dataset.userName || 
                                 row.querySelector('.user-link')?.textContent.trim() || 
                                 '---';
                const userUsername = depositButton.dataset.userUsername || 
                                     row.querySelector('.copy-badge span')?.textContent.trim() || 
                                     row.querySelectorAll('.info-value')[1]?.textContent.trim() || 
                                     '---';
                const rawBalance = depositButton.dataset.userBalance || 
                                   row.querySelector('.balance-highlight')?.textContent.trim() || 
                                   '0';
                const userBalance = rawBalance.replace(/[^0-9.]/g, '');
                
                // Cập nhật modal
                currentUserId = userId;
                currentUserBalance = parseFloat(userBalance) || 0;
                
                document.getElementById('modalUserName').textContent = userName;
                document.getElementById('modalUserUsername').textContent = '@' + userUsername.replace(/^@/, '');
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
    }
    
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
        document.getElementById('summaryNewBalance').textContent = newBalance.toFixed(5) + '$';
    }
    
    // =========================================================================
    // 6. Initialize secondary modals
    // =========================================================================
    let confirmModal = null;
    let successModal = null;
    let errorModal = null;
    
    function getConfirmModal() {
        if (!confirmModal) {
            confirmModal = new Modal(confirmModalElement, {
                backdrop: 'static',
                keyboard: true
            });
        }
        return confirmModal;
    }
    
    function getSuccessModal() {
        if (!successModal) {
            successModal = new Modal(successModalElement, {
                backdrop: true,
                keyboard: true
            });
        }
        return successModal;
    }
    
    function getErrorModal() {
        if (!errorModal) {
            errorModal = new Modal(errorModalElement, {
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
    
    // =========================================================================
    // 7. Xác nhận nạp tiền
    // =========================================================================
    confirmDepositBtn.addEventListener('click', function() {
        const amount = parseFloat(depositAmountInput.value);
        
        // Validation
        if (!amount || amount <= 0) {
            showError("Vui lòng nhập số tiền nạp hợp lệ lớn hơn 0!");
            return;
        }

        const isRealDeposit = document.getElementById('depositTypeReal').checked;
        const depositType = isRealDeposit ? 'Tiền nạp thực' : 'Tiền thưởng';
        
        // Hiển thị modal xác nhận
        document.getElementById('confirmMessage').innerHTML = `
            Bạn chắc chắn muốn nạp <strong class="text-success fs-5">${amount}$</strong> 
            (<strong class="text-primary">${depositType}</strong>) cho người dùng này?
        `;
        
        // Cleanup trước khi hiện modal mới
        cleanupBackdrop();
        
        setTimeout(() => {
            getConfirmModal().show();
        }, 100);
    });
    
    // Xử lý khi xác nhận đồng ý nạp tiền
    document.getElementById('confirmYesBtn').addEventListener('click', async function() {
        getConfirmModal().hide();
        
        // Cleanup backdrop sau khi đóng
        setTimeout(() => cleanupBackdrop(), 300);
        
        const amount = parseFloat(depositAmountInput.value);
        const isRealDeposit = document.getElementById('depositTypeReal').checked;
        
        // Disable button và hiển thị loading
        confirmDepositBtn.disabled = true;
        confirmDepositBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
        const spinner = document.getElementById('spinner');
        if (spinner) spinner.hidden = false;
        
        try {
            const result = await plus_money(amount, currentUserId, isRealDeposit);
            if (spinner) spinner.hidden = true;

            if (result.status === 400) {
                confirmDepositBtn.disabled = false;
                confirmDepositBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận nạp tiền';
                showError(result.message);
            } else if (result.status === 200) {
                depositModal.hide();
                setTimeout(() => {
                    cleanupBackdrop();
                    showSuccess(result.message);
                }, 300);
            } else {
                confirmDepositBtn.disabled = false;
                confirmDepositBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận nạp tiền';
                showError("Có lỗi không mong muốn xảy ra, vui lòng thử lại!");
            }
        } catch (err) {
            if (spinner) spinner.hidden = true;
            confirmDepositBtn.disabled = false;
            confirmDepositBtn.innerHTML = '<i class="fas fa-check me-2"></i>Xác nhận nạp tiền';
            showError("Có lỗi kết nối mạng hoặc hệ thống xảy ra, vui lòng thử lại!");
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
        confirmDepositBtn.innerHTML = '<i class="fas fa-check me-1"></i> Tiếp tục xác nhận';
    });
    
    // Chuyển số thành chữ tiếng Việt
    function numberToWords(num) {
        const ones = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        const tens = ['', '', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
        const hundreds = ['', 'một trăm', 'hai trăm', 'ba trăm', 'bốn trăm', 'năm trăm', 'sáu trăm', 'bảy trăm', 'tám trăm', 'chín trăm'];
        
        if (num === 0) return 'không';
        
        let integer = Math.floor(num);
        let decimal = Math.round((num - integer) * 100);
        
        let result = '';
        
        if (integer >= 1000000) {
            result += numberToWords(Math.floor(integer / 1000000)) + ' triệu ';
            integer %= 1000000;
        }

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
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
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
                    console.error(error);
                    reject(error);
                });
        });
    }
});
