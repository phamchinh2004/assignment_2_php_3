#!/bin/bash

APP_DIR="/var/www/amazon"

echo "🔧 Sửa lỗi Realtime Server..."
echo ""

# 1. Đảm bảo Redis chạy
echo "1️⃣ Kiểm tra và khởi động Redis..."
sudo systemctl enable redis
sudo systemctl start redis
if redis-cli ping | grep -q "PONG"; then
    echo "   ✅ Redis OK"
else
    echo "   ❌ Redis lỗi - vui lòng kiểm tra: sudo systemctl status redis"
    exit 1
fi

# 2. Copy cấu hình Reverb
echo ""
echo "2️⃣ Cập nhật cấu hình Reverb..."
sudo cp $APP_DIR/reverb.ini /etc/supervisord.d/laravel-reverb.ini

# 3. Đảm bảo quyền file log
echo ""
echo "3️⃣ Kiểm tra quyền file..."
sudo touch $APP_DIR/storage/logs/laravel-reverb.log
sudo touch $APP_DIR/storage/logs/laravel-reverb-error.log
sudo chown apache:apache $APP_DIR/storage/logs/laravel-reverb*.log
sudo chmod 664 $APP_DIR/storage/logs/laravel-reverb*.log

# 4. Reload Supervisor
echo ""
echo "4️⃣ Reload Supervisor..."
sudo supervisorctl reread
sudo supervisorctl update

# 5. Khởi động Reverb
echo ""
echo "5️⃣ Khởi động Reverb..."
sudo supervisorctl start laravel-reverb:*

# 6. Kiểm tra trạng thái
echo ""
echo "6️⃣ Kiểm tra trạng thái..."
sleep 2
sudo supervisorctl status laravel-reverb:*

echo ""
echo "✅ Hoàn tất! Kiểm tra logs:"
echo "   tail -f $APP_DIR/storage/logs/laravel-reverb.log"

