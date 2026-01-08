#!/bin/bash

echo "🔍 Kiểm tra Realtime Server Status..."
echo ""

echo "1️⃣ Kiểm tra Redis:"
if systemctl is-active --quiet redis; then
    echo "   ✅ Redis đang chạy"
    redis-cli ping
else
    echo "   ❌ Redis KHÔNG chạy"
    echo "   Chạy: sudo systemctl start redis"
fi

echo ""
echo "2️⃣ Kiểm tra Reverb trong Supervisor:"
if sudo supervisorctl status laravel-reverb:* 2>/dev/null | grep -q "RUNNING"; then
    echo "   ✅ Reverb đang chạy"
    sudo supervisorctl status laravel-reverb:*
else
    echo "   ❌ Reverb KHÔNG chạy"
    echo "   Kiểm tra: sudo supervisorctl status laravel-reverb:*"
fi

echo ""
echo "3️⃣ Kiểm tra Port 6001:"
if sudo netstat -tlnp 2>/dev/null | grep -q ":6001" || sudo ss -tlnp 2>/dev/null | grep -q ":6001"; then
    echo "   ✅ Port 6001 đang được lắng nghe"
    sudo netstat -tlnp 2>/dev/null | grep ":6001" || sudo ss -tlnp 2>/dev/null | grep ":6001"
else
    echo "   ❌ Port 6001 KHÔNG được lắng nghe"
fi

echo ""
echo "4️⃣ Kiểm tra Logs Reverb (10 dòng cuối):"
if [ -f /var/www/amazon/storage/logs/laravel-reverb.log ]; then
    echo "   📋 Logs:"
    tail -10 /var/www/amazon/storage/logs/laravel-reverb.log
else
    echo "   ⚠️  File log chưa tồn tại"
fi

echo ""
echo "5️⃣ Kiểm tra Logs Lỗi Reverb (10 dòng cuối):"
if [ -f /var/www/amazon/storage/logs/laravel-reverb-error.log ]; then
    echo "   📋 Error Logs:"
    tail -10 /var/www/amazon/storage/logs/laravel-reverb-error.log
else
    echo "   ⚠️  File error log chưa tồn tại"
fi

echo ""
echo "6️⃣ Kiểm tra Supervisor processes:"
sudo supervisorctl status

echo ""
echo "✅ Hoàn tất kiểm tra!"

