document.addEventListener('livewire:initialized', () => {
    // Auto-select conversation/user from hash
    function selectFromHash() {
        const hash = window.location.hash;
        
        if (hash && hash.startsWith('#user-')) {
            const userId = hash.replace('#user-', '');
            
            // Tìm Livewire component
            const chatRoot = document.getElementById('chat-root');
            if (chatRoot) {
                const component = window.Livewire.find(chatRoot.getAttribute('wire:id'));
                
                if (component) {
                    // Gọi method selectUserForChat
                    component.call('selectUserForChat', userId).then(() => {
                        console.log('✅ Auto-selected user:', userId);
                        
                        // Đợi một chút rồi scroll vào conversation
                        setTimeout(() => {
                            highlightSelectedConversation();
                        }, 500);
                    }).catch(error => {
                        console.error('❌ Error selecting user:', error);
                    });
                }
            }
        }
    }
    
    // Highlight conversation đã chọn trong sidebar
    function highlightSelectedConversation() {
        // Tìm conversation item được chọn (có background màu xanh nhạt)
        const selectedItems = document.querySelectorAll('.conversation-item[style*="background"]');
        
        if (selectedItems.length > 0) {
            const selectedItem = selectedItems[0];
            
            // Scroll vào view
            selectedItem.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
            
            // Thêm hiệu ứng pulse
            selectedItem.style.animation = 'pulse 1s ease-in-out';
            
            setTimeout(() => {
                selectedItem.style.animation = '';
            }, 1000);
        }
    }
    
    // Gọi khi page load
    setTimeout(() => {
        selectFromHash();
    }, 800);
    
    // Lắng nghe khi hash thay đổi
    window.addEventListener('hashchange', selectFromHash);
});

// Animation cho pulse effect
if (!document.querySelector('#chat-pulse-animation')) {
    const style = document.createElement('style');
    style.id = 'chat-pulse-animation';
    style.textContent = `
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.03); box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4); }
        }
    `;
    document.head.appendChild(style);
}

