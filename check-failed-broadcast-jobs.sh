#!/bin/bash

APP_DIR="/var/www/amazon"

echo "🔍 Kiểm tra chi tiết Failed Broadcast Jobs..."
echo ""

cd "$APP_DIR"

echo "1️⃣ Danh sách Failed Jobs (20 jobs gần nhất):"
echo ""
sudo -u apache php artisan queue:failed | head -25

echo ""
echo "2️⃣ Lấy chi tiết lỗi của 5 jobs gần nhất:"
echo ""

FAILED_JOBS=$(sudo -u apache php artisan queue:failed --json 2>/dev/null)

if [ -z "$FAILED_JOBS" ] || [ "$FAILED_JOBS" == "[]" ]; then
    echo "   ✅ Không có failed jobs!"
    exit 0
fi

# Lấy 5 job IDs đầu tiên
JOB_IDS=$(echo "$FAILED_JOBS" | jq -r '.[0:5][].id' 2>/dev/null)

if [ -z "$JOB_IDS" ]; then
    echo "   ⚠️  Không thể parse failed jobs. Hiển thị raw output:"
    sudo -u apache php artisan queue:failed
    exit 1
fi

COUNT=0
for JOB_ID in $JOB_IDS; do
    COUNT=$((COUNT + 1))
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Job #$COUNT - ID: $JOB_ID"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    
    sudo -u apache php artisan queue:failed "$JOB_ID" 2>&1 | sed 's/^/   /'
    
    echo ""
done

echo ""
echo "3️⃣ Phân tích nguyên nhân chung:"
echo ""

# Kiểm tra các lỗi phổ biến
if echo "$FAILED_JOBS" | grep -qi "connection\|timeout\|refused"; then
    echo "   ⚠️  Phát hiện lỗi kết nối!"
    echo "   → Queue worker không thể kết nối đến Reverb server"
    echo "   → Kiểm tra: REVERB_HOST phải là 127.0.0.1"
    echo "   → Kiểm tra: REVERB_PORT phải là 6001"
    echo "   → Kiểm tra: REVERB_SCHEME phải là http"
fi

if echo "$FAILED_JOBS" | grep -qi "ssl\|tls\|certificate"; then
    echo "   ⚠️  Phát hiện lỗi SSL/TLS!"
    echo "   → Queue worker đang cố kết nối HTTPS nhưng Reverb server chạy HTTP"
    echo "   → Sửa: REVERB_SCHEME=http trong .env"
fi

if echo "$FAILED_JOBS" | grep -qi "host\|dns\|resolve"; then
    echo "   ⚠️  Phát hiện lỗi DNS/hostname!"
    echo "   → Queue worker không thể resolve domain name"
    echo "   → Sửa: REVERB_HOST=127.0.0.1 trong .env"
fi

echo ""
echo "✅ Hoàn tất kiểm tra!"
echo ""
echo "💡 Để sửa lỗi, chạy:"
echo "   sudo $APP_DIR/fix-broadcasting-production.sh"

