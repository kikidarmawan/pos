# 🚀 Quick Start - POS System

## Instalasi Cepat

### 1️⃣ Install Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
DB_DATABASE=pos_system
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,127.0.0.1,127.0.0.1:5173
```

```bash
# Buat database
mysql -u root -e "CREATE DATABASE pos_system"

# Migrate & seed
php artisan migrate --seed

# Jalankan server
php artisan serve
```

✅ Backend ready di: **http://localhost:8000**

---

### 2️⃣ Install Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

✅ Frontend ready di: **http://localhost:5173**

---

### 3️⃣ Login

Buka browser: **http://localhost:5173**

Login dengan:
- Email: `admin@pos.com`
- Password: `password`

---

## 🎯 Test Fitur Utama

### Test Produk Multi-Satuan

1. Buka **Produk** → Tambah Produk
2. Isi data dasar:
   - Nama: "Kabel Listrik"
   - Kategori: Pilih kategori
   - Satuan Dasar: Meter
   - Harga Dasar: 5000
3. Tambah Multi-Satuan:
   - Satuan: Roll, Konversi: 100, Harga: 450000
   - Satuan: Meter, Konversi: 1, Harga: 5000
4. Simpan

### Test Pembelian

1. Buka **Pembelian** → Buat Pembelian
2. Pilih Supplier, Gudang
3. Tambah produk dengan satuan tertentu
4. Pilih rak penyimpanan
5. Simpan → Stok otomatis bertambah!

### Test POS (Kasir)

1. Buka **Point of Sale**
2. Pilih gudang
3. Search produk
4. Pilih satuan (Roll atau Meter)
5. Produk masuk keranjang
6. Atur diskon/pajak
7. Klik Bayar → Input jumlah → Selesaikan
8. Stok otomatis berkurang!

### Test Laporan & Export

1. Buka **Laporan Penjualan**
2. Pilih range tanggal
3. Klik Tampilkan
4. Klik icon Download → Excel terdownload!

---

## ✅ Checklist Fitur

- [x] Login Multi User dengan Role & Permission
- [x] Produk Multi-Satuan (Roll, Meter, Pcs, dll)
- [x] Pembelian dari Supplier
- [x] Gudang & Rak Penyimpanan
- [x] Laporan Komprehensif & Advanced
- [x] PWA Support
- [x] Export Laporan ke Excel
- [x] Tampilan Modern & Responsive

---

## 📞 Troubleshooting

**Problem: CORS Error**
```bash
cd backend
php artisan config:clear
# Check SANCTUM_STATEFUL_DOMAINS di .env
```

**Problem: 401 Unauthorized**
- Clear browser localStorage
- Login ulang
- Check token

**Problem: Migration Error**
- Drop database: `mysql -u root -e "DROP DATABASE pos_system"`
- Buat ulang: `mysql -u root -e "CREATE DATABASE pos_system"`
- Migrate lagi: `php artisan migrate --seed`

---

## 📚 Dokumentasi Lengkap

- `README.md` - Overview project
- `SETUP_GUIDE.md` - Setup detail
- `IMPLEMENTATION_GUIDE.md` - Status implementasi fitur
- `backend/README.md` - Backend API docs
- `frontend/README.md` - Frontend docs

---

**Happy Coding! 🎉**
