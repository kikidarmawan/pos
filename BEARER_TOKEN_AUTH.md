# 🔐 Bearer Token Authentication - CONFIGURED!

## ✅ Perubahan yang Sudah Dilakukan

### 1. Backend (`config/cors.php`)
```php
'supports_credentials' => false,  // ✅ Tidak pakai cookie
'allowed_origins' => ['*'],       // ✅ Simple CORS
```

### 2. Frontend (`src/utils/axios.js`)
```javascript
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  // withCredentials: true,  ❌ REMOVED
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})
```

### 3. Backend (`.env`)
```env
SESSION_DRIVER=database  # Tidak pakai cookie
FRONTEND_URL=http://localhost:5173
# SANCTUM_STATEFUL_DOMAINS ❌ REMOVED (tidak perlu untuk Bearer)
```

### 4. Backend (`bootstrap/app.php`)
```php
->withMiddleware(function (Middleware $middleware): void {
    // $middleware->statefulApi(); ❌ REMOVED
})
```

---

## 🔑 Cara Kerja Bearer Token

### 1. **Login** (Frontend → Backend)
```javascript
const response = await api.post('/login', { email, password })
const { token, user } = response.data

// Save token ke localStorage
localStorage.setItem('token', token)
```

### 2. **Request dengan Token** (Automatic)
Axios interceptor otomatis menambahkan header:
```javascript
// src/utils/axios.js
api.interceptors.request.use((config) => {
  const authStore = useAuthStore()
  if (authStore.token) {
    config.headers.Authorization = `Bearer ${authStore.token}`
  }
  return config
})
```

### 3. **Backend Verify Token**
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});
```

---

## 🧪 Test Authentication

### Login:
```bash
curl -X POST http://localhost:8001/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@pos.com",
    "password": "password"
  }'
```

Response:
```json
{
  "user": {...},
  "token": "1|abc123xyz..."
}
```

### Use Token:
```bash
curl http://localhost:8001/api/products \
  -H "Authorization: Bearer 1|abc123xyz..."
```

---

## ✅ Keuntungan Bearer Token

1. ✅ **Lebih Simple** - Tidak perlu cookie, session, CSRF
2. ✅ **Stateless** - Backend tidak perlu track session
3. ✅ **CORS Friendly** - Tidak ada masalah credentials
4. ✅ **Mobile Ready** - Cocok untuk mobile apps
5. ✅ **Standard** - Industry standard untuk API

---

## 🔄 Restart & Test

1. **Clear cache & restart backend:**
```bash
cd backend
php artisan config:clear
php artisan cache:clear
# Restart server (Ctrl+C, php artisan serve)
```

2. **Restart frontend:**
```bash
cd frontend
# Restart (Ctrl+C, npm run dev)
```

3. **Test Login:**
- Buka: http://localhost:5173
- Login: admin@pos.com / password
- Check Console: Token tersimpan di localStorage
- API calls otomatis pakai Bearer token

---

## 📝 Token Flow

```
┌─────────┐                    ┌─────────┐
│ Frontend│                    │ Backend │
└────┬────┘                    └────┬────┘
     │                              │
     │  POST /login                 │
     │  {email, password}           │
     ├─────────────────────────────>│
     │                              │
     │  Response                    │
     │  {token, user}               │
     │<─────────────────────────────┤
     │                              │
     │ Save to localStorage         │
     │                              │
     │  GET /products               │
     │  Authorization: Bearer token │
     ├─────────────────────────────>│
     │                              │
     │  Verify token                │
     │  Return data                 │
     │<─────────────────────────────┤
     │                              │
```

---

**Sekarang authentication pakai Bearer Token! 🎉**

**Restart backend & frontend, lalu test login!** 🚀
