// Chat Component JavaScript Functions

/**
 * Copy quick message to textarea
 */
function copyQuickMessage(message) {
    const textarea = document.getElementById('message-input-textarea');
    if (textarea) {
        textarea.value = message;
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
        
        // Trigger input event để Livewire nhận được giá trị
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        
        // Focus vào textarea
        textarea.focus();
        
        // Hiển thị thông báo nhỏ
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 end-0 m-3 alert alert-success';
        toast.style.zIndex = '9999';
        toast.innerHTML = '<i class="fas fa-check-circle me-2"></i>Đã sao chép tin nhắn!';
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 2000);
    }
}

/**
 * Confirm delete all messages in conversation
 */
function confirmDeleteMessages() {
    swal({
        title: "Xác nhận xóa",
        text: "Bạn có chắc muốn xóa tất cả tin nhắn trong đoạn chat này?",
        icon: "warning",
        buttons: {
            cancel: "Hủy",
            confirm: {
                text: "Xóa",
                value: true,
                className: "btn-danger"
            }
        },
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            Livewire.find(document.getElementById('chat-root').getAttribute('wire:id')).call('deleteAllMessages');
        }
    });
}

// Export functions to window for global access
window.copyQuickMessage = copyQuickMessage;
window.confirmDeleteMessages = confirmDeleteMessages;

