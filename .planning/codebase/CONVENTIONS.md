# Coding Conventions

**Analysis Date:** 2026-06-09

## Naming Patterns

**Files:**
- Frontend Vue Components / Views: PascalCase (e.g. `DashboardLayout.vue`, `SubscriptionExpiredModal.vue`, `DriverFormModal.vue`).
- Frontend Router/Stores/Utils: camelCase (e.g. `index.js`, `auth.js`, `api.js`).
- Backend PHP Classes: PascalCase (e.g. `SaleController.php`, `DeliveryItem.php`, `RolePermissionSeeder.php`).
- Backend Config/Routes: camelCase or kebab-case (e.g. `api.php`, `console.php`).

**Functions & Methods:**
- PHP Methods: camelCase (e.g., `store()`, `midtransNotification()`, `printReceipt()`).
- JavaScript Functions: camelCase (e.g., `formatNavbarDateTime()`, `toggleMenu()`, `handleLogout()`).

**Variables:**
- PHP/JS local variables: camelCase (e.g., `appVersion`, `dateTimeInterval`, `$deliveryItem`).
- Database columns / Eloquent relationships: snake_case (e.g., `delivery_items`, `driver_id`, `vehicle_id`).

**Types & Interfaces:**
- Rust (Tauri): PascalCase for structs and enums, snake_case for fields/functions.

## Code Style

**PHP/Laravel:**
- Standard PSR-12 coding standard.
- Linting/Formatting: Formatted via Laravel Pint (`npm run lint` or `vendor/bin/pint`).
- Indentation: 4 spaces.

**JavaScript/Vue:**
- Vue 3 composition API style using `<script setup>`.
- ES modules dynamic imports for router pages.
- Indentation: 2 spaces.
- CSS: Utility-first styling with TailwindCSS classes directly in Vue templates.

## Import Organization

**Vue Files:**
1. Vue core reactivity/lifecycle helpers (`ref`, `computed`, `watch`, `onMounted`, etc.).
2. Routing helpers (`useRouter`, `useRoute`).
3. Pinia state stores (e.g., `@/stores/auth`, `@/stores/subscription`).
4. Local components (e.g., `@/components/SubscriptionExpiredModal.vue`).
5. Icon libraries and third-party UI helpers (e.g., `@heroicons/vue/24/outline`).

**Path Aliases:**
- `@/` maps to the `frontend/src/` directory.

## Error Handling

**Backend Strategy:**
- Validations are declared inline or inside Laravel Request classes, returning `422 Unprocessable Entity` on mismatch.
- Database operations involving multiple tables are wrapped inside `DB::transaction()` to ensure atomicity.

**Frontend Strategy:**
- Async actions are wrapped inside `try/catch` blocks.
- Errors are flashed to users via the `vue-toastification` handler.

## Comments & Documentation

**Guidelines:**
- Use JSDoc style comments for Vue component methods, documenting props, events, and side-effects.
- Prefix temporary workarounds or feature items with `// TODO(username): description` or `// FIXME: description`.

---

*Convention analysis: 2026-06-09*
*Update when patterns change*
