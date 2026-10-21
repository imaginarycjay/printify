# Objectives vs. Modules Alignment Guide

**Source checked:** `Outline_capstone_temp.pdf` (157 PDF pages).
**Page numbers:** printed page numbers (PDF page = printed page + 12).

---

## 1. Decision: Keep the Objectives, Map the 5 Modules Under Them

**Keep the objectives as the anchor.** Lightly edit 3 of them, then map the 5 modules under them. Do **not** rewrite the objectives into "one objective per module".

### Why

| Reason | Explanation |
| :--- | :--- |
| **The title matches the objectives** | Title = *Dynamic Order, Job Scheduling, and Inventory Management ... with Automated Replenishment*. Objectives 1–3 map one-to-one to those three title terms. |
| **The objectives were approved at title defense** | They are already in the *Application for Research Title* (p. 104). A full rewrite changes your approved scope and invites "why did your objectives change?" |
| **The paper already has three pillars** | The RRL has three topics (Dynamic Order p. 16, Job Scheduling p. 19, Inventory w/ Automated Replenishment p. 21). The Statement of the Problem and the Definition of Terms follow the same three pillars. |
| **Module 1 (Auth/RBAC) should not be its own objective** | Panels often say login/registration is a standard feature, not a capstone contribution. It fits under the integration objective as the shared platform layer. |
| **The 5 modules are implementation detail** | Modules describe *how* the system is built. Objectives describe *what* the project achieves. One objective can have several modules. |

### What's wrong right now
The 5 modules are never introduced in the main chapters. They first appear in the appendices (p. 108). The objectives also miss two things that the modules implement:
- **Customer self-service ordering** (Module 3) is not in any objective. Objective 1 covers only the *administrator* side.
- **Spoilage tracking and sales/financial analytics** (Module 5) are not in any objective. "Financial" also conflicts with the Limitations (p. 8).

---

## 2. Target Mapping (Objectives → Modules → FRs)

The FR mapping below comes from your Appendix F test cases.

| Objective | Module | Functional Requirements | Owner (from TAM) |
| :--- | :--- | :--- | :--- |
| **Obj. 1** Dynamic Order Management | **Module 2** Dynamic Service Configuration & Catalog Pricing Engine | FR-04, FR-06, FR-15, FR-16 | Perez (70%) |
| | **Module 3** Customer Self-Service Ordering & Prepress Intake | FR-05, FR-07, FR-08, FR-09 | Comission (60%) |
| **Obj. 2** Job Scheduling | **Module 4** Production Floor 5-Stage Kanban Scheduling & Counter Intake | FR-10, FR-11, FR-13 | Perez (70%) |
| **Obj. 3** Inventory w/ Automated Replenishment | **Module 5** Automated Inventory Replenishment, Spoilage & Sales Reporting *(renamed)* | FR-12, FR-14, FR-17, FR-18 | Perez (70%) |
| **Obj. 4** Integration | **Module 1** User Onboarding, Authentication & RBAC (shared platform layer) | FR-01, FR-02, FR-03 | Comission (60%) |
| **Obj. 5** Testing | All modules | FR-01 to FR-18 (25 test cases) | Shared |

**Total:** 18 FRs, 5 modules, 25 test cases. All counts check out.

> [!WARNING]
> **FR-06 (Print Attribute Specification) is listed under both Module 2 (TC-MOD2-04) and Module 3 (TC-MOD3-03).** Each FR should belong to one module. The recommended home is **Module 2** (pricing computation). Keep TC-MOD3-03 but relabel it as an integration test, or move it to Module 2. Otherwise, assign FR-06 to Module 3 and adjust the table above.

---

## 3. Every Section That Needs Modification

Priority: 🔴 must fix (visible inconsistency) · 🟡 should fix · 🟢 optional polish

| # | Section | Page | Priority | What to change |
| :--- | :--- | :--- | :--- | :--- |
| 1 | **Objectives of the Project** | 6–7 | 🔴 | Edit Obj. 1, 3 and 4 (see §4A) |
| 2 | **Application for Research Title** (appendix) | 104 | 🔴 | Must be word-for-word identical to the edited objectives. **Ask your adviser first** whether the approved form can be updated. |
| 3 | **Statement of the Problem** | 4–5 | 🟡 | Second paragraph: add customer self-service ordering, spoilage/sales reporting and role-based access (see §4B) |
| 4 | **Expected Outputs** | 7–8 | 🔴 | Item 6: "18 functional **modules**" → "18 functional **requirements**". Add RBAC to item 1. Add spoilage/sales reports to item 5. Tag each output with its module number (see §4C). |
| 5 | **Limitations of the Project** | 8 | 🔴 | It says the system "excludes comprehensive financial accounting", but Module 5 says "Financial Analytics". Add a clarifying sentence (see §4D). |
| 6 | **NEW: System Modules subsection** | Insert before *System Requirement Specifications* (p. 42) | 🔴 | This is the main fix. Introduce the 5 modules in the main text and map them to the objectives (see §4E) |
| 7 | **System Requirement Specifications / FR list** | 42+ | 🟡 | FR-01 to FR-18 are a flat list. Add module sub-headings (e.g., "Module 1: ...") above each group, or add a "Module:" line to each FR. |
| 8 | **Methodology text: "18 core functional modules"** | 32 | 🔴 | → "18 core functional requirements" |
| 9 | **Table 1. Functional Test Plan & RTM**, "Module Scope" column | 32–34 | 🔴 | The column uses ad-hoc labels (*Customer Onboarding, Security & RBAC, Order Extension, Billing Intake...*). Replace them with the official module names (Module 1–5) so they match Appendix F/G. |
| 10 | **Definition of Terms** | 11–12 | 🟢 | Optionally add *Role-Based Access Control (RBAC)*, *Bill of Materials (BOM)*, *Material Spoilage*. Existing terms are fine. |
| 11 | **Conceptual Framework** text + Figure 1 | 13–15 | 🟢 | Already mentions the role-segregated platform and BI summaries. No change is required unless you want module labels in the Process box. |
| 12 | **Appendix F**, Master Functional Test Cases | 108–110 | 🟡 | Update the Module 5 short label if you rename it. Resolve the FR-06 double listing. |
| 13 | **Appendix G**, Test Case Specification headings | 111, 116, 120, 126, 131 | 🟡 | The Module 5 heading at p. 131 must match the new name |
| 14 | **Task Allocation Matrix** | 139–142 | 🟡 | Same module names as the System Modules subsection. Add an "Owner" label (Comission: M1, M3; Perez: M2, M4, M5). |
| 15 | **RRL** (3 topics) | 16–24 | ✅ | No change. It already matches Obj. 1–3, which is a main reason to keep the objectives. |
| 16 | **Use Case / Activity Diagrams** | 56+, 65+ | ✅ | No change from this alignment (adviser's naming comment is handled separately) |

---

## 4. Ready-to-Paste Replacement Text

### 4A. Objectives (p. 6–7). Changes in **bold**

> Specifically, this study aims to:
>
> 1. create a dynamic order management **module** that enables administrators to configure, add, or modify printing services, pricing variables, and material requirements without altering the source code, **and allows customers to configure print specifications, receive real-time quotations, and submit print orders online;**
> 2. develop a job scheduling module that digitizes the production queue, visually tracks order progress, and systematically allocates labor and machinery to ensure timely fulfillment;
> 3. implement an automated inventory replenishment module that calculates the real-time burn rate of materials based on active sales volume, **records material spoilage,** and triggers alerts at critical reorder points**, and generates sales and material consumption summary reports;**
> 4. integrate the dynamic ordering, job scheduling, and inventory replenishment modules into a single, cohesive web-based platform **secured by role-based access control for business owners, production staff, and customers;** and
> 5. conduct comprehensive functional testing and system verification of the developed platform using a structured Test Plan and discrete Test Cases to validate that all 18 functional requirements satisfy their specified operational parameters, boundary conditions, and database transactions on a pass-or-fail basis and execute operational user acceptance walkthroughs with printing shop stakeholders.

*Obj. 2 and Obj. 5 are unchanged. Obj. 1 changes "architecture" to "module" so all three core objectives use the same word.*

---

### 4B. Statement of the Problem (p. 5, second paragraph). Replace with

> To resolve these interconnected operational challenges, the project must explicitly address how to formulate a dynamic order management module that allows administrators to configure services, pricing tariffs, and consumable requirements on the fly without modifying application source code, **while enabling customers to configure print specifications, obtain instant quotations, and submit orders online.** Furthermore, it must establish how to engineer an interactive production floor scheduling module that digitizes workshop queues, visually tracks order progression across five defined stages, and systematically delegates machinery and labor. Concurrently, it must determine how to implement an automated inventory replenishment module that computes daily material burn rates, **records material spoilage,** and triggers timely replenishment alerts at dynamic reorder points. Finally, the project must answer how to integrate these targeted modules into a single, cohesive web-based platform **governed by role-based access control,** and how to conduct rigorous software functional testing and system verification using a structured Test Plan to empirically validate that all functional requirements execute accurately on a pass-or-fail basis.

---

### 4C. Expected Outputs (p. 7–8). Edited items only

> 1. A fully functional web-based platform that integrates dynamic order configuration, job scheduling, and inventory replenishment tailored specifically for printing and binding service operations, **with secure role-based access for business owners, production staff, and customers (Module 1).**
> 2. A dynamic order management module that grants business administrators the flexibility to seamlessly create, customize, and modify various printing services and pricing structures without needing backend code alterations **(Module 2).**
> 3. A customer-facing portal that provides an intuitive interface for clients to easily submit their specific printing requirements and track the real-time progress of their orders, eliminating the need for frequent physical follow-ups **(Module 3).**
> 4. A digital job scheduling module ... *(unchanged text)* ... **(Module 4).**
> 5. An automated inventory replenishment module that continuously tracks material consumption **and spoilage** and calculates real-time burn rates based on active sales, providing management with data-driven alerts before essential printing supplies reach critical depletion levels**, along with sales and consumption summary reports (Module 5).**
> 6. A comprehensive software quality assurance report comprising the Functional Test Plan, Requirements Traceability Matrix, and executed Test Case execution ledgers demonstrating 100% verification across all 18 functional **requirements** on a pass-or-fail basis.

---

### 4D. Limitations (p. 8). Add after the ERP sentence

> ...thus, it completely excludes comprehensive financial accounting, third-party logistics or courier integration, and Human Resource modules such as payroll and biometric employee attendance. **The sales and consumption reports generated by the system are limited to operational summaries derived from recorded orders, material usage, and spoilage; they do not constitute formal bookkeeping, tax computation, or audited financial statements.**

---

### 4E. NEW subsection: "System Modules" (insert right before *System Requirement Specifications*, p. 42)

> **System Modules**
>
> To realize the objectives of the project, Printify is organized into five (5) functional modules. Each module groups related functional requirements and is directly traceable to a specific objective, as presented in Table X.
>
> **Module 1: User Onboarding, Authentication, and Role-Based Access Control (RBAC).** This module serves as the shared security layer of the integrated platform. It handles customer registration, credential authentication, profile management, and session termination, and it restricts each actor (business owner, production staff, and customer) to their designated interfaces.
>
> **Module 2: Dynamic Service Configuration and Catalog Pricing Engine.** This module allows business owners to create and modify printing services, pricing variables, print attributes, and Bill of Materials (BOM) recipes through the user interface without altering source code, and it computes real-time multi-attribute quotations.
>
> **Module 3: Customer Self-Service Ordering and Prepress Intake.** This module provides the customer-facing portal for placing print orders, uploading print-ready documents, declaring customer-supplied paper, submitting proof of payment, and tracking order progress through a live five-stage status stepper.
>
> **Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake.** This module digitizes the production queue through a five-stage Kanban board, supports payment verification and customer-supplied paper inspection at the counter, and assigns jobs to production staff.
>
> **Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting.** This module automatically deducts consumables based on BOM recipes, records material spoilage, computes rolling burn rates and dynamic reorder points (ROP) to trigger low-stock alerts, and generates sales and consumption summary reports.

**Table X. Mapping of Project Objectives, System Modules, and Functional Requirements**

| Objective | System Module | Functional Requirements |
| :--- | :--- | :--- |
| Objective 1 | Module 2: Dynamic Service Configuration and Catalog Pricing Engine | FR-04, FR-06, FR-15, FR-16 |
| Objective 1 | Module 3: Customer Self-Service Ordering and Prepress Intake | FR-05, FR-07, FR-08, FR-09 |
| Objective 2 | Module 4: Production Floor 5-Stage Kanban Scheduling and Counter Intake | FR-10, FR-11, FR-13 |
| Objective 3 | Module 5: Automated Inventory Replenishment, Spoilage, and Sales Reporting | FR-12, FR-14, FR-17, FR-18 |
| Objective 4 | Module 1: User Onboarding, Authentication, and RBAC | FR-01, FR-02, FR-03 |
| Objective 5 | All Modules | FR-01 to FR-18 |

> [!NOTE]
> Renumber the tables after this insert. Table 1 (Test Plan) and Table 2 (Budget) come before p. 42, so the new table becomes Table 3, and any later tables shift by one.

---

## 5. Module 5 Rename (applies everywhere)

| Old | New |
| :--- | :--- |
| Module 5: Automated Inventory Telemetry, Spoilage & **Financial Analytics** | Module 5: Automated Inventory **Replenishment**, Spoilage & **Sales Reporting** |

**Reasons:** "Replenishment" matches the title and Obj. 3. "Telemetry" sounds like IoT, which your Limitations explicitly exclude. "Sales Reporting" avoids the "financial accounting" conflict.

**Update in:** System Modules subsection, Table 1 (p. 32–34), Appendix F (p. 108–110), Appendix G heading (p. 131), TAM (p. 140–141), and the Figure 24 / Figure 40 captions if you want them consistent (optional).

---

## 6. Defense Line (in case the panel asks "3 objectives but 5 modules?")

> "Our three core objectives follow the three components in our title: dynamic ordering, job scheduling, and inventory replenishment. For implementation we split them into five modules. Dynamic ordering has two modules, one for the business owner's service and pricing configuration and one for customer self-service ordering. Authentication and role-based access is the shared layer that integrates everything, under Objective 4. Table X traces every module and functional requirement back to an objective."
