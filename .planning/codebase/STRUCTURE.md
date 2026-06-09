# Codebase Structure

**Analysis Date:** 2026-06-09

## Directory Layout

```
pos/
├── backend/                  # Laravel REST API application
│   ├── app/                  # Application core code
│   │   ├── Http/             # Controllers, Middleware, Requests
│   │   └── Models/           # Eloquent Database Models
│   ├── config/               # Laravel configuration files
│   ├── database/             # Migrations, Seeders, Factories
│   ├── routes/               # Route definitions (api.php, console.php)
│   ├── tests/                # PHPUnit tests (Feature and Unit)
│   └── composer.json         # PHP dependency manifest
├── frontend/                 # Vue SPA & Tauri Desktop integration
│   ├── src/                  # Vue application source code
│   │   ├── components/       # Reusable Vue UI widgets
│   │   ├── layouts/          # DashboardLayout.vue and wrappers
│   │   ├── pages/            # View components mapped to routes
│   │   ├── router/           # Route definitions (index.js)
│   │   ├── stores/           # Pinia global store files
│   │   └── utils/            # Helper utilities and client instances
│   ├── src-tauri/            # Tauri desktop Rust codebase
│   │   ├── src/              # Rust source (lib.rs, main.rs)
│   │   └── tauri.conf.json   # Tauri manifest config
│   ├── package.json          # Node dependency manifest
│   └── vite.config.js        # Vite compilation configuration
└── .planning/                # GSD Project Management & Maps
```

## Directory Purposes

**backend/**
- Purpose: Laravel API server serving JSON data endpoints.
- Key files: `backend/composer.json`, `backend/artisan`.
- Subdirectories: `app/` (domain models and HTTP controllers), `database/` (migrations, factories, seeders), `routes/` (route mappings).

**frontend/**
- Purpose: Client interface of the system, supporting both browser SPA and Tauri desktop app.
- Key files: `frontend/package.json`, `frontend/vite.config.js`.
- Subdirectories: `src/` (Vue UI application), `src-tauri/` (Tauri desktop engine).

**frontend/src/pages/**
- Purpose: Router pages corresponding to navigation items.
- Contains: Feature indexes, forms, and print preview screens.
- Subdirectories: `pos/` (cashier interface), `sales/` (sales tracking), `products/` (product database), etc.

**frontend/src-tauri/**
- Purpose: Desktop app bridging, containing Rust files and configs.
- Key files: `tauri.conf.json` (Tauri layout and targets), `src/lib.rs` (native print implementation).

## Key File Locations

**Entry Points:**
- `backend/public/index.php` - HTTP Request entry point.
- `frontend/src/main.js` - SPA JavaScript engine startup.
- `frontend/src-tauri/src/main.rs` - Desktop Tauri wrapper entry point.

**Configuration:**
- `backend/.env` - Backend settings (DB connection, keys, printer name).
- `frontend/.env` - API backend target address config.
- `frontend/vite.config.js` - Asset compilation config.
- `frontend/src-tauri/tauri.conf.json` - Desktop build target definitions.

**Core Logic:**
- `backend/routes/api.php` - Endpoint route definitions.
- `backend/app/Http/Controllers/Api/` - Controller request processing.
- `frontend/src/stores/` - Local client state storage.

**Testing:**
- `backend/tests/` - Backend test suites.
- No frontend testing files are configured.

## Naming Conventions

**Files:**
- Vue components & layouts: PascalCase (e.g., `DashboardLayout.vue`, `VehicleFormModal.vue`).
- PHP Classes: PascalCase matching file names (e.g., `ProductController.php`).
- Configs & Routers: camelCase or kebab-case (e.g., `vite.config.js`, `index.js`).

**Directories:**
- Frontend src directories: lowerCamelCase / kebab-case (e.g., `src-tauri/`, `purchase-returns/`).
- Laravel app structure: standard namespace PascalCase folders (e.g., `Http/Controllers/Api/`).

## Where to Add New Code

**New Master Data Module:**
- Backend:
  - Model: `backend/app/Models/NewModel.php`
  - Migration: `backend/database/migrations/[timestamp]_create_new_models_table.php`
  - Controller: `backend/app/Http/Controllers/Api/NewModelController.php`
  - Route: Add `Route::apiResource('new-models', NewModelController::class)` inside `backend/routes/api.php`
- Frontend:
  - Views: Create directory `frontend/src/pages/new-models/` with `Index.vue` and modals.
  - Route: Add route definition inside `frontend/src/router/index.js` children of `DashboardLayout`.
  - Navigation: Add to sidebar in `frontend/src/layouts/DashboardLayout.vue`.

**New Tauri Native Command:**
- Implementation: Rust function inside `frontend/src-tauri/src/lib.rs`.
- Registration: Register inside `tauri::generate_handler![...]` in `lib.rs`.
- Frontend invocation: Invoke using `import { invoke } from "@tauri-apps/api/core"` (or equivalent) in Vue component.

---

*Structure analysis: 2026-06-09*
*Update when directory structure changes*
```
