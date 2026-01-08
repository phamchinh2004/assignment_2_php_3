#!/bin/bash

APP_DIR="/var/www/amazon"

echo "🔍 Kiểm tra chi tiết Failed Jobs..."
echo ""

cd $APP_DIR

# Lấy ID của failed job đầu tiên
FIRST_FAILED_ID=$(sudo -u apache php artisan queue:failed --json 2>/dev/null | jq -r '.[0].id' 2>/dev/null)

if [ -z "$FIRST_FAILED_ID" ] || [ "$FIRST_FAILED_ID" = "null" ]; then
    echo "❌ Không tìm thấy failed jobs"
    exit 1
fi

echo "📋 Xem chi tiết failed job ID: $FIRST_FAILED_ID"
echo ""
sudo -u apache php artisan queue:failed $FIRST_FAILED_ID

echo ""
echo "📝 Xem exception:"
sudo -u apache php artisan queue:failed $FIRST_FAILED_ID | grep -A 50 "Exception"

