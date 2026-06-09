# Codebase Concerns

**Analysis Date:** 2026-06-09

## Tech Debt

**Lack of Offline Cache / Sync Mechanism:**
- Issue: No offline caching or sync mechanism is implemented.
- Files: POS cart page (`frontend/src/pages/pos/Index.vue`), local stores.
- Why: Simple client-server structure designed assuming persistent network connectivity.
- Impact: If the internet connection drops, the cashier cannot process sales or save holds, causing transaction failures.
- Fix approach: Implement a Service Worker or IndexedDB/local-storage queue to store offline sales and synchronize them when connection is restored.

## Known Bugs

- None currently active or documented in codebase, but manual verification is heavily relied upon.

## Security Considerations

**Native Command Execution (Tauri Shell Plugin):**
- Risk: Tauri invokes native PowerShell and Shell commands (`lp`, `lpstat`, `powershell`) to list and print to devices. Vulnerable to injection if printer names or content parameters are not sanitized properly.
- Files: `frontend/src-tauri/src/lib.rs` (in `get_system_printers` and `print_receipt_to_printer`).
- Current mitigation: Basic sanitization of printer names (`printer_name.trim()`) and temp files.
- Recommendations: Avoid raw PowerShell commands for printing; use Rust native printing libraries (e.g. `printer` or `winprint` crate wrapper) if possible to avoid spawning subprocesses.

## Performance Bottlenecks

**Database Concurrency (SQLite Dev):**
- Problem: Development uses SQLite (`DB_CONNECTION=sqlite`).
- Cause: Simple, zero-config default.
- Impact: Concurrency write locks can happen if multiple users access the system simultaneously in a multi-cashier store.
- Improvement path: Migrate local development or test staging to MySQL/PostgreSQL to test database transactions under concurrency.

## Test Coverage Gaps

**Critical Flow Automated Verification:**
- What's not tested: Sales checkouts, payment processing callbacks (Midtrans), receipt printing commands, stock adjustments.
- Risk: Regression bugs can easily break the POS checkout or billing flows without detection.
- Priority: High.
- Difficulty to test: Requires setting up mock servers for Midtrans and native bridge adapters for Tauri commands.

---

*Concerns audit: 2026-06-09*
*Update as issues are fixed or new ones discovered*
