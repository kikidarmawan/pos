# Architecture

**Analysis Date:** 2026-06-09

## Pattern Overview

**Overall:** Decoupled Client-Server (Vue SPA + Laravel API) with Native Desktop Wrapper (Tauri).

**Key Characteristics:**
- **Client-Server Architecture:** Frontend (Vue) communicates with Backend (Laravel) entirely via JSON REST API.
- **Hybrid Desktop Shell:** The Vue SPA runs inside a Tauri WebView instance on desktop systems, allowing native access to system hardware (printers) via Rust.
- **Multitenancy & Subscriptions:** API routes are protected by subscription middleware verifying tenant payment status.

## Layers

**Client Layer (UI):**
- Purpose: User interface, pages, layout structure, client-side routing, and local state management.
- Location: `frontend/src/`
- Key Abstractions: Vue SPA Pages (`frontend/src/pages/`), Pinia stores (`frontend/src/stores/`), Router (`frontend/src/router/`).
- Depends on: Client-to-Native bridge (`@tauri-apps/api`), Axios for API communication.

**Native Bridge Layer (Tauri):**
- Purpose: Execute native OS-level commands (e.g., thermal printer output, system configuration).
- Location: `frontend/src-tauri/src/`
- Key Abstractions: Rust Tauri commands (`lib.rs`).
- Used by: Client Layer via Tauri invoke.

**API Routing & Middleware Layer:**
- Purpose: API endpoints mapping, rate limiting, authentication, and tenant subscription checks.
- Location: `backend/routes/api.php`, `backend/app/Http/Middleware/`
- Key Abstractions: Sanctum Guard, Custom `Subscription` Middleware.

**Controller Layer (API Handlers):**
- Purpose: Process HTTP request payloads, perform validations, invoke business logic, and construct JSON responses.
- Location: `backend/app/Http/Controllers/Api/`
- Key Abstractions: Laravel API Resources, resource controllers.

**Domain Model Layer (Database):**
- Purpose: Database models, relationships, transactions, and migration schemas.
- Location: `backend/app/Models/`, `backend/database/`
- Key Abstractions: Eloquent Models, Database Migrations, Seeders.

## Data Flow

### Typical Sale & Receipt Print Lifecycle:
1. **Cart Interaction:** The user adds products to the cart in `frontend/src/pages/pos/Index.vue`.
2. **API Submission:** User clicks "Checkout". Frontend dispatches `POST /api/sales` payload containing items.
3. **Middleware Guard:** Request passes through Laravel Sanctum (`auth:sanctum` middleware) and custom tenant subscription active check (`subscription` middleware).
4. **Controller Processing:** `App\Http\Controllers\Api\SaleController@store` validates quantities, creates DB records, updates stock levels (`Stock` & `StockMovement` models), and returns a JSON payload containing the invoice details.
5. **Print Invocation:** Frontend receives success response. If running in Tauri desktop shell, it compiles thermal receipt text layout and invokes Tauri command `print_receipt_to_printer`.
6. **Native OS Print:** Tauri/Rust backend commands the system printing daemon (`lp` or PowerShell `Out-Printer`) to print receipt.

### State Management:
- **Client State:** Kept in Pinia stores (e.g. `useAuthStore` in `frontend/src/stores/auth.js`). Persisted across reloads using local storage via `pinia-plugin-persistedstate`.
- **Server State:** Stateless session design. Tokens verify authorization per request, querying database records dynamically.

## Key Abstractions

**Custom Middleware (e.g., `Subscription` Middleware):**
- Purpose: Check active tenant subscription on every API request.
- Location: `backend/app/Http/Middleware/CheckActiveSubscription.php` (or similar)

**Pinia Stores:**
- Purpose: Centralized reactive client state.
- Examples: `useAuthStore` for token management, `useCartStore` for managing POS checkout carts.

**Eloquent Models:**
- Purpose: ORM mapping of database schema.
- Examples: `App\Models\Product`, `App\Models\Sale`, `App\Models\DeliveryItem`, `App\Models\Driver`.

## Entry Points

**Desktop Entry:**
- Location: `frontend/src-tauri/src/main.rs` -> calls `app::run()` in `lib.rs`
- Triggers: Launching the POS Desktop app.

**Web Frontend Entry:**
- Location: `frontend/index.html` -> loads `frontend/src/main.js`
- Triggers: Browser loading page / Tauri webview initialization.

**API Backend Entry:**
- Location: `backend/public/index.php` -> routes to `backend/routes/api.php`
- Triggers: Incoming HTTP requests from frontend client.

## Error Handling

**Client-Side Strategy:**
- Uses interceptors in Axios to detect `401 Unauthorized` or `403 Forbidden` responses.
- Displays toast notifications via `vue-toastification`.

**Server-Side Strategy:**
- Form request validation errors auto-return `422 Unprocessable Entity` with details.
- Exceptions caught and formatted as structured JSON by Laravel's global exception handler.

## Cross-Cutting Concerns

**Authentication:**
- Token-based API access via Sanctum header `Authorization: Bearer <token>`.

**Receipt Printing:**
- Direct ESC/POS printing (Tauri desktop app execution) vs. browser PDF print (`html2pdf.js`).

**Reports & Analytics:**
- Excel generation (`maatwebsite/excel`) and charts representation (`vue-chartjs`).

---

*Architecture analysis: 2026-06-09*
*Update when major patterns change*
