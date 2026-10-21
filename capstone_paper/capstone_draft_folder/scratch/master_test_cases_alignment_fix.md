# 1:1 Verbatim Functional Requirements Alignment for `Table 1` and `Appendix F`

This file provides the **exact 1:1 verbatim** Functional Requirement titles and Module names from Chapter IV (`System Modules`, Printed pp. 42–43, and `Functional Requirements`, Printed pp. 44–56) for:
1. **Appendix F: Master Functional Test Cases** (Printed pp. 112–114 / PDF pp. 124–126)
2. **Table 1: Functional Test Plan and Requirements Traceability Matrix** (Printed pp. 32–34 / PDF pp. 44–46)

---

## 1. Side-by-Side Comparison: Shortened Name vs. 1:1 Verbatim FR Name

| FR ID | Shortened Name (Currently in `Table 1` & `Appendix F`) | Exact 1:1 Verbatim Name (From `Functional Requirements`, pp. 44–56 & `Appendix G`) |
| :--- | :--- | :--- |
| **FR-01** | User Account Registration | **FR-01:** User Account Registration *(Already 1:1)* |
| **FR-02** | User Authentication (Login) | **FR-02:** User Authentication (Login) *(Already 1:1)* |
| **FR-03** | User Profile Management | **FR-03:** User Profile Management *(Already 1:1)* |
| **FR-04** | Session Termination & Logout | **FR-04:** Session Termination and Logout |
| **FR-05** | Service Catalog & Quotation | **FR-05:** Service Catalog Browsing and Quotation Calculation |
| **FR-06** | Print Order Placement & Upload | **FR-06:** Print Order Placement and Document Upload |
| **FR-07** | Print Attribute Specification | **FR-07:** Print Attribute and Finishing Specification |
| **FR-08** | Substrate Declaration | **FR-08:** Customer-Supplied Substrate Declaration |
| **FR-09** | Proof of Payment Submission | **FR-09:** Proof of Payment Submission *(Already 1:1)* |
| **FR-10** | Live Order Progress Tracking | **FR-10:** Live Order Progress and Status Tracking |
| **FR-11** | Payment Auditing & Verification | **FR-11:** Payment Transaction Auditing and Verification |
| **FR-12** | 5-Stage Kanban Floor Queue | **FR-12:** Production Floor 5-Stage Kanban Queue Management |
| **FR-13** | Automated BOM Stock Deduction | **FR-13:** Automated Bill of Materials (BOM) Inventory Deduction |
| **FR-14** | Substrate Counter Inspection | **FR-14:** Customer-Supplied Substrate Counter Inspection |
| **FR-15** | Material Spoilage Logging | **FR-15:** Material Spoilage Logging and Stock Adjustments |
| **FR-16** | Dynamic Service Configuration | **FR-16:** Dynamic Service and Rate Configuration |
| **FR-17** | BOM Recipe Definition | **FR-17:** Bill of Materials (BOM) Recipe Definition |
| **FR-18** | Inventory Oversight & ROP Alert | **FR-18:** Inventory Oversight and Dynamic Reorder Point (ROP) Alerting |
| **FR-19** | Analytics & Financial Reporting | **FR-19:** Sales, Cashflow, and Operational Analytics Generation |

---

## 2. Ready-to-Paste `Appendix F: Master Functional Test Cases` (100% 1:1 Verbatim FR & Module Names, All 26 Rows, No Duplicate Column)

| Test Case ID | Module | Linked Requirement | Test Case Title / Scenario | Test Type |
| :--- | :--- | :--- | :--- | :--- |
| **TC-MOD1-01** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-01:** User Account Registration | Customer Registration with Valid Inputs | Positive |
| **TC-MOD1-02** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-01:** User Account Registration | Customer Registration with Duplicate Email | Negative |
| **TC-MOD1-03** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-02:** User Authentication (Login) | Multi-Role Login & RBAC Route Guard | Positive / Security |
| **TC-MOD1-04** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-02:** User Authentication (Login) | Login with Invalid Credentials & Rate Limiting | Negative / Security |
| **TC-MOD1-05** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-04:** Session Termination and Logout | User Logout & Session Invalidation | Positive / Security |
| **TC-MOD1-06** | Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC) | **FR-03:** User Profile Management | Customer Profile Information & Password Update | Positive |
| **TC-MOD2-01** | Module 2: Dynamic Service Configuration and Catalog Pricing Engine | **FR-05:** Service Catalog Browsing and Quotation Calculation | Interactive Price Quotation Calculation | Positive |
| **TC-MOD2-02** | Module 2: Dynamic Service Configuration and Catalog Pricing Engine | **FR-16:** Dynamic Service and Rate Configuration | Admin Creation of New Print Service & Pricing Matrix | Positive |
| **TC-MOD2-03** | Module 2: Dynamic Service Configuration and Catalog Pricing Engine | **FR-17:** Bill of Materials (BOM) Recipe Definition | Admin Mapping of BOM Raw Material Deduction Ratios | Positive |
| **TC-MOD2-04** | Module 2: Dynamic Service Configuration and Catalog Pricing Engine | **FR-07:** Print Attribute and Finishing Specification | Dynamic Filtering of Incompatible Material/Size Attributes | Negative / Validation |
| **TC-MOD3-01** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-06:** Print Order Placement and Document Upload | Valid Artwork File Upload (`PDF`/`PNG`/`JPG`/`TIFF`) | Positive |
| **TC-MOD3-02** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-06:** Print Order Placement and Document Upload | Artwork Upload with Invalid Format (`.exe`/`.docx`) or `> 50 MB` | Negative / Security |
| **TC-MOD3-03** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-07:** Print Attribute and Finishing Specification | Complete Job Order Placement with Custom Specifications | Positive |
| **TC-MOD3-04** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-08:** Customer-Supplied Substrate Declaration | Order Placement with Customer-Supplied Substrate Toggle | Positive |
| **TC-MOD3-05** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-09:** Proof of Payment Submission | Digital Payment Receipt Upload & Reference Number Logging | Positive |
| **TC-MOD3-06** | Module 3: Customer Self-Service Ordering and Prepress Intake | **FR-10:** Live Order Progress and Status Tracking | Real-Time Customer Order Status Tracker Updates | Positive |
| **TC-MOD4-01** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-11:** Payment Transaction Auditing and Verification | Staff Approval of Valid Proof of Payment | Positive |
| **TC-MOD4-02** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-11:** Payment Transaction Auditing and Verification | Staff Rejection of Fraudulent/Blurry Payment Receipt | Negative |
| **TC-MOD4-03** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-12:** Production Floor 5-Stage Kanban Queue Management | Sequential Kanban Stage Progression (*Pending* to *Completed*) | Positive |
| **TC-MOD4-04** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-12:** Production Floor 5-Stage Kanban Queue Management | Restricted Transition to *In Production* Without Verified Payment | Negative / Business Rule |
| **TC-MOD4-05** | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | **FR-14:** Customer-Supplied Substrate Counter Inspection | Staff Acceptance & Rejection of Customer-Supplied Media | Positive / Negative |
| **TC-MOD5-01** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-13:** Automated Bill of Materials (BOM) Inventory Deduction | Automatic Raw Material Deduction Upon *In Production* Trigger | Positive |
| **TC-MOD5-02** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-13:** Automated Bill of Materials (BOM) Inventory Deduction | Zero Base-Substrate Deduction for Customer-Supplied Orders | Positive / Business Rule |
| **TC-MOD5-03** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-15:** Material Spoilage Logging and Stock Adjustments | Staff Logging of Damaged Materials & Reason Code Deduction | Positive |
| **TC-MOD5-04** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-18:** Inventory Oversight and Dynamic Reorder Point (ROP) Alerting | Low-Stock Threshold Triggering Automated ROP Warning Alert | Positive |
| **TC-MOD5-05** | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | **FR-19:** Sales, Cashflow, and Operational Analytics Generation | Generation of Date-Filtered Sales, Spoilage & Material Reports | Positive |

---

## 3. Ready-to-Paste `Table 1: Functional Test Plan and Requirements Traceability Matrix` (Printed pp. 32–34 / PDF pp. 44–46) with 1:1 Verbatim FR Names

*(Note: `Table 1` on Printed pp. 32–34 also used the shortened FR names in its `Linked Functional Requirement` column! Here is `Table 1` with the exact 1:1 verbatim FR names so `Table 1`, `Functional Requirements`, `Appendix F`, and `Appendix G` all match 100% word-for-word.)*

| Test Suite ID | Linked Functional Requirement | Module Scope | Verification Method | Evaluation Metric | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TS-01** | **FR-01:** User Account Registration | Customer Onboarding | Automated Feature Test & Form Boundary Validation | Pass / Fail | |
| **TS-02** | **FR-02:** User Authentication (Login) | Security & Access Control | Credential Validation & Role-Based Route Gate | Pass / Fail | |
| **TS-03** | **FR-03:** User Profile Management | User Profile & Settings | Field Validation, Bcrypt Hash Check & Profile Mutation | Pass / Fail | |
| **TS-04** | **FR-04:** Session Termination and Logout | Session Management | Token Invalidation & Browser Storage Eviction Test | Pass / Fail | |
| **TS-05** | **FR-05:** Service Catalog Browsing and Quotation Calculation | Pricing Engine | Reactive Livewire Formula Interrogation & Price Recalculation | Pass / Fail | |
| **TS-06** | **FR-06:** Print Order Placement and Document Upload | Ordering & Storage | Document MIME Validation, Disk Tokenization, & Order Creation | Pass / Fail | |
| **TS-07** | **FR-07:** Print Attribute and Finishing Specification | Job Modeling | Attribute Constraint Checking & Duplex Sheet Math ($\lceil P/2 \rceil \times C$) | Pass / Fail | |
| **TS-08** | **FR-08:** Customer-Supplied Substrate Declaration | Order Extension | Cover-Only Pricing Flagging & Intake Delivery Tagging | Pass / Fail | |
| **TS-09** | **FR-09:** Proof of Payment Submission | Billing Intake | Payment Proof Image Upload & Pending Status Transition | Pass / Fail | |
| **TS-10** | **FR-10:** Live Order Progress and Status Tracking | Customer Telemetry | Real-Time Production Milestone Telemetry Stepper Query | Pass / Fail | |
| **TS-11** | **FR-11:** Payment Transaction Auditing and Verification | Billing Audit | Staff Ledger Cross-Reference & Order Queue Release | Pass / Fail | |
| **TS-12** | **FR-12:** Production Floor 5-Stage Kanban Queue Management | Workshop Scheduling | Interactive 5-Stage Transition & QC Rework Step-Back Loops | Pass / Fail | |
| **TS-13** | **FR-13:** Automated Bill of Materials (BOM) Inventory Deduction | Inventory Automation | Automated Consumption Decrement & Customer-Paper Bypass Rule | Pass / Fail | |
| **TS-14** | **FR-14:** Customer-Supplied Substrate Counter Inspection | Production Intake | Sheet Count/Condition Physical Audit & Intake Hold Release | Pass / Fail | |
| **TS-15** | **FR-15:** Material Spoilage Logging and Stock Adjustments | Stock Adjustment | Scrap Inventory Decrement & Waste Audit Ledger Entry | Pass / Fail | |
| **TS-16** | **FR-16:** Dynamic Service and Rate Configuration | Admin Management | No-Code Parameter Injection & Relational Rate Persistence | Pass / Fail | |
| **TS-17** | **FR-17:** Bill of Materials (BOM) Recipe Definition | Inventory Mapping | Service-to-Item Consumable Mapping & Recipe Coefficient Setup | Pass / Fail | |
| **TS-18** | **FR-18:** Inventory Oversight and Dynamic Reorder Point (ROP) Alerting | Inventory Analytics | Rolling Burn Rate Calculation & Dynamic ROP Threshold Warning | Pass / Fail | |
| **TS-19** | **FR-19:** Sales, Cashflow, and Operational Analytics Generation | Business Intelligence | Transaction Aggregation, Profit Metrics, & CSV Ledger Export | Pass / Fail | |
