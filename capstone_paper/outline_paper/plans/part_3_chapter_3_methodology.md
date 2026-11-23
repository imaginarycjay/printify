# Part 3: Chapter 3 (Methodology), Diagrams, Curated Light-Mode UI, & Appendices F–I — *PressQueue* v2 Guide & Synced Reference

> **System Name**: **PressQueue**
> **Approved v2 Title**: ***PressQueue: A Multi-Tenant Web-Based Printing Shop Operations, Job Scheduling, and Inventory Management System***
> **Authoritative Manuscript Sync**: `capstone_paper/outline_paper/papers/outline_capstone_v2.docx` (**17 Locked FRs `FR-01`–`FR-17`**, **12 Smoke Tests `ST-01`–`ST-12`**, **46 Functional Tests `FT-01`–`FT-46`**, **`Tables 1–10`**, **`Appendices F–I`**; `Appendices A–E` are USM institutional forms and `Appendix J` is Curriculum Vitae).
> **Companion System Plan**: [`system_plan/pressqueue_v2_correction_plan_v2.md`](file:///home/imaginarycjay/printify/system_plan/pressqueue_v2_correction_plan_v2.md)
> **Reference Benchmark**: `capstone_paper/outline_paper/scratch/outline_example_1.pdf` (*Eventia* — UERC ethics-exempt functional verification & pure academic diagram standard).

---

## 1. Summary of Structural Changes in Chapter 3 (v1 → v2)

| v1 Section (`outline_capstone_v1_FinalDraft.pdf`) | v2 Section (`PressQueue` — Synced to `outline_capstone_v2.docx`) | Why It Changed |
| :--- | :--- | :--- |
| `Research Design` (Developmental + ISO/IEC 25010 descriptive survey) | **`Research Design`** (Developmental Research Design supported by **Requirements-Based Functional Testing** and **Deployment-Readiness Planning**) | Eliminates human survey respondents; evaluates every test case objectively as **`Passed`**, **`Failed`**, or **`Needs Revision`**. |
| `Project Participants` (3 Shop Owners, 9 Staff, 20 Customers) | **`Testing Personnel and Materials`** (No external human sample; test cases executed by project developers across 4 role accounts and reviewed by adviser/panel) | **Core UERC Ethics Exemption**: Verifies deterministic software conformance against `FR-01`–`FR-17` rather than collecting subjective ratings. |
| `Data Collection Procedure` (Field interviews, observations, survey forms) | **`Data Collection Procedure`** (Deriving `ST-01`–`ST-12` and `FT-01`–`FT-46` from `FR-01`–`FR-17`, preparing synthetic multi-tenant data and sample print files, executing step-by-step role workflows) | Removes all human-subject field collection that would otherwise require UERC clearance. |
| `Statistical Treatment` (Weighted Mean & 5-Point Likert Scale) | **`Data Analysis Procedure`** & **`Validity of the Testing Procedure`** (Comparing observed outputs against expected acceptance criteria + forward/backward FR traceability + regression verification) | Replaces subjective survey statistics with deterministic ISO/IEC/IEEE 29119 functional verification. |
| `Software Development Methodology: Agile Scrum Model` | **`Software Development Methodology: Modified Waterfall Model`** (5 sequential phases with feedback loops: *Requirements Analysis*, *System Design*, *Implementation*, *Testing and Refinement*, *Deployment Planning and Readiness*; **July 2026 – December 2026**) | Panel (`N`, `R`, Adviser) & `outline_example_1.pdf`. |
| *Missing in v1* | **`Phase 4: Project Assessment and System Testing Plan`** (**Smoke Testing `Table 1`**, **Role-Based Functional Testing `Tables 2–5`**, and **Regression Testing**) | Complete role-by-role testing blueprint (`ST-01`–`ST-12`, `FT-01`–`FT-46`). |
| *Missing in v1* | **`Phase 5: Deployment Planning and Readiness`** (**`Table 9: Proposed PressQueue Production Deployment Specification`**, **`Figure 3: Deployment Diagram [v5]`**, and **7 Operational Fallback Procedures**) | Demonstrates production SaaS readiness without falsely claiming live commercial hosting inside the capstone scope. |
| `Appendix A: Survey Questionnaire` | **`Appendix F: Test Cases for Smoke Testing (ST-01–ST-12)`**, **`Appendix G: Test Cases for Functional Testing (FT-01–FT-46)`**, **`Appendix H: Regression Test Records (RT-____)`**, **`Appendix I: Task Allocation Matrix (TAM)`** | Preserves USM Institutional Forms in `Appendices A–E` while replacing survey forms with executable ISO/IEC/IEEE 29119 test records. |

---

## 2. Opening Methodology Sections (`3.1` to `3.7`)

### 3.1 `Research Design`
> This capstone project employs a **developmental research design** supported by **requirements-based functional testing** and **deployment-readiness planning**. The developmental phase encompasses requirements specification, multi-tenant architectural modeling, and full-stack implementation of **PressQueue: A Multi-Tenant Web-Based Printing Shop Operations, Job Scheduling, and Inventory Management System**.
>
> System verification is conducted through structured **Smoke Testing**, **Role-Based Functional Testing**, and **Regression Testing** aligned with ISO/IEC/IEEE 29119. Rather than collecting subjective user perception ratings through external survey questionnaires, verification evaluates whether each implemented module satisfies its seventeen locked functional requirements (`FR-01` to `FR-17`) across representative multi-tenant scenarios, role permissions, validation boundaries, and concurrency rules. Each executed test case records its expected output, observed output, and classification as **Passed**, **Failed**, or **Needs Revision**. Any test case marked *Failed* or *Needs Revision* undergoes code refinement and targeted **Regression Testing** until all acceptance criteria are met.

### 3.2 `Project Locale`
> The design, development, and functional verification of **PressQueue** are conducted at the Department of Computing and Library Information Science, College of Engineering and Information Technology, University of Southern Mindanao (USM), Main Campus, Kabacan, Cotabato. While engineered as a cloud-ready multi-tenant SaaS platform capable of onboarding independent printing shops across any locality, the workflows, pricing matrices, multi-printer scheduling rules, and Bill of Materials (BOM) inventory configurations modeled in the test environment reflect commercial and campus-adjacent printing shop operations in Kabacan, Cotabato.

### 3.3 `Testing Personnel and Materials`
> The functional-testing procedure does not require a human respondent sample because it verifies deterministic software behavior against approved functional requirements (`FR-01` to `FR-17`) rather than collecting subjective user ratings. The project developers prepare, execute, and document all smoke, functional, and regression test cases across four role-based test accounts:
>
> 1. **Super Admin** (tenant approval/rejection/suspension, workspace isolation oversight, global file retention bounds, and storage health audit without accessing private shop financial ledgers);
> 2. **Printing Shop Owner** (shop profile, workload thresholds, service pause/resume, multi-printer fleet, staff invitations/revocation, modular App Launcher & service catalog, BOM & inventory ROP, payment proof verification with aging flags, counter settlement, manual refund ledger, and 3 standardized printable/exportable reports);
> 3. **Production Staff** (prepress artwork inspection, bidirectional Cover-Only intake & specification lock, multi-printer item-level scheduling & Maintenance failover, shelf/bin tagging, direct browser print dispatch, and decoupled paper + ink/toner QA spoilage logging); and
> 4. **Customer** (marketplace browsing with live *Normal*, *Busy*, *At capacity*, and *Paused* indicators, dynamic quotation & multi-item cart checkout, 50% downpayment or 100% full payment with two-step proof retry recovery, pre-print specification revision requests, split item-level order tracking, and official receipts).
>
> Testing materials consist of **Appendix F (`ST-01`–`ST-12`)**, **Appendix G (`FT-01`–`FT-46`)**, **Appendix H (`RT-____`)**, synthetic multi-tenant shop datasets, and sample print-ready documents (`PDF`, `PNG`, `DOCX`).

### 3.4 `Data Collection Procedure`
1. **Test Case Derivation:** Map all seventeen locked functional requirements (`FR-01`–`FR-17`) and panel edge cases (`R1`–`R5`) into `ST-01`–`ST-12` (`Appendix F`) and `FT-01`–`FT-46` (`Appendix G`).
2. **Test Environment & Synthetic Data Preparation:** Seed isolated tenant shops (*Normal*, *Busy*, *At capacity + Paused service*, and *Pending* application), physical printers (including wide-format/maintenance states), BOM-mapped paper/binding/ink inventory with ROP thresholds, role accounts, and sample print files.
3. **Smoke Test Execution:** Execute `ST-01`–`ST-12` to confirm core routes, role redirects, tenant isolation guards, database connectivity, browser print preview, and report export routes respond cleanly.
4. **Role-Based Functional Test Execution:** Execute `FT-01`–`FT-46` across positive paths and boundary conditions (concurrent `SELECT ... FOR UPDATE` stock locks, two-step proof retry, prepress locks & pre-print upgrades/downgrades with manual refunds, split early pickup with zero-balance completion, direct browser print dispatch, dual paper/ink QA spoilage, daily retention cleanup, and 3 standardized reports).
5. **Defect Resolution & Regression Logging:** Record any *Failed* or *Needs Revision* case in `Appendix H`, apply code refinements, and re-test both the failed case and dependent workflows.

### 3.5 `Data Analysis Procedure`, `3.6 Validity`, and `3.7 Ethical Consideration`
- **Data Analysis:** Compare `Actual Result` against `Expected Result` per test case using `Passed`, `Failed`, and `Needs Revision`, summarizing conformance across all four roles and modules.
- **Validity:** Established via explicit forward and backward traceability linking every smoke (`ST-01`–`ST-12`) and functional (`FT-01`–`FT-46`) test case directly to `FR-01`–`FR-17` and verified through adviser review.
- **Ethical Consideration:** Zero external human survey respondents or personally identifiable records; strictly synthetic tenant/customer datasets; logical multi-tenant isolation (`print_shop_id`), password hashing/2FA/passkeys, and automated post-completion artwork purging (`FR-17`).

---

## 3. Software Development Methodology: 5-Phase Modified Waterfall Model (`Figure 2`)

PressQueue follows the **5-Phase Modified Waterfall Model** (`PressQueue_Modified_Waterfall_v2.png`) with controlled iterative feedback loops across **July 2026 – December 2026**:

1. **Phase 1: Requirements Analysis** — Define the 17 locked functional requirements (`FR-01`–`FR-17`), multi-tenant boundaries, panel edge cases (`R1`–`R5`), and verification criteria (`ST-01`–`ST-12`, `FT-01`–`FT-46`).
2. **Phase 2: System Design** — Produce the normalized 18-table multi-tenant ERD, 4 Modular Use Case Diagrams (`UC-1`–`UC-4`), Class Diagram, 17 Swimlane Activity Diagrams (`AD-01`–`AD-17`), Deployment Diagram (`v5`), and Editorial Light-Mode UI architecture.
3. **Phase 3: Implementation** — Build the Laravel 13 + Livewire 4 + Flux UI + Tailwind CSS v4 + PostgreSQL 18 / MySQL InnoDB multi-tenant platform covering all 4 modules.
4. **Phase 4: Testing and Refinement** — Execute developer-led Smoke Testing (`ST-01`–`ST-12`), Role-Based Functional Testing (`FT-01`–`FT-46`), targeted defect refinement (including UI/UX Light-Mode overhaul and universal navigation), and Regression Testing (`RT-____`).
5. **Phase 5: Deployment Planning and Readiness** — Document production VPS/database/queue/scheduler specifications (`Table 9`), Deployment Diagram (`Figure 3` / `v5`), and 7 operational fallback procedures.

---

## 4. Phase 4: Project Assessment and System Testing Plan (`Tables 1–5`)

### 4.1 `Table 1. Components to Be Tested in PressQueue Through Smoke Testing` (`ST-01`–`ST-12`)

| Smoke Test ID | Feature | Scope | Role / Module |
| :-: | :--- | :--- | :--- |
| **ST-01** | Public Marketplace & Workload Badges | Landing page and shop directory displaying *Normal*, *Busy*, *At capacity*, and *Paused* service indicators. | Customer Portal |
| **ST-02** | Onboarding, Auth, Profile & Logout | Registration routes, role-gated login redirects, profile/security views, and session logout across all four roles. | Authentication & Onboarding |
| **ST-03** | Multi-Tenant Isolation Guard | Blocking cross-tenant URL or record access across Tenant A, Tenant B, and Tenant C workspaces. | Tenant Governance |
| **ST-04** | Super Admin Governance & Retention | Pending tenant approval/moderation console and global post-completion artwork retention configuration. | Super Admin Console |
| **ST-05** | Shop Fleet, Availability, Catalog & BOM | Loading printer fleet, service pause toggles, service pricing limits, and paper/binding/ink BOM mappings. | Shop Administration |
| **ST-06** | Order Quote & Print Spec Checks | Reactive Full-Package/Cover-Only pricing, duplex/binding limit checks, and 50% DP vs 100% full choices. | Ordering & Billing |
| **ST-07** | Artwork Upload & Prepress Queue | Uploading valid PDF, PNG, or DOCX print files into staff prepress inspection queue. | Prepress Intake |
| **ST-08** | Multi-Printer Item Queue Board | Loading active order items filtered by assigned printer, staff operator, and shelf tag. | Job Scheduling |
| **ST-09** | Direct Browser Print Modal | Opening locked print streams via browser print preview without saving loose desktop downloads. | Browser Print Dispatch |
| **ST-10** | Inventory Ledger, Stock-In & ROP | Displaying on-hand stock, reserved stock, Stock-In entry view, and low-stock Reorder Point (ROP) badges. | BOM Inventory |
| **ST-11** | Standardized Report Center Preview | Rendering Inventory, Sales, and Live Production Schedule and Job Status reports cleanly. | Reporting Module |
| **ST-12** | Report Print & Export Actions | Triggering browser Print View, PDF download, and CSV spreadsheet export cleanly. | Reporting Module |

### 4.2 Role-Based Functional Testing Scope (`Tables 2–5` — `FT-01`–`FT-46`)
- **`Table 2` — Super Admin Role (`FT-01` to `FT-06`):** Tenant approval/rejection resubmission (`FT-01`), suspension/reactivation & workspace isolation (`FT-02`), global retention policy & automated cleanup (`FT-03`–`FT-05`), and platform storage health audit (`FT-06`).
- **`Table 3` — Printing Shop Owner Role (`FT-07` to `FT-22`):** Owner onboarding/profile (`FT-07`), thresholds & service pause/resume (`FT-08`), multi-printer fleet (`FT-09`), staff invite/revoke (`FT-10`), service catalog & BOM (`FT-11`), ROP & Stock-In (`FT-12`–`FT-13`), flexible payment audit, retry recovery & pre-print revisions/manual refunds (`FT-14`–`FT-16`), workload reassignment, pickup settlement & official receipt (`FT-17`–`FT-18`), 3 standardized reports (`FT-19`–`FT-21`), and analytics/retention override (`FT-22`).
- **`Table 4` — Production Staff Role (`FT-23` to `FT-35`):** Prepress approval/spec lock & rejection (`FT-23`–`FT-24`), Cover-Only sheet intake & shift (`FT-25`), multi-printer/staff assignment (`FT-26`), split release & shelf/bin tagging (`FT-27`), spec lock & printing freeze (`FT-28`), direct browser print dispatch (`FT-29`), atomic BOM deduction & concurrent row-lock contention (`FT-30`–`FT-31`), finishing/binding stage (`FT-32`), QA pass/shelf alert & QA fail dual paper/ink spoilage (`FT-33`–`FT-34`), and Maintenance failover (`FT-35`).
- **`Table 5` — Customer Role (`FT-36` to `FT-46`):** Registration/security/logout (`FT-36`), marketplace workload visibility (`FT-37`), dynamic quotation/duplex/binding limit validation (`FT-38`), multi-item & Cover-Only order configuration (`FT-39`), file upload validation (`FT-40`), flexible 50%/100% payment submission (`FT-41`), concurrent stock-shortage prompt (`FT-42`), two-step proof retry recovery (`FT-43`), pre-print spec revision request (`FT-44`), granular split order tracker (`FT-45`), and post-retention order history & official receipt (`FT-46`).

---

## 5. Locked 17 Functional Requirements & Objective Mapping (`Table 6`)

### 5.1 `Table 6. Mapping of Project Objectives, System Modules, and Functional Requirements`

| Objective | System Module | Functional Requirements |
| :--- | :--- | :--- |
| **Objective 1:** Develop a Job Scheduling and Production Queue Module showing live shop workload, independent item scheduling across multiple printers/staff, and direct browser print dispatch. | **Module 1:** Job Scheduling, Multi-Printer Queue & Direct Browser Print Dispatch | `FR-04`, `FR-07`, `FR-13`, `FR-14` |
| **Objective 2:** Build a Multi-Tenant Order, Prepress, and Billing Module with Cover-Only intake, flexible 50%/100% payment verification, prepress spec locks, and scheduled file cleanup. | **Module 2:** Multi-Tenant Access, Order Intake, Prepress & Flexible Billing | `FR-01`, `FR-02`, `FR-03`, `FR-05`, `FR-08`, `FR-09`, `FR-10`, `FR-11`, `FR-12` |
| **Objective 3:** Implement an Inventory and Operational Reporting Module with BOM deductions, concurrency locks, rework spoilage logging, ROP alerts, and printable/downloadable reports. | **Module 3:** BOM Inventory Control & Standardized Operational Reporting | `FR-06`, `FR-15`, `FR-16` |
| **Objective 4:** Verify functional conformance, multi-tenant data isolation, and operational readiness through developer-executed Smoke, Functional, and Regression testing. | **Module 4:** Automated Storage Lifecycle, Governance, Notifications & Verification | `FR-17` *(plus full verification of `FR-01`–`FR-17` across `ST-01`–`ST-12` & `FT-01`–`FT-46`)* |

### 5.2 Locked 17 Functional Requirements (`FR-01`–`FR-17` — Exact 1-to-1 Parity with `UC-1`–`UC-4` & `AD-01`–`AD-17`)

1. **`FR-01` Account Registration and Tenant Onboarding** — Customer self-registration, shop owner registration with guided tenant onboarding, and 48-hour tokenized staff invitations with validation guards.
2. **`FR-02` Authentication, Profile Security, and Session Logout** — Credential login with optional 2FA/passkeys, strict role-portal routing, profile/security management, and clean session termination.
3. **`FR-03` Multi-Tenant Approval and Workspace Isolation** — Super Admin shop approval, rejection with mandatory reasons for resubmission, suspend/reactivate moderation, global retention bounds, and strict per-shop tenant boundary isolation.
4. **`FR-04` Shop Operations, Printer Fleet, and Service Availability Configuration** — Shop profile, operating hours, live workload thresholds (*Normal*, *Busy*, *At capacity*), staff invitations/revocation, multi-printer fleet (*Active*, *Busy*, *Maintenance*, wide-format), and per-service capacity/pause controls.
5. **`FR-05` Dynamic Service Catalog and Bill of Materials Configuration** — Enabling/disabling modular shop service applications (*Install* / *Remove*) from the Owner App Launcher and More Services modal, dynamic pricing tiers (paper size/gsm, color, simplex/duplex, Full-Package vs. Cover-Only, rush fees, max binding sheets), and BOM mapping to paper, binding, and ink/toner consumables.
6. **`FR-06` Raw Material Inventory, Stock-In Replenishment, and Reorder Point Alerting** — Raw material stock tracking (`stock_qty`, `reserved_qty`, `available_qty`), supplier Stock-In logging, and static ROP low-stock/out-of-stock in-app and email alerts.
7. **`FR-07` Marketplace Shop Directory and Live Workload Display** — Public browsing/filtering of approved, setup-complete shops showing active/paused services and real-time workload badges (*Normal*, *Busy*, *At capacity*) without arbitrary turnaround guarantees.
8. **`FR-08` Dynamic Quotation, Print Validation, and Multi-Item Order Placement** — Reactive itemized pricing (duplex math, binding sheet limits, file format/size validation), multi-service cart placement under one parent order, short-stock fallbacks (lower copies, substitute paper, or shift to Cover-Only), and `pending_payment` order + tracking code persistence prior to payment upload.
9. **`FR-09` Upfront Payment Submission and Proof Retry Recovery** — Minimum 50% downpayment or 100% full upfront payment choice, sub-50% & per-shop duplicate reference rejection, and connection-drop proof retry recovery without order or stock duplication.
10. **`FR-10` Payment Proof Verification, Counter Settlement, and Official Receipt** — Owner payment audit queue with aging flags (`> 2h`), `Accepted` transition on verification, rejected proof resubmission, offline pre-order counter verification, remaining pickup balance settlement, and printable/PDF Official Receipt.
11. **`FR-11` Prepress Artwork Inspection, Cover-Only Intake, and Specification Lock** — Prepress file approval/rejection with customer re-upload, physical Cover-Only sheet intake confirmation, bidirectional **Cover-Only paper rejection → shift to Full-Package shop paper** with recalculated totals and BOM paper reservation, and automatic `is_spec_locked = true` on approval.
12. **`FR-12` Pre-Print Specification Revision and Manual Refund Management** — Formal pre-print revision hold releasing prior BOM stock and locking updated quantities (upgrades require `additional_due` top-up; downgrades reduce `balance_due` or record `Manual Refund Pending → Settled`), permanently frozen once printing begins.
13. **`FR-13` Multi-Printer Scheduling, Failover, and Split Order Tracking** — Independent item-level production tickets assigned to compatible active printers and staff, Maintenance batch failover reassignment, `Partially Ready` split early pickup with optional `shelf_bin_code` and `ready_at` aging tracking, and strict parent `Completed` gate (all items `Claimed` AND `balance_due <= 0`).
14. **`FR-14` Direct Browser Print Dispatch and Quality Inspection** — Locked inline browser print preview dispatch (`window.print()`, no loose desktop saves; RIP export for wide-format plotters), QA Pass with shelf tagging, and QA Fail logging decoupled paper + BOM ink/toner `rework_spoilage` without changing customer billing.
15. **`FR-15` Concurrent Stock Reservation and Atomic BOM Deduction** — Transactional row-level locking (`SELECT ... FOR UPDATE`) during concurrent checkouts preventing negative balances, Cover-Only paper bypass (with atomic shop-paper lock if shifted to Full-Package), and print-time conversion of reservations + spoilage into confirmed deductions with ROP re-evaluation.
16. **`FR-16` Standardized Printable and Exportable Operational Reports** — Filterable on-screen preview, Browser Print View, PDF download, and CSV export across: *(a)* Inventory & Stock Movement Report, *(b)* Sales & Revenue Report, and *(c)* Live Production Schedule & Job Status Report.
17. **`FR-17` Automated File Retention, Audit Trail, and Workflow Notifications** — Daily `pressqueue:purge-expired-files` cleanup deleting heavy artwork on completed orders past retention TTL while skipping active/on-hold/partially-ready orders and preserving receipts; hourly `pressqueue:check-aging-alerts` checks (`> 2h` unverified proofs, `> 3d` unclaimed pickups); immutable `audit_logs`; and in-app + email notifications.

---

## 6. Software, Hardware, and Phase 5 Deployment Specifications (`Tables 7–9`)

- **`Table 7. Development Software Requirement Specification`:** PHP 8.4/8.5, Laravel 13, Livewire 4, Flux UI, Tailwind CSS v4, PostgreSQL 18 / MySQL 8.0 InnoDB (SQLite `:memory:` for isolated automated tests), Laravel Fortify, DomPDF, Pest 5 / Larastan Level 7, Nginx + Supervisor + Cron.
- **`Table 8. Hardware Requirement Specification`:** Development workstation, Cloud VPS staging/production specs, Shop Floor Workstation (Chrome/Edge + USB/LAN OS printer drivers / RIP), and Customer desktop/mobile devices.
- **`Table 9. Proposed PressQueue Production Deployment Specification`:** Single-VPS Linux Ubuntu 24.04 LTS topology (`2 vCPU, 4 GB RAM, 80 GB NVMe SSD`), TLS 1.3 Nginx reverse proxy, row-lock-capable relational DB with nightly encrypted backups, Supervisor queue worker + 1-minute scheduler cron, private tenant upload storage with TTL cleanup, and workstation browser print/RIP output.
- **7 Operational Fallback Procedures:** (1) Upfront Payment Connectivity Drop & Pre-Order Transfer, (2) Concurrent Stock Contention, (3) QA Failure & Dual Paper/Ink Spoilage, (4) Pre-Print Spec Revision & Manual Refund, (5) Printer Breakdown & Wide-Format RIP, (6) Split Early Pickup & Rack Management, (7) Accidental Purge Prevention & Offline Report Continuity.

---

## 7. Pure Academic Diagram Specifications (`outline_example_1.pdf` Standard)

> [!IMPORTANT]
> **Pure Diagram Visual Standard (Mandatory for `CR-3`):** Modeled strictly on `capstone_paper/outline_paper/scratch/outline_example_1.pdf`, every diagram PNG must contain **ONLY** the formal UML / ERD / Swimlane / Gantt notation on a clean white background. **Zero** slide-deck phase banners (`PHASE 2 SYSTEM DESIGN • ...`), subtitles, top-right pill badges, bottom meta-commentary callouts (`Adviser ERD Audit`, `Table 6 parity`, `Iterative Refinement Feedback Loops`), or AI-internal developer jargon (`WP-1`–`WP-5`, `PHPStan`).

1. **`Figure 1` — Conceptual Framework (`PressQueue_Conceptual_Framework_v2.png`):** 4-stage **IPOO** model (already clean — keep untouched).
2. **`Figure 2` — Modified Waterfall Model (`PressQueue_Modified_Waterfall_v2.png`):** 5-phase Modified Waterfall with feedback loops (already clean — keep untouched).
3. **`Figure 3` — Deployment Diagram (`PressQueue_Deployment_Diagram_v5.png` — NEW appended version):** Clean UML 2.5 single-VPS topology derived from `v4` with the bottom-left `Scope Note / Fallback Summary` box removed, leaving only pure deployment nodes and the minimal 2-line arrow legend.
4. **Entity-Relationship Diagram (`PressQueue_ERD_v2.png`):** Pure 18-table Crow's Foot ERD matching live Laravel migrations (`print_shops`, `users`, `platform_settings`, `staff_invitations`, `audit_logs`, `notifications`, `printers`, `shop_services`, `document_printing_configs`, `thesis_binding_configs`, `inventory_items`, `service_boms`, `thesis_binding_bom_items`, `stock_movements`, `orders`, `order_items`, `payments`, `spec_revisions`) with dark-slate entity headers and orthogonal connectors (zero callout boxes or banners).
5. **Four Modular Use Case Diagrams (`use_cases/PressQueue_UC-1`..`UC-4`):** Pure stick figures + single boundary rectangle titled inside at top + white ovals matching `FR-01`–`FR-17` verbatim:
   - **`UC-1` Account Access, Tenant Governance, and Shop Configuration:** `FR-01`, `FR-02`, `FR-03`, `FR-04`, `FR-05`
   - **`UC-2` Marketplace Ordering, Payment, and Settlement:** `FR-07`, `FR-08`, `FR-09`, `FR-10`
   - **`UC-3` Prepress, Scheduling, Direct Print, and Quality Control:** `FR-11`, `FR-12`, `FR-13`, `FR-14`
   - **`UC-4` Inventory Control, Operational Reporting, and File Lifecycle:** `FR-06`, `FR-15`, `FR-16`, `FR-17`
6. **Class Diagram (`PressQueue_Class_Diagram_v2.png`):** Pure UML 2.5 class model with `User` superclass at top (open-triangle generalization pointing UPWARD from `PlatformAdmin`, `BusinessOwner`, `ProductionStaff`, `Customer`) alongside `PrintShop`, `Printer`, `ShopService`, `InventoryItem`, `StockMovement`, `AuditLog`, `Order`, `OrderItem`, `Payment`, `SpecRevision`, and core services.
7. **Seventeen Swimlane Activity Diagrams (`activity_diagrams/PressQueue_AD-01`..`AD-17`):** Exact 1-to-1 title parity with `FR-01`–`FR-17`, single clean framed title bar matching `outline_example_1.pdf` Figure 25, monochrome white action boxes/diamonds, clean orthogonal routing, and zero label/border collisions.

---

## 8. Curated Light-Mode User Interface Screenshots (`14` Figures — Post-UI Overhaul `CR-4`)

All UI figures are captured in **native Pressroom Slate & Cobalt Light Mode** (`Instrument Sans`, crisp `#f1f5f9` slate canvas, white surfaces, `#2563eb` cobalt primary CTAs, geometric `PressQueue` logo mark, WCAG-AA high-contrast semantic badges, zero gradients or ambient radial glow blobs, zero UI emojis, consistent universal contextual back navigation) in **4 reviewed batches** after `CR-1` and `CR-2` complete:

1. `01_landing_page.png` — Public Platform Landing Page and Illustrative Order Ticket Preview (`FR-01`, `FR-07`)
2. `02_auth_login.png` — Unified Sign-In and Role Entry (`FR-01`, `FR-02`)
3. `03_super_admin_governance.png` — Super Admin Tenant Governance and Global File Retention Console (`FR-03`, `FR-17`)
4. `04_marketplace_directory.png` — Customer Marketplace Shop Directory with Live Workload Badges and Paused Service Notice (`FR-07`)
5. `05_customer_multi_item_checkout.png` — Customer Multi-Item Cart, Dynamic Quotation, and 50%/100% Payment Selection (`FR-08`, `FR-09`)
6. `06_customer_order_tracking.png` — Customer Split Item-Level Order Tracker, Shelf Tag, and Proof Retry/Receipt Access (`FR-09`, `FR-12`, `FR-13`)
7. `07_owner_app_launcher.png` — Printing Shop Owner App Launcher (`FR-05`)
8. `08_owner_more_services_modal.png` — Printing Shop Owner More Services App Store Modal (`FR-05`)
9. `09_owner_operations_and_fleet.png` — Shop Operations, Multi-Printer Fleet Failover, and Service Availability Controls (`FR-04`)
10. `10_owner_inventory_bom.png` — Raw Material Inventory Hub, BOM Recipes, and Low-Stock ROP Alert (`FR-06`, `FR-15`)
11. `11_owner_payment_audit_refunds.png` — Owner Payment Proof Audit (Aging Flags), Counter Settlement, and Manual Refund Ledger (`FR-10`, `FR-12`)
12. `12_owner_standardized_reports.png` — Standardized Operational Report Center with Print View, PDF, and CSV Controls (`FR-16`)
13. `13_staff_production_queue.png` — Production Staff Multi-Printer Queue, Prepress Lock, and Cover-Only Intake (`FR-11`, `FR-13`, `FR-14`)
14. `14_staff_direct_browser_print.png` — Direct Browser Print Dispatch Preview Modal (`FR-14`)

### Ready-to-Insert Introductory Paragraph for `01_landing_page.png`
> The **PressQueue Landing Page** serves as the public entry point of the platform, introducing customers and independent printing shop owners to the multi-tenant workflow before requiring account registration. Alongside direct navigation to the live marketplace directory and shop onboarding plans, the hero section presents an illustrative order ticket detailing itemized specifications, verified payment status, and stage-by-stage production tracking. Visitors can inspect available services—including document printing and thesis binding—and proceed directly into shop discovery or role-based sign-in.

### Ready-to-Insert Introductory Paragraph for `08_owner_more_services_modal.png`
> To accommodate the varied equipment capabilities of independent local print shops without cluttering each tenant's workspace, PressQueue includes a **More Services App Store** modal accessible directly from the Printing Shop Owner App Launcher. Through this interface, shop owners can selectively install or remove optional printing modules—such as Thesis Binding, Document Printing, and additional commercial services—while retaining essential core operational hubs (Analytics, Inventory, and Production Queue) as pre-installed defaults. Activating or removing a module dynamically updates both the owner's configuration tools and the shop's public service catalog on the customer marketplace.

---

## 9. Project Cost Estimate (`Table 10`) & Modified Waterfall Schedule (`Gantt Chart`)

### 9.1 `Table 10. Project Cost Estimate` *(Panel Mandate `B3`: Removed "Token of appreciation")*

| Items | Estimated Cost (PHP) |
| :--- | --: |
| Cloud VPS Staging Hosting (4 Months, 2 vCPU / 4 GB RAM) & Domain (`.shop`/`.com`) | 3,350.00 |
| Internet Broadband Connectivity Allowance (4 Months Development & Verification) | 4,000.00 |
| Capstone Outline & Final Manuscript Printing, Binding, and Reproduction | 3,500.00 |
| Development and Testing Software Stack (Open-Source Frameworks, Browsers, and Database Tools) | 0.00 |
| **Total** | **10,850.00** |

### 9.2 Modified Waterfall Project Gantt Chart Specification (**July 2026 – December 2026**, Matching `outline_capstone_v1_FinalDraft`)

Modeled after `outline_example_1.pdf` Figure 36 using clean monthly columns (`Jul 2026` – `Dec 2026`) and phase milestone diamonds (zero internal developer/AI codes like `WP-1..WP-5` or `PHPStan`):

| Modified Waterfall Phase & Academic Activity | Jul 2026 | Aug 2026 | Sep 2026 | Oct 2026 | Nov 2026 | Dec 2026 |
| :--- | :-: | :-: | :-: | :-: | :-: | :-: |
| **1. Requirements Analysis** *(Domain Workflow Audit, Multi-Tenant & Scheduling Rules, Finalize SRS `FR-01`–`FR-17`)* | ███ | █ | | | | |
| **2. System and UI/UX Design** *(Architecture, Normalized ERD, Use Case/Class/Activity Diagrams, Light-Mode UI Design)* | | ███ | ██ | | | |
| **3. Implementation** *(Tenant Governance, Multi-Item Ordering & 50%/100% Billing, Multi-Printer Queue, Direct Print, BOM Inventory & Reports)* | | | ██ | ███ | ██ | |
| **4. Testing and Refinement** *(Smoke Testing `ST-01`–`ST-12`, Functional Testing `FT-01`–`FT-46`, UI/UX Refinement & Regression Testing)* | | | | | ███ | ██ |
| **5. Deployment Planning and Readiness** *(Production Topology & Fallback Documentation, Final Verification & Manuscript Completion)* | | | | | | ███ |

---

## 10. Verification Appendices Index (`Appendices F–I` — Synced to `outline_capstone_v2.docx`)

*(Note: `Appendices A–E` hold official USM institutional forms and `Appendix J` holds the Curriculum Vitae.)*

### `Appendix F. Test Cases for Smoke Testing` (`ST-01` to `ST-12`)
- **ST-01:** Public Landing Page & Marketplace Workload Badges
- **ST-02:** Registration, Role Auth, Profile & Session Logout
- **ST-03:** Multi-Tenant Workspace Isolation Guard
- **ST-04:** Super Admin Governance & File Retention Console
- **ST-05:** Shop Fleet, Availability, Catalog & BOM Setup
- **ST-06:** Customer Order Form, Spec Checks & Quote Calculator
- **ST-07:** Artwork File Upload & Prepress Queue Display
- **ST-08:** Multi-Printer & Staff Production Queue Board
- **ST-09:** Direct Browser Print Dispatch Modal
- **ST-10:** Warehouse Inventory Ledger, Stock-In & ROP Badge
- **ST-11:** Standardized Report Center Preview
- **ST-12:** Printable & Downloadable Report Export Actions

### `Appendix G. Test Cases for Functional Testing` (`FT-01` to `FT-46`)

- **Super Admin Role (`FT-01`–`FT-06`):**
  - `FT-01` Tenant Shop Registration Approval & Rejection Resubmission (`FR-03`)
  - `FT-02` Tenant Suspension, Reactivation & Workspace Isolation (`FR-03`)
  - `FT-03` Global File Retention Policy Configuration (`FR-03`, `FR-17`)
  - `FT-04` Automated File Retention Cleanup Execution (`FR-17`)
  - `FT-05` Active Order File Protection Guard (`FR-17`)
  - `FT-06` Platform Storage & Tenant Health Audit (`FR-03`, `FR-17`)
- **Printing Shop Owner Role (`FT-07`–`FT-22`):**
  - `FT-07` Shop Owner Registration, Onboarding & Profile/Security Update (`FR-01`, `FR-02`)
  - `FT-08` Shop Thresholds & Service Availability Pause/Resume Control (`FR-04`)
  - `FT-09` Multi-Printer Fleet Registration & Status Management (`FR-04`)
  - `FT-10` Tenant Production Staff Invitation, Onboarding & Revocation (`FR-01`, `FR-04`)
  - `FT-11` Service Catalog, Binding Sheet Limits & Paper/Ink BOM Recipe Setup (`FR-05`)
  - `FT-12` Reorder Point (ROP) Low-Stock Alert Trigger (`FR-06`, `FR-17`)
  - `FT-13` Stock-In Delivery Replenishment & ROP Alert Clear (`FR-06`)
  - `FT-14` Flexible Upfront Payment Verification & Order Acceptance (`FR-10`)
  - `FT-15` Retried Proof Verification, Duplicate Guard & Offline Pre-Order Audit (`FR-09`, `FR-10`)
  - `FT-16` Pre-Print Spec Revision Approval — Upgrade Top-Up & Downgrade Manual Refund Ledger (`FR-12`, `FR-17`)
  - `FT-17` Manual Workload Reassignment Across Printers & Staff (`FR-13`)
  - `FT-18` Pickup Balance Settlement, Child Claim Gate & Official Receipt (`FR-10`, `FR-13`)
  - `FT-19` Standardized Inventory & Stock Movement Report (`FR-16`)
  - `FT-20` Standardized Sales & Revenue Report (`FR-16`)
  - `FT-21` Standardized Live Schedule & Job Status Report (`FR-16`)
  - `FT-22` Shop Operational Analytics & Retention Override (`FR-16`, `FR-17`)
- **Production Staff Role (`FT-23`–`FT-35`):**
  - `FT-23` Prepress Artwork Approval & Spec Lock Activation (`FR-11`)
  - `FT-24` Prepress Artwork Rejection & Customer Re-Upload Notification (`FR-11`, `FR-17`)
  - `FT-25` Cover-Only Sheet Intake Check & Short-Stock Shift Option (`FR-11`, `FR-15`)
  - `FT-26` Multi-Printer & Multi-Staff Queue Assignment (`FR-13`)
  - `FT-27` Split Item-Level Release, Shelf/Bin Tagging & Parent Hold (`FR-13`)
  - `FT-28` Pre-Print Spec Lock & Physical Printing Stage Freeze (`FR-11`, `FR-12`)
  - `FT-29` Direct Browser Print Dispatch Without Loose Downloads (`FR-14`)
  - `FT-30` Atomic BOM Stock Deduction Upon Print Execution (`FR-14`, `FR-15`)
  - `FT-31` Concurrent Stock Reservation Lock Under Contention (`FR-15`)
  - `FT-32` Post-Press Finishing & Binding Stage Transition (`FR-13`)
  - `FT-33` QA Inspection Pass, Optional Shelf Tag & Early Pickup Alert (`FR-13`, `FR-14`, `FR-17`)
  - `FT-34` QA Failure & Decoupled Paper/Ink Spoilage Logging (`FR-14`, `FR-15`)
  - `FT-35` Printer Maintenance Breakdown Failover Re-Queueing (`FR-04`, `FR-13`)
- **Customer Role (`FT-36`–`FT-46`):**
  - `FT-36` Customer Registration, Profile Security & Session Logout (`FR-01`, `FR-02`)
  - `FT-37` Marketplace Shop Workload & Service Availability Visibility (`FR-07`)
  - `FT-38` Dynamic Quotation, Duplex Sheet Math & Binding Limit Validation (`FR-08`)
  - `FT-39` Multi-Item & Cover-Only Order Configuration (`FR-08`)
  - `FT-40` Print File Upload Validation Guard (`FR-08`)
  - `FT-41` Flexible Upfront Payment Option Submission (`FR-09`)
  - `FT-42` Concurrent Checkout Stock Shortage Prompt (`FR-08`, `FR-15`)
  - `FT-43` Two-Step Pending-Payment Proof Retry Recovery (`FR-09`)
  - `FT-44` Post-Approval Pre-Print Specification Revision Request (`FR-12`)
  - `FT-45` Granular Item-Level Order Tracker & Early Pickup View (`FR-13`)
  - `FT-46` Post-Retention Order History & Official Receipt Access (`FR-10`, `FR-17`)

### `Appendix H. Regression Test Records` (`RT-____`, `RT-01`, `RT-02`) & `Appendix I. Task Allocation Matrix (TAM)`
- Structured ISO/IEC/IEEE 29119 regression record templates tracking defect cause, modified component, re-tested primary case, adjacent dependent cases, and sign-off status, followed by the project developer Task Allocation Matrix (`Appendix I`).
