# Auto Update – Panduan Teknis

Aplikasi POS bisa dijalankan sebagai **Web/PWA** (browser) atau **Tauri** (desktop). Cara auto update berbeda per platform.

---

## 1. Web / PWA (Browser)

### Cara kerja

1. **Service Worker (PWA)**  
   Saat deploy build baru ke server, browser yang sudah buka aplikasi akan mendeteksi service worker baru.  
   - **Mode prompt:** User dapat notifikasi "Versi baru tersedia" dan tombol **Muat ulang** untuk memuat versi terbaru.  
   - **Mode auto:** Service worker mengupdate di background; versi baru aktif setelah user reload/refresh halaman.

2. **Cek versi dari server (opsional)**  
   Aplikasi bisa memanggil endpoint `GET /api/version` (atau `/version`) untuk mendapatkan versi yang di-deploy. Jika versi server lebih tinggi dari versi yang jalan di client, tampilkan banner "Update tersedia" dan tombol muat ulang.

### Yang sudah diimplementasi

- **PWA prompt update:** `registerType: 'prompt'` + `registerSW` dengan callback `onNeedRefresh`. Saat ada SW baru, muncul toast "Versi baru tersedia" dan tombol "Muat ulang".
- **Endpoint versi backend:** `GET /api/version` mengembalikan `{ "version": "x.y.z" }` dari `config('app.version')` atau env `APP_VERSION`.
- **Banner update (frontend):** Secara berkala (misal setiap 30 menit) frontend memanggil `/api/version`, membandingkan dengan `__APP_VERSION__`. Jika server lebih baru, tampil banner "Update tersedia" dan tombol muat ulang.

### Deploy

- Setiap kali deploy build baru (misal `npm run build` lalu upload `dist/` ke server), pastikan:
  - Cache server/CDN tidak memaksa cache lama untuk `sw.js` dan `index.html`.
  - Versi di backend (env `APP_VERSION` atau `config/app.php`) disamakan dengan versi frontend (misal `package.json` → `version`).

---

## 2. Tauri (Desktop App)

### Cara kerja

1. **Tauri Updater**  
   Plugin `tauri-plugin-updater` dipakai untuk:
   - Cek ketersediaan update (dari URL yang dikonfigurasi).
   - Unduh installer/package update (.msi, .dmg, .AppImage, dll.).
   - Memandu user untuk menjalankan installer (atau auto-restart jika didukung).

2. **Server update**  
   Perlu host yang menyajikan:
   - **Manifest JSON** (misal `latest.json`) berisi versi, URL unduh, dan signature.
   - **File update** (installer + file `.sig` hasil sign dengan private key).

3. **Signing**  
   Update **harus** ditandatangani:
   - Generate key pair: `tauri signer generate -w ~/.tauri/pos.key`
   - Saat build: set env `TAURI_SIGNING_PRIVATE_KEY` (path atau isi private key).
   - Public key dimasukkan di `tauri.conf.json` → `plugins.updater.pubkey`.

### Langkah teknis (ringkas)

1. **Tambahkan plugin updater (Rust)**  
   Di `src-tauri/Cargo.toml`:
   ```toml
   [dependencies]
   tauri-plugin-updater = { version = "2", optional = true }
   ```
   Dan di `tauri.conf.json` aktifkan updater (endpoint + pubkey).

2. **Build dengan artifact updater**  
   Di `tauri.conf.json`:
   ```json
   "bundle": {
     "createUpdaterArtifacts": true
   }
   ```
   Setelah build, akan ada file `.sig` dan installer/package per platform.

3. **Host manifest + file**  
   - Opsi A: **GitHub Releases** – upload installer + `.sig`, pakai URL release sebagai endpoint (Tauri bisa pakai format GitHub).
   - Opsi B: **Server sendiri** – simpan `latest.json` (atau nama lain) + file update di CDN/server, URL endpoint diisi di konfigurasi.

4. **Frontend (Vue)**  
   Pakai `@tauri-apps/plugin-updater`:
   - Panggil `check()` untuk cek update.
   - Jika ada: tampilkan dialog "Update tersedia", lalu panggil `downloadAndInstall()` (atau buka URL download) sesuai dokumentasi Tauri 2.

### Dokumentasi resmi

- [Tauri v2 – Updater](https://v2.tauri.app/plugin/updater/)
- [tauri-plugin-updater](https://github.com/tauri-apps/tauri-plugin-updater)

---

## 3. Ringkasan per platform

| Platform   | Mekanisme              | Yang perlu disiapkan                          |
|-----------|------------------------|-----------------------------------------------|
| Web / PWA | Service Worker + prompt / auto + (opsional) cek `/api/version` | Deploy build baru; set `APP_VERSION` di server |
| Tauri     | tauri-plugin-updater  | Signing key, host manifest + file update, konfigurasi endpoint + pubkey |

Dengan ini, **bisa** mengimplementasikan auto update baik untuk Web maupun Tauri; teknisnya mengikuti poin-poin di atas.
