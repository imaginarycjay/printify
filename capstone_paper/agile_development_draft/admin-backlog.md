# Business Owner & Administrator Product Backlog

**Target Role:** Business Owner / Shop Administrator (`role: business_owner`)  
**Document Version:** 1.0 (Agile Draft)  
**Parent Document:** [lean-prd.md](file:///home/imaginarycjay/programming/capstone_system/capstone_paper/agile_development_draft/lean-prd.md)

---

## Overview
This backlog details all Epics, User Stories, and Acceptance Criteria for the Business Owner / Administrator persona. The owner is responsible for dynamic service configuration, pricing management, manual GCash payment verification, inventory maintenance, burn rate oversight, and production capacity controls.

---

## Epic 1: Dynamic Service & Pricing Configuration (`EPIC-ADM-1`)

### Story ADM-1.1: Multi-Tiered Base & Page Pricing Setup
- **User Story:** *As a Business Owner, I want to set and adjust base prices for hardbound/softbound binding and per-page rates for monochrome and color printing, so that I can update prices according to paper market fluctuations without touching the codebase.*
- **Priority:** `HIGH` | **Points:** 5 | **Status:** `IMPLEMENTED`
- **Acceptance Criteria:**
  - [x] Admin can edit `hardbound_base_price`, `softbound_base_price`, `page_price_bw`, `page_price_color`, and `rush_fee`.
  - [x] Live Price Estimator updates in real-time on the configuration screen.
  - [x] Changes persist into `thesis_binding_configs` table.

### Story ADM-1.2: Variant Management (Colors & Dimensions)
- **User Story:** *As a Business Owner, I want to add, remove, and manage available cover colors, hot foil stamping colors, and paper sizes, so that the customer form only shows options currently in stock.*
- **Priority:** `HIGH` | **Points:** 3 | **Status:** `IMPLEMENTED`
- **Acceptance Criteria:**
  - [x] Admin can dynamically add/remove cover color tags (e.g. Maroon, Dark Blue, Emerald Green, Black).
  - [x] Admin can dynamically add/remove foil color tags (e.g. Gold, Silver).
  - [x] Admin can select allowable paper sizes (`A4`, `Letter`, `Legal`).
  - [x] Changes are stored as JSON arrays in database and immediately reflect on the customer ordering UI.

### Story ADM-1.3: Dynamic Custom Cover Fields Definition
- **User Story:** *As a Business Owner, I want to define dynamic metadata fields (e.g., Thesis Title, Authors, Degree, School Year) required for foil stamping, so that customers provide all necessary details during online submission.*
- **Priority:** `MEDIUM` | **Points:** 3 | **Status:** `IMPLEMENTED`
- **Acceptance Criteria:**
  - [x] Admin can add custom field labels to the list.
  - [x] Admin can delete existing custom field labels.
  - [x] Customer ordering form renders dynamic input boxes corresponding to these fields.

### Story ADM-1.4: Production Limits & Turnaround Lead Times
- **User Story:** *As a Business Owner, I want to establish daily maximum book quotas and lead times for standard and rush orders, so that my shop does not get overloaded beyond its physical machinery capacity.*
- **Priority:** `HIGH` | **Points:** 5 | **Status:** `IMPLEMENTED (Config) / IN PROGRESS (Slot Booking)`
- **Acceptance Criteria:**
  - [x] Admin sets `daily_production_quota` (e.g., 20 books/day).
  - [x] Admin sets `standard_lead_time_days` (e.g., 4 days) and `rush_lead_time_days` (e.g., 1 day).
  - [ ] System automatically calculates earliest completion date on the customer checkout calendar.

### Story ADM-1.5: Pre-Printed Paper & Cover-Only Binding Configuration
- **User Story:** *As a Business Owner, I want to enable or disable accepting customer-supplied pre-printed pages ("Dala ang Papel") and set a dedicated base price for Cover-Only hardbound binding, so that students can avail of cheaper binding services while maintaining shop profit margins.*
- **Priority:** `HIGH` | **Points:** 5 | **Status:** `IMPLEMENTED`
- **Acceptance Criteria:**
  - [x] Admin can toggle `allow_customer_supplied_paper` on/off in the Pricing configuration tab.
  - [x] Admin can set the `hardbound_cover_only_price` (e.g., ₱300.00 base).
  - [x] Admin Live Price Simulator includes a toggle for `Full Package` vs `Cover Only` to preview customer checkout pricing in real time.


---

## Epic 2: Manual Payment Verification & Order Intake (`EPIC-ADM-2`)

### Story ADM-2.1: Payment Verification Dashboard Queue
- **User Story:** *As a Business Owner, I want to view a dedicated queue of pending customer orders with uploaded payment receipts, so that I can verify transactions before sending jobs to production.*
- **Priority:** `CRITICAL` | **Points:** 8 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Admin dashboard displays a badge count of pending payment submissions (`payment_status = pending_verification`).
  - [ ] Clicking a queue item opens a modal displaying the customer details, order summary, total amount, customer-provided reference number, and an interactive zoomable image preview of the uploaded GCash receipt.

### Story ADM-2.2: Payment Approval & Production Dispatch
- **User Story:** *As a Business Owner, I want to approve verified GCash receipts with one click, so that the order is instantly marked as paid and routed to the production staff job queue.*
- **Priority:** `CRITICAL` | **Points:** 5 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Admin clicks "Approve Payment" and optionally confirms the verified transaction reference.
  - [ ] System updates `orders.payment_status` to `verified_paid` and `orders.order_status` to `in_queue`.
  - [ ] System sets `orders.payment_verified_at` and records the admin `user_id`.
  - [ ] Customer order tracker immediately updates to show "Payment Verified & Queued for Production".

### Story ADM-2.3: Payment Rejection with Feedback Notes
- **User Story:** *As a Business Owner, I want to reject invalid, blurry, or fake receipts with an explanatory note, so that the customer is notified to re-upload a valid proof of payment.*
- **Priority:** `HIGH` | **Points:** 3 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Admin enters a reason (e.g., "Reference number not found in GCash app" or "Blurry screenshot").
  - [ ] Order status updates to `rejected` with `rejection_reason`.
  - [ ] Customer receives alert on their dashboard to re-upload proof of payment.

---

## Epic 3: Inventory Management & Bill of Materials (BOM) (`EPIC-ADM-3`)

### Story ADM-3.1: Raw Material Inventory CRUD
- **User Story:** *As a Business Owner, I want to add, edit, and manage raw inventory materials with SKU, category, stock count, unit, unit cost, and safety threshold, so that the system accurately mirrors physical shop supplies.*
- **Priority:** `HIGH` | **Points:** 5 | **Status:** `IMPLEMENTED`
- **Acceptance Criteria:**
  - [x] Admin can add new inventory item via Quick-Add Modal.
  - [x] Fields supported: `name`, `sku`, `category`, `stock_qty`, `unit`, `reorder_level`.
  - [x] Items stored in `inventory_items` table.

### Story ADM-3.2: Bill of Materials (BOM) Recipe Linkage
- **User Story:** *As a Business Owner, I want to define material recipes for Hardbound and Softbound options, so that the system knows exactly how much paper, chipboard, leatherette, and glue is used per copy.*
- **Priority:** `CRITICAL` | **Points:** 5 | **Status:** `IMPLEMENTED`
- **Acceptance Criteria:**
  - [x] Admin links an inventory item to a binding type (`hardbound` / `softbound`).
  - [x] Admin specifies usage quantity per book (e.g., 1 pc Chipboard, 1 sheet Leatherette).
  - [x] Stored in `thesis_binding_bom_items` table.

---

## Epic 4: Real-Time Burn Rate Engine & Restock Alerts (`EPIC-ADM-4`)

### Story ADM-4.1: Short-Term Consumption Velocity (Burn Rate) Calculation
- **User Story:** *As a Business Owner, I want the system to compute the daily burn rate of each material over an active 7-day window, so that I can see how fast materials are depleting during peak season.*
- **Priority:** `HIGH` | **Points:** 8 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] System calculates $\text{Daily Burn Rate} = \frac{\text{Total Units Consumed in Past 7 Days}}{7}$.
  - [ ] System computes $\text{Days of Stock Remaining} = \frac{\text{Current Stock}}{\text{Daily Burn Rate}}$.
  - [ ] Metric displays as a visual speedometer / progress bar on the Inventory tab.

### Story ADM-4.2: Critical Reorder Threshold Banner & Push Alert
- **User Story:** *As a Business Owner, I want to receive proactive visual warning banners when stock drops below the reorder point or when days remaining fall below supplier lead time, so that we never run out of supplies mid-production.*
- **Priority:** `HIGH` | **Points:** 5 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] If `stock_qty <= reorder_level`, item is flagged in Amber/Red on the Admin Dashboard.
  - [ ] Prominent notification banner appears on the main Owner Hub with item names and current shortages.

### Story ADM-4.3: 1-Click Restock Purchase Order (PO) Summary
- **User Story:** *As a Business Owner, I want to generate a 1-click Restock Purchase Order summary for all low-stock items, so that I can copy or export it to send to my paper/leatherette suppliers.*
- **Priority:** `MEDIUM` | **Points:** 3 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] "Generate Restock Order" button compiles all items where `stock_qty <= reorder_level`.
  - [ ] Displays recommended order quantity (e.g., target safety stock minus current stock).
  - [ ] Provides 1-click "Copy to Clipboard" formatted text for Messenger / Email communication with suppliers.

---

## Epic 5: Business Intelligence & Profit Margin Analytics (`EPIC-ADM-5`)

### Story ADM-5.1: Central Sales & Cashflow Executive Analytics Hub
- **User Story:** *As a Business Owner, I want a dedicated central analytics hub in my App Launcher, so that I can monitor overall shop revenue, cashflow, and transaction volumes in real time.*
- **Priority:** `HIGH` | **Points:** 5 | **Status:** `IN PROGRESS`
- **Acceptance Criteria:**
  - [ ] App Launcher includes pre-installed "Sales & Financial Analytics" core application.
  - [ ] Dashboard aggregates total gross sales from paid/verified customer orders.
  - [ ] Live stats pill on main dashboard displays today's revenue.

### Story ADM-5.2: Multi-Timeframe Cashflow & Order Trendline Graphs
- **User Story:** *As a Business Owner, I want interactive revenue and order timeline charts with period filters (Today, 7D, 30D, YTD, All Time), so that I can visualize peak production days and sales trends.*
- **Priority:** `MEDIUM` | **Points:** 5 | **Status:** `IN PROGRESS`
- **Acceptance Criteria:**
  - [ ] Filter controls dynamically re-render SVG revenue timeline and order volume charts.
  - [ ] Tooltip points display daily revenue figures and order counts.

### Story ADM-5.3: Automated BOM Material Cost & Net Profit Margin Analysis
- **User Story:** *As a Business Owner, I want to see estimated raw material expenses and recorded spoilage losses deducted from gross sales, so that I know my true net profit and margin percentage.*
- **Priority:** `HIGH` | **Points:** 8 | **Status:** `IN PROGRESS`
- **Acceptance Criteria:**
  - [ ] Computes exact consumed BOM raw material cost based on inventory unit prices.
  - [ ] Deducts recorded material spoilage/wastage losses from gross profit.
  - [ ] Displays Net Profit (₱) and Profit Margin % prominently.

### Story ADM-5.4: Service Product Mix & Fulfillment Distribution Analytics
- **User Story:** *As a Business Owner, I want to see breakdowns of Full Package vs Cover-Only orders, Hardbound vs Softbound, and leatherette cover color popularity, so that I can optimize inventory purchasing.*
- **Priority:** `MEDIUM` | **Points:** 5 | **Status:** `IN PROGRESS`
- **Acceptance Criteria:**
  - [ ] Visual progress distribution for Full Package vs Cover-Only ("Dala ang Papel").
  - [ ] Ratio breakdown for Hardbound vs Softbound thesis options.
  - [ ] Cover color leaderboard ranking most requested colors.

### Story ADM-5.5: 1-Click Financial Transaction Ledger & CSV Export
- **User Story:** *As a Business Owner, I want a complete transaction ledger table with 1-click CSV download, so that I can export sales records for accounting and external bookkeeping.*
- **Priority:** `MEDIUM` | **Points:** 3 | **Status:** `IN PROGRESS`
- **Acceptance Criteria:**
  - [ ] Searchable, filtered transaction ledger table.
  - [ ] 1-Click "Download CSV" generates a standard formatted financial spreadsheet.

