# Technology Stack

**Analysis Date:** 2026-06-09

## Languages

**Primary:**
- PHP ^8.2 - Backend API and business logic (`backend/`)
- JavaScript (ES Modules) - Frontend SPA (`frontend/src/`)

**Secondary:**
- Rust - Desktop runtime wrapper / Tauri interface (`frontend/src-tauri/`)

## Runtime

**Environment:**
- PHP ^8.2 CLI / FPM - Backend web server runtime
- Node.js (v18.x or later recommended) - Frontend development and build tool
- Rust (Cargo) - Tauri desktop compiler/runtime
- Browser (Chromium / Webkit / Gecko) - Client-side SPA target
- Tauri WebView2 / WebKit - Client-side desktop target

**Package Manager:**
- Composer (PHP) - Backend package management (`backend/composer.json`, lockfile present)
- npm (Node) - Frontend package management (`frontend/package.json`, lockfile present)
- Cargo (Rust) - Tauri package management (`frontend/src-tauri/Cargo.toml`, lockfile present)

## Frameworks

**Core:**
- Laravel ^12.0 - Backend REST API framework
- Vue.js ^3.3.11 - Frontend SPA framework
- Tauri ^2.0.0 - Desktop application wrapper
- TailwindCSS ^3.4.0 - CSS utility framework

**Testing:**
- PHPUnit ^11.5.3 - Backend unit and feature testing
- None - No automated testing framework set up for Vue frontend or Tauri desktop app

**Build/Dev:**
- Vite ^5.0.10 - Frontend bundler and dev server
- `@tauri-apps/cli` ^2.0.0 - Tauri build tooling
- `@vitejs/plugin-vue` ^5.0.0 - Vue compiler plugin for Vite

## Key Dependencies

**Critical:**
- `laravel/sanctum` ^4.3 - Token-based API authentication
- `spatie/laravel-permission` ^6.24 - Role and permission management
- `midtrans/midtrans-php` ^2.6 - Payment gateway integration for subscriptions
- `@tauri-apps/api` ^2.0.0 - JS API to interact with the Tauri native layer
- `pinia` ^2.1.7 - State management library for Vue
- `vue-router` ^4.2.5 - Client-side routing

**Infrastructure & Utilities:**
- `mike42/escpos-php` ^4.0 - Thermal printer driver support
- `maatwebsite/excel` ^3.1 - Excel import/export functionality
- `axios` ^1.6.2 - HTTP client for API requests
- `jsbarcode` ^3.12.3 - Barcode generation on the client
- `chart.js` ^4.4.1 - Charting library for reports

## Configuration

**Environment:**
- Backend: `.env` file for database, queue, mail, Midtrans, and receipt printer config
- Frontend: `.env` file for API URLs (e.g. `VITE_API_URL`)

**Build:**
- `backend/vite.config.js` - Backend build configurations (if used, though Laravel serves as API)
- `frontend/vite.config.js` - Vite server and asset compiler setup
- `frontend/tailwind.config.js` - Tailwind utility styling configuration
- `frontend/src-tauri/tauri.conf.json` - Tauri desktop app manifest

## Platform Requirements

**Development:**
- macOS / Linux / Windows
- PHP 8.2+ installed locally or via Docker
- Node.js 18+ and npm
- Rust compiler and build tools (e.g., Xcode Command Line Tools for macOS, Build Tools for C++ on Windows)

**Production:**
- Backend: Cloud VM (GCP deployment guide provided in `backend/DEPLOY_GCP.md`), Docker, or traditional LAMP server
- Frontend: Desktop builds for macOS (.dmg, .app), Windows (.msi, .exe), Linux (.deb, .AppImage) compiled using Tauri CLI

---

*Stack analysis: 2026-06-09*
*Update after major dependency changes*
