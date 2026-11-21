# Comprehensive ERD Architectural Guide & Defense Handbook
**Project Title:** Integrated Dynamic Order, Job Scheduling, and Inventory Management System for Printing Services with Automated Replenishment (Printify)  
**Document Purpose:** Technical Backbone Documentation & Panelist Q&A Preparation  
**Target Audience:** Capstone Development Team & Thesis Defense Presenters  

---

## Table of Contents
1. [Executive Summary & Architectural Philosophy](#1-executive-summary--architectural-philosophy)
2. [Complete Entity-by-Entity Technical Breakdown](#2-complete-entity-by-entity-technical-breakdown)
   - [Entity 1: USERS](#entity-1-users)
   - [Entity 2: PRINT_SHOPS](#entity-2-print_shops)
   - [Entity 3: SHOP_SERVICES](#entity-3-shop_services)
   - [Entity 4: SERVICE_BOMS](#entity-4-service_boms)
   - [Entity 5: INVENTORY_ITEMS](#entity-5-inventory_items)
   - [Entity 6: STOCK_MOVEMENTS](#entity-6-stock_movements)
   - [Entity 7: ORDERS](#entity-7-orders)
   - [Entity 8: ORDER_ITEMS](#entity-8-order_items)
3. [Relationship Matrix & Cardinality Rules](#3-relationship-matrix--cardinality-rules)
4. [The Life of a Transaction: End-to-End Operational Workflows](#4-the-life-of-a-transaction-end-to-end-operational-workflows)
   - [Workflow A: Service Provisioning & Configuration](#workflow-a-service-provisioning--configuration)
   - [Workflow B: Customer Order Intake & Dynamic Pricing](#workflow-b-customer-order-intake--dynamic-pricing)
   - [Workflow C: Payment Verification & 5-Stage Kanban Scheduling](#workflow-c-payment-verification--5-stage-kanban-scheduling)
   - [Workflow D: Automated BOM Deduction & Stock Ledger Auditing](#workflow-d-automated-bom-deduction--stock-ledger-auditing)
   - [Workflow E: Burn Rate Monitoring & Dynamic Reorder Point (ROP) Alerts](#workflow-e-burn-rate-monitoring--dynamic-reorder-point-rop-alerts)
5. [Database Normalization & Design Pattern Defense](#5-database-normalization--design-pattern-defense)
   - [Normalization Proof (1NF, 2NF, 3NF/BCNF)](#normalization-proof-1nf-2nf-3nfbcnf)
   - [The Hybrid Relational-Document Model (Why JSON is Used)](#the-hybrid-relational-document-model-why-json-is-used)
   - [Elimination of the "Table-per-Service" Anti-Pattern](#elimination-of-the-table-per-service-anti-pattern)
   - [Elimination of the "Domain Pollution" Anti-Pattern](#elimination-of-the-domain-pollution-anti-pattern)
6. [Anticipated Panelist Defense Questions & Winning Answers](#6-anticipated-panelist-defense-questions--winning-answers)
7. [Defense Quick-Reference Cheatsheet](#7-defense-quick-reference-cheatsheet)

---

## 1. Executive Summary & Architectural Philosophy

The **Integrated Dynamic Order, Job Scheduling, and Inventory Management System (Printify)** is built on a **modular 8-table relational architecture**. Rather than treating a printing business like a simple static retail shop, this database schema models a **custom light-manufacturing enterprise**. 

### The 4 Foundational Pillars:
1. **Multi-Tenant Shop Administration (`USERS`, `PRINT_SHOPS`)**: Manages role-based access control (RBAC) across three distinct actors (Business Owner, Production Staff, Customer) while anchoring all enterprise records to a single shop instance.
2. **Dynamic Service & Recipe Engine (`SHOP_SERVICES`, `SERVICE_BOMS`)**: Enables the shop owner to dynamically introduce, configure, price, and bind raw materials to any print service without requiring database migrations or schema alterations.
3. **5-Stage Kanban Job Scheduling & Universal Ordering (`ORDERS`, `ORDER_ITEMS`)**: Manages customer transactions from intake to payment verification, and orchestrates the digital job ticket through a 5-stage workshop Kanban pipeline (*Queue $\rightarrow$ Printing $\rightarrow$ Finishing/Assembly $\rightarrow$ Quality Check $\rightarrow$ Ready for Pickup*).
4. **Inventory Telemetry & Immutable Audit Ledger (`INVENTORY_ITEMS`, `STOCK_MOVEMENTS`)**: Tracks physical balances, calculates rolling daily consumption burn rates, triggers dynamic Reorder Point (ROP) alerts, and maintains a zero-loss append-only ledger for every material change.

---

## 2. Complete Entity-by-Entity Technical Breakdown

```
                       ┌─────────────────────────┐
                       │          USERS          │
                       └────────────┬────────────┘
                                    │ (1:1)
                                    ▼
┌─────────────────────────┐    ┌─────────────────────────┐    ┌─────────────────────────┐
│         ORDERS          │◀───│       PRINT_SHOPS       │───▶│      SHOP_SERVICES      │
└───────────┬─────────────┘    └────────────┬────────────┘    └────────────┬────────────┘
            │ (1:N)                         │ (1:N)                        │ (1:N)
            ▼                               ▼                              ▼
┌─────────────────────────┐    ┌─────────────────────────┐    ┌─────────────────────────┐
│       ORDER_ITEMS       │    │     INVENTORY_ITEMS     │◀───│      SERVICE_BOMS       │
└─────────────────────────┘    └────────────┬────────────┘    └─────────────────────────┘
                                            │ (1:N)
                                            ▼
                               ┌─────────────────────────┐
                               │     STOCK_MOVEMENTS     │
                               └─────────────────────────┘
```

---

### Entity 1: `USERS`
* **Table Role:** Central authentication, security credential storage, and Role-Based Access Control (RBAC).
* **Primary Key:** `user_id` (`bigint`, Auto-increment)

| Attribute | Type | Nullable | Description & Domain Significance |
| :--- | :--- | :---: | :--- |
| **`user_id`** | `bigint` | No | Unique identifier for every authenticated actor. |
| **`name`** | `varchar(255)` | No | Full personal name or display name. |
| **`email`** | `varchar(255)` | No | Unique login credential used for authentication and password resets. |
| **`password`** | `varchar(255)` | No | One-way bcrypt-hashed cryptographic password string. |
| **`role`** | `varchar(50)` | No | Role discriminator: `'business_owner'`, `'production_staff'`, or `'customer'`. Used by route middleware for strict access containment. |
| **`contact_number`** | `varchar(50)` | Yes | Phone number for automated SMS notifications, job pickup alerts, and customer inquiry. |
| **`avatar_path`** | `varchar(255)` | Yes | Relative filesystem path to user profile photo. |
| **`two_factor_enabled`**| `boolean` | No | Boolean flag indicating whether Fortify 2FA / WebAuthn passkey authentication is activated. |
| **`created_at`** | `timestamp` | No | Account registration timestamp. |

---

### Entity 2: `PRINT_SHOPS`
* **Table Role:** Multi-tenant root entity representing the printing establishment. Serves as the foreign key anchor for all services, orders, inventory items, and configurations.
* **Primary Key:** `print_shop_id` (`bigint`, Auto-increment)

| Attribute | Type | Nullable | Description & Domain Significance |
| :--- | :--- | :---: | :--- |
| **`print_shop_id`** | `bigint` | No | Primary Key identifying the shop tenant. |
| **`owner_user_id`** | `bigint` | No | **[FK]** References `USERS.user_id`. Enforces a strict 1:1 ownership relationship with the business owner. |
| **`shop_name`** | `varchar(255)` | No | Commercial business trade name (e.g., "Apex Print Hub"). |
| **`contact_number`** | `varchar(50)` | Yes | Official shop customer service phone number. |
| **`email`** | `varchar(255)` | Yes | Official business inquiry email address. |
| **`address`** | `varchar(255)` | Yes | Physical workshop location for customer pickup. |
| **`is_setup_completed`**| `boolean` | No | State flag indicating whether the initial onboarding setup wizard has been completed by the owner. |
| **`created_at`** | `timestamp` | No | Tenant initialization timestamp. |

---

### Entity 3: `SHOP_SERVICES`
* **Table Role:** Dynamic service registry. Represents an active printing module offered by the print shop (e.g., Thesis Binding, Document Printing, Tarpaulin, Custom Apparel).
* **Primary Key:** `service_id` (`bigint`, Auto-increment)

| Attribute | Type | Nullable | Description & Domain Significance |
| :--- | :--- | :---: | :--- |
| **`service_id`** | `bigint` | No | Primary Key. |
| **`print_shop_id`** | `bigint` | No | **[FK]** References `PRINT_SHOPS.print_shop_id`. Multi-tenant shop anchor. |
| **`service_key`** | `varchar(100)` | No | Unique program slug (e.g., `'thesis_binding'`, `'document_printing'`). |
| **`service_name`** | `varchar(255)` | No | Display title shown on customer storefront. |
| **`is_active`** | `boolean` | No | Soft toggle allowing owner to enable/disable service intake without deleting historic orders. |
| **`display_order`** | `integer` | No | Visual order position on the customer ordering portal. |
| **`daily_production_quota`**| `integer` | No | Maximum daily unit capacity for this service to prevent shop floor over-commitment. |
| **`standard_lead_time_days`**| `integer` | No | Default fulfillment turnaround window in calendar days. |
| **`rush_lead_time_days`**| `integer` | No | Expedited fulfillment turnaround window in calendar days. |
| **`settings`** | `json` | Yes | Extensible JSON object storing service-specific pricing rules, page rates, paper stock fees, and finishing options. |

---

### Entity 4: `SERVICE_BOMS`
* **Table Role:** Unified Bill of Materials (BOM) recipe engine. Decouples printing services from physical raw materials, allowing dynamic multi-item material deductions.
* **Primary Key:** `bom_id` (`bigint`, Auto-increment)

| Attribute | Type | Nullable | Description & Domain Significance |
| :--- | :--- | :---: | :--- |
| **`bom_id`** | `bigint` | No | Primary Key. |
| **`print_shop_id`** | `bigint` | No | **[FK]** References `PRINT_SHOPS.print_shop_id`. Multi-tenant shop anchor. |
| **`service_id`** | `bigint` | No | **[FK]** References `SHOP_SERVICES.service_id`. Links recipe to a specific service. |
| **`inventory_item_id`**| `bigint` | No | **[FK]** References `INVENTORY_ITEMS.inventory_item_id`. Target raw material deducted. |
| **`component_name`** | `varchar(255)` | No | Component description (e.g., "A4 Paper", "Chipboard", "Ring Spine"). |
| **`usage_type`** | `varchar(50)` | No | Formula discriminator: `'per_unit'`, `'per_page'`, `'per_sheet'` (duplex-aware), or `'fixed'`. |
| **`usage_qty`** | `decimal(8,2)` | No | Multiplier quantity deducted per calculation unit. |
| **`unit`** | `varchar(50)` | No | Measurement unit matching the target inventory item (`sheets`, `pcs`, `rolls`). |
| **`trigger_conditions`**| `json` | Yes | Conditional filter rules (e.g., `{"binding_type": "hardbound"}`, `{"paper_size": "A4"}`). |

---

### Entity 5: `INVENTORY_ITEMS`
* **Table Role:** Master catalog of raw materials, paper stocks, hardware, and finished merchandise with real-time stock balances and safety thresholds.
* **Primary Key:** `inventory_item_id` (`bigint`, Auto-increment)

| Attribute | Type | Nullable | Description & Domain Significance |
| :--- | :--- | :---: | :--- |
| **`inventory_item_id`**| `bigint` | No | Primary Key. |
| **`print_shop_id`** | `bigint` | No | **[FK]** References `PRINT_SHOPS.print_shop_id`. Multi-tenant shop anchor. |
| **`sku`** | `varchar(100)` | Yes | Stock Keeping Unit code for barcode scanning and supplier tracking. |
| **`item_name`** | `varchar(255)` | No | Descriptive material title (e.g., "Hardbound Chipboard #20", "A4 80gsm Paper"). |
| **`category`** | `varchar(100)` | No | Broad classification (`'Paper'`, `'Binding'`, `'Apparel'`, `'Hardware/Inks'`). |
| **`item_type`** | `varchar(50)` | No | Operational classification: `'raw_material'`, `'ready_to_sell'`, `'consumable'`. |
| **`stock_qty`** | `decimal(10,2)`| No | Current verified physical inventory count. |
| **`unit`** | `varchar(50)` | No | Unit of measure (`sheets`, `reams`, `pcs`, `rolls`, `ml`). |
| **`reorder_level`** | `decimal(10,2)`| No | Dynamic Reorder Point (ROP) baseline. Triggers low-stock warnings when `stock_qty <= reorder_level`. |
| **`unit_cost`** | `decimal(10,2)`| No | Acquisition purchase cost per unit for margin calculation. |
| **`supplier_name`** | `varchar(255)` | Yes | Primary vendor or distributor contact. |

---

### Entity 6: `STOCK_MOVEMENTS`
* **Table Role:** Immutable, append-only transaction audit ledger. Tracks every unit deducted or added to physical inventory with user and order attribution.
* **Primary Key:** `movement_id` (`bigint`, Auto-increment)

| Attribute | Type | Nullable | Description & Domain Significance |
| :--- | :--- | :---: | :--- |
| **`movement_id`** | `bigint` | No | Primary Key. |
| **`inventory_item_id`**| `bigint` | No | **[FK]** References `INVENTORY_ITEMS.inventory_item_id`. Target material. |
| **`logged_by_user_id`**| `bigint` | Yes | **[FK]** References `USERS.user_id`. Operator or admin who initiated or authorized the transaction. |
| **`order_id`** | `bigint` | Yes | **[FK]** References `ORDERS.order_id`. Traces consumption directly to the initiating customer order (null for manual restocks). |
| **`movement_type`** | `varchar(50)` | No | Transaction category: `'production_deduction'`, `'manual_stock_in'`, `'spoilage_waste'`, `'inventory_adjustment'`. |
| **`quantity`** | `decimal(10,2)`| No | Delta amount (negative value for consumption/spoilage, positive for supplier delivery). |
| **`previous_stock`** | `decimal(10,2)`| No | Exact on-hand balance immediately prior to this transaction. |
| **`resulting_stock`** | `decimal(10,2)`| No | Exact on-hand balance immediately following this transaction. |
| **`audit_notes`** | `varchar(255)` | Yes | Explanatory reason, invoice reference, or machine defect note. |
| **`created_at`** | `timestamp` | No | Precise timestamp used to calculate daily rolling burn rate velocities. |

---

### Entity 7: `ORDERS`
* **Table Role:** Central commercial transaction and workshop job scheduling entity. Drives the 5-stage production Kanban board and payment verification workflow.
* **Primary Key:** `order_id` (`bigint`, Auto-increment)

| Attribute | Type | Nullable | Description & Domain Significance |
| :--- | :--- | :---: | :--- |
| **`order_id`** | `bigint` | No | Primary Key. |
| **`order_number`** | `varchar(100)` | No | Public human-readable tracking code (e.g., `ORD-20260910-0042`). |
| **`print_shop_id`** | `bigint` | No | **[FK]** References `PRINT_SHOPS.print_shop_id`. Multi-tenant shop anchor. |
| **`customer_user_id`**| `bigint` | No | **[FK]** References `USERS.user_id`. Customer who submitted the order. |
| **`assigned_staff_user_id`**| `bigint`| Yes | **[FK]** References `USERS.user_id`. Workshop technician assigned to execute the job. |
| **`service_id`** | `bigint` | No | **[FK]** References `SHOP_SERVICES.service_id`. Service classification. |
| **`order_status`** | `varchar(50)` | No | High-level commercial status: `'pending_payment'`, `'in_queue'`, `'in_production'`, `'completed'`, `'cancelled'`. |
| **`payment_status`** | `varchar(50)` | No | Payment verification state: `'unpaid'`, `'pending_verification'`, `'verified_paid'`, `'rejected'`. |
| **`production_stage`**| `varchar(50)` | No | Active workshop Kanban workstation: `'queue'`, `'printing'`, `'assembly'`, `'qc'`, `'ready'`. |
| **`is_rush`** | `boolean` | No | Priority expedite flag. Triggers shorter lead times and rush fee calculations. |
| **`subtotal_amount`** | `decimal(10,2)`| No | Net cost of print line items. |
| **`rush_fee_amount`** | `decimal(10,2)`| No | Additional rush surcharge applied. |
| **`total_amount`** | `decimal(10,2)`| No | Gross payable transaction invoice total. |
| **`payment_method`** | `varchar(50)` | Yes | Payment channel: `'gcash'`, `'maya'`, `'cash_counter'`. |
| **`payment_reference_no`**| `varchar(100)`| Yes | Digital e-wallet / bank transaction reference code. |
| **`payment_proof_path`**| `varchar(255)`| Yes | Filesystem storage path of customer-uploaded payment receipt screenshot. |
| **`target_completion_date`**| `date` | Yes | Calculated customer deadline based on service lead time and rush selection. |

---

### Entity 8: `ORDER_ITEMS`
* **Table Role:** Universal generalized line item specifications. Stores individual print item parameters, quantities, itemized calculations, and client file attachments.
* **Primary Key:** `order_item_id` (`bigint`, Auto-increment)

| Attribute | Type | Nullable | Description & Domain Significance |
| :--- | :--- | :---: | :--- |
| **`order_item_id`** | `bigint` | No | Primary Key. |
| **`order_id`** | `bigint` | No | **[FK]** References `ORDERS.order_id`. Parent order anchor. |
| **`item_name`** | `varchar(255)` | No | Product title (e.g., "BSIS Hardbound Manuscript", "Duplex Handouts"). |
| **`quantity`** | `integer` | No | Number of finished copies/pieces ordered. |
| **`unit_price`** | `decimal(10,2)`| No | Computed price per finished unit based on dynamic formula. |
| **`total_price`** | `decimal(10,2)`| No | Line item total (`quantity * unit_price`). |
| **`specifications`** | `json` | No | Structured JSON dictionary encapsulating dynamic physical options (e.g., paper size, page count, binding type, cover foil color, customer-supplied paper toggle). |
| **`document_file_path`**| `varchar(255)`| Yes | Filesystem path to client's uploaded PDF/artwork file. |
| **`document_original_name`**| `varchar(255)`| Yes | Original client filename shown in staff dashboard for download. |

---

## 3. Relationship Matrix & Cardinality Rules

| Parent Table & PK | Child Table & FK | Cardinality | Business Semantics & Integrity Rule |
| :--- | :--- | :---: | :--- |
| **`USERS.user_id`** | **`PRINT_SHOPS.owner_user_id`** | **1 : 1** | A single registered business owner owns exactly one print shop enterprise instance. Enforced via unique constraint on `owner_user_id`. |
| **`USERS.user_id`** | **`ORDERS.customer_user_id`** | **1 : N** | A registered customer places zero or more print orders over time. Deleting a user is restricted (`restrictOnDelete`) if active orders exist. |
| **`USERS.user_id`** | **`ORDERS.assigned_staff_user_id`**| **1 : N** | A production staff member is delegated zero or more active orders as the primary machine operator. |
| **`USERS.user_id`** | **`STOCK_MOVEMENTS.logged_by_user_id`**| **1 : N** | A staff member or owner logs zero or more physical stock adjustments or production deductions. |
| **`PRINT_SHOPS.print_shop_id`**| **`SHOP_SERVICES.print_shop_id`** | **1 : N** | A print shop configures and maintains multiple active service catalog offerings. Deleting a shop cascades to delete its services. |
| **`PRINT_SHOPS.print_shop_id`**| **`INVENTORY_ITEMS.print_shop_id`** | **1 : N** | A print shop stocks multiple raw materials, consumables, and finished goods. |
| **`PRINT_SHOPS.print_shop_id`**| **`ORDERS.print_shop_id`** | **1 : N** | A print shop processes and fulfills multiple customer orders. |
| **`SHOP_SERVICES.service_id`** | **`SERVICE_BOMS.service_id`** | **1 : N** | A service defines one or more material consumption recipes in the Bill of Materials. |
| **`SHOP_SERVICES.service_id`** | **`ORDERS.service_id`** | **1 : N** | A service categorizes multiple incoming customer orders. |
| **`INVENTORY_ITEMS.inventory_item_id`**| **`SERVICE_BOMS.inventory_item_id`**| **1 : N** | A single raw material (e.g., A4 Paper, Gold Foil) can be bound across multiple service BOM recipes. |
| **`INVENTORY_ITEMS.inventory_item_id`**| **`STOCK_MOVEMENTS.inventory_item_id`**| **1 : N** | An inventory item maintains an immutable audit trail of multiple physical stock movements. |
| **`ORDERS.order_id`** | **`ORDER_ITEMS.order_id`** | **1 : N** | An order contains one or more customized print line items. Cascades on order deletion. |
| **`ORDERS.order_id`** | **`STOCK_MOVEMENTS.order_id`** | **1 : N** | An order links directly to the material deductions logged in the inventory ledger for complete traceability. |

---

## 4. The Life of a Transaction: End-to-End Operational Workflows

Understanding how data traverses across these 8 tables during daily operations is the single most important skill for a thesis defense:

### Workflow A: Service Provisioning & Configuration
```
[Business Owner] 
       │
       ▼
1. Navigates to App Store / Services Wizard
       │
       ▼
2. Modifies pricing formulas, quotas, and lead times
       │
       ▼
3. Writes to `SHOP_SERVICES` (stores rates in `settings` JSON)
       │
       ▼
4. Configures material recipes in `SERVICE_BOMS` (links to `INVENTORY_ITEMS`)
```

### Workflow B: Customer Order Intake & Dynamic Pricing
```
[Customer]
       │
       ▼
1. Selects service on Storefront (`SHOP_SERVICES`)
       │
       ▼
2. Fills out Order Wizard (selects paper size, pages, binding type, finishes)
       │
       ▼
3. Client-side Livewire engine computes subtotal using dynamic pricing from `SHOP_SERVICES.settings`
       │
       ▼
4. Uploads document/manuscript PDF
       │
       ▼
5. Creates record in `ORDERS` (`order_status = 'pending_payment'`, `production_stage = 'queue'`)
       │
       ▼
6. Creates child records in `ORDER_ITEMS` (`specifications` stores all custom attributes)
```

### Workflow C: Payment Verification & 5-Stage Kanban Scheduling
```
[Customer] ──────────────────────────┐
       │                             │
       ▼                             ▼
Uploads GCash/Maya Receipt      Input Reference Number
       │                             │
       └──────────────┬──────────────┘
                      ▼
Updates `ORDERS.payment_proof_path` & `payment_reference_no`
`payment_status` becomes `'pending_verification'`
                      │
                      ▼
[Production Staff] inspects receipt in Staff Dashboard
                      │
                      ▼
Clicks "Verify Payment":
  - `ORDERS.payment_status` = `'verified_paid'`
  - `ORDERS.order_status` = `'in_production'`
  - Job card enters the visual 5-Stage Kanban board:
    1. Queue ──▶ 2. Printing ──▶ 3. Assembly ──▶ 4. QC ──▶ 5. Ready for Pickup
```

### Workflow D: Automated BOM Deduction & Stock Ledger Auditing
```
Job card advances to production stage (e.g. Printing or Assembly)
                      │
                      ▼
`InventoryDeductionService` activates automatically:
  1. Queries `SERVICE_BOMS` where `service_id = order.service_id`
  2. Evaluates `trigger_conditions` (e.g., checks if `binding_type == 'hardbound'`)
  3. Checks customer paper toggle:
     - If `specifications.customer_supplied_paper == true`, bypasses paper sheets deduction!
  4. Calculates physical consumption based on `usage_type`:
     - `per_sheet`: `ceil(total_pages / 2) * copies` (handles duplex automatically)
     - `per_copy`: `usage_qty * copies`
  5. Decrements `INVENTORY_ITEMS.stock_qty`
  6. Inserts immutable record into `STOCK_MOVEMENTS`:
     - `movement_type` = `'production_deduction'`
     - `quantity` = -consumedQty
     - `order_id` = order.order_id (Direct traceability!)
     - `logged_by_user_id` = staff.user_id
```

### Workflow E: Burn Rate Monitoring & Dynamic Reorder Point (ROP) Alerts
```
Scheduled task or inventory hub dashboard loads:
  1. Queries `STOCK_MOVEMENTS` over rolling 7-day and 30-day consumption windows:
     Daily Burn Rate = Total Quantity Deducted / Days
  2. Evaluates Dynamic Reorder Point (ROP):
     ROP = (Average Daily Burn Rate × Supplier Lead Time) + Safety Stock
  3. Evaluates current on-hand balance:
     If `INVENTORY_ITEMS.stock_qty <= ROP`:
       - Flags item with `low_stock` alert badge
       - Triggers automated reorder recommendation on Owner Dashboard
```

---

## 5. Database Normalization & Design Pattern Defense

When panelists ask about your database design rigor, use these formal justifications:

### Normalization Proof (1NF, 2NF, 3NF/BCNF):
1. **First Normal Form (1NF)**:
   - All attributes contain atomic values (every field stores a single logical value or a structured object).
   - Every table has a unique Primary Key (`{entity}_id`).
   - There are no repeating groups of columns (e.g., no `paper1`, `paper2`, `paper3` columns).
2. **Second Normal Form (2NF)**:
   - The database is in 1NF.
   - All non-key attributes are fully functionally dependent on the entire Primary Key. Because all Primary Keys are single-column surrogate keys (`bigint`), no partial dependencies can mathematically exist.
3. **Third Normal Form (3NF) & Boyce-Codd Normal Form (BCNF)**:
   - The database is in 2NF.
   - There are zero transitive dependencies among non-key attributes. Non-key columns depend *only* on the primary key, directly and non-transitively.

---

### The Hybrid Relational-Document Model (Why JSON is Used):
A panelist may ask:  
> *"Why are you using JSON for `settings` in `SHOP_SERVICES` and `specifications` in `ORDER_ITEMS` instead of making more tables?"*

**The Defense Script:**
> *"Sir/Ma'am, we implemented a **Hybrid Relational-Document Architecture** (PostgreSQL/MySQL 8 JSONB standard). In a multi-service printing enterprise, physical product attributes are inherently polymorphic: a thesis has cover colors, foil stamping, and page counts; document printing has duplex modes and ring bindings; an apparel service has garment sizes and fabric types; a tarpaulin service has square footage and grommets.*
>
> *If we used a traditional pure-relational model, we would face the **Entity-Attribute-Value (EAV) anti-pattern**, which requires expensive recursive multi-table JOINs, or the **Table-per-Service anti-pattern**, which forces schema migrations every time a new product is introduced.*
>
> *By using structured JSON documents embedded within normalized relational entities, all universal transactional fields (`order_id`, `quantity`, `unit_price`, `total_price`) remain strictly relational and indexed for fast aggregate reporting, while the polymorphic physical parameters remain dynamic, flexible, and completely migration-free."*

---

### Elimination of the "Table-per-Service" Anti-Pattern:
- **Legacy Flaw**: Creating `thesis_binding_configs`, `document_printing_configs`, `tarpaulin_configs`, etc.
- **Architectural Fix**: Consolidated into `SHOP_SERVICES.settings`. Adding 10 new printing services requires **zero new database tables**.

### Elimination of the "Domain Pollution" Anti-Pattern:
- **Legacy Flaw**: Hardcoding `paper_size`, `bw_pages_count`, and `color_pages_count` into `ORDER_ITEMS`. When a customer orders T-shirts or mugs, those columns are forced to be `NULL`.
- **Architectural Fix**: Replaced with the **Universal Line Item Pattern**. Physical variables reside in `specifications`, keeping the entity clean and universally applicable across any commercial product.

---

## 6. Anticipated Panelist Defense Questions & Winning Answers

### Question 1: "Why do you only have 8 tables? Isn't a printing business too complex for just 8 tables?"
**Winning Answer:**  
> *"Our 8-table schema is the result of systematic normalization and architectural refactoring. Initially, naive designs create 15 to 20 tables because they create separate configuration tables, separate BOM tables, and separate order tables for every individual service. 
> 
> By utilizing a **Unified Bill of Materials Engine (`SERVICE_BOMS`)** and a **Dynamic Service Registry (`SHOP_SERVICES`)**, we achieved high architectural cohesion and low coupling. Our schema supports infinite print services, infinite material recipes, multi-stage Kanban scheduling, and full inventory telemetry using 8 highly normalized tables."*

---

### Question 2: "What happens if the print shop adds T-Shirt Printing, Mug Sublimation, or Sticker Cutting next month? Do you need to run a new migration?"
**Winning Answer:**  
> *"No, sir/ma'am. Zero migrations or schema changes are needed. The owner simply:
> 1. Activates the service in `SHOP_SERVICES` with its specific pricing options stored in `settings`.
> 2. Creates the corresponding material deduction recipes in `SERVICE_BOMS` (e.g., 1 T-shirt blank or 1 ceramic mug per copy).
> 3. Customers immediately order it, and `ORDER_ITEMS.specifications` captures shirt sizes or mug finishes dynamically.
> 
> The database is 100% extensible by design."*

---

### Question 3: "How does your system prevent duplicate inventory deductions if a job is moved back and forth on the Kanban board?"
**Winning Answer:**  
> *"Our system enforces **Idempotent Deduction Guards** within the `InventoryDeductionService`. Before applying any production deduction, the system verifies whether an existing `STOCK_MOVEMENTS` record linked to that `order_id` and `movement_type = 'production_deduction'` already exists. If it has already been deducted, the system bypasses re-deduction, completely preventing double-counting or phantom inventory decrements."*

---

### Question 4: "Why does `STOCK_MOVEMENTS` store `previous_stock` and `resulting_stock`? Isn't that redundant if you have `quantity`?"
**Winning Answer:**  
> *"In enterprise ERP and financial accounting standards, storing `previous_stock` and `resulting_stock` alongside `quantity` is a fundamental requirement for **Zero-Loss Auditability**. It creates a verifiable mathematical proof chain:
> $$\text{Previous Stock} + \text{Quantity} = \text{Resulting Stock}$$
> If a database administrator or malicious actor tampers with a single row, the mathematical continuity of the entire ledger breaks immediately, allowing the system to detect unauthorized data manipulation."*

---

### Question 5: "Why is there no drawn line between `USERS` and `STOCK_MOVEMENTS` in the diagram if `logged_by_user_id` is an FK?"
**Winning Answer:**  
> *"In logical and physical ERD layout design, secondary audit relationships that span diagonally across the canvas are intentionally omitted from visual line routing to prevent visual clutter and line crossings. 
> 
> The relationship is strictly enforced in the data dictionary and schema via the foreign key constraint `logged_by_user_id REFERENCES USERS(user_id)`. Leaving the visual line off maintains a clean 3-column topology without sacrificing relational integrity."*

---

### Question 6: "How does your schema handle customers who bring their own paper ('Dala ang Papel')?"
**Winning Answer:**  
> *"In `ORDER_ITEMS`, the customer selection is stored inside `specifications.customer_supplied_paper = true`. When the order advances to production, the `InventoryDeductionService` inspects this flag. While it still deducts binding supplies (chipboards, gold foil, ring spines), it automatically bypasses deduction of shop paper reams, preventing artificial inventory shortages."*

---

### Question 7: "What happens if an owner deactivates a service in `SHOP_SERVICES`? Does it delete historical orders or sales reports?"
**Winning Answer:**  
> *"No. The system uses a **soft-state toggle (`is_active = false`)** on `SHOP_SERVICES`. Deactivating a service merely hides it from the customer ordering wizard so no new orders can be submitted. All historic rows in `ORDERS`, `ORDER_ITEMS`, and `STOCK_MOVEMENTS` remain completely untouched, ensuring that sales analytics, cashflow reports, and audit trails remain permanently accurate."*

---

## 7. Defense Quick-Reference Cheatsheet

| Technical Term | What It Means in Our System | Where It Lives |
| :--- | :--- | :--- |
| **Surrogate Key** | Auto-incrementing numeric ID (`bigint`) used as the Primary Key instead of natural text keys. | All 8 tables (`{entity}_id`) |
| **Referential Integrity** | Prevents orphaned records (e.g., cannot delete a print shop if it has active orders). | All Foreign Key constraints (`[FK]`) |
| **Surrogate Polymorphism** | Using structured JSON to store variant attributes without creating separate tables. | `SHOP_SERVICES.settings`, `ORDER_ITEMS.specifications` |
| **Idempotent Deduction** | Ensuring material deduction only occurs once, even if Kanban cards are clicked repeatedly. | `InventoryDeductionService` + `STOCK_MOVEMENTS` |
| **Dynamic Reorder Point (ROP)** | Safety stock calculation based on 7-day rolling burn rate velocity and supplier lead time. | Computed from `STOCK_MOVEMENTS` vs `INVENTORY_ITEMS.reorder_level` |
| **Immutable Audit Ledger** | Append-only table where stock changes are never edited or overwritten, only inserted. | `STOCK_MOVEMENTS` |
| **Multi-Tenancy** | Architectural separation ensuring every shop's data, inventory, and services are scoped to its own ID. | `print_shop_id` across all operational entities |
