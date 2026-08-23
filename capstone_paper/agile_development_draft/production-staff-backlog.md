# Production Staff Product Backlog

**Target Role:** Production Staff / Machine Operators (`role: production_staff`)  
**Document Version:** 1.0 (Agile Draft)  
**Parent Document:** [lean-prd.md](file:///home/imaginarycjay/programming/capstone_system/capstone_paper/agile_development_draft/lean-prd.md)

---

## Overview
This backlog details all Epics, User Stories, and Acceptance Criteria for the Production Staff persona. Staff members use the digital hub to replace paper job tickets, process incoming printing and thesis binding tasks, execute stage transitions, inspect customer specifications and PDF assets, and trigger automated material deductions upon task fulfillment.

---

## Epic 1: Visual Digital Production Queue (`EPIC-STF-1`)

### Story STF-1.1: Interactive Production Queue & Kanban Board
- **User Story:** *As Production Staff, I want to see an organized digital queue of all confirmed print jobs categorized by production stage, so that our team can easily see what to work on next without physical paper tickets.*
- **Priority:** `CRITICAL` | **Points:** 8 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Staff dashboard renders active jobs in organized columns / list:
    - `Queued / Ready to Print`
    - `Printing in Progress`
    - `Binding & Foil Stamping`
    - `Quality Inspection (QC)`
    - `Ready for Customer Pickup`
  - [ ] Rush orders are prominently highlighted with glowing amber/red priority badges and countdown timers.
  - [ ] Jobs display target due dates and customer names.

### Story STF-1.2: Queue Filtering & Sorting Controls
- **User Story:** *As Production Staff, I want to filter the production queue by service type (Thesis Binding vs Document Printing) and sort by urgency/due date, so that we can prioritize approaching deadlines effectively.*
- **Priority:** `MEDIUM` | **Points:** 3 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Filter by service type (`thesis_binding`, `document_printing`, etc.).
  - [ ] Quick-filter button for `Rush Orders Only`.
  - [ ] Sort by earliest fulfillment deadline.

### Story STF-1.3: Physical Paper Drop-off Intake Confirmation ("Dala ang Papel")
- **User Story:** *As Production Staff, when a customer drops off their pre-printed and collated manuscript pages at our counter, I want to confirm receipt with a single click on their job card, so that our team knows the physical paper is in-shop and ready for cover assembly.*
- **Priority:** `CRITICAL` | **Points:** 5 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Jobs marked as `fulfillment_type = cover_only` prominently display a `Paper Drop-off Needed` intake alert on the queue card.
  - [ ] 1-Click "Mark Physical Paper Received" action updates `order_items.is_paper_received = true`.
  - [ ] Card updates to `Paper Received at Shop` and enables progression to binding and stamping stages.
  - [ ] Customer dashboard timeline reactively updates to show paper is in-shop.

---

## Epic 2: Job Specification & Asset Inspection (`EPIC-STF-2`)

### Story STF-2.1: Digital Job Ticket Modal & Details View
- **User Story:** *As Production Staff, I want to open a comprehensive job ticket showing exact page counts (B/W vs Color), paper size, cover color, foil stamping text, spine thickness in millimeters, and special notes, so that we produce the item with zero defect.*
- **Priority:** `CRITICAL` | **Points:** 5 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Modal displays full customer specifications:
    - Number of Copies, Binding Type (Hardbound vs Softbound), Fulfillment Mode (Full Package vs Cover-Only).
    - Page Breakdown: Exact B/W and Color page count.
    - Dynamic Spine Width: Exact calculated spine thickness in mm for chipboard cutting and creasing.
    - Cover Details: Selected leatherette color, foil color, and filled custom metadata (Thesis Title, Authors, Course, School Year) with 1-click copy helpers.
  - [ ] Formatted view for quick reference during machine setup.

### Story STF-2.2: 1-Click Document PDF Download & Preview
- **User Story:** *As Production Staff, I want to download or preview the customer's uploaded PDF file directly from the job ticket, so that I can send it to the designated laser printer without searching through shared folders.*
- **Priority:** `CRITICAL` | **Points:** 3 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] "Download Print PDF" button securely retrieves the file from private storage.
  - [ ] Embedded browser preview option for fast visual verification before sending to print.

---

## Epic 3: Stage Progression & Machine Logging (`EPIC-STF-3`)

### Story STF-3.1: 1-Click Stage Advancement
- **User Story:** *As Production Staff, I want to advance a job's stage with a single click (e.g. from Printing to Binding, or Binding to Ready), so that the system immediately reflects progress to the customer and management.*
- **Priority:** `CRITICAL` | **Points:** 5 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] 1-click status update action button on each job card.
  - [ ] Customer tracking timeline updates reactively in real-time.
  - [ ] Timestamps (`started_at`, `completed_at`) are logged in `orders` / `job_tasks` table.

### Story STF-3.2: Machine & Workstation Assignment Logging
- **User Story:** *As Production Staff, I want to record which printing machine or binding press was used for a job, so that management has an equipment audit trail in case of paper jams or maintenance issues.*
- **Priority:** `LOW` | **Points:** 3 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Optional dropdown to select machine (e.g., `Laser Printer A`, `Laser Printer B`, `Heavy Foil Press 1`).
  - [ ] Machine name saved to `orders.assigned_machine`.

---

## Epic 4: Automated Material Consumption Deduction (`EPIC-STF-4`)

### Story STF-4.1: Automated Inventory Stock Deduction Trigger
- **User Story:** *As Production Staff, when I complete a job order, I want the system to automatically deduct the consumed raw materials from inventory based on the Bill of Materials (BOM), so that stock counts remain accurate without manual logging.*
- **Priority:** `CRITICAL` | **Points:** 8 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] Marking a job as completed or in-production triggers `InventoryDeductionService`.
  - [ ] For Full Package orders: Deducts paper sheets, chipboards, leatherette covers, foil rolls, and glue units according to `thesis_binding_bom_items`.
  - [ ] For Cover-Only orders: Deducts chipboards, leatherette covers, foil rolls, and glue units, but **0 sheets of paper**.
  - [ ] Creates records in `stock_movements` table with `movement_type = production_deduction`.
  - [ ] If deduction causes stock to drop below `reorder_level`, system flags the item for immediate admin replenishment alerts.

### Story STF-4.2: Spoilage & Waste Material Reporting
- **User Story:** *As Production Staff, I want to report spoiled or wasted materials (e.g., misprinted cover, jammed paper) during production, so that physical inventory counts remain synchronized with the digital database.*
- **Priority:** `MEDIUM` | **Points:** 3 | **Status:** `PLANNED`
- **Acceptance Criteria:**
  - [ ] "Report Material Spoilage" modal allows staff to input wasted quantity and reason (e.g., "Paper jam on page 42", "Misaligned foil stamp").
  - [ ] Deducts stock with `movement_type = adjustment_spoilage` and logs staff `user_id`.

