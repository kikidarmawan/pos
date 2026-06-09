# Testing Patterns

**Analysis Date:** 2026-06-09

## Test Framework

**Backend (Laravel API):**
- Runner: PHPUnit v11.x (configured in `backend/phpunit.xml`).
- Assertion matches: Standard PHPUnit assertions (`assertStatus`, `assertJson`, `assertDatabaseHas`).
- Command to run:
  ```bash
  composer test   # Runs: php artisan config:clear && php artisan test
  ```

**Frontend (Vue & Tauri):**
- Runner: None configured.
- Assertion matches: N/A.

## Test File Organization

**Backend:**
- Location: Located in `backend/tests/` directory.
- Subdirectories:
  - `Unit/`: Standard unit level assertions (contains `ExampleTest.php`).
  - `Feature/`: HTTP Endpoint and request cycle integration tests (contains `ExampleTest.php`).
- Naming Convention: `*Test.php` suffix.

**Frontend:**
- No automated frontend test suites exist.

## Mocking

- Backend: Uses Mockery and Laravel's built-in facade mocking (`Queue::fake()`, `Event::fake()`, `Http::fake()`).
- External APIs (e.g. Midtrans): Stubbed or routed to sandbox environments during testing.

## Coverage

- Coverage tracking: Not enforced in CI or local configurations.

## Test Types & Verification

**Automated Tests:**
- Limited to Laravel backend boilerplate tests (e.g. `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php`).

**Manual Verification:**
- Local execution of the Laravel API server (`php artisan serve`) and Vite dev server (`npm run dev` in `frontend/`) to manually verify page behaviors and data saves.
- Running the desktop app locally with Tauri dev environment (`npm run tauri:dev`) to verify native capabilities (thermal printer detection).

---

*Testing analysis: 2026-06-09*
*Update when test patterns change*
