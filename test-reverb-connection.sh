#!/bin/bash

APP_DIR="/var/www/amazon"

echo "🧪 Test kết nối Reverb từ Queue Worker..."
echo ""

cd "$APP_DIR"

echo "1️⃣ Kiểm tra cấu hình hiện tại:"
sudo -u apache php artisan tinker --execute="
echo 'Broadcast Driver: ' . config('broadcasting.default') . PHP_EOL;
echo 'Reverb Host: ' . config('broadcasting.connections.reverb.options.host') . PHP_EOL;
echo 'Reverb Port: ' . config('broadcasting.connections.reverb.options.port') . PHP_EOL;
echo 'Reverb Scheme: ' . config('broadcasting.connections.reverb.options.scheme') . PHP_EOL;
echo 'REVERB_APP_KEY: ' . config('broadcasting.connections.reverb.key') . PHP_EOL;
echo 'REVERB_APP_SECRET: ' . (config('broadcasting.connections.reverb.secret') ? '***' : 'NULL') . PHP_EOL;
"

echo ""
echo "2️⃣ Test kết nối HTTP đến Reverb server:"
if timeout 3 curl -s http://127.0.0.1:6001 > /dev/null 2>&1; then
    echo "   ✅ Có thể kết nối đến http://127.0.0.1:6001"
else
    echo "   ❌ KHÔNG thể kết nối đến http://127.0.0.1:6001"
    echo "   ⚠️  Kiểm tra Reverb server: sudo supervisorctl status laravel-reverb:*"
fi

echo ""
echo "3️⃣ Test broadcast event (MessageSent):"
sudo -u apache php artisan tinker --execute="
try {
    \$message = \App\Models\Message::first();
    if (\$message) {
        echo 'Testing broadcast với Message ID: ' . \$message->id . PHP_EOL;
        broadcast(new \App\Events\MessageSent(\$message));
        echo '✅ Broadcast thành công!' . PHP_EOL;
    } else {
        echo '⚠️  Không có message nào trong database để test' . PHP_EOL;
    }
} catch (\Exception \$e) {
    echo '❌ Lỗi: ' . \$e->getMessage() . PHP_EOL;
    echo 'Stack trace:' . PHP_EOL;
    echo \$e->getTraceAsString() . PHP_EOL;
}
"

echo ""
echo "4️⃣ Kiểm tra jobs trong queue sau 5 giây:"
sleep 5
sudo -u apache php artisan queue:monitor redis:default --max=5

echo ""
echo "✅ Hoàn tất test!"

