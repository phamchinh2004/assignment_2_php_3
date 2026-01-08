#!/bin/bash

APP_DIR="/var/www/amazon"
ENV_FILE="$APP_DIR/.env"

echo "🔧 Sửa lỗi Broadcasting trên Production Server..."
echo ""

if [ ! -f "$ENV_FILE" ]; then
    echo "❌ File .env không tồn tại tại $ENV_FILE!"
    exit 1
fi

echo "📝 Đang kiểm tra và sửa cấu hình Reverb..."
echo ""

# Backup .env file
cp "$ENV_FILE" "$ENV_FILE.backup.$(date +%Y%m%d_%H%M%S)"
echo "✅ Đã backup .env file"

# Function để update hoặc add env variable
update_env_var() {
    local var_name=$1
    local var_value=$2
    local comment=$3
    
    if grep -q "^${var_name}=" "$ENV_FILE"; then
        # Update existing value
        if [[ "$OSTYPE" == "darwin"* ]]; then
            # macOS
            sed -i '' "s|^${var_name}=.*|${var_name}=${var_value}|" "$ENV_FILE"
        else
            # Linux
            sed -i "s|^${var_name}=.*|${var_name}=${var_value}|" "$ENV_FILE"
        fi
        echo "   ✅ Đã cập nhật $var_name=$var_value"
    else
        # Add new variable
        echo "" >> "$ENV_FILE"
        if [ -n "$comment" ]; then
            echo "# $comment" >> "$ENV_FILE"
        fi
        echo "${var_name}=${var_value}" >> "$ENV_FILE"
        echo "   ➕ Đã thêm $var_name=$var_value"
    fi
}

# Cấu hình cho Queue Worker (kết nối nội bộ đến Reverb server)
# Queue worker chạy trên cùng server với Reverb, nên phải dùng localhost
echo "1️⃣ Cấu hình cho Queue Worker (kết nối nội bộ):"
update_env_var "REVERB_HOST" "127.0.0.1" "Reverb Host cho Queue Worker (localhost)"
update_env_var "REVERB_PORT" "6001" "Reverb Port cho Queue Worker (HTTP)"
update_env_var "REVERB_SCHEME" "http" "Reverb Scheme cho Queue Worker (HTTP)"

echo ""
echo "2️⃣ Kiểm tra các biến REVERB cần thiết:"

# Đảm bảo có REVERB_APP_ID, KEY, SECRET
REVERB_APP_ID=$(grep "^REVERB_APP_ID=" "$ENV_FILE" | cut -d '=' -f2)
if [ -z "$REVERB_APP_ID" ]; then
    REVERB_APP_ID=$(openssl rand -hex 16)
    update_env_var "REVERB_APP_ID" "$REVERB_APP_ID" "Reverb App ID"
fi

REVERB_APP_KEY=$(grep "^REVERB_APP_KEY=" "$ENV_FILE" | cut -d '=' -f2)
if [ -z "$REVERB_APP_KEY" ]; then
    REVERB_APP_KEY=$(openssl rand -hex 16)
    update_env_var "REVERB_APP_KEY" "$REVERB_APP_KEY" "Reverb App Key"
fi

REVERB_APP_SECRET=$(grep "^REVERB_APP_SECRET=" "$ENV_FILE" | cut -d '=' -f2)
if [ -z "$REVERB_APP_SECRET" ]; then
    REVERB_APP_SECRET=$(openssl rand -hex 32)
    update_env_var "REVERB_APP_SECRET" "$REVERB_APP_SECRET" "Reverb App Secret"
fi

echo ""
echo "3️⃣ Lấy domain cho Frontend configuration:"
DOMAIN=$(grep "^APP_URL=" "$ENV_FILE" | cut -d '=' -f2 | sed 's|https\?://||' | sed 's|/$||')
if [ -z "$DOMAIN" ]; then
    read -p "Nhập domain của bạn (ví dụ: amz.hethongphanphoi.shop): " DOMAIN
fi

echo "   Domain: $DOMAIN"

echo ""
echo "4️⃣ Cấu hình cho Frontend (client-side):"
# Frontend vẫn dùng domain và HTTPS (thông qua reverse proxy)
update_env_var "VITE_REVERB_HOST" "$DOMAIN" "Vite Reverb Host cho Frontend (domain)"
update_env_var "VITE_REVERB_PORT" "443" "Vite Reverb Port cho Frontend (HTTPS)"
update_env_var "VITE_REVERB_SCHEME" "https" "Vite Reverb Scheme cho Frontend (HTTPS)"
update_env_var "VITE_REVERB_APP_KEY" "$REVERB_APP_KEY" "Vite Reverb App Key"

echo ""
echo "5️⃣ Đảm bảo Broadcast Connection:"
update_env_var "BROADCAST_CONNECTION" "reverb" "Broadcast Connection"

echo ""
echo "6️⃣ Clear cache Laravel:"
cd "$APP_DIR"
sudo -u apache php artisan config:clear
sudo -u apache php artisan cache:clear
echo "   ✅ Đã clear cache"

echo ""
echo "7️⃣ Kiểm tra kết nối đến Reverb server:"
if curl -s http://127.0.0.1:6001 > /dev/null 2>&1; then
    echo "   ✅ Reverb server đang chạy trên port 6001"
else
    echo "   ⚠️  Không thể kết nối đến Reverb server trên port 6001"
    echo "   Kiểm tra: sudo supervisorctl status laravel-reverb:*"
fi

echo ""
echo "8️⃣ Kiểm tra cấu hình sau khi update:"
cd "$APP_DIR"
sudo -u apache php artisan tinker --execute="
echo 'Broadcast Driver: ' . config('broadcasting.default') . PHP_EOL;
echo 'Reverb Host (queue): ' . config('broadcasting.connections.reverb.options.host') . PHP_EOL;
echo 'Reverb Port (queue): ' . config('broadcasting.connections.reverb.options.port') . PHP_EOL;
echo 'Reverb Scheme (queue): ' . config('broadcasting.connections.reverb.options.scheme') . PHP_EOL;
"

echo ""
echo "✅ Hoàn tất cấu hình!"
echo ""
echo "📦 Các bước tiếp theo:"
echo "   1. Restart Queue Workers:"
echo "      sudo supervisorctl restart laravel-queue:*"
echo ""
echo "   2. Rebuild Frontend (nếu cần):"
echo "      cd $APP_DIR && npm run build"
echo ""
echo "   3. Kiểm tra lại:"
echo "      sudo $APP_DIR/debug-broadcasting.sh"
echo ""
echo "   4. Kiểm tra failed jobs:"
echo "      cd $APP_DIR && sudo -u apache php artisan queue:failed"
echo ""
echo "   5. Retry failed jobs (nếu muốn):"
echo "      cd $APP_DIR && sudo -u apache php artisan queue:retry all"
echo ""

