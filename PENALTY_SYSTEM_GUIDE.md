# Hướng dẫn Hệ thống Quản lý Tiền Phạt

## 📋 Tổng quan

Hệ thống tự động phạt người dùng khi không hoàn thành đơn hàng đặc biệt trong vòng 24 giờ kể từ khi nhận.

---

## 🎯 Tính năng đã triển khai

### 1. **Database**
- ✅ Thêm cột `penalty_amount` vào bảng `frozen_orders`
- ✅ Lưu số tiền phạt (30% giá trị đơn hàng)
- ✅ Migration: `2025_10_21_142344_add_penalty_amount_to_frozen_orders_table.php`

### 2. **Backend Logic**
- ✅ Command `CheckSpecialOrdersReminder` tự động lưu penalty_amount khi gửi mail phạt
- ✅ Controller `OrderController` trừ tiền phạt khỏi lợi nhuận khi phân phối
- ✅ Lưu lịch sử transaction với type "penalty"

### 3. **Giao diện User**
- ✅ Hiển thị badge "⚠ BỊ PHẠT" cho đơn bị phạt
- ✅ Border đỏ highlight đơn bị phạt
- ✅ Hiển thị chi tiết tiền phạt trong bảng tính toán
- ✅ Warning box cho đơn bị phạt chưa hoàn thành
- ✅ Info box cho đơn đã hoàn thành nhưng bị phạt
- ✅ Responsive design cho mobile

---

## 📊 Luồng hoạt động

### Kịch bản 1: Người dùng BỊ PHẠT nhưng chưa hoàn thành đơn

1. **Sau 24 giờ không phân phối:**
   - ✉️ Hệ thống gửi mail thông báo phạt
   - 💾 Lưu `penalty_amount` vào database
   - 📝 Cập nhật `penalty_sent = true`, `penalty_sent_at = now()`

2. **Hiển thị trên giao diện:**
   ```
   ┌─────────────────────────────────────────┐
   │ 📦 Thông báo đơn hàng...   [⚠ BỊ PHẠT]  │
   │ Mã: ORD123                               │
   │ ┌─────────────────────────────────────┐ │
   │ │ [Ảnh sản phẩm] Tên sản phẩm        │ │
   │ └─────────────────────────────────────┘ │
   │                                          │
   │ Tổng tiền:        $100.00                │
   │ Chiết khấu:       $10.00                 │
   │ ⚠ Tiền phạt (30%): -$30.00              │
   │ Hoàn nhập:        -$20.00                │
   │                                          │
   │ ┌────────────────────────────────────┐  │
   │ │ ⚠ Đơn hàng bị phạt do quá hạn!    │  │
   │ │ • Tiền phạt: $30.00 (30% đơn)     │  │
   │ │ • Vui lòng hoàn thành phân phối   │  │
   │ └────────────────────────────────────┘  │
   │                                          │
   │ [      Phân phối ngay      ]            │
   └─────────────────────────────────────────┘
   ```

### Kịch bản 2: Người dùng HOÀN THÀNH đơn đã bị phạt

1. **Khi click "Phân phối ngay":**
   - 💰 Tính profit = chiết khấu - tiền phạt
   - 📝 Lưu transaction history (order, profit, penalty)
   - ✅ Cập nhật balance của user
   - 🎉 Thông báo thành công

2. **Hiển thị sau khi hoàn thành:**
   ```
   ┌─────────────────────────────────────────┐
   │ 📦 Thông báo đơn hàng...   [⚠ BỊ PHẠT]  │
   │ Mã: ORD123                  [✓ Thành công]│
   │ ┌─────────────────────────────────────┐ │
   │ │ [Ảnh sản phẩm] Tên sản phẩm        │ │
   │ └─────────────────────────────────────┘ │
   │                                          │
   │ Tổng tiền:        $100.00                │
   │ Chiết khấu:       $10.00                 │
   │ ⚠ Tiền phạt (30%): -$30.00              │
   │ Hoàn nhập:        -$20.00                │
   │                                          │
   │ ┌────────────────────────────────────┐  │
   │ │ ℹ️ Thông tin phạt:                 │  │
   │ │ • Đơn đã hoàn thành nhưng bị phạt │  │
   │ │ • Tiền phạt $30 đã trừ khỏi lợi   │  │
   │ └────────────────────────────────────┘  │
   └─────────────────────────────────────────┘
   ```

---

## 🚀 Triển khai

### 1. Chạy Migration

```bash
# Trên local
php artisan migrate

# Trên server (sau khi push code)
cd /path/to/project
git pull
php artisan migrate
```

### 2. Kiểm tra Database

```sql
-- Kiểm tra cột mới
DESC frozen_orders;

-- Xem đơn bị phạt
SELECT id, user_id, order_id, penalty_sent, penalty_amount, created_at 
FROM frozen_orders 
WHERE penalty_sent = 1;
```

### 3. Test Chức năng

#### Test 1: Kiểm tra mail gửi và lưu penalty
```bash
# Chạy command thủ công
php artisan orders:check-special-reminder

# Xem log
tail -f storage/logs/laravel.log
```

#### Test 2: Kiểm tra giao diện
1. Tạo đơn test bị phạt trong database:
```sql
UPDATE frozen_orders 
SET penalty_sent = 1, 
    penalty_amount = 30.00,
    penalty_sent_at = NOW() 
WHERE id = 1;
```

2. Truy cập `/order` và kiểm tra:
   - ✅ Badge "BỊ PHẠT" hiển thị
   - ✅ Border đỏ
   - ✅ Dòng tiền phạt trong table
   - ✅ Warning box hiển thị

#### Test 3: Kiểm tra phân phối
1. Click "Phân phối ngay" cho đơn bị phạt
2. Kiểm tra:
   - ✅ Balance được cập nhật đúng (trừ penalty)
   - ✅ Transaction history có record "penalty"
   - ✅ Giao diện chuyển sang trạng thái "đã hoàn thành"

---

## 📁 Files đã thay đổi

### Database & Models
- `database/migrations/2025_10_21_142344_add_penalty_amount_to_frozen_orders_table.php` (MỚI)
- `app/Models/Frozen_order.php` - Thêm 'penalty_amount' vào fillable

### Backend
- `app/Console/Commands/CheckSpecialOrdersReminder.php` - Lưu penalty_amount
- `app/Http/Controllers/User/OrderController.php` - Xử lý penalty khi phân phối

### Frontend
- `resources/css/user/order.css` - Styles cho penalty UI
- `resources/js/user/order.js` - Logic hiển thị penalty

---

## 🎨 CSS Classes mới

```css
.penalized                 /* Áp dụng cho order_item bị phạt */
.penalty_badge            /* Badge "⚠ BỊ PHẠT" */
.penalty_info             /* Info box (đã hoàn thành) */
.penalty_info_warning     /* Warning box (chưa hoàn thành) */
.penalty_text             /* Text màu vàng */
.penalty_text_danger      /* Text màu đỏ */
.penalty_amount           /* Số tiền phạt */
.penalty_row              /* Dòng tiền phạt trong table */
```

---

## 💡 Logic tính toán

### Công thức:

```javascript
// Chiết khấu ban đầu
profit = (price × quantity) × commission_percentage

// Nếu bị phạt
penalty_amount = custom_price × 0.3  // 30%

// Lợi nhuận thực tế
actual_profit = profit - penalty_amount

// Số tiền hoàn nhập
refund = (price × quantity) + profit - penalty_amount
```

### Ví dụ:

```
Đơn hàng: $100
Chiết khấu: 10% = $10
Penalty: 30% × $100 = $30

→ Số tiền hoàn nhập: $100 + $10 - $30 = $80
→ Lợi nhuận thực: $10 - $30 = -$20 (LỖ!)
```

---

## 🔍 Troubleshooting

### Vấn đề 1: Penalty không hiển thị trên giao diện

**Nguyên nhân:** Controller không trả về penalty_amount

**Kiểm tra:**
```bash
# Xem response API
# Mở DevTools → Network → check response của /get-list-orders-by-tab
```

**Giải pháp:** Đảm bảo `select('frozen_orders.*')` trong query

---

### Vấn đề 2: Penalty amount = NULL

**Nguyên nhân:** Chưa chạy migration

**Giải pháp:**
```bash
php artisan migrate
```

---

### Vấn đề 3: Balance sai sau khi phân phối

**Nguyên nhân:** Không trừ penalty trong handle_so_du

**Kiểm tra:**
```php
// Trong OrderController::handle_so_du
$actual_profit = $rose - $penalty_amount; // Phải có dòng này
```

---

## 📞 Support

Nếu gặp vấn đề:
1. Kiểm tra logs: `storage/logs/laravel.log`
2. Kiểm tra database: `SELECT * FROM frozen_orders WHERE penalty_sent = 1`
3. Kiểm tra console browser: F12 → Console tab

---

## ✅ Checklist Triển khai

- [ ] Chạy migration trên local
- [ ] Test gửi mail penalty
- [ ] Test hiển thị giao diện đơn bị phạt
- [ ] Test phân phối đơn bị phạt
- [ ] Kiểm tra transaction history
- [ ] Test responsive trên mobile
- [ ] Push code lên server
- [ ] Chạy migration trên server
- [ ] Test trên server production

---

## 🎉 Kết luận

Hệ thống penalty đã hoàn thiện với:
- ✅ Tự động tính và lưu tiền phạt
- ✅ Giao diện trực quan, dễ hiểu
- ✅ Logic tính toán chính xác
- ✅ Lưu đầy đủ lịch sử transaction
- ✅ Responsive cho mobile

**Số tiền phạt = 30% giá trị đơn hàng**
**Áp dụng cho đơn không hoàn thành trong 24h**

