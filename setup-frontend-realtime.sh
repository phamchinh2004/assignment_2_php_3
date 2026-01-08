#!/bin/bash

APP_DIR="/var/www/amazon"
ENV_FILE="$APP_DIR/.env"

echo "🔧 Cấu hình Frontend Realtime..."
echo ""

if [ ! -f "$ENV_FILE" ]; then
    echo "❌ File .env không tồn tại!"
    exit 1
fi

# Lấy domain từ APP_URL hoặc hỏi user
DOMAIN=$(grep "^APP_URL=" $ENV_FILE | cut -d '=' -f2 | sed 's|https\?://||' | sed 's|/$||')

if [ -z "$DOMAIN" ]; then
    echo "⚠️  Không tìm thấy APP_URL trong .env"
    read -p "Nhập domain của bạn (ví dụ: amz.hethongphanphoi.shop): " DOMAIN
fi

echo "📝 Đang cấu hình với domain: $DOMAIN"
echo ""

# Kiểm tra và thêm các biến môi trường nếu chưa có
add_env_var() {
    local var_name=$1
    local var_value=$2
    local comment=$3
    
    if grep -q "^${var_name}=" $ENV_FILE; then
        echo "   ✅ $var_name đã tồn tại"
    else
        echo "   ➕ Thêm $var_name"
        echo "" >> $ENV_FILE
        if [ -n "$comment" ]; then
            echo "# $comment" >> $ENV_FILE
        fi
        echo "${var_name}=${var_value}" >> $ENV_FILE
    fi
}

# Lấy giá trị từ .env hoặc tạo mới
REVERB_APP_ID=$(grep "^REVERB_APP_ID=" $ENV_FILE | cut -d '=' -f2)
if [ -z "$REVERB_APP_ID" ]; then
    REVERB_APP_ID=$(openssl rand -hex 16)
    add_env_var "REVERB_APP_ID" "$REVERB_APP_ID" "Reverb App ID"
fi

REVERB_APP_KEY=$(grep "^REVERB_APP_KEY=" $ENV_FILE | cut -d '=' -f2)
if [ -z "$REVERB_APP_KEY" ]; then
    REVERB_APP_KEY=$(openssl rand -hex 16)
    add_env_var "REVERB_APP_KEY" "$REVERB_APP_KEY" "Reverb App Key"
fi

REVERB_APP_SECRET=$(grep "^REVERB_APP_SECRET=" $ENV_FILE | cut -d '=' -f2)
if [ -z "$REVERB_APP_SECRET" ]; then
    REVERB_APP_SECRET=$(openssl rand -hex 32)
    add_env_var "REVERB_APP_SECRET" "$REVERB_APP_SECRET" "Reverb App Secret"
fi

# Cấu hình các biến REVERB
add_env_var "REVERB_HOST" "$DOMAIN" "Reverb Host"
add_env_var "REVERB_PORT" "6001" "Reverb Port"
add_env_var "REVERB_SCHEME" "http" "Reverb Scheme (http hoặc https)"
add_env_var "BROADCAST_CONNECTION" "reverb" "Broadcast Connection"

# Cấu hình các biến VITE cho frontend
add_env_var "VITE_REVERB_APP_KEY" "$REVERB_APP_KEY" "Vite Reverb App Key (cho frontend)"
add_env_var "VITE_REVERB_HOST" "$DOMAIN" "Vite Reverb Host (cho frontend)"
add_env_var "VITE_REVERB_PORT" "6001" "Vite Reverb Port (cho frontend)"
add_env_var "VITE_REVERB_SCHEME" "http" "Vite Reverb Scheme (cho frontend)"

echo ""
echo "✅ Đã cấu hình xong!"
echo ""
echo "📦 Bước tiếp theo:"
echo "   1. Rebuild frontend: cd $APP_DIR && npm run build"
echo "   2. Clear cache: php artisan config:clear && php artisan cache:clear"
echo "   3. Restart Reverb: sudo supervisorctl restart laravel-reverb:*"
echo ""
echo "🔍 Kiểm tra lại:"
echo "   sudo $APP_DIR/check-frontend-realtime.sh"

