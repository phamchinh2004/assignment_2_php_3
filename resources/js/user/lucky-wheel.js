// ======================= LUCKY WHEEL - VÒNG QUAY MAY MẮN ======================= 

document.addEventListener('DOMContentLoaded', function() {
    const prizeWheel = document.getElementById('prizeWheel');
    const spinButton = document.getElementById('wheelSpinButton');
    const wheelSpinSound = document.getElementById('wheelSpinSound');
    const applauseSound = document.getElementById('applauseSound');
    
    let isSpinning = false;
    let currentRotation = 0;
    
    // Danh sách giải thưởng (8 phần) khớp với HTML
    const prizes = [
        { name: 'SH Mode', icon: 'fas fa-motorcycle', index: 0, tier: 'high' },
        { name: '$2', icon: 'fas fa-dollar-sign', index: 1, tier: 'low' },
        { name: 'Chúc bạn may mắn lần sau', icon: 'fas fa-gem', index: 2, tier: 'lose' },
        { name: '$10', icon: 'fas fa-dollar-sign', index: 3, tier: 'mid' },
        { name: '$2', icon: 'fas fa-dollar-sign', index: 4, tier: 'low' },
        { name: '$5', icon: 'fas fa-dollar-sign', index: 5, tier: 'low' },
        { name: 'Chúc bạn may mắn lần sau', icon: 'fas fa-gem', index: 6, tier: 'lose' },
        { name: '$2', icon: 'fas fa-dollar-sign', index: 7, tier: 'low' }
    ];
    
    // Tăng xác suất cho ô "Chúc bạn may mắn lần sau" và các ô tiền thấp
    // Bỏ hoàn toàn các giải trị giá cao
    const weightedPool = [
        { ...prizes[1], weight: 4 }, // $2 (slice-2)
        { ...prizes[4], weight: 4 }, // $2 (slice-5)
        { ...prizes[7], weight: 4 }, // $2 (slice-8)
        { ...prizes[5], weight: 3 }, // $5 (slice-6)
        { ...prizes[3], weight: 1 }, // $10 (slice-4) - tỷ lệ thấp
        { ...prizes[2], weight: 6 }, // Chúc bạn may mắn (slice-3)
        { ...prizes[6], weight: 6 }  // Chúc bạn may mắn (slice-7)
    ];
    
    function pickWeightedPrize() {
        const totalWeight = weightedPool.reduce((sum, p) => sum + p.weight, 0);
        const r = Math.random() * totalWeight;
        let acc = 0;
        for (const p of weightedPool) {
            acc += p.weight;
            if (r <= acc) return p;
        }
        return weightedPool[weightedPool.length - 1];
    }
    
    // Hàm quay vòng
    window.spinWheel = async function() {
        if (isSpinning) return;
        
        isSpinning = true;
        spinButton.classList.add('spinning');
        spinButton.disabled = true;
        
        // Chọn giải thưởng theo trọng số (ưu tiên lose và tiền thấp)
        const prize = pickWeightedPrize();
        const randomPrizeIndex = prize.index; // Lấy index thực tế trên vòng quay
        
        try {
            // Gọi API để kiểm tra và lưu lịch sử quay
            const response = await fetch('/spin-lucky-wheel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    prize: prize.name
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                // Nếu không được phép quay, hiển thị thông báo và reset
                Swal.fire({
                    icon: 'warning',
                    title: 'Thông báo',
                    text: data.message,
                    confirmButtonText: 'Đóng'
                });
                
                isSpinning = false;
                spinButton.classList.remove('spinning');
                spinButton.disabled = false;
                return;
            }
            
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
                if (applauseSound) {
                    applauseSound.currentTime = 0;
                    applauseSound.play();
                }
                
                // Hiển thị modal giải thưởng
                showPrizeModal(prize);
                
                // Không enable lại nút - đã quay rồi
                // spinButton.disabled vẫn = true
            }, 4000);
            
        } catch (error) {
            console.error('Error spinning wheel:', error);
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: 'Có lỗi xảy ra. Vui lòng thử lại!',
                confirmButtonText: 'Đóng'
            });
            
            isSpinning = false;
            spinButton.classList.remove('spinning');
            spinButton.disabled = false;
        }
    };
    
    // Thêm event listener cho nút quay
    if (spinButton) {
        spinButton.addEventListener('click', spinWheel);
    }
    
    // Hàm hiển thị modal giải thưởng
    function showPrizeModal(prize) {
        const modal = document.getElementById('prizeModalOverlay');
        const prizeIconDisplay = document.getElementById('prizeIconDisplay');
        const prizeTextDisplay = document.getElementById('prizeTextDisplay');
        const confettiContainer = document.getElementById('prizeConfetti');
        
        // Set icon và text
        prizeIconDisplay.className = `prize-icon-display ${prize.icon}`;
        prizeTextDisplay.textContent = prize.name;
        
        // Tạo confetti
        createConfetti(confettiContainer);
        
        // Hiển thị modal
        modal.classList.add('show');
    }
    
    // Hàm đóng modal
    window.closePrizeModal = function() {
        const modal = document.getElementById('prizeModalOverlay');
        modal.classList.remove('show');
        
        // Xóa confetti
        const confettiContainer = document.getElementById('prizeConfetti');
        if (confettiContainer) {
            confettiContainer.innerHTML = '';
        }
        
        // Reload trang để cập nhật trạng thái
        setTimeout(() => {
            window.location.reload();
        }, 300);
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
});

