# POS System — Point of Sale

Aplikasi POS (Point of Sale) untuk mengelola penjualan, stok, dan laporan toko. Backend Laravel API + Frontend Vue.js, dengan aplikasi desktop (Tauri) dan dukungan cetak struk ke printer thermal.

---

## Struktur Project

```
pos/
├── backend/              # Laravel API
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/api.php
│   └── ...
│
├── frontend/             # Vue.js 3 + Vite
│   ├── src/
│   │   ├── pages/        # Halaman (POS, Penjualan, Laporan, Settings, dll)
│   │   ├── components/
│   │   ├── layouts/
│   │   ├── router/
│   │   ├── stores/
│   │   └── utils/
│   ├── src-tauri/        # Aplikasi desktop (Tauri)
│   ├── package.json
│   └── vite.config.js
│
├── README.md
├── SETUP_GUIDE.md
└── PRINTER_RECEIPT_NOTES.md
```

---

## Fitur

### Penjualan & Kasir
- **POS / Kasir** — Keranjang, quick search, barcode, multi satuan
- **Pembayaran** — Tunai, kartu, transfer, utang (kredit)
- **Cetak struk** — Dari aplikasi (Tauri) ke printer thermal/default; di browser via backend
- **Hold transaksi** — Tunda dan lanjut nanti
- **Riwayat penjualan** — Daftar & detail transaksi, cetak ulang struk
- **Data pelanggan** — Nama & no. HP di struk

### Stok & Gudang
- Multi gudang & rak
- Stok masuk/keluar, penyesuaian (opname)
- Riwayat pergerakan stok

### Pembelian
- Input pembelian & retur
- Data supplier

### Produk & Master
- Produk dengan kategori & satuan
- Multi satuan per produk (konversi), harga per satuan
- Barcode

### Laporan
- Dashboard, penjualan, pembelian, stok, laba/rugi
- Laporan kasir harian, supplier & pelanggan
- Export (Excel)

### Pengaturan
- **Toko** — Nama, alamat, telepon
- **Printer** — Lebar kertas (58/80 mm), ukuran font, header toko, pilih printer untuk struk, scan printer, cetak uji
- **User & role** — Super Admin, Admin, Kasir, Gudang (Spatie Permission)

### Aplikasi
- **Browser** — SPA Vue.js (Vite)
- **Desktop** — Tauri (macOS / Windows / Linux), cetak struk langsung ke printer

---

## Tech Stack

| Layer    | Teknologi |
|----------|-----------|
| Backend  | Laravel 12, MySQL, Sanctum, Spatie Permission, Maatwebsite Excel, Mike42 Escpos (struk via backend) |
| Frontend | Vue 3, Vite, Pinia, Vue Router, Tailwind CSS, Axios |
| Desktop  | Tauri 2 (Rust) |

---

## Quick Start

### Persyaratan
- PHP 8.2+, Composer
- Node.js 18+, npm
- MySQL (atau database lain yang didukung Laravel)
- (Opsional) Rust + Cargo — untuk build aplikasi desktop Tauri

### 1. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Sesuaikan DB_* di .env
php artisan migrate --seed
php artisan serve
```

API: `http://localhost:8000`

### 2. Frontend (browser)

```bash
cd frontend
npm install
cp .env.example .env
# Sesuaikan VITE_API_URL jika perlu
npm run dev
```

Frontend: `http://localhost:5173`

### 3. Desktop (Tauri, opsional)

```bash
cd frontend
npm run tauri dev
```

Build distribusi:
- macOS: `npm run tauri:build` atau `npm run tauri:build:mac`
- Windows: `npm run tauri:build:win`
- Linux: `npm run tauri:build:linux`

### 4. Login

| Email             | Password  |
|-------------------|-----------|
| admin@pos.com      | password  |

---

## Pengaturan Printer (Desktop / Tauri)

- **Settings → Printer**: atur lebar kertas (58/80 mm), ukuran font, tampilkan header toko, pilih printer untuk struk.
- **Scan printer**: deteksi printer yang terpasang (termasuk Bluetooth yang sudah dipasang di sistem).
- **Cetak uji**: tes struk dengan data contoh.
- Pengaturan disimpan di database (tabel `stores`).

Struk dari POS dan dari detail penjualan memakai alur yang sama (cetak dari aplikasi di Tauri, atau via backend di browser).

---

## Dokumentasi

- **[SETUP_GUIDE.md](./SETUP_GUIDE.md)** — Panduan setup lengkap
- **[PRINTER_RECEIPT_NOTES.md](./PRINTER_RECEIPT_NOTES.md)** — Catatan cetak struk & `buildReceiptText`

---

## License

MIT — bebas dipakai untuk keperluan pribadi maupun komersial.

---

Dibuat dengan Laravel & Vue.js. Untuk pertanyaan teknis, lihat SETUP_GUIDE.md.
