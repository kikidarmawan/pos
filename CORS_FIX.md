# 🔧 CORS Error - FIXED!

## Problem
```
Access to XMLHttpRequest at 'http://localhost:8001/api/login' from origin 'http://localhost:5173' 
has been blocked by CORS policy: The value of the 'Access-Control-Allow-Credentials' header 
in the response is '' which must be 'true' when the request's credentials mode is 'include'.
```

## Solution Applied ✅

### 1. Created `config/cors.php`
```php
'supports_credentials' => true,
'allowed_origins' => [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
],
```

### 2. Updated `bootstrap/app.php`
```php
->withRouting(
    api: __DIR__.'/../routes/api.php',  // ✅ API routes enabled
)
->withMiddleware(function (Middleware $middleware): void {
    $middleware->statefulApi();  // ✅ Sanctum stateful API
})
```

### 3. Updated `.env`
```env
SANCTUM_STATEFUL_DOMAINS=localhost:5173,127.0.0.1:5173
FRONTEND_URL=http://localhost:5173
SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost
```

### 4. Cleared Cache
```bash
php artisan config:clear
php artisan cache:clear
```

---

## ✅ Now Restart Backend Server

**PENTING:** Restart backend server agar perubahan diterapkan!

```bash
# Kill server yang sedang running
# Ctrl+C di terminal backend

# Restart
cd backend
php artisan serve
```

Atau jika menggunakan Herd, restart service.

---

## 🧪 Test CORS

Sekarang coba login lagi dari frontend:
- Frontend: http://localhost:5173
- Login: admin@pos.com / password

CORS error seharusnya sudah hilang! ✅

---

## 📝 Catatan

Laravel 12 menggunakan struktur bootstrap/app.php yang baru.
Key changes:
1. ✅ API routes harus di-enable explicitly
2. ✅ `statefulApi()` middleware untuk Sanctum
3. ✅ CORS config dengan `supports_credentials: true`
4. ✅ Sanctum stateful domains harus include port

---

**Restart backend server dan test lagi!** 🚀
