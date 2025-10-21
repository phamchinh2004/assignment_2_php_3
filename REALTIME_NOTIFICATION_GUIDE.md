# Hướng dẫn Thông báo Realtime khi Nạp tiền

## 📋 Tổng quan

Hệ thống thông báo realtime sử dụng Laravel Broadcasting (Pusher/Reverb) để hiển thị thông báo ngay lập tức khi admin nạp tiền cho user.

---

## ✨ Tính năng

- ✅ **Thông báo realtime:** User nhận thông báo ngay khi admin nạp tiền
- ✅ **2 loại notification:** Tiền nạp thật (💰) và Tiền thưởng (🎁)  
- ✅ **Hiệu ứng đẹp mắt:** Gradient background, shimmer effect, animation
- ✅ **Âm thanh thông báo:** Play sound khi nhận thông báo
- ✅ **Cập nhật balance tự động:** Số dư hiển thị cập nhật realtime
- ✅ **Responsive:** Hoạt động tốt trên mobile
- ✅ **Auto dismiss:** Tự động đóng sau 8 giây

---

## 🎨 Giao diện

### Design: Sang trọng, Chuyên nghiệp, Không màu mè

**Đặc điểm:**
- ✅ Background trắng tinh khiết
- ✅ Border trái 4px để phân loại (Xanh = Normal, Vàng = Bonus)
- ✅ Shadow mềm mại, tạo depth chuyên nghiệp
- ✅ Icons FontAwesome trong box màu pastel
- ✅ Typography sạch sẽ, spacing chuẩn
- ✅ Animation: Trượt từ phải sang với bounce nhẹ
- ✅ Progress bar đếm ngược tự đóng

### Notification Tiền Thật (Normal)
```
┌────────────────────────────────────┐
│                               × │
│ ┌────┐                            │
│ │ 💵 │  Nạp tiền                  │
│ └────┘  Tài khoản đã được nạp    │
│                                    │
│         +$100.00                   │
│  ────────────────────────────────  │
│  Số dư: $250.00    từ Admin Nguyễn │
│  ▓░░░░░░░░░░░░░░░░░░░░░░░░░░      │ ← Progress bar
└────────────────────────────────────┘
Border trái: Xanh lá (#10b981)
```

### Notification Tiền Thưởng (Bonus)
```
┌────────────────────────────────────┐
│                               × │
│ ┌────┐                            │
│ │ 🎁 │  Tiền thưởng               │
│ └────┘  Bạn đã nhận tiền thưởng   │
│                                    │
│         +$50.00                    │
│  ────────────────────────────────  │
│  Số dư: $150.00      từ Admin Trần │
│  ▓░░░░░░░░░░░░░░░░░░░░░░░░░░      │ ← Progress bar
└────────────────────────────────────┘
Border trái: Vàng cam (#f59e0b)
```

---

## 🔧 Cấu trúc Code

### 1. Event: `MoneyDeposited`
**File:** `app/Events/MoneyDeposited.php`

Broadcast thông tin khi admin nạp tiền:
- `userId`: ID user nhận tiền
- `amount`: Số tiền nạp
- `newBalance`: Số dư mới
- `transactionType`: 'normal' hoặc 'bonus'
- `adminName`: Tên admin nạp tiền

### 2. Controller: `UserController@plus_money`
**File:** `app/Http/Controllers/Admin/UserController.php`

Sau khi nạp tiền thành công, dispatch event:
```php
event(new \App\Events\MoneyDeposited(
    $user_id,
    $value,
    $get_user->balance,
    $isRealDeposit ? 'normal' : 'bonus',
    Auth::user()->full_name
));
```

### 3. CSS: Notification Styles
**File:** `resources/css/user/notification.css`

Styles cho notification container và animations.

### 4. JavaScript: NotificationManager
**File:** `resources/js/user/notification.js`

Class quản lý hiển thị notifications và listen events.

### 5. Layout: Include Scripts
**File:** `resources/views/user/layouts/master.blade.php`

Include CSS và JS trong layout chính.

---

## 📡 Broadcasting Channel

```php
// routes/channels.php
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
```

Mỗi user có private channel riêng: `user.{userId}`

---

## 🚀 Cách hoạt động

### Flow:

1. **Admin nạp tiền:**
   ```
   Admin clicks "Thêm tiền" 
   → Nhập số tiền và chọn loại (Tiền thật/Thưởng)
   → Submit
   ```

2. **Backend xử lý:**
   ```
   UserController@plus_money
   → Cập nhật balance trong DB
   → Lưu transaction history
   → Dispatch MoneyDeposited event
   ```

3. **Event Broadcasting:**
   ```
   MoneyDeposited event
   → Broadcast to private channel: user.{userId}
   → Pusher/Reverb gửi đến client
   ```

4. **Client nhận realtime:**
   ```
   Echo listen on channel user.{userId}
   → Receive event .money.deposited
   → NotificationManager.show(data)
   → Hiển thị notification
   → Play sound
   → Update balance display
   ```

---

## 🧪 Testing

### Test 1: Nạp tiền thật
1. Login vào trang user (browser 1)
2. Login vào admin panel (browser 2)
3. Admin: Vào "Quản lý người dùng"
4. Click nút "+" (Thêm tiền) cho user
5. Nhập số tiền: 100
6. Chọn: "Có" (Tiền nạp thật)
7. Submit

**Kỳ vọng:**
- ✅ Notification trượt từ bên phải màn hình với bounce nhẹ
- ✅ Background trắng, border trái xanh lá, icon 💵
- ✅ Hiển thị: "Nạp tiền" + số tiền + số dư + tên admin
- ✅ Progress bar màu xanh chạy từ 100% → 0% trong 8 giây
- ✅ Số dư cập nhật với fade effect và highlight xanh nhạt
- ✅ Có âm thanh thông báo (nếu có file audio)
- ✅ Tự động đóng sau 8 giây hoặc click × để đóng ngay

### Test 2: Nạp tiền thưởng
1-5: Same as Test 1
6. Chọn: "Không" (Tiền thưởng)
7. Submit

**Kỳ vọng:**
- ✅ Notification trượt từ bên phải với animation tương tự
- ✅ Background trắng, border trái vàng cam, icon 🎁
- ✅ Hiển thị: "Tiền thưởng" + số tiền + số dư + tên admin
- ✅ Progress bar màu vàng cam chạy từ 100% → 0%
- ✅ Số dư cập nhật với fade effect

### Test 3: Multi-user
1. Login 2 user khác nhau (2 browsers)
2. Admin nạp tiền cho user A
3. Chỉ user A nhận notification, user B không nhận

**Kỳ vọng:**
- ✅ Notification chỉ hiện cho đúng user
- ✅ Private channel hoạt động đúng

---

## 🎯 Customization

### Thay đổi thời gian auto-dismiss:

**File:** `resources/js/user/notification.js`

```javascript
// Line ~42
setTimeout(() => {
    notification.classList.add('hiding');
    setTimeout(() => {
        notification.remove();
    }, 400);
}, 8000); // ← Đổi 8000 thành số ms khác (5000 = 5 giây)
```

### Thay đổi màu sắc notification:

**File:** `resources/css/user/notification.css`

```css
/* Normal deposit - màu xanh lá */
.notification-item.normal {
    border-left-color: #10b981; /* Border trái */
}

.notification-item.normal .notification-icon {
    background: #d1fae5; /* Background icon */
    color: #065f46; /* Màu icon */
}

.notification-item.normal .notification-amount {
    color: #059669; /* Màu số tiền */
}

.notification-item.normal .notification-progress {
    color: #10b981; /* Progress bar */
}

/* Bonus deposit - màu vàng cam */
.notification-item.bonus {
    border-left-color: #f59e0b;
}

.notification-item.bonus .notification-icon {
    background: #fef3c7;
    color: #92400e;
}

.notification-item.bonus .notification-amount {
    color: #d97706;
}

.notification-item.bonus .notification-progress {
    color: #f59e0b;
}
```

### Thêm/Thay âm thanh:

**Hiện tại:** Hệ thống đã có âm thanh tự động bằng **Web Audio API** (2 beep)

**Nếu muốn dùng file audio chuyên nghiệp:**

#### Option 1: Tạo audio ngay trong project
1. Mở file: `public/audio/generate-notification-sound.html` trong browser
2. Chọn preset (Success/Notification/Coin/Ding)
3. Click "Nghe thử" để test
4. Click "Download WAV"
5. Convert WAV → MP3 tại: https://cloudconvert.com/wav-to-mp3
6. Đổi tên thành `notification.mp3`
7. Copy vào `public/audio/`

#### Option 2: Download miễn phí
1. **Pixabay:** https://pixabay.com/sound-effects/search/notification/
2. **Mixkit:** https://mixkit.co/free-sound-effects/notification/
3. Download MP3, đổi tên thành `notification.mp3`
4. Copy vào `public/audio/`

Xem chi tiết trong: `public/audio/DOWNLOAD_AUDIO.md`

---

## 🐛 Troubleshooting

### Vấn đề 1: Không nhận notification

**Kiểm tra:**
```javascript
// Mở Console (F12) trên browser user
// Xem có log này không:
"Echo connected"
"Money deposited event received: {data}"
```

**Nếu không thấy:**
- Kiểm tra Pusher/Reverb đang chạy chưa
- Kiểm tra `.env` có đúng PUSHER credentials
- Kiểm tra browser console có lỗi không

### Vấn đề 2: Echo is not defined

**Nguyên nhân:** Vite chưa build hoặc Echo chưa load

**Giải pháp:**
```bash
npm run dev
# hoặc
npm run build
```

### Vấn đề 3: Notification không có âm thanh

**Nguyên nhân:** File audio không tồn tại hoặc browser block autoplay

**Giải pháp:**
- Thêm file `public/audio/notification.mp3`
- Hoặc bỏ qua audio (console sẽ log "No notification sound available")

### Vấn đề 4: Balance không update

**Kiểm tra:**
- Element có `id="so_du_user"` tồn tại không?
- Hoặc element có `data-user-balance` attribute

---

## 📱 Mobile Responsive

Notification tự động responsive:
- Desktop: Top-right, max-width 400px
- Mobile: Full width với padding 10px

CSS media query:
```css
@media screen and (max-width: 768px) {
    .notification-container {
        top: 60px;
        right: 10px;
        left: 10px;
        max-width: none;
    }
}
```

---

## 📁 Files Đã Tạo/Sửa

### Files Mới:
1. ✅ `app/Events/MoneyDeposited.php`
2. ✅ `resources/css/user/notification.css`
3. ✅ `resources/js/user/notification.js`

### Files Đã Sửa:
1. ✅ `app/Http/Controllers/Admin/UserController.php` - Thêm event dispatch
2. ✅ `resources/views/user/layouts/master.blade.php` - Include CSS/JS

### Files Đã Có (Không cần sửa):
1. ✅ `routes/channels.php` - Channel authorization đã có sẵn

---

## 🎉 Kết luận

Hệ thống notification realtime đã hoàn tất với:
- ✅ Broadcasting event khi admin nạp tiền
- ✅ User nhận notification realtime
- ✅ UI/UX đẹp mắt với animations
- ✅ Âm thanh thông báo
- ✅ Auto update balance
- ✅ Responsive design

**Không cần migration!** Tất cả hoạt động với cơ sở dữ liệu hiện tại.

---

## 🔗 Related

- Laravel Broadcasting: https://laravel.com/docs/broadcasting
- Pusher Documentation: https://pusher.com/docs
- Laravel Reverb: https://laravel.com/docs/reverb

