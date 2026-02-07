# POS System - Point of Sale

Aplikasi POS (Point of Sale) modern dengan arsitektur terpisah antara Backend (Laravel) dan Frontend (Vue.js).

## 🏗️ Struktur Project

```
pos/
├── backend/          # Laravel 10 API
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── ...
│
├── frontend/         # Vue.js 3 SPA
│   ├── src/
│   │   ├── pages/
│   │   ├── stores/
│   │   ├── router/
│   │   └── ...
│   ├── package.json
│   └── vite.config.js
│
├── README.md         # File ini
└── SETUP_GUIDE.md    # Panduan setup lengkap ⭐
```

## ✨ Fitur Lengkap

✅ **Multi-User dengan Role & Permission**
- Login dengan Laravel Sanctum
- Role: Super Admin, Admin, Kasir, Gudang
- Permission granular per fitur

✅ **Produk Multi-Satuan**
- Produk bisa punya banyak satuan (Roll, Meter, Pcs, Box, etc)
- Konversi otomatis antar satuan
- Harga jual berbeda per satuan
- Barcode per satuan

✅ **Pembelian dari Supplier**
- Form pembelian lengkap
- Auto stock update
- Support multi-warehouse & rak

✅ **Posisi Penyimpanan (Rak & Gudang)**
- Multi-warehouse
- Rak penyimpanan per gudang
- Stock tracking per lokasi

✅ **Point of Sale (POS)**
- Interface kasir modern
- Quick search produk
- Barcode scanning
- Multiple payment methods
- Auto calculate tax, discount, change

✅ **Laporan Komprehensif**
- Dashboard statistik real-time
- Laporan: Penjualan, Pembelian, Stok, Profit
- Filter by date, warehouse, supplier
- Export ke Excel

✅ **PWA Support**
- Progressive Web App
- Install di mobile/desktop
- Offline capability

✅ **UI/UX Modern**
- Tailwind CSS
- Responsive design
- Dark mode ready

## 🚀 Quick Start

### 1. Backend Setup

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Edit .env untuk database
php artisan migrate --seed
php artisan serve
```

Backend: `http://localhost:8000`

### 2. Frontend Setup

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Frontend: `http://localhost:5173`

### 3. Login

- Email: `admin@pos.com`
- Password: `password`

## 📚 Dokumentasi Lengkap

➡️ **Baca [SETUP_GUIDE.md](./SETUP_GUIDE.md) untuk panduan lengkap!**

File tersebut berisi:
- Langkah-langkah setup detail
- Daftar pages yang perlu dibuat
- Template contoh code
- API usage examples
- Troubleshooting guide

## 🛠️ Tech Stack

**Backend:**
- Laravel 10
- MySQL
- Laravel Sanctum (Auth)
- Spatie Permission (Role & Permission)
- Maatwebsite Excel (Export)

**Frontend:**
- Vue.js 3 (Composition API)
- Vite
- Tailwind CSS
- Pinia (State Management)
- Vue Router
- Axios
- PWA Plugin

## 📊 Database Schema

- `users`, `roles`, `permissions` - User management
- `categories`, `units`, `products`, `product_units` - Products
- `warehouses`, `racks` - Storage locations
- `suppliers` - Supplier data
- `purchases`, `purchase_details` - Purchase transactions
- `sales`, `sale_details` - Sales transactions
- `stocks`, `stock_movements` - Stock tracking

## 🎯 Status Pengembangan

### ✅ Completed
- Backend API structure
- Frontend boilerplate
- Authentication (Login/Logout)
- Dashboard Layout with Sidebar
- Router with permissions
- State management setup
- API integration setup

### 🚧 In Progress
- Dashboard page
- POS (Kasir) page
- Product management pages
- Other CRUD pages

### 📋 To Do
- Semua pages sesuai routing
- Testing & bug fixes
- Production deployment

## 👥 Default Users

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@pos.com | password |
| Admin | admin.user@pos.com | password |
| Kasir | kasir@pos.com | password |
| Gudang | gudang@pos.com | password |

## 📝 License

MIT License - Bebas digunakan untuk project komersial maupun pribadi.

## 🤝 Contributing

Silakan fork dan buat pull request untuk kontribusi.

## 📞 Support

Untuk pertanyaan dan bantuan, lihat [SETUP_GUIDE.md](./SETUP_GUIDE.md)

---

**Dibuat dengan ❤️ menggunakan Laravel & Vue.js**

🎉 **Happy Coding!**
