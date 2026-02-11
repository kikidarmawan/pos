# Tauri (Desktop App)

Aplikasi POS bisa di-build sebagai desktop app dengan **Tauri** (selain Electron). Tauri memakai webview sistem dan Rust, sehingga ukuran bundle lebih kecil.

## Persyaratan

- **Rust**: Install dari [rustup.rs](https://rustup.rs/)
- **macOS**: Xcode Command Line Tools (`xcode-select --install`)
- **Windows**: Visual Studio Build Tools dengan workload "Desktop development with C++"
- **Linux**: `libwebkit2gtk-4.1-dev`, `libgtk-3-dev`, dll. (lihat [Tauri docs](https://v2.tauri.app/start/prerequisites/))

## Cara menjalankan

1. **Install dependensi** (termasuk Tauri CLI & API):
   ```bash
   npm install
   ```

2. **Mode development** (Vite dev server + Tauri window):
   ```bash
   npm run tauri:dev
   ```
   Tauri akan menjalankan `npm run dev` lalu membuka jendela yang load dari `http://localhost:5173`.

3. **Build aplikasi desktop**:
   ```bash
   npm run tauri:build
   ```
   Hasil ada di `src-tauri/target/release/bundle/` (misalnya `.app` di macOS, `.exe` di Windows, `.AppImage` di Linux).

   Build per platform:
   - `npm run tauri:build:mac` — macOS (universal: Intel + Apple Silicon)
   - `npm run tauri:build:win` — Windows x64
   - `npm run tauri:build:linux` — Linux x64

## Icon aplikasi

Konfigurasi saat ini memakai `icon: []`. Untuk menambah icon:

1. Siapkan gambar **1024×1024** PNG.
2. Generate icon:
   ```bash
   npx tauri icon path/to/icon.png
   ```
   Ini akan mengisi folder `src-tauri/icons/` dan memperbarui `tauri.conf.json`.

## Perbedaan dengan Electron

| | Electron | Tauri |
|---|---|---|
| Ukuran | Lebih besar (Chromium) | Lebih kecil (webview sistem) |
| Printer thermal USB/Bluetooth | Ya (serialport) | Perlu plugin/implementasi Rust |
| Build | `npm run dist` / `dist:mac` | `npm run tauri:build` / `tauri:build:mac` |

Fitur cetak struk ke printer thermal (serial/USB/Bluetooth) saat ini diimplementasi di **Electron** saja. Untuk Tauri, cetak struk bisa lewat backend (API) atau nanti ditambah plugin Rust untuk serial.
