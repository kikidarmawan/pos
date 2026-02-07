# 🚀 Setup Guide - POS System

## Struktur Project

```
pos/
├── backend/         # Laravel API (Project Laravel Anda)
├── frontend/        # Vue.js 3 SPA (Baru dibuat)
└── README.md
```

## ✅ Yang Sudah Dibuat

### Backend (Laravel)
- ✅ Struktur config files (cors.php, sanctum.php, etc)
- ✅ Bootstrap files
- ⚠️ **GUNAKAN project Laravel yang sudah ada**

### Frontend (Vue.js)
- ✅ Package.json dengan semua dependencies
- ✅ Vite configuration dengan PWA support
- ✅ Tailwind CSS setup
- ✅ Router (Vue Router) lengkap dengan guards
- ✅ State Management (Pinia) untuk Auth & Cart
- ✅ Axios configuration dengan interceptors
- ✅ Utility functions (formatCurrency, formatDate, etc)
- ✅ Login page
- ✅ Dashboard Layout dengan sidebar
- ✅ PWA manifest

## 🎯 Langkah Setup

### 1. Setup Backend Laravel

```bash
cd backend

# Jika belum install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Edit .env untuk database dan frontend URL
# DB_DATABASE=pos_system
# FRONTEND_URL=http://localhost:5173
# SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,127.0.0.1,127.0.0.1:5173

# Migrate & Seed (jika belum)
php artisan migrate --seed

# Jalankan server
php artisan serve
```

Backend akan running di: **http://localhost:8000**

### 2. Setup Frontend Vue.js

```bash
cd frontend

# Install dependencies
npm install

# Setup environment
cp .env.example .env

# .env content:
# VITE_API_URL=http://localhost:8000

# Run development server
npm run dev
```

Frontend akan running di: **http://localhost:5173**

### 3. Login

Buka browser ke `http://localhost:5173`

Login dengan:
- Email: `admin@pos.com`
- Password: `password`

## 📋 Pages yang Perlu Dibuat

Saya sudah membuat struktur dan routing, tinggal buat file-file component berikut di folder `frontend/src/pages/`:

### Priority 1 (Penting):
- ✅ `auth/Login.vue`
- ❌ `Dashboard.vue`
- ❌ `pos/Index.vue` (POS/Kasir) ⭐ **PALING PENTING**
- ❌ `products/Index.vue`

### Priority 2 (Sedang):
- ❌ `sales/Index.vue`
- ❌ `purchases/Index.vue`
- ❌ `purchases/Create.vue`
- ❌ `stocks/Index.vue`

### Priority 3 (Rendah):
- ❌ `categories/Index.vue`
- ❌ `units/Index.vue`
- ❌ `suppliers/Index.vue`
- ❌ `warehouses/Index.vue`
- ❌ `users/Index.vue`
- ❌ `roles/Index.vue`
- ❌ `reports/Sales.vue`
- ❌ `reports/Stock.vue`
- ❌ `reports/Purchases.vue`
- ❌ `reports/Profit.vue`
- ❌ `Profile.vue`

## 🔧 Template Contoh Page

Contoh untuk membuat halaman baru:

```vue
<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Judul Halaman</h1>
    
    <div class="card">
      <!-- Content here -->
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import api from '@/utils/axios'
import { formatCurrency, formatDate } from '@/utils/format'

const authStore = useAuthStore()
const toast = useToast()

// Your logic here

onMounted(() => {
  // Load data
})
</script>
```

## 🎨 CSS Classes yang Tersedia

Sudah ada utility classes di `frontend/src/assets/style.css`:

- **Buttons**: `btn`, `btn-primary`, `btn-success`, `btn-danger`, `btn-warning`, `btn-secondary`
- **Forms**: `input`, `label`
- **Cards**: `card`
- **Tables**: `table`
- **Badges**: `badge`, `badge-success`, `badge-danger`, `badge-warning`, `badge-info`

## 📡 Cara Panggil API

```javascript
// GET
const response = await api.get('/products')
const products = response.data

// POST
const response = await api.post('/sales', {
  warehouse_id: 1,
  total: 100000,
  details: [...]
})

// PUT
await api.put('/products/1', { name: 'New Name' })

// DELETE
await api.delete('/products/1')
```

## 🔐 Check Permission

```vue
<button v-if="hasPermission('create_products')">
  Tambah Produk
</button>

<script setup>
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const hasPermission = (permission) => authStore.hasPermission(permission)
</script>
```

## 🛠️ Development Tips

1. **Hot Reload**: Vite akan auto-reload saat save file
2. **Error Debugging**: Buka Console di browser (F12)
3. **API Testing**: Gunakan Postman untuk test API backend
4. **Vue DevTools**: Install extension untuk debugging Vue

## 📦 Build Production

```bash
# Frontend
cd frontend
npm run build
# Output di: frontend/dist/

# Backend
cd backend
php artisan config:cache
php artisan route:cache
composer install --optimize-autoloader --no-dev
```

## 🚨 Troubleshooting

### CORS Error
- Check `SANCTUM_STATEFUL_DOMAINS` di backend `.env`
- Check `FRONTEND_URL` di backend `.env`

### 401 Unauthorized
- Clear browser cache
- Re-login
- Check token di localStorage (F12 → Application → Local Storage)

### Page Not Found (404)
- Pastikan server development running (frontend & backend)
- Check Vue Router configuration

## 📞 Next Steps

1. **Sekarang**: Test login ke frontend
2. **Lalu**: Buat Dashboard.vue dan pos/Index.vue
3. **Kemudian**: Lanjutkan pages lainnya sesuai prioritas

---

Jika butuh bantuan membuat pages yang lain, tinggal bilang! 🚀
