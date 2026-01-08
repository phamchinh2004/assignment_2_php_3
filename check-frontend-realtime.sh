#!/bin/bash

APP_DIR="/var/www/amazon"

echo "🔍 Kiểm tra cấu hình Frontend Realtime..."
echo ""

echo "1️⃣ Kiểm tra biến môi trường trong .env:"
echo ""
if [ -f "$APP_DIR/.env" ]; then
    echo "   REVERB_APP_ID:"
    grep "^REVERB_APP_ID=" $APP_DIR/.env | sed 's/^/      /' || echo "      ❌ CHƯA CÓ"
    
    echo "   REVERB_APP_KEY:"
    grep "^REVERB_APP_KEY=" $APP_DIR/.env | sed 's/^/      /' || echo "      ❌ CHƯA CÓ"
    
    echo "   REVERB_APP_SECRET:"
    grep "^REVERB_APP_SECRET=" $APP_DIR/.env | sed 's/^/      /' || echo "      ❌ CHƯA CÓ"
    
    echo "   REVERB_HOST:"
    grep "^REVERB_HOST=" $APP_DIR/.env | sed 's/^/      /' || echo "      ❌ CHƯA CÓ"
    
    echo "   REVERB_PORT:"
    grep "^REVERB_PORT=" $APP_DIR/.env | sed 's/^/      /' || echo "      ⚠️  Sử dụng mặc định: 6001"
    
    echo "   REVERB_SCHEME:"
    grep "^REVERB_SCHEME=" $APP_DIR/.env | sed 's/^/      /' || echo "      ⚠️  Sử dụng mặc định: http"
    
    echo "   BROADCAST_CONNECTION:"
    grep "^BROADCAST_CONNECTION=" $APP_DIR/.env | sed 's/^/      /' || echo "      ⚠️  Sử dụng mặc định: reverb"
    
    echo ""
    echo "   VITE_REVERB_APP_KEY:"
    grep "^VITE_REVERB_APP_KEY=" $APP_DIR/.env | sed 's/^/      /' || echo "      ❌ CHƯA CÓ (QUAN TRỌNG cho frontend!)"
    
    echo "   VITE_REVERB_HOST:"
    grep "^VITE_REVERB_HOST=" $APP_DIR/.env | sed 's/^/      /' || echo "      ❌ CHƯA CÓ (QUAN TRỌNG cho frontend!)"
    
    echo "   VITE_REVERB_PORT:"
    grep "^VITE_REVERB_PORT=" $APP_DIR/.env | sed 's/^/      /' || echo "      ⚠️  Sử dụng mặc định: 6001"
    
    echo "   VITE_REVERB_SCHEME:"
    grep "^VITE_REVERB_SCHEME=" $APP_DIR/.env | sed 's/^/      /' || echo "      ⚠️  Sử dụng mặc định: http"
else
    echo "   ❌ File .env không tồn tại!"
fi

echo ""
echo "2️⃣ Kiểm tra file build frontend:"
if [ -d "$APP_DIR/public/build" ]; then
    echo "   ✅ Thư mục build tồn tại"
    echo "   📁 Kích thước:"
    du -sh $APP_DIR/public/build
else
    echo "   ❌ Thư mục build KHÔNG tồn tại"
    echo "   ⚠️  Cần chạy: npm run build"
fi

echo ""
echo "3️⃣ Kiểm tra cấu hình trong routes/web.php:"
if grep -q "reverb_host\|reverb_port" $APP_DIR/routes/web.php 2>/dev/null; then
    echo "   ✅ Có cấu hình reverb trong routes"
else
    echo "   ⚠️  Không tìm thấy cấu hình reverb trong routes"
fi

echo ""
echo "4️⃣ Kiểm tra file echo.js:"
if [ -f "$APP_DIR/resources/js/echo.js" ]; then
    echo "   ✅ File echo.js tồn tại"
    if grep -q "VITE_REVERB" $APP_DIR/resources/js/echo.js; then
        echo "   ✅ Đã cấu hình VITE_REVERB variables"
    else
        echo "   ❌ Chưa cấu hình VITE_REVERB variables"
    fi
else
    echo "   ❌ File echo.js không tồn tại"
fi

echo ""
echo "✅ Hoàn tất kiểm tra!"
echo ""
echo "📝 Lưu ý:"
echo "   - Nếu thiếu biến VITE_REVERB_*, cần thêm vào .env"
echo "   - Sau khi thêm biến môi trường, cần rebuild frontend: npm run build"
echo "   - Kiểm tra browser console để xem lỗi kết nối WebSocket"

