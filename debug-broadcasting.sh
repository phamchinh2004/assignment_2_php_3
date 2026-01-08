#!/bin/bash

APP_DIR="/var/www/amazon"

echo "🔍 Debug Broadcasting System..."
echo ""

echo "1️⃣ Kiểm tra Queue Worker:"
sudo supervisorctl status laravel-queue:*

echo ""
echo "2️⃣ Kiểm tra Jobs trong Queue:"
cd $APP_DIR
sudo -u apache php artisan queue:monitor redis:default --max=10

echo ""
echo "3️⃣ Kiểm tra Failed Jobs:"
sudo -u apache php artisan queue:failed | head -20

echo ""
echo "4️⃣ Kiểm tra Redis Connection:"
redis-cli ping
redis-cli INFO stats | grep -E "total_commands_processed|keyspace"

echo ""
echo "5️⃣ Kiểm tra Reverb Status:"
sudo supervisorctl status laravel-reverb:*

echo ""
echo "6️⃣ Kiểm tra Logs Queue (10 dòng cuối):"
tail -10 $APP_DIR/storage/logs/laravel-queue.log 2>/dev/null || echo "   ⚠️  File log không tồn tại"

echo ""
echo "7️⃣ Kiểm tra Logs Reverb (10 dòng cuối):"
tail -10 $APP_DIR/storage/logs/laravel-reverb.log 2>/dev/null || echo "   ⚠️  File log không tồn tại"

echo ""
echo "8️⃣ Kiểm tra Laravel Log (lỗi broadcasting):"
tail -50 $APP_DIR/storage/logs/laravel.log | grep -i "broadcast\|reverb\|queue" | tail -10

echo ""
echo "9️⃣ Test Broadcasting Config:"
cd $APP_DIR
sudo -u apache php artisan tinker --execute="
echo 'Broadcast Driver: ' . config('broadcasting.default') . PHP_EOL;
echo 'Reverb Host: ' . config('broadcasting.connections.reverb.options.host') . PHP_EOL;
echo 'Reverb Port: ' . config('broadcasting.connections.reverb.options.port') . PHP_EOL;
"

echo ""
echo "✅ Hoàn tất debug!"

