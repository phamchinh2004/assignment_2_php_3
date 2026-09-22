// ======================= LUCKY WHEEL - VÒNG QUAY MAY MẮN ======================= 

document.addEventListener('DOMContentLoaded', function() {
    const root = document.querySelector('[data-home-page]');
    if (!root) return;

    const prizeWheel = root.querySelector('#prizeWheel');
    const spinButton = root.querySelector('#wheelSpinButton');
    const wheelSpinSound = root.querySelector('#wheelSpinSound');
    const applauseSound = root.querySelector('#applauseSound');
    if (!prizeWheel || !spinButton) return;
    
    let isSpinning = false;
    let currentRotation = 0;
    let lastFocusedElement = null;
    
    // Metadata hiển thị; kết quả thật được quyết định ở backend.
    const prizes = [
        { name: '18 Pro Max', image: '/images/spin/18prm.webp', index: 0 },
        { name: '$2', image: '/images/spin/dollars.webp', index: 1 },
        { name: 'Chúc bạn may mắn lần sau', icon: 'fa-clover', index: 2 },
        { name: '$10', image: '/images/spin/dollars.webp', index: 3 },
        { name: '$2', image: '/images/spin/dollars.webp', index: 4 },
        { name: '$5', image: '/images/spin/dollars.webp', index: 5 },
        { name: 'Chúc bạn may mắn lần sau', icon: 'fa-clover', index: 6 },
        { name: '$2', image: '/images/spin/dollars.webp', index: 7 }
    ];
    
    // Hàm quay vòng
    window.spinWheel = async function() {
        if (isSpinning) return;
        
        isSpinning = true;
        spinButton.classList.add('spinning');
        spinButton.disabled = true;
        
        // Backend quyết định kết quả; client chỉ chạy animation theo prize_index trả về.
        let prize = null;
        let randomPrizeIndex = null;
        
        try {
            // Gọi API để kiểm tra và lưu lịch sử quay
            const response = await fetch('/spin-lucky-wheel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            });
            
            const data = await response.json();
            
            if (!data.success) {
                // Nếu không được phép quay, hiển thị thông báo và reset
                AppDialog.alert({
                    icon: 'warning',
                    title: 'Thông báo',
                    text: data.message,
                    confirmText: 'Đóng'
                });
                
                isSpinning = false;
                spinButton.classList.remove('spinning');
                spinButton.disabled = false;
                return;
            }

            randomPrizeIndex = Number(data.prize_index);
            prize = {
                ...(prizes[randomPrizeIndex] || { index: randomPrizeIndex }),
                name: data.prize
            };
            
            // Nếu được phép quay, tiến hành quay
            // Phát âm thanh quay
            if (wheelSpinSound) {
                wheelSpinSound.currentTime = 0;
                wheelSpinSound.play();
            }
            
            // Tính góc quay
            // Mỗi phần = 360/8 = 45 độ
            // Phần 0 ở trên cùng, quay ngược chiều kim đồng hồ
            const degreesPerSlice = 45;
            const baseRotation = 360 * 5; // Quay 5 vòng
            const targetRotation = baseRotation + (360 - (randomPrizeIndex * degreesPerSlice)) - (degreesPerSlice / 2);
            
            // Quay vòng
            currentRotation = currentRotation % 360;
            const finalRotation = currentRotation + targetRotation;
            
            prizeWheel.style.transform = `rotate(${finalRotation}deg)`;
            currentRotation = finalRotation;
            
            // Sau khi quay xong
            setTimeout(() => {
                isSpinning = false;
                spinButton.classList.remove('spinning');
                
                // Phát âm thanh vỗ tay
                if (data.reward_type !== 'none' && applauseSound) {
                    applauseSound.currentTime = 0;
                    applauseSound.play().catch(() => {});
                }
                
                // Hiển thị modal giải thưởng
                showPrizeModal(prize, data);
                
                // Nếu còn lượt admin cấp thì có thể quay tiếp.
                spinButton.disabled = Number(data.bonus_spins_remaining || 0) <= 0;
            }, 4000);
            
        } catch (error) {
            console.error('Error spinning wheel:', error);
            AppDialog.alert({
                icon: 'error',
                title: 'Lỗi',
                text: 'Có lỗi xảy ra. Vui lòng thử lại!',
                confirmText: 'Đóng'
            });
            
            isSpinning = false;
            spinButton.classList.remove('spinning');
            spinButton.disabled = false;
        }
    };
    
    // Thêm event listener cho nút quay
    spinButton.addEventListener('click', spinWheel);
    
    // Hàm hiển thị modal giải thưởng
    function showPrizeModal(prize, data) {
        const modal = root.querySelector('#prizeModalOverlay');
        const prizeIconDisplay = root.querySelector('#prizeIconDisplay');
        const prizeFallbackIcon = root.querySelector('#prizeFallbackIcon');
        const prizeTextDisplay = root.querySelector('#prizeTextDisplay');
        const prizeTitle = root.querySelector('#prizeModalTitle');
        const prizeSubtitle = root.querySelector('#prizeModalSubtitle');
        const prizeEyebrow = root.querySelector('#prizeModalEyebrow');
        const prizeStatusPill = root.querySelector('#prizeStatusPill');
        const prizeMessage = root.querySelector('#prizeModalMessage');
        const prizeRewardId = root.querySelector('#prizeRewardId');
        const prizePayoutType = root.querySelector('#prizePayoutType');
        const confettiContainer = root.querySelector('#prizeConfetti');
        if (!modal || !prizeIconDisplay || !prizeFallbackIcon || !prizeTextDisplay || !confettiContainer) return;

        const isApproved = data.reward_status === 'approved';
        const isPending = data.reward_status === 'pending';
        const isNoReward = data.reward_status === 'no_reward';
        const stateClass = isApproved ? 'is-approved' : (isPending ? 'is-pending' : 'is-no-reward');

        modal.classList.remove('is-approved', 'is-pending', 'is-no-reward');
        modal.classList.add(stateClass);
        prizeTitle.textContent = isNoReward ? 'Hẹn bạn ở lượt tiếp theo' : 'Chúc mừng bạn!';
        prizeEyebrow.textContent = isNoReward ? 'Kết quả vòng quay' : 'Phần thưởng đã ghi nhận';
        prizeSubtitle.textContent = isApproved
            ? 'Tiền thưởng đã được cộng vào tài khoản của bạn.'
            : (isPending ? 'Phần thưởng đang chờ quản trị viên duyệt.' : 'Lượt quay này chưa có phần thưởng.');
        prizeMessage.textContent = data.reward_message || data.message;
        prizeRewardId.textContent = data.reward_id ? `#${data.reward_id}` : '—';
        prizePayoutType.textContent = isApproved
            ? (data.approval_method === 'automatic' ? 'Tự động cộng tiền' : 'Tiền thưởng')
            : (isPending ? 'Chờ duyệt' : 'Không phát sinh');

        const statusIcon = isApproved ? 'fa-circle-check' : (isPending ? 'fa-clock' : 'fa-clover');
        prizeStatusPill.innerHTML = `<i class="fas ${statusIcon}" aria-hidden="true"></i><span>${data.reward_status_label}</span>`;
        
        if (prize.image && !isNoReward) {
            prizeIconDisplay.src = prize.image;
            prizeIconDisplay.hidden = false;
            prizeFallbackIcon.hidden = true;
        } else {
            prizeIconDisplay.removeAttribute('src');
            prizeIconDisplay.hidden = true;
            prizeFallbackIcon.className = `fas ${prize.icon || 'fa-clover'}`;
            prizeFallbackIcon.hidden = false;
        }
        prizeTextDisplay.textContent = prize.name;
        
        if (!isNoReward) {
            createConfetti(confettiContainer);
        }
        
        lastFocusedElement = document.activeElement;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('prize-modal-open');
        modal.querySelector('.prize-modal-x')?.focus();
    }
    
    // Hàm đóng modal
    window.closePrizeModal = function() {
        const modal = root.querySelector('#prizeModalOverlay');
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('prize-modal-open');
        
        // Xóa confetti
        const confettiContainer = root.querySelector('#prizeConfetti');
        if (confettiContainer) {
            confettiContainer.innerHTML = '';
        }
        lastFocusedElement?.focus?.();
        
        // Reload trang để cập nhật trạng thái
        setTimeout(() => {
            window.location.reload();
        }, 300);
    };

    window.viewPrizeStatus = function() {
        window.location.hash = 'reward-history';
        window.location.reload();
    };
    
    // Hàm tạo confetti
    function createConfetti(container) {
        const colors = ['#FFD700', '#FF6B6B', '#4ECDC4', '#F06292', '#FFD93D', '#667eea'];
        const confettiCount = 50;
        
        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.style.position = 'absolute';
            confetti.style.width = Math.random() * 10 + 5 + 'px';
            confetti.style.height = Math.random() * 10 + 5 + 'px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.top = '-10px';
            confetti.style.opacity = Math.random();
            confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
            confetti.style.animation = `confettiFall ${Math.random() * 3 + 2}s linear forwards`;
            confetti.style.animationDelay = Math.random() * 0.5 + 's';
            
            container.appendChild(confetti);
        }
        
        // Xóa confetti sau khi animation kết thúc
        setTimeout(() => {
            container.innerHTML = '';
        }, 6000);
    }

    root.querySelector('#prizeModalOverlay')?.addEventListener('click', function(event) {
        if (event.target === this) {
            window.closePrizeModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && root.querySelector('#prizeModalOverlay.show')) {
            window.closePrizeModal();
        }
    });
});
