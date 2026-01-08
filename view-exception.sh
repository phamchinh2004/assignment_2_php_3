#!/bin/bash

APP_DIR="/var/www/amazon"

echo "🔍 Xem Exception từ Failed Jobs..."
echo ""

cd $APP_DIR

# Lấy failed job đầu tiên và hiển thị exception
sudo -u apache php artisan queue:failed --json 2>/dev/null | jq -r '.[0] | "ID: \(.id)\nConnection: \(.connection)\nQueue: \(.queue)\nFailed at: \(.failed_at)\n\nException:\n\(.exception)"' 2>/dev/null || sudo -u apache php artisan queue:failed | head -50

