# Hướng dẫn sửa lỗi Broadcasting trên Production Server

## 🔴 Vấn đề

Trên production server, các `BroadcastEvent` jobs đang bị fail với lỗi:
- Queue worker không thể kết nối đến Reverb server
- Nguyên nhân: Cấu hình `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` không phù hợp cho queue worker

## 🔍 Nguyên nhân

### 1. Khác biệt giữa Local và Production

**Local (hoạt động tốt):**
- Reverb server: `http://127.0.0.1:6001`
- Queue worker connect đến: `http://127.0.0.1:6001` ✅
- Frontend connect đến: `http://localhost:6001` ✅

**Production (lỗi):**
- Reverb server: `http://127.0.0.1:6001` (chạy nội bộ)
- Queue worker đang cố connect đến: `https://amz.hethongphanphoi.shop:443` ❌
- Frontend cần connect đến: `wss://amz.hethongphanphoi.shop:443` (qua reverse proxy) ✅

### 2. Cấu hình không đúng

- `REVERB_HOST=amz.hethongphanphoi.shop` → Queue worker không thể resolve domain từ trong server
- `REVERB_PORT=443` → Reverb server không chạy trên port 443
- `REVERB_SCHEME=https` → Reverb server chạy HTTP, không phải HTTPS

## ✅ Giải pháp

### Queue Worker vs Frontend - Cấu hình khác nhau

**Queue Worker (Laravel backend):**
- Phải connect đến Reverb server **nội bộ** (localhost)
- Dùng **HTTP** (không qua SSL)
- Config: `REVERB_HOST=127.0.0.1`, `REVERB_PORT=6001`, `REVERB_SCHEME=http`

**Frontend (Browser):**
- Connect qua **domain** và **HTTPS** (qua reverse proxy/nginx)
- Dùng **WSS** (WebSocket Secure)
- Config: `VITE_REVERB_HOST=amz.hethongphanphoi.shop`, `VITE_REVERB_PORT=443`, `VITE_REVERB_SCHEME=https`

## 🛠️ Các bước sửa lỗi

### Bước 1: Chạy script tự động sửa

```bash
cd /var/www/amazon
sudo chmod +x fix-broadcasting-production.sh
sudo ./fix-broadcasting-production.sh
```

Script này sẽ:
- Backup file `.env`
- Sửa `REVERB_HOST=127.0.0.1` (cho queue worker)
- Sửa `REVERB_PORT=6001` (cho queue worker)
- Sửa `REVERB_SCHEME=http` (cho queue worker)
- Giữ nguyên `VITE_REVERB_*` cho frontend (domain + HTTPS)
- Clear Laravel cache

### Bước 2: Restart Queue Workers

```bash
sudo supervisorctl restart laravel-queue:*
```

### Bước 3: Kiểm tra lại

```bash
sudo ./debug-broadcasting.sh
```

### Bước 4: Xem chi tiết lỗi (nếu vẫn còn)

```bash
sudo ./check-failed-broadcast-jobs.sh
```

### Bước 5: Retry failed jobs (sau khi sửa)

```bash
cd /var/www/amazon
sudo -u apache php artisan queue:retry all
```

## 📋 Cấu hình .env đúng

```env
# ============================================
# REVERB - Cho Queue Worker (Backend)
# ============================================
REVERB_HOST=127.0.0.1
REVERB_PORT=6001
REVERB_SCHEME=http
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret

# ============================================
# VITE_REVERB - Cho Frontend (Browser)
# ============================================
VITE_REVERB_HOST=amz.hethongphanphoi.shop
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
VITE_REVERB_APP_KEY=your-app-key

# ============================================
# Broadcast Connection
# ============================================
BROADCAST_CONNECTION=reverb
```

## 🌐 Cấu hình Nginx (Reverse Proxy)

Đảm bảo nginx được cấu hình để proxy WebSocket từ port 443 đến Reverb server:

```nginx
location /app/ {
    proxy_pass http://127.0.0.1:6001;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

## 🧪 Test kết nối

```bash
# Test từ server (queue worker)
curl http://127.0.0.1:6001

# Test từ browser console (frontend)
# Mở DevTools và kiểm tra WebSocket connection
```

## 📝 Lưu ý quan trọng

1. **KHÔNG bao giờ** dùng domain name cho `REVERB_HOST` trong .env (cho queue worker)
2. **LUÔN dùng** `127.0.0.1` hoặc `localhost` cho `REVERB_HOST` (queue worker)
3. Frontend vẫn dùng domain và HTTPS thông qua reverse proxy
4. Sau khi sửa `.env`, phải clear cache: `php artisan config:clear`
5. Sau khi sửa cấu hình, phải restart queue workers

## 🔄 So sánh Local vs Production

| Thành phần | Local | Production |
|-----------|-------|------------|
| Reverb Server | `127.0.0.1:6001` | `127.0.0.1:6001` |
| Queue Worker → Reverb | `http://127.0.0.1:6001` | `http://127.0.0.1:6001` ✅ |
| Frontend → Reverb | `ws://localhost:6001` | `wss://domain:443` (qua nginx) |
| REVERB_HOST | `127.0.0.1` | `127.0.0.1` ✅ |
| REVERB_PORT | `6001` | `6001` ✅ |
| REVERB_SCHEME | `http` | `http` ✅ |

## ❓ Troubleshooting

### Vẫn còn lỗi sau khi sửa?

1. Kiểm tra Reverb server có chạy không:
   ```bash
   sudo supervisorctl status laravel-reverb:*
   ```

2. Kiểm tra port 6001 có bị block không:
   ```bash
   netstat -tuln | grep 6001
   ```

3. Xem log Reverb:
   ```bash
   tail -f /var/www/amazon/storage/logs/laravel-reverb.log
   ```

4. Xem log Queue:
   ```bash
   tail -f /var/www/amazon/storage/logs/laravel-queue.log
   ```

5. Xem log Laravel:
   ```bash
   tail -f /var/www/amazon/storage/logs/laravel.log
   ```

