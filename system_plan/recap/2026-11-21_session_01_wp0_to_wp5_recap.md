# PressQueue v2 — Session 01 Recap (`WP-0` through `WP-5` Completion & Handoff)

- **Date / Time:** 2026-11-21 (`+08:00`)
- **Git Branch:** `sys-implementation-1`
- **Commit Checkpoints:**
  - `26f91fa` (`major update`): Initial `WP-0`, `WP-1`, and `WP-2` implementation plus capstone manuscript/diagram baseline.
  - `bb5d35b` (`wp 3 done`): `WP-3` implementation (Multi-Printer Fleet, Prepress Locks, Spec Revisions, Direct Browser Print & Dual QA Spoilage).
  - **Uncommitted Working Tree (Ready to Review / Commit):** `WP-4`, `WP-5`, `system_plan/` reorganization, and `AGENTS.md` / `.agents/AGENTS.md` session-transfer updates.
- **Authoritative System Plan:** `system_plan/pressqueue_v2_system_implementation_plan.md` (moved cleanly out of `capstone_paper/outline_paper/plans/`).

---

## 1. Locked Rules & Project Conventions
- **Mandatory English Chat Responses:** Always reply in English (`AGENTS.md`, `.agents/AGENTS.md`).
- **Tenant Boundary (`.ai/rules/pages.md`):** `PrintShop` is the strict tenant boundary in a shared PostgreSQL 18 database. Never fall back to `PrintShop::first()`. Owners resolve via their own shop; staff require `staff_shop_id`; customers select an explicit approved shop and pass active-service/unexpired-pause validation.
- **Livewire Single-File Page Convention:** Full-page Livewire 4 components live under `resources/views/pages/` with the `⚡` filename prefix (`pages::...` + `Route::livewire()`).
- **SDLC Terminology:** Strictly **5-Phase Modified Waterfall Model** (*Work Packages `WP-0`–`WP-5`*). Never use Agile/Sprint terminology anywhere.
- **Locked Functional Requirements:** All **17** FR titles (`FR-01`–`FR-17`) are locked word-for-word (`Table 6` = Modular `UC-1`–`UC-4` = `AD-01`–`AD-17`). `Web Builder` is permanently retired/deleted.
- **Manuscript Guard:** Chapter 2 RRL inside `capstone_paper/outline_paper/papers/outline_capstone_v2.docx` was intentionally left untouched (`word/comments.xml` preserved intact).

---

## 2. Work Packages Completed (`WP-0` to `WP-5`)

### Work Package 0 — Environment & PDF Tooling (`26f91fa`)
- Fixed Sail container file permissions under `storage/framework/testing` so filesystem tests run cleanly.
- Installed `barryvdh/laravel-dompdf` for real `.pdf` generation (Official Receipts and Standardized Reports).

### Work Package 1 — Rebrand, Web Builder Retirement, Tenant Governance & Audit Foundation (`26f91fa`)
- Rebranded `Printify -> PressQueue` across `.env.example`, `config/app.php`, auth layouts/components/pages, headers, emails, and pages, while fixing Light-Mode white-on-white brand text contrast on `resources/views/layouts/auth/split.blade.php`.
- Retired & deleted `Web Builder` (`⚡web-builder.blade.php`, `WebBuilderAppTest.php`, and references across `PrintServiceCatalog`, `routes/web.php`, owner pages, and navigation/wizard tests).
- Migrations & Governance (`2026_11_19_133057`..`133100`):
  - `platform_settings` (`PlatformSetting` model: `default_retention_days`, `min_retention_days`, `max_retention_days`, `max_upload_mb`).
  - `print_shops` additions: `file_retention_days`, `busy_threshold`, `at_capacity_threshold`, `operating_hours`.
  - `audit_logs` (`AuditLog` + `App\Services\AuditLogger`).
  - Super Admin console (`pages::admin-shops` + `PlatformController::review/updateSettings`) upgraded with `Suspend / Reactivate`, global retention bounds, and Storage Health audit counters while isolating private shop financial ledgers. Suspended shop blocks enforced at auth/middleware and marketplace queries.
- Verified in `tests/Feature/TenantPlatformTest.php`.

### Work Package 2 — Normalized Schema, Multi-Item Checkout, Concurrency Locks & Proof Retry (`26f91fa`)
- Migrations (`2026_11_19_145013`..`145021`):
  - `inventory_items.reserved_qty` and `stock_movements.order_item_id`.
  - Normalized `orders` (nullable/deprecated `service_key`, `tracking_code`, `payment_option`, `amount_paid`, `balance_due`, `refund_due`, `refund_status`, `completed_at`) and `order_items` (per-item `service_key`, `assigned_staff_id`, `production_stage`, `prepress_status`, `prepress_rejection_reason`, `is_spec_locked`, `shelf_bin_code`, `ready_at`, `claimed_at`, `file_purged_at`).
  - `payments` table (`Payment` model) enforcing per-shop unique `(print_shop_id, reference_no)`.
  - `thesis_binding_configs.max_binding_sheets` (default `250`).
- Services & UI:
  - `InventoryDeductionService` upgraded with ordered `lockForUpdate()` pessimistic locking (`reserveForOrderItems`, `releaseReservation`, `commitReservationOnPrint`, `InsufficientStockException` with copy/paper/Cover-Only fallbacks).
  - `OrderCheckoutService` & `PaymentService`: unified multi-item cart (`pages::customer.⚡shop-cart`) combining Document Printing + Thesis Binding in one parent `Order`, duplex & max-binding validation, `pending_payment` order + tracking code saved prior to proof upload, min `50%` DP vs `100%` Full validation, two-step proof retry in `⚡customer-dashboard`, Owner Payment Audit queue in `⚡analytics-hub`, offline counter settlement, and HTML/DomPDF Official Receipt (`resources/views/receipts/official-receipt.blade.php`, route `orders.receipt`).
- Verified in `tests/Feature/OrderCheckoutAndPaymentTest.php`.

### Work Package 3 — Multi-Printer Fleet, Prepress Locks, Spec Revisions, Direct Print & Dual QA Spoilage (`bb5d35b`)
- Migrations (`2026_11_21_044117`..`044118`): `printers` table (`order_items.printer_id` FK) and `spec_revisions` table (`Printer` & `SpecRevision` models).
- Services, Routes & UI:
  - `SpecRevisionService`: pre-print `on_hold` revisions releasing/re-locking BOM stock, upgrade top-ups vs downgrade balance credits / `Manual Refund Pending -> Settled`, and hard-blocking edits once printing begins.
  - `ProductionQueueService`: item-level prepress (`is_spec_locked = true`), artwork rejection + re-upload, bidirectional **Cover-Only paper rejection -> shift to Full-Package shop paper** (`rejectCoverOnlyAndShiftToShopPaper`), multi-printer/staff assignment + `Maintenance` batch failover (`setPrinterStatusWithFailover`), split early pickup (`Partially Ready`, `shelf_bin_code`, `ready_at`), zero-balance + all-items-claimed parent completion gate, and QA Fail dual Paper + BOM Ink/Toner `rework_spoilage` (`failQualityCheckWithDualSpoilage`) keeping customer billing locked.
  - Inline locked stream route `orders.file.preview` (`PlatformController::previewDocument`, `Content-Disposition: inline`) + **Direct Browser Print Preview Modal** in `⚡staff-dashboard` (plus RIP export path for wide-format plotters).
  - UI updates in `⚡owner-platform`, `⚡shops`, `⚡shop`, `⚡analytics-hub`, `⚡staff-dashboard`, and `⚡customer-dashboard`.
- Verified in `tests/Feature/ProductionWorkflowAndRevisionsTest.php`.

### Work Package 4 — Standardized Reports, Notifications, Scheduled Cleanup & CI Gate (Working Tree)
- **Notifications (`FR-06`, `FR-17`):**
  - Migration `database/migrations/2026_11_21_050001_create_notifications_table.php` (`notifications` UUID table).
  - 6 queued (`ShouldQueue`, `database` + `mail`) classes under `app/Notifications/`: `LowStockReorderNotification`, `PaymentStatusNotification`, `PrepressActionRequiredNotification`, `SpecRevisionNotification`, `OrderPickupReadyNotification`, `AgingOperationalAlertNotification`.
  - Wired with `->afterCommit()` and PHP-side deduplication (cross-compatible with PostgreSQL 18 & SQLite `:memory:`) across `InventoryDeductionService`, `PaymentService`, `ProductionQueueService`, and `SpecRevisionService`.
  - Livewire `<livewire:notification-bell />` (`resources/views/components/⚡notification-bell.blade.php`) embedded in top bars of `platform-header.blade.php`, `⚡owner-dashboard`, `⚡analytics-hub`, `⚡staff-dashboard`, and `⚡customer-dashboard`.
- **Standardized 3-Tab Report Center (`FR-16`):**
  - `App\Services\ReportService` (`buildInventoryReport`, `buildSalesReport`, `buildProductionScheduleReport`, `exportCsv`) scoped strictly by `PrintShop`.
  - Route `owner.reports.show` in `routes/web.php` + `PlatformController::report()` serving HTML Print View (`resources/views/reports/standardized-report.blade.php`), `?format=pdf` (DomPDF download), and `?format=csv` (streamed RFC-4180 CSV).
  - Added 3rd `Standardized Reports` (`reports`) tab in `resources/views/pages/owner/⚡analytics-hub.blade.php` while keeping `'overview'` as default.
- **Scheduled Maintenance (`FR-17`):**
  - `App\Console\Commands\PurgeExpiredOrderFiles` (`pressqueue:purge-expired-files`, `daily()->withoutOverlapping()` in `routes/console.php`): deletes heavy artwork files past platform-clamped shop TTL, stamps `file_purged_at`, writes `files.purged` audit logs, preserves Official Receipts, and skips uncompleted, `on_hold`, and `partially_ready` orders.
  - `App\Console\Commands\CheckAgingOperationalAlerts` (`pressqueue:check-aging-alerts`, `hourly()->withoutOverlapping()`): alerts owners on pending payment proofs aging `> 2h` and customers on unclaimed ready-for-pickup items aging `> 3d` with deduplication.
- Verified in `tests/Feature/ReportsNotificationsAndMaintenanceTest.php`.

### Work Package 5 — Demo Seeder, Live Light-Mode Screenshots, Synced Diagrams & Manuscript Sync (Working Tree)
- **Demo Seeder (`database/seeders/PressQueueDemoSeeder.php` + `tests/Feature/PressQueueDemoSeederTest.php`):**
  - Idempotent seeder run against live PostgreSQL 18 (`sail artisan db:seed --class=PressQueueDemoSeeder`).
  - Accounts (all password `password`):
    - Super Admin: `admin@pressqueue.test`
    - Tenant 1 (`Kabacan Academic Press` — `Normal`): Owner `owner@pressqueue.test`, Staff `staff@pressqueue.test` & `staff2@pressqueue.test`, Customer `customer@pressqueue.test`
    - Tenant 2 (`USM Gate Copy & Bind Center` — `Busy`): Owner `owner2@pressqueue.test`, Staff `staff3@pressqueue.test`, Customer `customer2@pressqueue.test`
    - Tenant 3 (`Cotabato Campus Print Hub` — `At capacity` + paused `thesis_binding`): Owner `owner3@pressqueue.test`
    - Tenant 4 (`Mindanao Thesis Press` — `pending` approval): Owner `owner4@pressqueue.test`
  - Seeds 3 printers (including `Canon imagePROGRAF PRO-2100 (Plotter)` in `maintenance` + wide-format), low-stock ROP alert item (`Legal Bond Paper 80gsm`), orders `PQ-DEMO-1001`..`PQ-DEMO-1006`, aging pending proof, `Manual Refund Pending`, dual `rework_spoilage`, completed receipt + settled refund, and sample local PDFs (`orders/manuscripts/demo-thesis-manuscript.pdf`, `orders/documents/demo-handout-pack.pdf`).
- **Light-Mode CSS Gradient Fix:**
  - Updated `resources/css/app.css` under `html:not(.dark)` to override `.from-stone-900/.from-stone-950`, `.via-stone-900/.via-stone-950`, and `.to-stone-900/.to-stone-950` gradient stops after screenshot QA caught a dark gradient fade on the Inventory Hub restock alert banner. Rebuilt Vite production assets (`sail npm run build`, removed stale `public/hot`).
- **Live Screenshots (`17` PNGs in `capstone_paper/outline_paper/diagrams/ui/`):**
  - `01_landing_page.png` through `17_standardized_schedule_report.png` captured cleanly in Light Mode (`1440x900`, `2x` DPR).
- **Synced Stage 2 & Stage 3 Diagrams (`24` PNGs in `capstone_paper/outline_paper/diagrams/`):**
  - `PressQueue_ERD_v2.png` (all 18 live PostgreSQL tables + Adviser ERD normalization callout), `PressQueue_Class_Diagram_v2.png`, `PressQueue_Gantt_Chart_v2.png` (5-Phase Modified Waterfall), 4 modular use cases (`use_cases/PressQueue_UC-1`..`UC-4`), and 17 swimlane activity diagrams (`activity_diagrams/PressQueue_AD-01`..`AD-17`).
- **Manuscript Body-Clause Refinements (`capstone_paper/outline_paper/papers/outline_capstone_v2.docx`):**
  - Surgically updated only body prose of `FR-05`, `FR-08`, `FR-10`, `FR-11`, `FR-13`, `FR-15`, and `FR-17` via XML span patching while keeping all 17 FR titles verbatim and `word/comments.xml` (`sha256: efdd5366...`, Chapter 2 RRL) untouched.

---

## 3. Verification State at Handoff
- `./vendor/bin/sail composer test` (run after final PHP, CSS, seeder, and manuscript changes):
  - **Pint:** `passed`
  - **PHPStan (Level 7):** `passed` (`0` errors)
  - **Pest:** `143` passed, `0` failed (`925` assertions, `10765ms`)
- `./vendor/bin/sail npm run build`: `1.95s` (no Vite dev `public/hot`).
- No edits to `package.json` or `composer.json` in `WP-4`/`WP-5`.

---

## 4. Open Items for Next Conversation
- **User Review & Bug Fixes:** The user reviewed the `WP-0`–`WP-5` build and noted a few issues to inspect and fix next. Review the user's findings carefully against this baseline, fix targeted code/UI/diagram/manuscript items cleanly, and re-run `./vendor/bin/sail pint --dirty --format agent && ./vendor/bin/sail composer test` (plus `./vendor/bin/sail npm run build` and live screenshot re-capture if UI views change).
- **Uncommitted Changes:** Once the user approves the fixes, commit `WP-4`, `WP-5`, and `system_plan/` changes cleanly on `sys-implementation-1` if requested.
