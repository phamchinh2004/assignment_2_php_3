# Hướng dẫn: Thông tin số tiền cần nạp cho đơn bị phạt

## 📋 Tổng quan

Hiển thị số tiền người dùng cần nạp thêm để có thể phân phối đơn hàng bị phạt.

---

## 💡 Công thức tính

```javascript
Số tiền cần nạp = Tổng tiền phân phối - Số dư hiện tại

Trong đó:
- Tổng tiền phân phối = Giá sản phẩm × Số lượng
- Số dư hiện tại = Balance của user
```

### Ví dụ:

**Trường hợp 1: Cần nạp thêm tiền**
```
Tổng tiền phân phối: $100
Số dư hiện tại: $60
→ Cần nạp thêm: $100 - $60 = $40
```

**Trường hợp 2: Đủ tiền**
```
Tổng tiền phân phối: $100
Số dư hiện tại: $120
→ Cần nạp thêm: $100 - $120 = -$20 (không hiển thị)
```

---

## 🎨 Giao diện

### Khi CẦN nạp thêm tiền:

```
┌────────────────────────────────────────────┐
│ ⚠ Đơn hàng bị phạt do quá thời hạn!       │
│ • Bạn đã nhận thông báo qua email          │
│ • Tiền phạt: $30.00 (30% giá trị đơn)     │
│ • Cần nạp thêm: $40.00 để có thể phân phối │ ← MỚI
│ • Vui lòng nạp tiền và hoàn thành          │
└────────────────────────────────────────────┘
```

### Khi ĐỦ tiền:

```
┌────────────────────────────────────────────┐
│ ⚠ Đơn hàng bị phạt do quá thời hạn!       │
│ • Bạn đã nhận thông báo qua email          │
│ • Tiền phạt: $30.00 (30% giá trị đơn)     │
│ • Vui lòng hoàn thành phân phối sớm nhất   │
└────────────────────────────────────────────┘
```

---

## 🔧 Triển khai

### 1. Truyền balance của user vào JavaScript

**File:** `resources/views/user/order.blade.php`

```javascript
const userBalance = @json($user->balance ?? 0);
```

### 2. Tính toán số tiền cần nạp

**File:** `resources/js/user/order.js`

```javascript
// Tính số tiền cần nạp thêm cho đơn bị phạt
const total_payment_needed = frozen_order.order.quantity * price;
const money_need_to_deposit = total_payment_needed - userBalance;
const money_need_to_deposit_formatted = format_currency(money_need_to_deposit);
```

### 3. Hiển thị có điều kiện

```javascript
${money_need_to_deposit > 0 ? `
    <p>• <strong>Cần nạp thêm: ${money_need_to_deposit_formatted}</strong></p>
` : ''}
```

### 4. Thêm CSS cho highlight

**File:** `resources/css/user/order.css`

```css
.money_need_highlight {
    background-color: #fff3cd;
    padding: 5px 10px;
    border-radius: 4px;
    border: 1px solid #ffc107;
    display: inline-block;
    margin: 2px 0;
}
```

---

## 📊 Các trường hợp

### Case 1: User có đủ tiền
```
Đơn hàng: $100
Số dư: $150
→ KHÔNG hiển thị thông tin nạp tiền
→ Hiển thị: "Vui lòng hoàn thành phân phối sớm nhất"
```

### Case 2: User thiếu tiền
```
Đơn hàng: $100
Số dư: $50
→ Hiển thị: "Cần nạp thêm: $50.00"
→ Hiển thị: "Vui lòng nạp tiền và hoàn thành phân phối"
```

### Case 3: User không có tiền
```
Đơn hàng: $100
Số dư: $0
→ Hiển thị: "Cần nạp thêm: $100.00"
→ Hiển thị: "Vui lòng nạp tiền và hoàn thành phân phối"
```

---

## 🎯 Logic hiển thị

```javascript
if (frozen_order.penalty_sent == 1 && frozen_order.is_frozen == 1) {
    // Đơn bị phạt và chưa hoàn thành
    
    if (money_need_to_deposit > 0) {
        // Thiếu tiền → Hiển thị số tiền cần nạp
        "• Cần nạp thêm: ${money_need_to_deposit_formatted}"
        "• Vui lòng nạp tiền và hoàn thành phân phối"
    } else {
        // Đủ tiền → Không hiển thị
        "• Vui lòng hoàn thành phân phối sớm nhất"
    }
}
```

---

## 📱 Responsive

Thông tin hiển thị tốt trên cả desktop và mobile:

**Desktop:**
```
• Cần nạp thêm: $40.00 để có thể phân phối
```

**Mobile (auto wrap):**
```
• Cần nạp thêm: $40.00 
  để có thể phân phối
```

---

## ✨ Tính năng

- ✅ Tự động tính toán số tiền cần nạp
- ✅ Chỉ hiển thị khi thiếu tiền (> 0)
- ✅ Format currency đúng chuẩn
- ✅ Text động theo tình trạng balance
- ✅ Màu sắc nổi bật (đỏ cho warning)
- ✅ Responsive trên mobile

---

## 🔍 Testing

### Test Case 1: Đủ tiền
1. User có balance = $200
2. Đơn hàng bị phạt = $100
3. Truy cập /order
4. **Kỳ vọng:** Không thấy dòng "Cần nạp thêm"

### Test Case 2: Thiếu tiền
1. User có balance = $50
2. Đơn hàng bị phạt = $100
3. Truy cập /order
4. **Kỳ vọng:** Thấy "Cần nạp thêm: $50.00"

### Test Case 3: Không có tiền
1. User có balance = $0
2. Đơn hàng bị phạt = $100
3. Truy cập /order
4. **Kỳ vọng:** Thấy "Cần nạp thêm: $100.00"

---

## 📁 Files đã thay đổi

1. ✅ `resources/views/user/order.blade.php` - Truyền userBalance
2. ✅ `resources/js/user/order.js` - Logic tính toán và hiển thị
3. ✅ `resources/css/user/order.css` - Style cho highlight

---

## 💡 Lưu ý

### 1. Balance thay đổi real-time
Balance được load khi trang load, nếu user nạp tiền sau đó cần refresh trang để thấy cập nhật.

### 2. Penalty không tính vào
Penalty chỉ được trừ SAU KHI phân phối xong, nên không tính vào số tiền cần nạp để phân phối.

### 3. Chỉ hiển thị cho đơn chưa hoàn thành
```javascript
frozen_order.penalty_sent == 1 && frozen_order.is_frozen == 1
```
Chỉ áp dụng cho đơn bị phạt VÀ chưa phân phối.

---

## 🎉 Kết quả

Người dùng giờ biết chính xác:
- ✅ Đơn hàng bị phạt bao nhiêu
- ✅ Cần nạp thêm bao nhiêu tiền để phân phối
- ✅ Hành động cần làm tiếp theo

→ Tăng trải nghiệm người dùng và giảm confusion! 🚀

