#!/bin/bash

APP_DIR="/var/www/amazon"

echo "🔧 Sửa lỗi Broadcasting..."
echo ""

# 1. Clear cache
echo "1️⃣ Clear cache..."
cd $APP_DIR
sudo -u apache php artisan config:clear
sudo -u apache php artisan cache:clear
sudo -u apache php artisan route:clear
sudo -u apache php artisan view:clear

# 2. Restart queue workers
echo ""
echo "2️⃣ Restart Queue Workers..."
sudo supervisorctl restart laravel-queue:*

# 3. Restart Reverb
echo ""
echo "3️⃣ Restart Reverb..."
sudo supervisorctl restart laravel-reverb:*

# 4. Kiểm tra failed jobs và retry
echo ""
echo "4️⃣ Kiểm tra và retry failed jobs..."
FAILED_COUNT=$(sudo -u apache php artisan queue:failed --json | jq length 2>/dev/null || echo "0")
if [ "$FAILED_COUNT" -gt "0" ]; then
    echo "   ⚠️  Có $FAILED_COUNT failed jobs"
    read -p "   Bạn có muốn retry tất cả failed jobs? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        sudo -u apache php artisan queue:retry all
    fi
else
    echo "   ✅ Không có failed jobs"
fi

# 5. Kiểm tra Redis
echo ""
echo "5️⃣ Kiểm tra Redis..."
if redis-cli ping | grep -q "PONG"; then
    echo "   ✅ Redis đang chạy"
else
    echo "   ❌ Redis không chạy - khởi động..."
    sudo systemctl start redis
fi

# 6. Kiểm tra cấu hình
echo ""
echo "6️⃣ Kiểm tra cấu hình Broadcasting..."
cd $APP_DIR
BROADCAST_DRIVER=$(sudo -u apache php artisan tinker --execute="echo config('broadcasting.default');" 2>/dev/null)
echo "   Broadcast Driver: $BROADCAST_DRIVER"

if [ "$BROADCAST_DRIVER" != "reverb" ]; then
    echo "   ⚠️  Broadcast driver không phải 'reverb'"
    echo "   Kiểm tra file .env: BROADCAST_CONNECTION=reverb"
fi

# 7. Kiểm tra trạng thái
echo ""
echo "7️⃣ Kiểm tra trạng thái services..."
sleep 2
sudo supervisorctl status

echo ""
echo "✅ Hoàn tất! Kiểm tra logs nếu vẫn có vấn đề:"
echo "   tail -f $APP_DIR/storage/logs/laravel.log"
echo "   tail -f $APP_DIR/storage/logs/laravel-queue.log"
echo "   tail -f $APP_DIR/storage/logs/laravel-reverb.log"

