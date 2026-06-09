# External Integrations

**Analysis Date:** 2026-06-09

## APIs & External Services

**Payment Processing & Subscriptions:**
- Midtrans - Payment gateway in Indonesia for subscription packages.
  - SDK/Client: `midtrans/midtrans-php` v2.6 on backend
  - Auth: Server Key (`MIDTRANS_SERVER_KEY`) and Client Key (`MIDTRANS_CLIENT_KEY`) in backend `.env`
  - Integration: Creates transactions, checks status, and listens for status callbacks via webhooks.

**Receipt Printing:**
- ESC/POS Thermal Printers:
  - Backend integration via `mike42/escpos-php` for server-side printing commands.
  - Frontend/Tauri Desktop integration via native command execution:
    - macOS / Linux: Invokes native `lp` and `lpstat` system commands.
    - Windows: Invokes native `powershell` with `Get-Printer` and `Out-Printer` cmdlets.
    - Configuration: Configured via `RECEIPT_PRINTER` env var or user selections saved in frontend local state.

## Data Storage

**Databases:**
- SQLite (Default Local) / MySQL / PostgreSQL (Production):
  - Connection: Configured via `DB_CONNECTION` (defaulting to SQLite in `database/database.sqlite` for local dev).
  - Client: Eloquent ORM (built into Laravel).
  - Migrations: Managed via `php artisan migrate` located in `backend/database/migrations/`.

**File Storage:**
- Laravel Storage:
  - Local Disk: Default driver for uploads (`storage/app/public/`).
  - Driver & Product Images: File uploads handled through custom controller actions (`update-with-file` endpoints).

## Authentication & Identity

**Auth Provider:**
- Laravel Sanctum - Token-based API authentication.
  - Implementation: User authentication tokens (`auth_token`) issued upon successful login, stored in Pinia persistent store (`authStore` using `localStorage` on client).
  - Session lifetime and security configured via Sanctum's configuration in `backend/config/sanctum.php`.

## CI/CD & Deployment

**Hosting:**
- Google Cloud Platform (GCP) or generic VPS:
  - Deployment instructions documented in `backend/DEPLOY_GCP.md`.
  - Dockerized containerization supported via `backend/Dockerfile` and `backend/docker-entrypoint.sh`.

**CI/CD:**
- Local compile script triggers Tauri targets for compilation:
  - macOS: `tauri build --target aarch64-apple-darwin`
  - Windows: `tauri build --target x86_64-pc-windows-msvc`
  - Linux: `tauri build --target x86_64-unknown-linux-gnu`

## Environment Configuration

**Development:**
- Required env vars:
  - Backend: `DB_CONNECTION`, `APP_KEY`, `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `RECEIPT_PRINTER`
  - Frontend: `VITE_API_URL` (usually pointing to `http://localhost:8000`)
- Mock/stub services:
  - Midtrans runs in sandbox/test mode (`MIDTRANS_IS_PRODUCTION=false`).

## Webhooks & Callbacks

**Incoming:**
- Midtrans Notification Webhook:
  - Endpoint: `POST /api/subscriptions/midtrans-notification`
  - Verification: Handled internally in `SubscriptionController` using Midtrans status requests.
  - Actions: Updates tenant subscription packages and logs active subscription statuses.

---

*Integration audit: 2026-06-09*
*Update when adding/removing external services*
