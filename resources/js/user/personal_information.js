//Khởi tạo slimselect
// Tắt focus cho modal
document
    .getElementById("bankLinkModal")
    .addEventListener("shown.bs.modal", function () {
        const modal = bootstrap.Modal.getInstance(this);
        if (modal && modal._focustrap) {
            modal._focustrap.deactivate();
        }
    });

// Tạo slimselect
const bankSelect = new SlimSelect({
    select: "#bankName",
    settings: {
        searchPlaceholder: "Tìm kiếm ngân hàng...",
        showSearch: true,
    },
    events: {
        afterOpen: () => {
            setTimeout(() => {
                const searchInput = document.querySelector(".ss-search input");
                if (searchInput) {
                    searchInput.focus();
                }
            }, 50);
        },
    },
});
// Handle payment method click
window.handlePaymentMethodClick = function () {
    // Get data from data attributes
    const userData = document.getElementById("user-data");
    const hasBankAccount = userData.dataset.bankStatus === "true";
    const bankName = userData.dataset.bankName || "Ngân hàng";

    if (!hasBankAccount) {
        // Show bank link modal
        const bankLinkModal = new bootstrap.Modal(
            document.getElementById("bankLinkModal")
        );
        bankLinkModal.show();
    } else {
        // If already linked, you can redirect to edit page or show info
        alert("Tài khoản ngân hàng đã được liên kết: " + bankName);
    }
};

// Toggle password visibility
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + "Icon");

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// Submit bank link form
window.submitBankLinkForm = function () {
    const form = document.getElementById("bankLinkForm");
    const formData = new FormData(form);

    // Validate form
    const accountName = formData.get("accountName");
    const bankName = formData.get("bankName");
    const accountNumber = formData.get("accountNumber");
    const transactionPassword = formData.get("transactionPassword");
    const confirmPassword = formData.get("confirmPassword");

    if (
        !accountName ||
        !bankName ||
        !accountNumber ||
        !transactionPassword ||
        !confirmPassword
    ) {
        alert("Vui lòng điền đầy đủ thông tin!");
        return;
    }

    if (transactionPassword !== confirmPassword) {
        alert("Mật khẩu giao dịch không khớp!");
        return;
    }

    // Show loading state
    const modal = document.getElementById("bankLinkModal");
    modal.classList.add("loading");

    // Get route from data attributes
    const userData = document.getElementById("user-data");
    const bankLinkRoute = userData.dataset.bankLinkRoute;

    // Submit form via AJAX
    fetch(bankLinkRoute, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            username_bank: accountName,
            bank_name: bankName,
            account_number: accountNumber,
            transaction_password: transactionPassword,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            modal.classList.remove("loading");

            if (data.status === 200) {
                alert(
                    data.message || "Liên kết tài khoản ngân hàng thành công!"
                );
                // Close modal
                const bankLinkModal = bootstrap.Modal.getInstance(modal);
                bankLinkModal.hide();
                // Reload page to update status
                location.reload();
            } else {
                alert(
                    "Lỗi: " +
                        (data.message ||
                            "Không thể liên kết tài khoản ngân hàng")
                );
            }
        })
        .catch((error) => {
            modal.classList.remove("loading");
            console.error("Error:", error);
            alert("Có lỗi xảy ra khi liên kết tài khoản ngân hàng!");
        });
};

// Initialize form with existing data if available
// Avatar Upload Functions
window.openAvatarUpload = function() {
    const avatarModal = new bootstrap.Modal(document.getElementById('avatarUploadModal'));
    avatarModal.show();
};

window.uploadAvatar = function() {
    const fileInput = document.getElementById('avatarFile');
    const file = fileInput.files[0];
    
    if (!file) {
        showAvatarMessage('Vui lòng chọn một file ảnh!', 'error');
        return;
    }
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        showAvatarMessage('Vui lòng chọn file ảnh hợp lệ!', 'error');
        return;
    }
    
    // Validate file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
        showAvatarMessage('Kích thước file không được vượt quá 2MB!', 'error');
        return;
    }
    
    // Show progress
    showProgress(true);
    hideAvatarMessage();
    
    // Create FormData
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    // Upload via AJAX
    const xhr = new XMLHttpRequest();
    
    // Track upload progress
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            updateProgress(percentComplete);
        }
    });
    
    xhr.addEventListener('load', function() {
        showProgress(false);
        
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 200) {
                    showAvatarMessage('Cập nhật ảnh đại diện thành công!', 'success');
                    
                    // Update avatar previews
                    updateAvatarPreviews(response.avatar_url);
                    
                    // Close modal after delay
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('avatarUploadModal'));
                        modal.hide();
                        location.reload(); // Reload to update status
                    }, 1500);
                } else {
                    showAvatarMessage(response.message || 'Có lỗi xảy ra khi cập nhật ảnh!', 'error');
                }
            } catch (e) {
                showAvatarMessage('Có lỗi xảy ra khi xử lý phản hồi!', 'error');
            }
        } else {
            showAvatarMessage('Có lỗi xảy ra khi tải lên ảnh!', 'error');
        }
    });
    
    xhr.addEventListener('error', function() {
        showProgress(false);
        showAvatarMessage('Có lỗi xảy ra khi tải lên ảnh!', 'error');
    });
    
    // Get route from data attributes
    const userData = document.getElementById('user-data');
    const avatarUploadRoute = userData.dataset.avatarUploadRoute;
    
    xhr.open('POST', avatarUploadRoute);
    xhr.send(formData);
};

// File input change handler
document.addEventListener('DOMContentLoaded', function() {
    const usernameBank = document.getElementById("username_bank_input").value;
    if (usernameBank) {
        document.getElementById("accountName").value = usernameBank;
    }
    
    // Avatar file input change
    const avatarFileInput = document.getElementById('avatarFile');
    if (avatarFileInput) {
        avatarFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                previewNewAvatar(file);
            }
        });
    }
});

function previewNewAvatar(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const newPreview = document.getElementById('newAvatarPreview');
        const newPreviewContainer = document.querySelector('.new-avatar-preview');
        
        newPreview.src = e.target.result;
        newPreviewContainer.style.display = 'flex';
        
        // Hide messages
        hideAvatarMessage();
    };
    reader.readAsDataURL(file);
}

function updateAvatarPreviews(avatarUrl) {
    // Update current avatar preview
    document.getElementById('currentAvatarPreview').src = avatarUrl;
    
    // Update new avatar preview to match current
    document.getElementById('newAvatarPreview').src = avatarUrl;
}

function showProgress(show) {
    const progressContainer = document.querySelector('.upload-progress');
    const uploadBtn = document.getElementById('uploadAvatarBtn');
    
    if (show) {
        progressContainer.style.display = 'block';
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang tải lên...';
    } else {
        progressContainer.style.display = 'none';
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Cập nhật ảnh';
    }
}

function updateProgress(percent) {
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.querySelector('.progress-text');
    
    progressBar.style.width = percent + '%';
    progressText.textContent = `Đang tải lên... ${Math.round(percent)}%`;
}

function showAvatarMessage(message, type) {
    const messagesContainer = document.getElementById('avatarUploadMessages');
    messagesContainer.innerHTML = `
        <div class="upload-message ${type}">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            ${message}
        </div>
    `;
}

function hideAvatarMessage() {
    const messagesContainer = document.getElementById('avatarUploadMessages');
    messagesContainer.innerHTML = '';
}
