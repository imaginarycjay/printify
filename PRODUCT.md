# PressQueue

<!-- impeccable:product-schema 1 -->

## Platform

web

## Record Basis

Updated from the implemented PressQueue v2 codebase (`WP-0` through `WP-5`) on 2026-11-21 to replace legacy `Printify` starter descriptions. Implemented behavior below reflects verified application code and automated tests, not claims of commercial deployment or external user studies.

## Users

- **Super Admin (`platform_admin`):** Approves, requests changes on (`rejected`), suspends, or reactivates printing shop tenants; configures global file retention TTL bounds and maximum customer upload limits; monitors private disk footprint and governance audit logs without viewing private shop financial ledgers.
- **Printing Shop Owner (`business_owner`):** Registers and onboards a tenant print shop (`PrintShop`), configures operating hours, live workload thresholds (`busy_threshold`, `at_capacity_threshold`), multi-printer fleet (`Printer`), staff invitations/revocations, modular shop applications via the App Launcher and More Services modal (`Install` / `Remove`), service pricing and Bill of Materials (BOM) recipes, raw material stock-in and Reorder Point (ROP) alerts, payment proof audits (50% downpayment / 100% full / offline counter / aging flags), pre-print specification revisions and manual refund settlements, and three standardized printable/exportable (PDF/CSV) operational reports.
- **Production Staff (`production_staff`):** Works strictly within the assigned tenant shop (`staff_shop_id`) to inspect prepress artwork, confirm Cover-Only customer-supplied paper intake (or shift rejected Cover-Only sheets to Full-Package shop paper), enforce post-approval specification locks, dispatch print jobs via inline direct browser print preview (`window.print()`, or RIP export for wide-format plotters), advance item-level stages across assigned printers, record optional shelf/bin tags for split early pickup, and log decoupled paper + ink/toner quality-assurance rework spoilage.
- **Customer (`customer`):** Browses approved, setup-complete shops and live workload indicators (`Normal`, `Busy`, `At capacity`) plus paused service notices; configures single or mixed multi-item Document Printing and Thesis Binding orders (Full-Package or Cover-Only) with reactive pricing and stock-shortage fallbacks; persists `pending_payment` orders with unique tracking codes prior to two-step proof submission/retry (50% downpayment or 100% full payment); requests pre-print specification revisions; tracks item-level production and split pickup readiness; and views/downloads official payment receipts.

## Product Purpose

Coordinate multi-tenant printing shop operations, multi-printer item-level job scheduling, prepress and flexible payment verification, and atomic Bill of Materials (BOM) inventory control in one unified web platform—replacing disconnected walk-in notebooks, chat threads, and loose desktop print downloads.

## Positioning

PressQueue is a multi-tenant SaaS marketplace and print-operations system built on Laravel 13, Livewire 4, Flux UI, Tailwind CSS v4, and PostgreSQL 18. Each printing shop operates inside a logically isolated `PrintShop` tenant boundary while sharing a public customer discovery marketplace. Per-shop `Web Builder` tooling has been retired and removed in favor of the unified marketplace storefront (`shops.show`) and Shop Settings (`owner.platform`).

## Operating Context

1. A printing shop owner registers (`owner.register`), completes guided setup (`owner.wizard`), and awaits Super Admin approval (`admin.shops`). Once approved and setup-complete, the shop appears in the public marketplace (`shops.index` → `shops.show`).
2. Customers browse approved shops with live 30-second-polled workload badges (`Normal`, `Busy`, `At capacity`) and service pause explanations without arbitrary completion-time promises.
3. Unified checkout (`customer.shop.checkout`) and single-service wizards validate page counts, duplex sheet equivalents, binding sheet limits (`max_binding_sheets`), and uploaded files (`PDF`, `PNG`, `DOCX`), reserving BOM stock atomically via ordered pessimistic row locks (`SELECT ... FOR UPDATE`) and saving the `pending_payment` order + tracking code before payment proof upload.
4. Upfront payment accepts either a minimum `50%` downpayment or `100%` full payment with per-shop unique reference enforcement, two-step retry recovery after connectivity drops, and owner verification/offline counter settlement (`payments` ledger + DomPDF/HTML Official Receipts).
5. Prepress approval locks item specifications (`is_spec_locked = true`). Pre-print specification revisions route through `spec_revisions` (`on_hold`, upgrade top-ups, downgrade balance credits or `Manual Refund Pending` → `Manual Refund Settled`) and are frozen permanently once physical printing begins.
6. Production Staff execute item-level multi-printer scheduling (`printer_id`, `assigned_staff_id`, Maintenance failover), direct inline browser print preview (`orders.file.preview`), split item pickup (`Partially Ready`, `shelf_bin_code`, `ready_at`), zero-balance + all-items-claimed parent completion, and decoupled paper + ink/toner `rework_spoilage`.
7. Scheduled background jobs (`pressqueue:purge-expired-files`, `pressqueue:check-aging-alerts`) purge expired completed-order artwork while preserving receipts/active jobs and emit deduplicated database + mail notifications surfaced via `<livewire:notification-bell />`.

## Capabilities and Constraints

- **Stack:** PHP 8.5, Laravel 13, Livewire 4 single-file `⚡` components under `resources/views/pages/`, Flux UI v2, Tailwind CSS v4, Fortify (2FA + passkeys), DomPDF, Larastan Level 7, and Pest 5.
- **Tenant Boundary (`.ai/rules/pages.md`):** `PrintShop` is the strict tenant boundary. Owners resolve their own shop; staff require `staff_shop_id`; `PrintShop::first()` fallbacks are strictly forbidden; private artwork, payment proofs, receipts, and reports enforce shop/customer authorization.
- **Explicit Out-of-Scope Boundaries:** Hardware printer firmware sheet/ink polling, automated e-wallet banking webhooks or payout APIs, physical delivery tracking, and commercial hosting claims are out of scope.

## Brand Commitments

- **Application Name:** `PressQueue` (rebranded cleanly from `Printify`).
- **Interface Copy & Language:** Concise, scannable operational English matching `Instrument Sans` editorial typography.
- **Visual Authority:** Native Light-Mode-first surfaces (`DESIGN.md`) with clean `dark:` variants; all UI assets, icons, and labels must remain legible in both interactive browsers and printed academic screenshots.

## Evidence on Hand

- `routes/web.php`, `routes/console.php`, `routes/settings.php`
- `app/Services/{InventoryDeductionService,OrderCheckoutService,PaymentService,ProductionQueueService,SpecRevisionService,ReportService,SalesAnalyticsService,AuditLogger}.php`
- `resources/views/welcome.blade.php`, `resources/views/pages/**`, `resources/views/components/**`
- `tests/Feature/` (`143` passing Pest tests, `0` PHPStan Level 7 errors)
- `system_plan/pressqueue_v2_system_implementation_plan.md` & `system_plan/pressqueue_v2_correction_plan_v2.md`
