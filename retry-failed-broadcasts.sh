#!/bin/bash

APP_DIR="/var/www/amazon"

echo "🔄 Retry tất cả Failed Broadcast Jobs..."
echo ""

cd $APP_DIR

# Đếm số failed jobs
FAILED_COUNT=$(sudo -u apache php artisan queue:failed --json 2>/dev/null | jq length 2>/dev/null || echo "0")

if [ "$FAILED_COUNT" = "0" ]; then
    echo "✅ Không có failed jobs"
    exit 0
fi

echo "📊 Tìm thấy $FAILED_COUNT failed jobs"
echo ""

# Retry tất cả
echo "🔄 Đang retry tất cả failed jobs..."
sudo -u apache php artisan queue:retry all

echo ""
echo "⏳ Đợi 5 giây để jobs được xử lý..."
sleep 5

echo ""
echo "📋 Kiểm tra lại failed jobs:"
NEW_FAILED_COUNT=$(sudo -u apache php artisan queue:failed --json 2>/dev/null | jq length 2>/dev/null || echo "0")
echo "   Còn lại: $NEW_FAILED_COUNT failed jobs"

if [ "$NEW_FAILED_COUNT" -lt "$FAILED_COUNT" ]; then
    echo "   ✅ Đã retry thành công một số jobs!"
else
    echo "   ⚠️  Vẫn còn lỗi, kiểm tra logs để xem chi tiết"
fi

echo ""
echo "✅ Hoàn tất!"

