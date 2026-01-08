#!/bin/bash

APP_DIR="/var/www/amazon"

echo "🔍 Kiểm tra chi tiết Failed Jobs từ Database..."
echo ""

cd $APP_DIR

# Kiểm tra failed jobs trực tiếp từ database
sudo -u apache php artisan tinker --execute="
\$failed = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->first();
if (\$failed) {
    echo 'ID: ' . \$failed->id . PHP_EOL;
    echo 'Connection: ' . \$failed->connection . PHP_EOL;
    echo 'Queue: ' . \$failed->queue . PHP_EOL;
    echo 'Failed at: ' . \$failed->failed_at . PHP_EOL;
    echo PHP_EOL;
    echo 'Exception:' . PHP_EOL;
    \$payload = json_decode(\$failed->payload, true);
    if (isset(\$payload['data']['commandName'])) {
        echo 'Command: ' . \$payload['data']['commandName'] . PHP_EOL;
    }
    echo PHP_EOL;
    echo 'Exception Message:' . PHP_EOL;
    \$exception = json_decode(\$failed->exception, true);
    if (isset(\$exception['message'])) {
        echo \$exception['message'] . PHP_EOL;
    }
    if (isset(\$exception['file'])) {
        echo 'File: ' . \$exception['file'] . ':' . \$exception['line'] . PHP_EOL;
    }
    if (isset(\$exception['trace'])) {
        echo PHP_EOL . 'Stack Trace (first 5 lines):' . PHP_EOL;
        \$trace = \$exception['trace'];
        for (\$i = 0; \$i < min(5, count(\$trace)); \$i++) {
            if (isset(\$trace[\$i]['file'])) {
                echo '  ' . \$trace[\$i]['file'] . ':' . \$trace[\$i]['line'] . PHP_EOL;
            }
        }
    }
} else {
    echo 'Không có failed jobs trong database' . PHP_EOL;
}
"

