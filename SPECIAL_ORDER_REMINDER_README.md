# Chức năng Kiểm tra và Gửi Mail Nhắc nhở Đơn hàng Đặc biệt

## Mô tả
Chức năng này tự động kiểm tra các đơn hàng đặc biệt chưa được phân phối và gửi mail nhắc nhở theo lịch trình:

- **Sau 8 tiếng**: Gửi mail nhắc nhở người dùng phân phối đơn hàng
- **Sau 24 tiếng**: Gửi mail thông báo phạt 30% tổng giá trị đơn hàng (USD)

## Các file đã tạo/cập nhật

### 1. Command
- `app/Console/Commands/CheckSpecialOrdersReminder.php` - Command chính để kiểm tra và gửi mail

### 2. Mail Classes
- `app/Mail/SpecialOrderReminderMail.php` - Mail nhắc nhở sau 8 tiếng
- `app/Mail/SpecialOrderPenaltyMail.php` - Mail phạt sau 24 tiếng

### 3. Email Templates
- `resources/views/emails/special_order_reminder.blade.php` - Template mail nhắc nhở
- `resources/views/emails/special_order_penalty.blade.php` - Template mail phạt

### 4. Database Migration
- `database/migrations/2025_10_21_042320_add_reminder_tracking_to_frozen_orders_table.php` - Thêm trường tracking

### 5. Model Updates
- `app/Models/Frozen_order.php` - Thêm các trường mới vào fillable

### 6. Schedule Configuration
- `app/Console/Kernel.php` - Cấu hình chạy command mỗi giờ

## Cách sử dụng

### 1. Chạy Migration
```bash
php artisan migrate
```

### 2. Chạy Command thủ công (để test)
```bash
php artisan orders:check-special-reminder
```

### 3. Cấu hình Schedule (tự động)
Command sẽ tự động chạy mỗi giờ thông qua Laravel Scheduler. Đảm bảo cron job được cấu hình:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Logic hoạt động

1. **Kiểm tra đơn hàng đặc biệt**: Tìm các đơn hàng có `is_frozen = true`, `spun = true`, và `custom_price` không null
2. **Tính thời gian**: Tính số giờ đã trôi qua từ khi tạo đơn hàng
3. **Gửi mail nhắc nhở**: Nếu đã qua 8-24 giờ và chưa gửi mail nhắc nhở
4. **Gửi mail phạt**: Nếu đã qua 24 giờ và chưa gửi mail phạt
5. **Tracking**: Cập nhật trạng thái đã gửi mail để tránh gửi trùng lặp

## Các trường tracking mới

- `reminder_sent`: Boolean - Đã gửi mail nhắc nhở chưa
- `reminder_sent_at`: Timestamp - Thời gian gửi mail nhắc nhở
- `penalty_sent`: Boolean - Đã gửi mail phạt chưa  
- `penalty_sent_at`: Timestamp - Thời gian gửi mail phạt

## Lưu ý

- Đảm bảo cấu hình mail trong `.env` đã đúng
- Command sẽ chỉ gửi mail một lần cho mỗi trạng thái (nhắc nhở/phạt)
- Số tiền phạt được tính là 30% của `custom_price` (đơn vị USD)
- Command chạy mỗi giờ để đảm bảo kiểm tra kịp thời
