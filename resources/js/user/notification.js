// Notification Manager cho realtime deposit notifications
class NotificationManager {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Tạo container cho notifications
        this.container = document.createElement('div');
        this.container.className = 'notification-container';
        this.container.setAttribute('aria-live', 'polite');
        this.container.setAttribute('aria-label', 'Thông báo giao dịch');
        document.body.appendChild(this.container);
    }

    show(data) {
        const isBonus = data.transaction_type === 'bonus';
        const notification = document.createElement('div');
        notification.className = `notification-item ${isBonus ? 'bonus' : 'normal'}`;
        notification.setAttribute('role', 'status');
        
        // Icon cho từng loại
        const icon = isBonus
            ? '<i class="fas fa-gift"></i>' 
            : '<i class="fas fa-money-bill-wave"></i>';
        
        const title = isBonus ? 'Đã nhận tiền thưởng' : 'Nạp tiền thành công';
        
        const transactionLabel = isBonus ? 'Tiền thưởng' : 'Tiền nạp';
        
        notification.innerHTML = `
            <div class="notification-glow" aria-hidden="true"></div>
            <button class="notification-close" type="button" aria-label="Đóng thông báo">
                <i class="fas fa-xmark" aria-hidden="true"></i>
            </button>
            <div class="notification-header">
                <div class="notification-icon">${icon}</div>
                <div class="notification-heading">
                    <div class="notification-status">
                        <span class="notification-status-dot"></span>
                        Giao dịch hoàn tất
                    </div>
                    <div class="notification-title">${title}</div>
                </div>
            </div>
            <div class="notification-amount-panel">
                <span class="notification-amount-label">${transactionLabel}</span>
                <strong class="notification-amount">+${this.formatCurrency(data.amount)}</strong>
            </div>
            <div class="notification-details">
                <div class="notification-detail">
                    <span class="notification-detail-label">Số dư mới</span>
                    <strong class="notification-balance">${this.formatCurrency(data.new_balance)}</strong>
                </div>
                <div class="notification-source">
                    <span class="notification-source-icon"><i class="fas fa-circle-check"></i></span>
                    <span>Đã xác nhận<br><small>Vừa xong</small></span>
                </div>
            </div>
            <div class="notification-progress"></div>
        `;

        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.hideNotification(notification);
        });

        this.container.appendChild(notification);
        
        // Trigger animation by adding active class
        setTimeout(() => {
            this.container.classList.add('active');
        }, 10);

        // Play sound
        this.playNotificationSound();

        // Update balance in header if exists
        this.updateBalanceDisplay(data.new_balance);

        // Auto remove after 8 seconds
        setTimeout(() => {
            this.hideNotification(notification);
        }, 8000);
    }

    hideNotification(notification) {
        notification.classList.add('hiding');
        setTimeout(() => {
            notification.remove();
            // Remove active class if no more notifications
            if (this.container.children.length === 0) {
                this.container.classList.remove('active');
            }
        }, 400);
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 7
        }).format(amount);
    }

    playNotificationSound() {
        try {
            // Try to play file audio first (if exists)
            const audio = new Audio('/audio/notification_fb.mp3');
            audio.volume = 0.4;
            audio.play().catch(e => {
                // If file not found, use Web Audio API to generate sound
                this.playBeepSound();
            });
        } catch (e) {
            // Fallback to generated sound
            this.playBeepSound();
        }
    }

    playBeepSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Create a pleasant notification sound (2 tones)
            const playTone = (frequency, startTime, duration) => {
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = frequency;
                oscillator.type = 'sine';
                
                // Envelope for smooth sound
                gainNode.gain.setValueAtTime(0, startTime);
                gainNode.gain.linearRampToValueAtTime(0.3, startTime + 0.01);
                gainNode.gain.exponentialRampToValueAtTime(0.01, startTime + duration);
                
                oscillator.start(startTime);
                oscillator.stop(startTime + duration);
            };
            
            const now = audioContext.currentTime;
            playTone(800, now, 0.1);        // First tone
            playTone(1000, now + 0.1, 0.15); // Second tone (higher pitch)
            
        } catch (e) {
            console.log('Web Audio API not supported');
        }
    }

    updateBalanceDisplay(newBalance) {
        // Update balance in header/sidebar if element exists
        const balanceElement = document.getElementById('so_du_user');
        if (balanceElement) {
            const formattedBalance = this.formatCurrency(newBalance);
            
            // Smooth fade transition
            balanceElement.style.transition = 'opacity 0.3s ease';
            balanceElement.style.opacity = '0.3';
            
            setTimeout(() => {
                balanceElement.textContent = `Số dư hiện tại: ${formattedBalance}`;
                balanceElement.style.opacity = '1';
                
                // Add highlight effect
                balanceElement.style.backgroundColor = '#d1fae5';
                setTimeout(() => {
                    balanceElement.style.transition = 'background-color 0.5s ease';
                    balanceElement.style.backgroundColor = '';
                }, 500);
            }, 300);
        }

        // Update in other locations if needed
        const balanceElements = document.querySelectorAll('[data-user-balance]');
        balanceElements.forEach(el => {
            el.style.transition = 'opacity 0.3s ease';
            el.style.opacity = '0.3';
            setTimeout(() => {
                el.textContent = this.formatCurrency(newBalance);
                el.style.opacity = '1';
            }, 300);
        });
    }
}

// Initialize notification manager
const notificationManager = new NotificationManager();

// Listen to money deposited event
if (window.Echo && window.userId) {
    window.Echo.private(`user.${window.userId}`)
        .listen('.money.deposited', (data) => {
            console.log('Money deposited event received:', data);
            notificationManager.show(data);
        });
} else {
    console.warn('Echo or userId not defined. Real-time notifications disabled.');
}
